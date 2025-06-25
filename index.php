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
        header('Location: selecao.php');
        exit;
    } else {
        $erro = "Usuário ou senha inválidos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEMPUS - Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Orbitron', sans-serif;
            background-image: url('css/imagens/4.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding-left: 8vw;
            color: white;
        }

        .login-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
            max-width: 400px;
            width: 100%;
            margin-left: 300px;
        }

        .logo-area {
            text-align: center;
        }

        .logo-title {
            font-size: 100px;
            font-weight: bold;
            color: white;
            margin-bottom: 80px;
            margin-left: -60px;
            /* Desloca para direita */
        }

        .logo-subtitle {
            color: #00ffff;
            font-size: 20px;
            text-align: center;
            margin-bottom: 15px;
            text-transform: uppercase;
            margin-left: -200px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            text-align: left;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 14px;
            color: #ccc;
            margin-bottom: 5px;
        }

        .form-group input {
            padding: 20px;
            height: 60px;
            border: none;
            border-radius: 50px;
            background-color: black;
            color: white;
            font-size: 18px;
            outline: none;

        }

        .form-group input::placeholder {
            color: #aaa;
        }

        .btn {
            padding: 15px;
            background-color: #00aaff;
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        .btn:hover {
            background-color: #008ecc;
            transform: scale(1.05);
        }

        .alert {
            background-color: rgba(255, 0, 0, 0.1);
            border: 1px solid red;
            color: red;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="logo-area">
            <div class="logo-subtitle">Monitoramento de Produção</div>
            <div class="logo-title">TEMPUS</div>
        </div>

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