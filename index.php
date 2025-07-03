<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = md5($_POST['senha'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM auditoria WHERE usuario = ? AND senha = ?");
    $stmt->execute([$usuario, $senha]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['nivel_acesso'] = $user['nivel_acesso'];

        if ($user['nivel_acesso'] === 'marcacao') {
            header('Location: marcacao/index.php');
            exit;
        } elseif ($user['nivel_acesso'] === 'auditoria') {
            header('Location: auditoria/inicio.php');
            exit;
        }
    } else {
        $erro = "Usuário ou senha incorretos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEMPUS - Login</title>
    <link rel="shortcut icon" href="css/imagens/1.png" type="image/x-icon">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Orbitron', sans-serif;
            background-image: url('css/imagens/13.jpg');
            /* imagem de fundo mantida */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            height: 100vh;
            color: white;
            display: flex;
            justify-content: center;
            /* centraliza horizontalmente */
            align-items: center;
            /* centraliza verticalmente */
        }

        .login-container {
            margin-top: 150px;
            margin-right: 1050px;
            padding: 30px 40px;
            border-radius: 25px;
            /* cantos ainda mais suaves */
            width: 400px;
            /* largura maior */
            max-width: 90%;
            /* responsivo em telas menores */
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            font-size: 14px;
            color: #1A1D26;
            margin-bottom: 5px;
            display: block;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #9CA0A6;
            border-radius: 50px;
            background-color: #1A1D26;
            color: #EBEFF2;
            font-size: 16px;
            outline: none;
        }

        .form-group input::placeholder {
            color: #777;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background-color: #0E3659;
            color: #EBEFF2;
            border: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        .btn:hover {
            background-color: #9CA0A6;
            transform: scale(1.05);
        }

        .alert {
            background-color: rgba(255, 0, 0, 0.15);
            /* vermelho mais suave */
            color: #ff4d4d;
            /* vermelho mais claro */
            padding: 12px 18px;
            /* mais espaço interno */
            border-radius: 5px;
            /* cantos mais arredondados */
            margin-bottom: 20px;
            /* mais respiro abaixo */
            font-size: 14px;
            /* tamanho de texto suave */
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            /* leve sombra para profundidade */
        }
    </style>
</head>

<body>

    <div class="login-container">
        <?php if (isset($erro)): ?>
            <div class="alert"><?= $erro ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="usuario">Username</label>
                <input type="text" id="usuario" name="usuario" placeholder="Digite seu usuário" required>
            </div>
            <div class="form-group">
                <label for="senha">Password</label>
                <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>

</body>

</html>