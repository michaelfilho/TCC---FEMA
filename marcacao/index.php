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
        /* 🎯 Reset e fonte */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        /* 🌌 Fundo fixo */
        body {
            background: url('../css/imagens/12.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* 🧠 Container principal */
        .marcacao-container {
            background-color: rgba(0, 0, 0, 0.9);
            padding: 25px;
            border-radius: 20px;
            max-width: 1200px;
            width: 100%;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* 🔷 Título */
        .marcacao-container h1 {
            text-align: center;
            font-size: 36px;
            color: #0ff;
        }

        /* 📦 Informações */
        .info-box {
            display: flex;
            justify-content: space-around;
            background-color: rgba(255, 255, 255, 0.05);
            padding: 12px;
            border-radius: 12px;
        }

        /* Texto info */
        .info-box p {
            font-size: 18px;
        }

        /* 🎛️ Botões principais */
        .actions {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        /* 🔘 Botões */
        .btn {
            background-color: #0ff;
            color: #000;
            padding: 10px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            border: none;
        }

        .btn:hover {
            background-color: #00d5d5;
            transform: scale(1.05);
        }

        /* ⚠️ Botão de perigo */
        .btn-danger {
            background-color: transparent;
            border: 2px solid red;
            color: red;
        }

        .btn-danger:hover {
            background-color: red;
            color: white;
        }

        /* 🔸 Botões pequenos */
        .btn-small {
            padding: 6px 12px;
            font-size: 14px;
        }

        .btn-deletar {
            background:rgb(255, 3, 3);
            padding: 6px 12px;
            font-size: 14px;
        }
        .btn-deletar:hover {
            background-color: red;
            color: white;
        }

        /* 🔥 Área da tabela COM SCROLL apenas nela */
        .production-form {
            max-height: 50vh;
            overflow-y: auto;
            border-radius: 12px;
        }

        /* 🧾 Tabela */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* 🎯 Cabeçalho da tabela FIXO */
        table thead th {
            position: sticky;
            top: 0;
            background-color: rgba(0, 255, 255, 0.3);
            backdrop-filter: blur(5px);
            z-index: 2;
            color: #0ff;
            font-weight: 600;
        }

        /* 🔲 Células */
        table th,
        table td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Efeito hover na linha */
        table tr:hover {
            background-color: rgba(255, 255, 255, 0.08);
        }

        /* 🖊️ Inputs e selects */
        input[type="number"],
        input[type="text"],
        select {
            padding: 6px 10px;
            border-radius: 8px;
            border: none;
            background-color: rgba(255, 255, 255, 0.9);
            color: #000;
        }

        input:focus,
        select:focus {
            box-shadow: 0 0 5px #0ff;
        }

        /* 📊 Total do horário */
        .total-box {
            text-align: center;
            margin-top: 10px;
            font-size: 20px;
        }

        .total-box span {
            color: #0ff;
            font-weight: 600;
        }

        /* ➕ Área adicionar funcionário (FIXA) */
        .add-funcionario {
            background-color: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 12px;
        }

        .add-funcionario h3 {
            text-align: center;
            margin-bottom: 10px;
            color: #0ff;
        }

        .add-funcionario form {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* 🔼🔽 Ordem */
        .ordem-controls {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .ordem-controls button {
            background-color: #0ff;
            border: none;
            border-radius: 5px;
            padding: 3px;
            cursor: pointer;
        }

        .ordem-controls button:hover {
            background-color: #00d5d5;
        }

        /* Status colors */
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

            <div class="total-box">
                <h3>Total do Horário: <span id="totalHorario">0</span> copos</h3>
            </div>
        </div>

        <div class="add-funcionario">
            <h3>Adicionar Funcionário</h3>
            <form method="POST">
                <input type="text" name="nome" placeholder="Nome" required>
                <input type="number" name="numero" placeholder="Número" required>
                <button type="submit" name="adicionar_funcionario" class="btn">Adicionar</button>
            </form>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/5.5.2/bootbox.min.js"></script>
    <script src="../js/script.js"></script>
</body>

</html>