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

            case 'salvar_tudo':
                // Validação dos dados
                if (!isset($_POST['horario'], $_POST['data'], $_POST['dados'])) {
                    http_response_code(400);
                    die(json_encode(['status' => 'error', 'message' => 'Dados incompletos']));
                }

                $horario = $_POST['horario'];
                $data = $_POST['data'];
                $dados = json_decode($_POST['dados'], true);

                // Converter data do formato brasileiro para o formato do banco (yyyy-mm-dd)
                if (strpos($data, '/') !== false) {
                    $parts = explode('/', $data);
                    $data = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                }

                try {
                    $pdo->beginTransaction();

                    // Buscar a meta vigente no momento da marcação
                    $meta = $pdo->query("SELECT valor_meta FROM metas ORDER BY id_meta DESC LIMIT 1")->fetchColumn();
                    $meta = (float)$meta;

                    foreach ($dados as $item) {
                        $id_funcionario = (int) $item['id_funcionario'];
                        $quantidade = (int) $item['quantidade'];
                        $justificativa = !empty($item['justificativa']) ? (int) $item['justificativa'] : null;

                        // Verificar se já existe registro para esse funcionário, data e horário
                        $stmt = $pdo->prepare("SELECT id_producao FROM producao 
                                               WHERE id_funcionario = ? AND data = ? AND horario = ?");
                        $stmt->execute([$id_funcionario, $data, $horario]);
                        $existe = $stmt->fetch();

                        if ($existe) {
                            // Atualizar registro existente
                            $stmt = $pdo->prepare("UPDATE producao 
                                                   SET quantidade = ?, id_justificativa = ?, meta_utilizada = ? 
                                                   WHERE id_producao = ?");
                            $stmt->execute([$quantidade, $justificativa, $meta, $existe['id_producao']]);
                        } else {
                            // Inserir novo registro
                            $stmt = $pdo->prepare("INSERT INTO producao 
                                (id_funcionario, data, horario, quantidade, id_justificativa, meta_utilizada) 
                                VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$id_funcionario, $data, $horario, $quantidade, $justificativa, $meta]);
                        }
                    }

                    $pdo->commit();
                    echo json_encode(['status' => 'success', 'message' => 'Dados salvos com sucesso']);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar dados: ' . $e->getMessage()]);
                }
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

            case 'inativar_funcionario':
                if (!isset($_POST['id_funcionario'])) {
                    http_response_code(400);
                    die(json_encode(['status' => 'error', 'message' => 'ID não informado']));
                }

                $id = (int) $_POST['id_funcionario'];
                $stmt = $pdo->prepare("UPDATE funcionarios SET ativo = 0 WHERE id_funcionario = ?");
                $stmt->execute([$id]);

                echo json_encode(['status' => 'success', 'message' => 'Funcionário inativado']);
                break;

            case 'ativar_funcionario':
                if (!isset($_POST['id_funcionario'])) {
                    http_response_code(400);
                    die(json_encode(['status' => 'error', 'message' => 'ID não informado']));
                }

                $id = (int) $_POST['id_funcionario'];
                $stmt = $pdo->prepare("UPDATE funcionarios SET ativo = 1 WHERE id_funcionario = ?");
                $stmt->execute([$id]);

                echo json_encode(['status' => 'success', 'message' => 'Funcionário ativado']);
                break;
            case 'alterar_nome':
                if (!isset($_POST['id_funcionario'], $_POST['novo_nome'])) {
                    http_response_code(400);
                    die(json_encode(['status' => 'error', 'message' => 'Dados incompletos']));
                }

                $id_funcionario = (int) $_POST['id_funcionario'];
                $novo_nome = trim($_POST['novo_nome']);

                if (strlen($novo_nome) < 2) {
                    http_response_code(400);
                    die(json_encode(['status' => 'error', 'message' => 'Nome inválido']));
                }

                $stmt = $pdo->prepare("UPDATE funcionarios SET nome = ? WHERE id_funcionario = ?");
                $stmt->execute([$novo_nome, $id_funcionario]);

                echo json_encode(['status' => 'success', 'message' => 'Nome alterado com sucesso']);
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
