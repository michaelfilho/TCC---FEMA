<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'auditoria') {
    header('Location: ../../index.php');
    exit;
}

include '../../includes/db.php';

$datas = $pdo->query("SELECT DISTINCT data FROM producao ORDER BY data DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEMPUS - Avaliação</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>

        q
        .avaliacao-container {
            max-width: 1000px;
            margin: 20px auto;
        }
        .relatorio-table {
            width: 100%;
            border-collapse: collapse;
        }
        .relatorio-table th, .relatorio-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .relatorio-table th {
            background-color: #f2f2f2;
        }
        .relatorio-total {
            font-weight: bold;
            background-color: #e8f4fd;
        }
    </style>
</head>
<body>
<div class="sidebar">
        <a href="../index.php">Relatório de Comportamento</a>
        <a href="../metas/definir_meta.php">Definir Meta</a>
        <a href="../index.php">Voltar</a>
    </div>
    <div class="avaliacao-container">
        <h1>Relatório de Comportamento</h1>
        
        <div class="date-selector">
            <form id="avaliacaoForm">
                <label for="data">Selecione a data:</label>
                <select name="data" id="data" required>
                    <?php foreach ($datas as $data): ?>
                        <option value="<?= $data['data'] ?>"><?= date('d/m/Y', strtotime($data['data'])) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Gerar Relatório</button>
            </form>
        </div>
        
        <div id="relatorioResultado">
        </div>
    </div>

    <script>
    document.getElementById('avaliacaoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const data = document.getElementById('data').value;
        
        fetch('relatorio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `data=${data}`
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('relatorioResultado').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
    </script>
</body>
</html>