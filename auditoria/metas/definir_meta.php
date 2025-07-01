<?php
session_start();

// Caminho absoluto para o arquivo de conexão
$caminho_db = __DIR__ . '/../../includes/db.php';

// Verificação de acesso
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'auditoria') {
    header('Location: ../../index.php');
    exit;
}

// Conectar ao banco de dados
require_once $caminho_db;

$mensagem = '';
$erro = '';
$meta_atual = 0;

// Buscar meta atual do banco de dados
try {
    $stmt = $pdo->query("SELECT valor_meta FROM metas ORDER BY id_meta DESC LIMIT 1");
    $meta_atual = $stmt->fetchColumn();
    if ($meta_atual === false) {
        $meta_atual = 0;
    }
} catch (PDOException $e) {
    $erro = "Erro ao buscar a meta atual!";
    error_log("Erro ao buscar meta atual: " . $e->getMessage());
}

// Processar atualização da meta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_meta'])) {
    $nova_meta = (int)$_POST['nova_meta'];

    if ($nova_meta > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO metas (valor_meta) VALUES (?)");
            $stmt->execute([$nova_meta]);
            $mensagem = "Meta atualizada com sucesso para $nova_meta copos!";
            $meta_atual = $nova_meta;
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar meta no banco de dados!";
            error_log("Erro ao atualizar meta: " . $e->getMessage());
        }
    } else {
        $erro = "A meta deve ser um número positivo!";
    }
}
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
            background-color: #e5ecf0;
        }

        .meta-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px;
            width: 600px;
            /* aumentado */
            min-height: 50px;
            /* opcional */
            padding: 40px 60px;
            box-sizing: border-box;
            text-align: center;
            border: 2px solid;
            margin: auto;
            margin-left: center;
            margin-top: 60px;
        }

        h1 {
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 700;
            color: #212529;
        }

        .form-group {
            margin-bottom: 20px;
        }

        input[type="number"] {
            width: 100%;
            padding: 8px 12px;
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
            margin-top: 10px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #132B40;
        }

        .alert {
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
        }

        .btn-logout {
            display: inline-block;
            margin-top: 15px;
            background-color: #888;
        }

        .btn-logout:hover {
            background-color: #555;
        }
    </style>
</head>

<body>
    <!-- Barra lateral -->
    <div class="sidebar">
        <a href="../index.php">Relatório de Produçao</a>
        <a href="../avaliacao/index.php">Relatório de Comportamento</a>
        <a href="../metas/definir_meta.php">Definir Meta</a>
        <a href="../../index.php">Voltar</a>
    </div>

    <div class="meta-container">
        <h1>Definir Meta de Produção</h1>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <div class="current-meta">
            <p>Meta atual: <strong><?= htmlspecialchars($meta_atual) ?></strong> copos por horário</p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="nova_meta">Nova Meta:</label>
                <input type="number" id="nova_meta" name="nova_meta" min="1" required value="<?= htmlspecialchars($meta_atual) ?>">
            </div>
            <button type="submit" class="btn">Atualizar Meta</button>
        </form>
    </div>
</body>

</html>