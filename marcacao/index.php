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
    <link rel="shortcut icon" href="../css/imagens/1.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
            min-height: 100vh;
        }

        .marcacao-container {
            background-color: #f1f3f6;
            padding: 30px;
            border-radius: 20px;
            width: 100%;
            max-width: 1750px;
            height: auto;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .marcacao-container h1 {
            text-align: center;
            font-size: 42px;
            color: #1A1D26;
        }

        .info-box {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            background-color: #cfd4db;
            padding: 14px 20px;
            border-radius: 12px;
        }

        .info-box p {
            font-size: 18px;
            font-weight: 500;
            color: #1A1D26;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn {
            background-color: #343A40;
            color: #ffffff;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: #4a5056;
            color: white;
            transform: scale(1.05);
        }

        .btn-success {
            background-color: #28a745;
            border: none;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-danger {
            background-color: red;
            border: none;
        }

        .btn-danger:hover {
            background-color: red;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 14px;
        }

        .btn-deletar {
            background-color: red;
            color: white;
            padding: 6px 12px;
            font-size: 14px;
            border: none;
        }

        .btn-deletar:hover {
            background-color: red;
            color: white;
        }

        .btn-danger:hover {
            background-color: red;
            color: white;
        }

        .production-form {
            border-radius: 12px;
            overflow-y: auto;
            max-height: 400px;
            border: 1px solid #ccc;
        }

        .production-form table {
            width: 100%;
            border-collapse: collapse;
            min-width: 100%;
        }

        .production-form thead th {
            position: sticky;
            top: 0;
            background-color: #dfe3e8;
            z-index: 1;
            text-align: center;
            padding: 12px;
            font-size: 16px;
            color: #1A1D26;
        }

        .production-form th,
        .production-form td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #ccc;
        }

        .production-form tr:hover {
            background-color: #f0f2f5;
        }

        input[type="number"],
        input[type="text"],
        select {
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background-color: #fff;
            width: 100%;
            max-width: 130px;
        }

        .invalid-input {
            border: 1px solid red !important;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #9CA0A6;
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.1);
        }

        .total-box {
            text-align: center;
            font-size: 28px;
            font-weight: 600;
        }

        .total-box span {
            color: #2c3e50;
        }

        .add-funcionario {
            background-color: #e3e6ea;
            padding: 20px;
            border-radius: 12px;
        }

        .add-funcionario h3 {
            text-align: center;
            font-size: 26px;
            margin-bottom: 12px;
            color: #1A1D26;
        }

        .add-funcionario form {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .ordem-controls {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .ordem-controls button {
            background-color: #adb5bd;
            border: none;
            border-radius: 4px;
            padding: 3px 6px;
            cursor: pointer;
            font-weight: bold;
            color: #1A1D26;
            transition: background 0.2s ease;
        }

        .ordem-controls button:hover {
            background-color: #ced4da;
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

        /* Responsivo */
        @media (max-width: 768px) {
            .info-box {
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }

            .actions {
                flex-direction: column;
            }

            .add-funcionario form {
                flex-direction: column;
                align-items: center;
            }

            input[type="number"],
            input[type="text"],
            select {
                max-width: 100%;
            }

            .production-form {
                max-height: 300px;
            }
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
            <button id="salvarTudo" class="btn btn-success">Salvar Tudo</button>
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
                                <div class="btn-group">
                                    <button type="button" class="btn btn-small btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Menu
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item btn-alterar-codigo" href=""
                                            data-id="<?= $func['id_funcionario'] ?>"
                                            data-codigo="<?= $func['numero'] ?>">Alterar Código</a>
                                        <a class="dropdown-item btn-alterar-nome" href=""
                                            data-id="<?= $func['id_funcionario'] ?>"
                                            data-nome="<?= $func['nome'] ?>">Alterar Nome</a>
                                        <a class="dropdown-item excluir text-danger" href="#">Excluir</a>
                                    </div>
                                </div>
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
    <script src="../js/script.js?versao=1"></script>
</body>
</html>