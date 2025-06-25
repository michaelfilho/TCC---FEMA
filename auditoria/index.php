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
</head>
<body>
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
                <a href="../selecao.php" class="btn">Voltar</a>

            </form>
        </div>
        
        <div id="relatorioResultado" class="relatorio-resultado">
        </div>
    </div>
    
    <script src="../js/script.js"></script>
</body>
</html>