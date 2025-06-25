<?php
session_start();
include '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'];
    
    // 1. Obter os IDs reais das justificativas
    $justificativas = $pdo->query("SELECT id_justificativa, descricao FROM justificativas")->fetchAll();
    
    // Mapeamento dinâmico
    $mapeamento = [
        'broca_morta' => null,
        'fungos' => null,
        'crisalida' => null,
        'colaborador' => null
    ];
    
    foreach ($justificativas as $just) {
        $desc_lower = strtolower($just['descricao']);
        if (strpos($desc_lower, 'broca') !== false || strpos($desc_lower, 'morta') !== false) {
            $mapeamento['broca_morta'] = $just['id_justificativa'];
        } elseif (strpos($desc_lower, 'fungo') !== false) {
            $mapeamento['fungos'] = $just['id_justificativa'];
        } elseif (strpos($desc_lower, 'crisálida') !== false || strpos($desc_lower, 'crisalida') !== false) {
            $mapeamento['crisalida'] = $just['id_justificativa'];
        } elseif (strpos($desc_lower, 'colaborador') !== false) {
            $mapeamento['colaborador'] = $just['id_justificativa'];
        }
    }
    
    // 2. Consulta principal
    $stmt = $pdo->prepare("
        SELECT 
            f.id_funcionario, 
            f.numero, 
            f.nome, 
            COALESCE(SUM(p.quantidade), 0) as total_copos,
            SUM(CASE WHEN p.id_justificativa = ? THEN 1 ELSE 0 END) as broca_morta,
            SUM(CASE WHEN p.id_justificativa = ? THEN 1 ELSE 0 END) as fungos,
            SUM(CASE WHEN p.id_justificativa = ? THEN 1 ELSE 0 END) as crisalida,
            SUM(CASE WHEN p.id_justificativa = ? THEN 1 ELSE 0 END) as colaborador
        FROM 
            funcionarios f
        LEFT JOIN 
            producao p ON f.id_funcionario = p.id_funcionario AND p.data = ?
        GROUP BY 
            f.id_funcionario
        ORDER BY 
            f.numero
    ");
    
    $stmt->execute([
        $mapeamento['broca_morta'],
        $mapeamento['fungos'],
        $mapeamento['crisalida'],
        $mapeamento['colaborador'],
        $data
    ]);
    
    $funcionarios = $stmt->fetchAll();
    
    // Calcular total produzido no dia
    $total_dia_stmt = $pdo->prepare("SELECT COALESCE(SUM(quantidade), 0) FROM producao WHERE data = ?");
    $total_dia_stmt->execute([$data]);
    $total_dia = $total_dia_stmt->fetchColumn();
    
    // Gerar tabela HTML
    echo '<table class="relatorio-table">';
    echo '<thead><tr>
            <th>Número</th>
            <th>Funcionário</th>
            <th>Total Copos</th>
            <th>Broca Morta</th>
            <th>Fungos</th>
            <th>Crisálida</th>
            <th>Colaborador</th>
          </tr></thead>';
    echo '<tbody>';
    
    foreach ($funcionarios as $func) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($func['numero']) . '</td>';
        echo '<td>' . htmlspecialchars(ucfirst(strtolower($func['nome']))) . '</td>';
        echo '<td>' . htmlspecialchars($func['total_copos']) . '</td>';
        echo '<td>' . htmlspecialchars($func['broca_morta']) . ' vez(es)</td>';
        echo '<td>' . htmlspecialchars($func['fungos']) . ' vez(es)</td>';
        echo '<td>' . htmlspecialchars($func['crisalida']) . ' vez(es)</td>';
        echo '<td>' . htmlspecialchars($func['colaborador']) . ' vez(es)</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '<tfoot><tr class="relatorio-total">
            <td colspan="5">Total Produzido no Dia</td>
            <td colspan="2">' . htmlspecialchars($total_dia) . ' copos</td>
          </tr></tfoot>';
    echo '</table>';
}
?>