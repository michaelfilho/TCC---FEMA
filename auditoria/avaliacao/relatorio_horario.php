<?php
session_start();
include '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'];

    $horarios = [8, 10, 12, 14, 16, 18];

    // Pega lista de funcionários
    $funcionarios = $pdo->query("SELECT id_funcionario, numero, nome FROM funcionarios ORDER BY numero")->fetchAll();

    echo '<table class="relatorio-table">';
    echo '<thead><tr><th>Número</th><th>Funcionário</th>';

    foreach ($horarios as $h) {
        echo "<th>$h h</th>";
    }

    echo '<th>Total</th></tr></thead><tbody>';

    foreach ($funcionarios as $func) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($func['numero']) . '</td>';
        echo '<td>' . htmlspecialchars(ucfirst(strtolower($func['nome']))) . '</td>';

        $total_geral = 0;

        foreach ($horarios as $h) {
            $stmt = $pdo->prepare("
                SELECT SUM(quantidade) as total 
                FROM producao 
                WHERE id_funcionario = ? AND data = ? AND horario LIKE ?
            ");
            $stmt->execute([
                $func['id_funcionario'],
                $data,
                str_pad($h, 2, '0', STR_PAD_LEFT) . ':%'
            ]);

            $qtd = $stmt->fetch()['total'] ?? 0;
            $total_geral += $qtd;
            echo '<td>' . ($qtd > 0 ? $qtd : 'x') . '</td>';
        }

        echo '<td><strong>' . $total_geral . '</strong></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}
