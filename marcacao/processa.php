<?php
session_start();
include '../includes/db.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    die("Acesso não autorizado");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verifica o tipo de ação
        $acao = $_POST['acao'] ?? 'salvar_producao';

        switch ($acao) {
            
            case 'alterar_nome':
                $id = $_POST['id_funcionario'] ?? '';
                $novoNome = trim($_POST['novo_nome'] ?? '');
            
                if ($id && $novoNome !== '') {
                    try {
                        $stmt = $pdo->prepare("UPDATE funcionarios SET nome = ? WHERE id_funcionario = ?");
                        $stmt->execute([$novoNome, $id]);
            
                        echo 'sucesso';
                        exit;
                    } catch (Exception $e) {
                        echo 'erro';
                        exit;
                    }
                } else {
                    echo 'erro';
                    exit;
                }                
            case 'excluir':
                $id = $_POST['id_funcionario'] ?? '';
            
                if ($id) {
                    try {
                        // Inicia uma transação
                        $pdo->beginTransaction();
            
                        // Exclui registros na tabela producao primeiro
                        $stmt1 = $pdo->prepare("DELETE FROM producao WHERE id_funcionario = ?");
                        $stmt1->execute([$id]);
            
                        // Agora exclui o funcionário
                        $stmt2 = $pdo->prepare("DELETE FROM funcionarios WHERE id_funcionario = ?");
                        $stmt2->execute([$id]);
            
                        // Finaliza a transação
                        $pdo->commit();
            
                        echo json_encode(['status' => 'success']);
                        exit;
                    } catch (Exception $e) {
                        // Reverte a transação em caso de erro
                        $pdo->rollBack();
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Erro ao excluir: ' . $e->getMessage()
                        ]);
                        exit;
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'ID do funcionário inválido.']);
                    exit;
                }

            case 'salvar_producao':
                // Validação dos dados de produção
                if (!isset($_POST['id_funcionario'], $_POST['quantidade'], $_POST['horario'], $_POST['data'])) {
                    http_response_code(400);
                    die("Dados incompletos para salvar produção");
                }

                $id_funcionario = (int) $_POST['id_funcionario'];
                $quantidade = (int) $_POST['quantidade'];
                $justificativa = !empty($_POST['justificativa']) ? (int) $_POST['justificativa'] : null;
                $horario = $_POST['horario'];
                $data = $_POST['data'];

                // Converter data do formato brasileiro para o formato do banco (se necessário)
                if (strpos($data, '/') !== false) {
                    $parts = explode('/', $data);
                    $data = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                }

                // Verificar se já existe registro para este funcionário, data e horário
                $stmt = $pdo->prepare("SELECT id_producao FROM producao WHERE id_funcionario = ? AND data = ? AND horario = ?");
                $stmt->execute([$id_funcionario, $data, $horario]);
                $existe = $stmt->fetch();

                if ($existe) {
                    // Atualizar registro existente
                    $stmt = $pdo->prepare("UPDATE producao SET quantidade = ?, id_justificativa = ? WHERE id_producao = ?");
                    $stmt->execute([$quantidade, $justificativa, $existe['id_producao']]);
                } else {
                    // Inserir novo registro
                    $stmt = $pdo->prepare("INSERT INTO producao (id_funcionario, data, horario, quantidade, id_justificativa) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$id_funcionario, $data, $horario, $quantidade, $justificativa]);
                }

                echo json_encode(['status' => 'success', 'message' => 'Produção salva com sucesso']);
                break;

            case 'alterar_codigo':
                // Validação para alterar código
                if (!isset($_POST['id_funcionario'], $_POST['novo_codigo'])) {
                    http_response_code(400);
                    die("Dados incompletos para alterar código");
                }

                $id_funcionario = (int) $_POST['id_funcionario'];
                $novo_codigo = (int) $_POST['novo_codigo'];

                // Verificar se o novo código já existe
                $stmt = $pdo->prepare("SELECT id_funcionario FROM funcionarios WHERE numero = ? AND id_funcionario != ?");
                $stmt->execute([$novo_codigo, $id_funcionario]);

                if ($stmt->fetch()) {
                    http_response_code(409);
                    die("Este código já está em uso por outro funcionário");
                }

                // Atualizar o código
                $stmt = $pdo->prepare("UPDATE funcionarios SET numero = ? WHERE id_funcionario = ?");
                $stmt->execute([$novo_codigo, $id_funcionario]);

                echo json_encode(['status' => 'success', 'message' => 'Código do funcionário atualizado com sucesso']);
                break;

            case 'mudar_lugar':
                // Validação para mudar lugar
                if (!isset($_POST['id_origem'], $_POST['id_destino'])) {
                    http_response_code(400);
                    die("Dados incompletos para mudar lugar");
                }

                $id_origem = (int) $_POST['id_origem'];
                $id_destino = (int) $_POST['id_destino'];

                // Verificar se os funcionários existem
                $stmt = $pdo->prepare("SELECT id_funcionario FROM funcionarios WHERE id_funcionario IN (?, ?)");
                $stmt->execute([$id_origem, $id_destino]);
                $existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (count($existentes) !== 2) {
                    http_response_code(404);
                    die("Um ou ambos os funcionários não foram encontrados");
                }

                // Transferir produção
                $stmt = $pdo->prepare("UPDATE producao SET id_funcionario = ? WHERE id_funcionario = ?");
                $stmt->execute([$id_destino, $id_origem]);

                echo json_encode(['status' => 'success', 'message' => 'Produção transferida com sucesso']);
                break;

            default:
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Ação desconhecida']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Erro no processa.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Erro no servidor']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido']);
}
?>