<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'];
    
    // Obter meta
    $meta = $pdo->query("SELECT valor_meta FROM metas LIMIT 1")->fetch()['valor_meta'];
    
    // Obter funcionários e sua produção
    $funcionarios = $pdo->query("SELECT f.id_funcionario, f.nome, f.numero FROM funcionarios f")->fetchAll();
    
    // Obter produção total do dia
    $total_dia = $pdo->prepare("SELECT SUM(quantidade) as total FROM producao WHERE data = ?");
    $total_dia->execute([$data]);
    $total_dia = $total_dia->fetch()['total'] ?? 0;
    
    echo '<table class="relatorio-table">';
    echo '<thead><tr><th>Número</th><th>Funcionário</th><th>Total Copos</th><th>Abaixo da Meta</th><th>Razoável</th><th>Meta Atingida</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($funcionarios as $func) {
        // Obter produção do funcionário no dia
        $stmt = $pdo->prepare("SELECT quantidade FROM producao WHERE id_funcionario = ? AND data = ?");
        $stmt->execute([$func['id_funcionario'], $data]);
        $producoes = $stmt->fetchAll();
        
        $total_func = 0;
        $abaixo_meta = 0;
        $razoavel = 0;
        $meta_atingida = 0;
        
        foreach ($producoes as $prod) {
            $quantidade = $prod['quantidade'];
            $total_func += $quantidade;
            
            if ($quantidade < $meta * 0.5) {
                $abaixo_meta++;
            } elseif ($quantidade < $meta ) {
                $razoavel++;
            } else {
                $meta_atingida++;
            }
        }
        
        echo '<tr>';
        echo '<td>' . $func['numero'] . '</td>';
        echo '<td>' . $func['nome'] . ' </td>';
        echo '<td>' . $total_func . '</td>';
        echo '<td>' . $abaixo_meta . ' vez(es)</td>';
        echo '<td>' . $razoavel . ' vez(es)</td>';
        echo '<td>' . $meta_atingida . ' vez(es)</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '<tfoot><tr class="relatorio-total"><td colspan="4">Total Produzido no Dia</td><td>' . $total_dia . ' copos</td></tr></tfoot>';
    echo '</table>';
}
?>