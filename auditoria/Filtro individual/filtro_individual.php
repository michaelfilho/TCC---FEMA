<?php
session_start();
include '../../includes/db.php';

$funcionario = null;
$inicio = null;
$fim = null;
$resultado = null;
$erro = null;

// Obter a meta mais recente
$meta = $pdo->query("SELECT valor_meta FROM metas ORDER BY id_meta DESC LIMIT 1")->fetchColumn();
$meta = (float)$meta;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'];
    $inicio = $_POST['inicio'];
    $fim = $_POST['fim'];

    // Validação de datas
    if ($inicio > $fim) {
        $erro = "A data de início não pode ser maior que a data de fim.";
    } elseif (!is_numeric($codigo)) {
        $erro = "O código informado não é válido.";
    } else {
        $stmtFunc = $pdo->prepare("SELECT id_funcionario, nome, numero FROM funcionarios WHERE id_funcionario = ? OR numero = ?");
        $stmtFunc->execute([$codigo, $codigo]);
        $funcionario = $stmtFunc->fetch();

        if ($funcionario) {
            $stmtProd = $pdo->prepare("
                SELECT data, SUM(quantidade) as quantidade 
                FROM producao 
                WHERE id_funcionario = ? AND data BETWEEN ? AND ?
                GROUP BY data
                ORDER BY data
            ");
            $stmtProd->execute([$funcionario['id_funcionario'], $inicio, $fim]);
            $producoes = $stmtProd->fetchAll();

            $stmtDetalhes = $pdo->prepare("
                SELECT quantidade 
                FROM producao 
                WHERE id_funcionario = ? AND data BETWEEN ? AND ?
            ");
            $stmtDetalhes->execute([$funcionario['id_funcionario'], $inicio, $fim]);
            $detalhes = $stmtDetalhes->fetchAll();

            $tipos_justificativas = [
                'broca_morta' => null,
                'fungos' => null,
                'crisalida' => null,
                'colaborador' => null,
                'falta' => null,
                'saida_antecipada' => null
            ];
            $todos_tipos = $pdo->query("SELECT id_justificativa, descricao FROM justificativas")->fetchAll();
            foreach ($todos_tipos as $j) {
                $descricao = strtolower($j['descricao']);
                if (strpos($descricao, 'broca') !== false || strpos($descricao, 'morta') !== false) {
                    $tipos_justificativas['broca_morta'] = $j['id_justificativa'];
                } elseif (strpos($descricao, 'fungo') !== false) {
                    $tipos_justificativas['fungos'] = $j['id_justificativa'];
                } elseif (strpos($descricao, 'crisálida') !== false || strpos($descricao, 'crisalida') !== false) {
                    $tipos_justificativas['crisalida'] = $j['id_justificativa'];
                } elseif (strpos($descricao, 'colaborador') !== false) {
                    $tipos_justificativas['colaborador'] = $j['id_justificativa'];
                } elseif (strpos($descricao, 'falta') !== false) {
                    $tipos_justificativas['falta'] = $j['id_justificativa'];
                } elseif (strpos($descricao, 'saída antecipada') !== false || strpos($descricao, 'saida antecipada') !== false) {
                    $tipos_justificativas['saida_antecipada'] = $j['id_justificativa'];
                }
            }

            $stmtJustResumo = $pdo->prepare("
                SELECT 
                    SUM(CASE WHEN id_justificativa = :broca_morta THEN 1 ELSE 0 END) AS broca_morta,
                    SUM(CASE WHEN id_justificativa = :fungos THEN 1 ELSE 0 END) AS fungos,
                    SUM(CASE WHEN id_justificativa = :crisalida THEN 1 ELSE 0 END) AS crisalida,
                    SUM(CASE WHEN id_justificativa = :colaborador THEN 1 ELSE 0 END) AS colaborador,
                    MAX(CASE WHEN id_justificativa = :falta THEN 1 ELSE 0 END) AS falta,
                    MAX(CASE WHEN id_justificativa = :saida_antecipada THEN 1 ELSE 0 END) AS saida_antecipada
                FROM producao 
                WHERE id_funcionario = :id_funcionario AND data BETWEEN :inicio AND :fim
            ");
            $stmtJustResumo->execute([
                ':broca_morta' => $tipos_justificativas['broca_morta'],
                ':fungos' => $tipos_justificativas['fungos'],
                ':crisalida' => $tipos_justificativas['crisalida'],
                ':colaborador' => $tipos_justificativas['colaborador'],
                ':falta' => $tipos_justificativas['falta'],
                ':saida_antecipada' => $tipos_justificativas['saida_antecipada'],
                ':id_funcionario' => $funcionario['id_funcionario'],
                ':inicio' => $inicio,
                ':fim' => $fim
            ]);
            $just_resumo = $stmtJustResumo->fetch(PDO::FETCH_ASSOC);

            $total_copos = 0;
            $abaixo = 0;
            $razoavel = 0;
            $atingida = 0;

            $stmtDetalhes = $pdo->prepare("
            SELECT quantidade, meta_utilizada 
            FROM producao 
            WHERE id_funcionario = ? AND data BETWEEN ? AND ?
        ");
            $stmtDetalhes->execute([$funcionario['id_funcionario'], $inicio, $fim]);
            $detalhes = $stmtDetalhes->fetchAll();

            $total_copos = 0;
            $abaixo = 0;
            $razoavel = 0;
            $atingida = 0;

            foreach ($detalhes as $d) {
                $q = (int)$d['quantidade'];
                $meta_usada = isset($d['meta_utilizada']) && $d['meta_utilizada'] !== null ? (float)$d['meta_utilizada'] : $meta;

                $total_copos += $q;

                if ($q < $meta_usada * 0.5) {
                    $abaixo++;
                } elseif ($q < $meta_usada) {
                    $razoavel++;
                } else {
                    $atingida++;
                }
            }

            $resultado = [
                'total_copos' => $total_copos,
                'abaixo' => $abaixo,
                'razoavel' => $razoavel,
                'atingida' => $atingida,
                'producoes' => $producoes,
                'just_resumo' => $just_resumo
            ];
        } else {
            $erro = "Funcionário não encontrado.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <title>Filtro Individual - TEMPUS</title>
    <link rel="shortcut icon" href="../../css/imagens/1.png" type="image/x-icon" />
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 220px;
            background-color: #1A1D26;
            color: white;
            padding-top: 30px;
            padding-left: 50px;
            display: flex;
            flex-direction: column;
            gap: 20px;
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

        .main-content {
            flex-grow: 1;
            padding: 40px;
            background-color: #e6eaef;
            overflow-y: auto;
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

        .form-row {
            display: flex;
            align-items: flex-end;
            gap: 20px;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            font-size: 16px;
        }

        input[type="date"],
        input[type="number"] {
            padding: 12px;
            font-size: 16px;
            width: 220px;
        }

        table.relatorio-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table.relatorio-table th,
        table.relatorio-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        .error {
            color: red;
            margin-top: 20px;
        }

        .relatorio-container {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.08);
            margin-top: 30px;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <a href="../producao/index.php">Relatório de Produção</a>
        <a href="../avaliacao/index.php">Relatório de Comportamento</a>
        <a href="../metas/definir_meta.php">Definir Meta</a>
        <a href="../somar/index.php">Totalizar</a>
        <a href="filtro_individual.php">Filtro Individual</a>
        <a href="../cadastrar/cadastar.php">Cadastrar Coordenador</a>
        <a href="../../index.php">Voltar</a>
    </div>

    <div class="main-content">
        <h1>Filtro Individual por Funcionário</h1>

        <form method="post" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="codigo">Código do Funcionário (ID ou Número):</label>
                    <input type="number" id="codigo" name="codigo" required value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>" />
                </div>
                <div class="form-group">
                    <label for="inicio">Data Início:</label>
                    <input type="date" id="inicio" name="inicio" required value="<?= htmlspecialchars($inicio ?? '') ?>" />
                </div>
                <div class="form-group">
                    <label for="fim">Data Fim:</label>
                    <input type="date" id="fim" name="fim" required value="<?= htmlspecialchars($fim ?? '') ?>" />
                </div>
            </div>
            <button type="submit" class="btn">Filtrar Produção</button>
        </form>

        <?php if (!empty($erro)) : ?>
            <p class="error"><?= $erro ?></p>
        <?php endif; ?>

        <?php if ($resultado) : ?>
            <div class="relatorio-container">
                <h2>Resumo do Funcionário: <?= htmlspecialchars($funcionario['nome']) ?></h2>

                <table class="relatorio-table">
                    <thead>
                        <tr>
                            <th>Total de Copos</th>
                            <th>Abaixo da Meta</th>
                            <th>Razoável</th>
                            <th>Meta Atingida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= $resultado['total_copos'] ?></td>
                            <td><?= $resultado['abaixo'] ?> vez(es)</td>
                            <td><?= $resultado['razoavel'] ?> vez(es)</td>
                            <td><?= $resultado['atingida'] ?> vez(es)</td>
                        </tr>
                    </tbody>
                </table>

                <?php if (!empty($resultado['producoes'])) : ?>
                    <h3>Produção por Dia</h3>
                    <table class="relatorio-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Quantidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resultado['producoes'] as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['data']) ?></td>
                                    <td><?= $p['quantidade'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php if (!empty($resultado['just_resumo'])) : ?>
                    <h3>Justificativas no Período</h3>
                    <table class="relatorio-table">
                        <thead>
                            <tr>
                                <th>Broca Morta</th>
                                <th>Fungos</th>
                                <th>Crisálida</th>
                                <th>Colaborador</th>
                                <th>Falta</th>
                                <th>Saída Antecipada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?= $resultado['just_resumo']['broca_morta'] ?? 0 ?> vez(es)</td>
                                <td><?= $resultado['just_resumo']['fungos'] ?? 0 ?> vez(es)</td>
                                <td><?= $resultado['just_resumo']['crisalida'] ?? 0 ?> vez(es)</td>
                                <td><?= $resultado['just_resumo']['colaborador'] ?? 0 ?> vez(es)</td>
                                <td><?= $resultado['just_resumo']['falta'] ?? 0 ?> vez(es)</td>
                                <td><?= $resultado['just_resumo']['saida_antecipada'] ?? 0 ?> vez(es)</td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>