<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'auditoria') {
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEMPUS - Auditoria</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="shortcut icon" href="../css/imagens/1.png" type="image/x-icon">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            background-image: url('../css/imagens/2.jpg');
            background-size: cover;
            background-position: center;
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
    </style>
</head>

<body>
    <div class="sidebar">
        <a href="index.php">Relatório de Produção</a>
        <a href="avaliacao/index.php">Relatório de Comportamento</a>
        <a href="metas/definir_meta.php">Definir Meta</a>
        <a href="../index.php">Voltar</a>
    </div>
</body>

</html>