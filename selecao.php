<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEMPUS - Seleção</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: url('css/imagens/11.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body.auditoria {
            background: url('css/imagens/8.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        /* ==================== CONTAINER ==================== */
        .selection-container {
            padding: 50px;
            border-radius: 20px;
            text-align: center;
            transform: translateX(-300px);
            margin-top: 50px;
        }

        .selection-container h1 {
            font-size: 50px;
            color: #fff;
            margin-bottom: 10px;

        }

        .selection-container h2 {
            font-size: 24px;
            color: #ccc;
            margin-bottom: 40px;
        }

        /* ==================== COORDENADOR ==================== */

        .options {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 60px;
            align-items: center;
        }

        /* ==================== BOTÕES ==================== */

        .btn {
            background-color: #fff;
            color: #1a1a1a;
            text-decoration: none;
            font-size: 48px;
            padding: 15px 40px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 800;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            width: 380px;

        }

        .btn:hover {
            background-color: #0ff;
            color: #000;
            box-shadow: 0 0 20px #0ff;
            transform: scale(1.05);
        }

        /* ==================== BOTÃO SAIR ==================== */
        .btn-logout {
            background-color: transparent;
            color: #f00;
            border: 2px solid #f00;
            margin-bottom: -500px;
        }

        .btn-sair {
            background-color: transparent;
            color: #f00;
            border: 2px solid #f00;
            margin-top: 80px;
        }

        .btn-sair:hover {
            background-color: #f00;
            color: #fff;
            box-shadow: 0 0 15px
        }

        .btn-logout:hover { 
            background-color: #f00;
            color: #fff;
            box-shadow: 0 0 15px #f00;
        }

        a {
            cursor: pointer;
        }
    </style>
</head>

<div class="selection-container">
    <h1>Bem-vindo, <?= $_SESSION['usuario'] ?></h1>
    <h2>Selecione a opção:</h2>

    <body class="<?php echo ($_SESSION['nivel_acesso'] === 'auditoria') ? 'auditoria' : ''; ?>">

        <?php if ($_SESSION['nivel_acesso'] === 'marcacao'): ?>
            <div class="options">
                <a href="marcacao/" class="btn">Marcação</a>
                <a href="index.php" class="btn btn-logout">Sair</a>
            </div>
        <?php endif; ?>

        <?php if ($_SESSION['nivel_acesso'] === 'auditoria'): ?>
            <div class="options_2">
                <a href="auditoria/" class="btn">Auditoria</a>
                <a href="auditoria/avaliacao/index.php" class="btn">Avaliação</a>
                <a href="auditoria/metas/definir_meta.php" class="btn">Definir Meta</a>
            </div>
            <a href="index.php" class="btn btn-logout">Sair</a>
        <?php endif; ?>
</div>

</body>

</html>