<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'auditoria') {
    header('Location: ../index.php');
    exit;
}

include '../../includes/db.php';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $senha = md5($_POST['senha']);
    $nivel_acesso = 'marcacao';

    $stmt = $pdo->prepare("INSERT INTO auditoria (usuario, senha, nivel_acesso) VALUES (?, ?, ?)");
    if ($stmt->execute([$usuario, $senha, $nivel_acesso])) {
        $mensagem = "Coordenador cadastrado com sucesso!";
    } else {
        $erro = "Erro ao cadastrar coordenador.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEMPUS - Cadastrar Coordenador</title>
    <link rel="shortcut icon" href="../../css/imagens/1.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
            background-color: #e5ecf0;
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

        .form-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px;
            width: 400px;
            padding: 40px 60px;
            box-sizing: border-box;
            margin: auto;
            margin-left: center; 
            margin-top: 60px;
        }

        h1 {
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 700;
            color: #212529;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            font-size: 16px;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        .btn {
            background-color: #0E3659;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #132B40;
        }

        .alert {
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="../producao/index.php">Relatório de Produção</a>
        <a href="../avaliacao/index.php">Relatório de Comportamento</a>
        <a href="../metas/definir_meta.php">Definir Meta</a>
        <a href="../somar/index.php">Totalizar</a>
        <a href="../Filtro individual/filtro_individual.php">Filtro Individual</a>
        <a href="cadastar.php">Cadastrar Coordenador</a>
        <a href="../../index.php">Voltar</a>
    </div>

    <!-- Conteúdo -->
    <div class="form-container">
        <h1>Cadastrar Coordenador</h1>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="usuario" placeholder="Nome do coordenador" required>
            </div>
            <div class="form-group">
                <input type="password" name="senha" placeholder="Senha" required>
            </div>
            <button type="submit" class="btn">Cadastrar</button>
        </form>
    </div>
</body>

</html>
