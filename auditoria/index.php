<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'auditoria') {
    header('Location: ../index.php');
    exit;
}

include '../includes/db.php';

$datas = $pdo->query("SELECT DISTINCT data FROM producao ORDER BY data DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEMPUS - Auditoria</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 220px;
            background-color: #1A1D26;
            color: white;
            padding-top: 30px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding-left: 50px;
        }

        .sidebar a {
            color: #EBEFF2;
            text-decoration: none;
            font-weight: 600;
            padding: 10px;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .sidebar a:hover {
            background-color: #132B40;
        }

        .main-content {
            flex-grow: 1;
            padding: 40px;
            background-color: #EBEFF2;
        }

        .btn {
            background-color: #0E3659;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
            margin-right: 10px;
            font-weight: 600;
        }

        .btn:hover {
            background-color: #132B40;
        }

        .date-selector {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Barra lateral -->
    <div class="sidebar">
        <a href="avaliacao/index.php">Relatório de Comportamento</a>
        <a href="metas/definir_meta.php">Definir Meta</a>
        <a href="../index.php">Voltar</a>
    </div>

    <!-- Conteúdo principal -->
    <div class="main-content">
        <div class="auditoria-container">
            <h1>Relatório de Produção</h1>
            
            <div class="date-selector">
                <form id="relatorioForm">
                    <label for="data">Selecione a data:</label>
                    <select name="data" id="data" required>
                        <?php foreach ($datas as $data): ?>
                            <option value="<?= $data['data'] ?>"><?= date('d/m/Y', strtotime($data['data'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn">Gerar Relatório</button>
                </form>
            </div>
            
            <div id="relatorioResultado" class="relatorio-resultado">
                <!-- Resultado do relatório será inserido aqui -->
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>