<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['nivel_acesso'] !== 'auditoria') {
    header('Location: ../../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>TEMPUS - Total de Produção por Intervalo</title>
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

        .form-row {
            display: flex;
            align-items: flex-end;
            gap: 20px;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            font-size: 16px;
        }

        input[type="date"] {
            padding: 12px;
            font-size: 16px;
            width: 220px;
        }

        .relatorio-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .relatorio-table th,
        .relatorio-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        .relatorio-total {
            font-weight: bold;
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="../index.php">Relatório de Produção</a>
        <a href="../avaliacao/index.php">Relatório de Comportamento</a>
        <a href="../metas/definir_meta.php">Definir Meta</a>
        <a href="../somar/index.php">Totalizar</a>
        <a href="../filtro_individual.php">Filtro Individual</a>
        <a href="../../index.php">Voltar</a>

    </div>

    <!-- Conteúdo principal -->
    <div class="main-content">
        <div class="auditoria-container">
            <h1>Total de Produção por Intervalo</h1>

            <div class="date-selector">
                <form id="somatorioForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="dataInicio">Data Início:</label>
                            <input type="date" id="dataInicio" required>
                        </div>
                        <div class="form-group">
                            <label for="dataFim">Data Fim:</label>
                            <input type="date" id="dataFim" required>
                        </div>
                    </div>
                    <div class="btns-container">
                        <button type="submit" class="btn">Gerar Relatório</button>
                        <button type="button" class="btn" id="baixarPdf" style="display: none;">Baixar PDF</button>
                    </div>
                </form>
            </div>

            <!-- Resultado -->
            <div id="relatorioResultado" class="relatorio-resultado">
                <!-- Relatório inserido via fetch -->
            </div>
        </div>
    </div>

    <!-- Bibliotecas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- Script -->
    <script>
        const form = document.getElementById('somatorioForm');
        const resultadoDiv = document.getElementById('relatorioResultado');
        const btnPdf = document.getElementById('baixarPdf');

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const inicio = document.getElementById('dataInicio').value;
            const fim = document.getElementById('dataFim').value;

            if (!inicio || !fim) {
                alert('Por favor, preencha ambas as datas.');
                return;
            }

            fetch('../somar/relatorio_somatorio.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `inicio=${encodeURIComponent(inicio)}&fim=${encodeURIComponent(fim)}`
            })
            .then(response => response.text())
            .then(html => {
                resultadoDiv.innerHTML = html;
                btnPdf.style.display = 'inline-block';
            })
            .catch(error => {
                console.error('Erro ao buscar relatório:', error);
            });
        });

        btnPdf.addEventListener('click', function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'pt', 'a4');
            const content = document.getElementById('relatorioResultado');

            doc.html(content, {
                callback: function (doc) {
                    doc.save('relatorio_total_intervalo.pdf');
                },
                x: 10,
                y: 10,
                autoPaging: 'text',
                html2canvas: {
                    scale: 0.55,
                    useCORS: true
                }
            });
        });
    </script>
</body>
</html>
