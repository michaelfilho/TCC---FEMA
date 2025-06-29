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
    <title>Definir Meta de Produção</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
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
        <a href="../../selecao.php" class="btn btn-logout">Voltar</a>
    </div>
</body>
</html>
