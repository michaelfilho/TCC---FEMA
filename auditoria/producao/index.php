<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'auditoria') {
    header('Location: ../index.php');
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
    <title>TEMPUS - Auditoria</title>
    <link rel="shortcut icon" href="../../css/imagens/1.png" type="image/x-icon">
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
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

        #paginacao {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <!-- Barra lateral -->
    <div class="sidebar">
        <a href="index.php">Relatório de Produçao</a>
        <a href="../avaliacao/index.php">Relatório de Comportamento</a>
        <a href="../metas/definir_meta.php">Definir Meta</a>
        <a href="../somar/index.php">Totalizar</a>
        <a href="../Filtro Individual/filtro_individual.php">Filtro Individual</a>
        <a href="../cadastrar/cadastar.php">Cadastrar Coordenador</a>
        <a href="../../index.php">Voltar</a>
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
                    <button type="button" class="btn" id="relatorioHorario">Relatório por Horário</button>
                    <button type="button" class="btn" id="baixarPdf" style="display:none;">Baixar PDF</button>
                </form>
            </div>

            <div id="relatorioResultado" class="relatorio-resultado">
                <!-- Resultado do relatório será inserido aqui -->
            </div>
        </div>
    </div>

    <!-- Bibliotecas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="../../js/script.js"></script>
    
</body>
</html>
