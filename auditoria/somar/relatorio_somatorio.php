<?php
session_start();
include '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inicio = $_POST['inicio'];
    $fim = $_POST['fim'];

    $meta = $pdo->query("SELECT valor_meta FROM metas ORDER BY id_meta DESC LIMIT 1")->fetchColumn();
    $funcionarios = $pdo->query("SELECT id_funcionario, nome, numero FROM funcionarios")->fetchAll();

    echo '<table class="relatorio-table">';
    echo '<thead><tr><th>Número</th><th>Funcionário</th><th>Total Copos</th><th>Abaixo da Meta</th><th>Razoável</th><th>Meta Atingida</th></tr></thead>';
    echo '<tbody>';

    $total_geral = 0;

    foreach ($funcionarios as $func) {
        $stmt = $pdo->prepare("SELECT quantidade FROM producao WHERE id_funcionario = ? AND data BETWEEN ? AND ?");
        $stmt->execute([$func['id_funcionario'], $inicio, $fim]);
        $producoes = $stmt->fetchAll();

        $total_func = 0;
        $abaixo = 0;
        $razoavel = 0;
        $atingida = 0;

        foreach ($producoes as $p) {
            $q = $p['quantidade'];
            $total_func += $q;
            if ($q < $meta * 0.5) {
                $abaixo++;
            } elseif ($q < $meta) {
                $razoavel++;
            } else {
                $atingida++;
            }
        }

        $total_geral += $total_func;

        echo "<tr>
            <td>{$func['numero']}</td>
            <td>{$func['nome']}</td>
            <td>{$total_func}</td>
            <td>{$abaixo} vez(es)</td>
            <td>{$razoavel} vez(es)</td>
            <td>{$atingida} vez(es)</td>
        </tr>";
    }

    echo '</tbody>';
    echo "<tfoot><tr class='relatorio-total'><td colspan='4'>Total Geral Produzido</td><td colspan='2'>{$total_geral} copos</td></tr></tfoot>";
    echo '</table>';
}
?>
