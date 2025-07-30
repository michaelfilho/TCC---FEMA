<?php
session_start();
include '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'];

    $horarios = [8, 9, 10, 11, 12, 14, 15, 16, 17, 18];

    echo '<table class="relatorio-table">';
    echo '<thead><tr><th>Horário</th>';

    foreach ($horarios as $h) {
        echo "<th>$h h</th>";
    }

    echo '</tr></thead><tbody>';

    echo '<tr><td>Quantidade</td>';

    foreach ($horarios as $h) {
        $stmt = $pdo->prepare("SELECT SUM(quantidade) as total FROM producao WHERE data = ? AND horario LIKE ?");
        $stmt->execute([$data, str_pad($h, 2, '0', STR_PAD_LEFT) . ':%']);
        $total = $stmt->fetch()['total'] ?? 0;
        echo "<td>" . ($total > 0 ? $total : 'x') . "</td>";
    }

    echo '</tr></tbody></table>';
}
