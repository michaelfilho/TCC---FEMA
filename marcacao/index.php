<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'marcacao') {
    header('Location: ../index.php');
    exit;
}

include '../includes/db.php';

// Ordenar por campo 'ordem' se existir, caso contrário por nome
$funcionarios = $pdo->query("SELECT * FROM funcionarios ORDER BY 
                            CASE WHEN EXISTS (SELECT 1 FROM information_schema.columns 
                            WHERE table_name = 'funcionarios' AND column_name = 'ordem') 
                            THEN ordem ELSE nome END")->fetchAll();

$meta = $pdo->query("SELECT valor_meta FROM metas ORDER BY id_meta DESC LIMIT 1")->fetchColumn();
$justificativas = $pdo->query("SELECT * FROM justificativas")->fetchAll();

$horarios = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'];
$horario_atual = $horarios[0];

$hoje = date('Y-m-d');
$stmt = $pdo->prepare("SELECT horario FROM producao WHERE data = ? ORDER BY horario DESC LIMIT 1");
$stmt->execute([$hoje]);
$ultimo_horario = $stmt->fetchColumn();

if ($ultimo_horario) {
    $index = array_search($ultimo_horario, $horarios);
    if ($index !== false && $index < count($horarios) - 1) {
        $horario_atual = $horarios[$index + 1];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['adicionar_funcionario'])) {
        $nome = $_POST['nome'];
        $numero = $_POST['numero'];

        $stmt = $pdo->prepare("INSERT INTO funcionarios (nome, numero) VALUES (?, ?)");
        $stmt->execute([$nome, $numero]);
        header('Location: ./');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEMPUS - Marcação</title>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    body {
        background: #1A1D26;
        color: #1A1D26;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .marcacao-container {
        background-color: #DDE2E7;
        padding: 25px;
        border-radius: 20px;
        width: 90vw;
        height: 90vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .marcacao-container h1 {
        text-align: center;
        font-size: 45px;
        color: #1A1D26;
    }

    .info-box {
        display: flex;
        justify-content: space-around;
        background-color: #9CA0A6;
        padding: 12px;
        border-radius: 12px;
    }

    .info-box p {
        font-size: 18px;
        color: #1A1D26;
    }

    .actions {
        display: flex;
        justify-content: center;
        gap: 20px;
    }

    .btn {
        background-color: #9CA0A6;
        color: #1A1D26;
        padding: 10px 25px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: 0.3s;
        border: none;
    }

    .btn:hover {
        background-color: #c2c6cb;
        transform: scale(1.05);
    }

    .btn-danger {
        background-color: red;
        border: 2px solid red;
        color: white;
    }

    .btn-danger:hover {
        background-color: red;
        color: white;
    }

    .btn-small {
        padding: 6px 12px;
        font-size: 14px;
    }

    .btn-deletar {
        background: rgb(255, 3, 3);
        color:white;
        padding: 6px 12px;
        font-size: 14px;
    }

    .btn-deletar:hover {
        background-color: red;
        color: white;
    }

    .production-form {
        max-height: 50vh;
        overflow-y: auto;
        border-radius: 12px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table thead th {
        position: sticky;
        top: 0;
        background-color: #C5C9CF;
        backdrop-filter: blur(5px);
        z-index: 2;
        color: #1A1D26;
        font-weight: 600;
    }

    table th,
    table td {
        padding: 10px;
        text-align: center;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }

    table tr:hover {
        background-color: rgba(0, 0, 0, 0.04);
    }

    input[type="number"],
    input[type="text"],
    select {
        padding: 6px 10px;
        border-radius: 8px;
        border: none;
        background-color: #fff;
        color: #1A1D26;
    }

    input:focus,
    select:focus {
        box-shadow: 0 0 5px #9CA0A6;
    }

    .total-box {
        text-align: center;
        margin-top: 10px;
        font-size: 40px;
    }

    .total-box span {
        color: #1A1D26;
        font-weight: 600;
    }

    .add-funcionario {
        background-color: #C5C9CF;
        padding: 15px;
        border-radius: 12px;
    }

    .add-funcionario h3 {
        font-size: 30px;
        text-align: center;
        margin-bottom: 10px;
        color: #1A1D26;
    }

    .add-funcionario form {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .ordem-controls {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .ordem-controls button {
        background-color: #9CA0A6;
        border: none;
        border-radius: 5px;
        padding: 3px;
        cursor: pointer;
        color: #1A1D26;
    }

    .ordem-controls button:hover {
        background-color: #EBEFF2;
    }

    .status-baixo {
        color: #e74c3c;
        font-weight: bold;
    }

    .status-razoavel {
        color: #f39c12;
        font-weight: bold;
    }

    .status-meta {
        color: #27ae60;
        font-weight: bold;
    }
</style>
</head>

<body>
    <div class="marcacao-container">
        <h1>Marcação de Produção</h1>
        <div class="info-box">
            <p>Data: <?= date('d/m/Y') ?></p>
            <p>Horário Atual: <?= $horario_atual ?></p>
            <p>Meta: <?= $meta ?> copos</p>
        </div>

        <div class="actions">
            <button id="proximoHorario" class="btn">Próximo Horário</button>
            <button id="encerrarDia" class="btn btn-danger">Encerrar Dia</button>
        </div>

        <div class="production-form">
            <table>
                <thead>
                    <tr>
                        <th>Ordem</th>
                        <th>Número</th>
                        <th>Funcionário</th>
                        <th>Produção</th>
                        <th>Status</th>
                        <th>Justificativa</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($funcionarios as $index => $func): ?>
                        <tr data-funcionario="<?= $func['id_funcionario'] ?>" data-ordem="<?= $index ?>">
                            <td class="ordem-controls">
                                <button class="btn-move-up" title="Mover para cima">▲</button>
                                <button class="btn-move-down" title="Mover para baixo">▼</button>
                            </td>
                            <td class="funcionario-codigo"><?= $func['numero'] ?></td>
                            <td><?= $func['nome'] ?></td>
                            <td>
                                <input type="number" class="quantidade" min="0" value="0">
                            </td>
                            <td class="status">-</td>
                            <td>
                                <select class="justificativa">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($justificativas as $just): ?>
                                        <option value="<?= $just['id_justificativa'] ?>"><?= $just['descricao'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <button class="btn btn-small salvar">Salvar</button>
                                <button class="btn btn-small btn-alterar-codigo" data-id="<?= $func['id_funcionario'] ?>"
                                    data-codigo="<?= $func['numero'] ?>">
                                    Alterar Código
                                </button>
                                <button class="btn btn-deletar excluir">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            
        </div>

        <div class="add-funcionario">
            <h3>Adicionar Funcionário</h3>
            
            <form method="POST">
                <input type="text" name="nome" placeholder="Nome" required>
                <input type="number" name="numero" placeholder="Número" required>
                <button type="submit" name="adicionar_funcionario" class="btn">Adicionar</button>
            </form>
        </div>
        <div class="total-box">
                <h3>Total do Horário: <span id="totalHorario">0</span> copos</h3>
            </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.js"></script>
    <script src="../js/script.js"></script>
</body>

</html>