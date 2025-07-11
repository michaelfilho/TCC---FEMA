<?php
session_start();
include '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'];

    // 1. Obter os IDs reais das justificativas
    $justificativas = $pdo->query("SELECT id_justificativa, descricao FROM justificativas")->fetchAll();

    // 2. Atualizar o mapeamento para incluir 'falta'
    $mapeamento = [
        'broca_morta' => null,
        'fungos' => null,
        'crisalida' => null,
        'colaborador' => null,
        'falta' => null
    ];

    // 3. Buscar também o id da justificativa 'falta'
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
        } elseif (strpos($desc_lower, 'falta') !== false) {
            $mapeamento['falta'] = $just['id_justificativa'];
        }
    }

    // 4. Preparar a query com a coluna "falta"
    $stmt = $pdo->prepare("
        SELECT 
            f.id_funcionario, 
            f.numero, 
            f.nome, 
            COALESCE(SUM(p.quantidade), 0) as total_copos,
            SUM(CASE WHEN p.id_justificativa = ? THEN 1 ELSE 0 END) as broca_morta,
            SUM(CASE WHEN p.id_justificativa = ? THEN 1 ELSE 0 END) as fungos,
            SUM(CASE WHEN p.id_justificativa = ? THEN 1 ELSE 0 END) as crisalida,
            SUM(CASE WHEN p.id_justificativa = ? THEN 1 ELSE 0 END) as colaborador,
            MAX(CASE WHEN p.id_justificativa = ? THEN 1 ELSE 0 END) as falta
        FROM 
            funcionarios f
        LEFT JOIN 
            producao p ON f.id_funcionario = p.id_funcionario AND p.data = ?
        WHERE 
            f.ativo = 1
        GROUP BY 
            f.id_funcionario
        ORDER BY 
            f.numero
    ");

    // 5. Executar a query passando os parâmetros
    $stmt->execute([
        $mapeamento['broca_morta'],
        $mapeamento['fungos'],
        $mapeamento['crisalida'],
        $mapeamento['colaborador'],
        $mapeamento['falta'],
        $data
    ]);

    // 6. Buscar os resultados
    $funcionarios = $stmt->fetchAll();

    // 7. Montar e exibir a tabela HTML
    echo '<table class="relatorio-table">';
    echo '<thead><tr>
            <th>Número</th>
            <th>Funcionário</th>
            <th>Total Copos</th>
            <th>Broca Morta</th>
            <th>Fungos</th>
            <th>Crisálida</th>
            <th>Colaborador</th>
            <th>Falta</th>
          </tr></thead>';
    echo '<tbody>';

    if ($funcionarios) {
        foreach ($funcionarios as $func) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($func['numero']) . '</td>';
            echo '<td>' . htmlspecialchars(ucfirst(strtolower($func['nome']))) . '</td>';
            echo '<td>' . htmlspecialchars($func['total_copos']) . '</td>';
            echo '<td>' . htmlspecialchars($func['broca_morta']) . ' vez(es)</td>';
            echo '<td>' . htmlspecialchars($func['fungos']) . ' vez(es)</td>';
            echo '<td>' . htmlspecialchars($func['crisalida']) . ' vez(es)</td>';
            echo '<td>' . htmlspecialchars($func['colaborador']) . ' vez(es)</td>';
            echo '<td>' . ($func['falta'] == 1 ? '1 ' : '-') . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="8" style="text-align:center;">Nenhum dado encontrado para a data selecionada.</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';
}
