<?php
session_start();
include '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'];

    // Obter funcionários ativos
    $funcionarios = $pdo->query("SELECT id_funcionario, nome, numero FROM funcionarios WHERE ativo = 1 ORDER BY numero")->fetchAll();

    // Obter produção total do dia
    $total_dia_stmt = $pdo->prepare("SELECT SUM(quantidade) as total FROM producao WHERE data = ?");
    $total_dia_stmt->execute([$data]);
    $total_dia = $total_dia_stmt->fetchColumn() ?? 0;

    echo '<table class="relatorio-table">';
    echo '<thead><tr><th>Número</th><th>Funcionário</th><th>Total Copos</th><th>Abaixo da Meta</th><th>Razoável</th><th>Meta Atingida</th></tr></thead>';
    echo '<tbody>';

    foreach ($funcionarios as $func) {
        $id_funcionario = $func['id_funcionario'];

        // Buscar produção do funcionário nesse dia, com a meta utilizada
        $stmt = $pdo->prepare("SELECT quantidade, meta_utilizada FROM producao WHERE id_funcionario = ? AND data = ?");
        $stmt->execute([$id_funcionario, $data]);
        $producoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_func = 0;
        $abaixo_meta = 0;
        $razoavel = 0;
        $meta_atingida = 0;

        foreach ($producoes as $prod) {
            if (!isset($prod['meta_utilizada']) || $prod['meta_utilizada'] === null) {
                continue; // ignora se não houver meta registrada
            }
            $quantidade = (int)$prod['quantidade'];
            $meta = (float)$prod['meta_utilizada'];

            if ($quantidade <= 0) {
                continue; // ignora marcações zeradas
            }

            $total_func += $quantidade;

            if ($quantidade < $meta * 0.5) {
                $abaixo_meta++;
            } elseif ($quantidade < $meta) {
                $razoavel++;
            } else {
                $meta_atingida++;
            }
        }

        echo '<tr>';
        echo '<td>' . htmlspecialchars($func['numero']) . '</td>';
        echo '<td>' . htmlspecialchars($func['nome']) . '</td>';
        echo '<td>' . $total_func . '</td>';
        echo '<td>' . $abaixo_meta . ' vez(es)</td>';
        echo '<td>' . $razoavel . ' vez(es)</td>';
        echo '<td>' . $meta_atingida . ' vez(es)</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '<tfoot><tr class="relatorio-total"><td colspan="4">Total Produzido no Dia</td><td colspan="2">' . $total_dia . ' copos</td></tr></tfoot>';
    echo '</table>';
}
