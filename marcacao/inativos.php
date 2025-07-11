<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'marcacao') {
    header('Location: ../index.php');
    exit;
}

include '../includes/db.php';

// Buscar funcionários inativos
$funcionarios = $pdo->query("SELECT * FROM funcionarios WHERE ativo = 0 ORDER BY nome")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funcionários Inativos</title>
    <link rel="shortcut icon" href="../css/imagens/1.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #1A1D26;
            color: #1A1D26;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }

        .container {
            background-color: #f1f3f6;
            padding: 30px;
            border-radius: 20px;
            max-width: 1200px;
            width: 100%;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            font-size: 36px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ccc;
        }

        th {
            background-color: #dfe3e8;
            color: #1A1D26;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-reativar {
            background-color: #28a745;
            color: white;
        }

        .btn-reativar:hover {
            background-color: #218838;
        }

        .voltar {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>
<div class="container">
    <a href="index.php" class="voltar">Voltar para Marcação</a>
    <h1>Funcionários Inativos</h1>
    <table>
        <thead>
        <tr>
            <th>Número</th>
            <th>Nome</th>
            <th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($funcionarios as $func): ?>
            <tr data-id="<?= $func['id_funcionario'] ?>">
                <td><?= $func['numero'] ?></td>
                <td><?= $func['nome'] ?></td>
                <td>
                    <button class="btn btn-reativar" onclick="reativarFuncionario(<?= $func['id_funcionario'] ?>)">Reativar</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function reativarFuncionario(id) {
        if (confirm('Deseja reativar este funcionário?')) {
            fetch('processa.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'acao=ativar_funcionario&id_funcionario=' + id
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Funcionário reativado com sucesso!');
                        location.reload();
                    } else {
                        alert(data.message || 'Erro ao reativar funcionário.');
                    }
                })
                .catch(() => alert('Erro ao conectar com o servidor.'));
        }
    }
</script>
</body>
</html>
