<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'auditoria') {
    header('Location: ../../index.php');
    exit;
}

include '../../includes/db.php';

$datas = $pdo->query("SELECT DISTINCT data FROM producao ORDER BY data DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEMPUS - Auditoria</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="shortcut icon" href="../../css/imagens/1.png" type="image/x-icon">

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
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

        .date-selector {
            margin-top: 20px;
        }

        .btns-container {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <!-- Barra lateral -->
    <div class="sidebar">
        <a href="../index.php">Relatório de Produçao</a>
        <a href="../avaliacao/index.php">Relatório de Comportamento</a>
        <a href="../metas/definir_meta.php">Definir Meta</a>
        <a href="../../index.php">Voltar</a>
    </div>

    <!-- Conteúdo principal -->
    <div class="main-content">
        <div class="auditoria-container">
            <h1>Relatório de Comportamento</h1>

            <div class="date-selector">
                <form id="avaliacaoForm">
                    <label for="data">Selecione a data:</label>
                    <select name="data" id="data" required>
                        <?php foreach ($datas as $data): ?>
                            <option value="<?= $data['data'] ?>"><?= date('d/m/Y', strtotime($data['data'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn">Gerar Relatório</button>
                    <button type="button" class="btn" id="baixarPdf" style="display: none;">Baixar PDF</button>

                </form>
            <!-- Resultado -->
            <div id="relatorioResultado" class="relatorio-resultado">
                <!-- Resultado do relatório será inserido aqui -->
            </div>
        </div>
    </div>

    <!-- Bibliotecas necessárias -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- Script principal -->
    <script>
        const form = document.getElementById('avaliacaoForm');
        const relatorioResultado = document.getElementById('relatorioResultado');
        const btnPdf = document.getElementById('baixarPdf');

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const data = document.getElementById('data').value;

            fetch('relatorio.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `data=${encodeURIComponent(data)}`
            })
            .then(response => response.text())
            .then(html => {
                relatorioResultado.innerHTML = html;
                btnPdf.style.display = 'inline-block'; // Exibe o botão de PDF
            })
            .catch(error => {
                console.error('Erro:', error);
            });
        });

        btnPdf.addEventListener('click', function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'pt', 'a4'); // retrato

            const content = document.getElementById('relatorioResultado');

            doc.html(content, {
                callback: function (doc) {
                    doc.save('relatorio_comportamento.pdf');
                },
                x: 10,
                y: 10,
                autoPaging: 'text',
                html2canvas: {
                    scale: 0.55, // reduz o zoom
                    useCORS: true
                }
            });
        });
    </script>
</body>

</html>
