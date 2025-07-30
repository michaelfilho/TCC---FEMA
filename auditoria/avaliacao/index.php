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
        <a href="../producao/index.php">Relatório de Produçao</a>
        <a href="../avaliacao/index.php">Relatório de Comportamento</a>
        <a href="../metas/definir_meta.php">Definir Meta</a>
        <a href="../somar/index.php">Totalizar</a>
        <a href="../Filtro individual/filtro_individual.php">Filtro Individual</a>
        <a href="../cadastrar/cadastar.php">Cadastrar Coordenador</a>
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
                    <button type="button" class="btn" id="relatorioPorHorario">Relatório por Horário</button>
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

            function aplicarPaginacao() {
                const linhasPorPagina = 8;
                const tabela = document.querySelector('#relatorioResultado table');
                if (!tabela) return;

                const linhas = Array.from(tabela.querySelectorAll('tbody tr'));
                let paginaAtual = 1;
                const totalPaginas = Math.ceil(linhas.length / linhasPorPagina);

                // Remove paginação antiga se existir
                const antigo = document.getElementById('paginacao');
                if (antigo) antigo.remove();

                function mostrarPagina(pagina) {
                    const inicio = (pagina - 1) * linhasPorPagina;
                    const fim = inicio + linhasPorPagina;

                    linhas.forEach((linha, index) => {
                        linha.style.display = (index >= inicio && index < fim) ? '' : 'none';
                    });

                    const info = document.getElementById('paginacaoInfo');
                    if (info) info.textContent = `Página ${pagina} de ${totalPaginas}`;
                }

                function criarControlesPaginacao() {
                    const container = document.createElement('div');
                    container.id = 'paginacao';
                    container.style.display = 'flex';
                    container.style.justifyContent = 'center';
                    container.style.alignItems = 'center';
                    container.style.gap = '10px';
                    container.style.marginTop = '20px';

                    container.innerHTML = `
                    <button id="anterior">Anterior</button>
                    <span id="paginacaoInfo" style="margin: 0 10px;">Página ${paginaAtual} de ${totalPaginas}</span>
                    <button id="proximo">Próximo</button>
                `;
                    tabela.parentNode.appendChild(container);

                    document.getElementById('anterior').onclick = () => {
                        if (paginaAtual > 1) {
                            paginaAtual--;
                            mostrarPagina(paginaAtual);
                        }
                    };

                    document.getElementById('proximo').onclick = () => {
                        if (paginaAtual < totalPaginas) {
                            paginaAtual++;
                            mostrarPagina(paginaAtual);
                        }
                    };
                }

                criarControlesPaginacao();
                mostrarPagina(paginaAtual);
            }

            form.addEventListener('submit', function(e) {
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
                        aplicarPaginacao(); // Aplica paginação ao carregar relatório
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                    });
            });

            btnPdf.addEventListener('click', function() {
                const {
                    jsPDF
                } = window.jspdf;
                const doc = new jsPDF('p', 'pt', 'a4'); // retrato

                const content = document.getElementById('relatorioResultado');
                const dataRelatorio = document.getElementById('data').value;

                // Formata a data do relatório para dd/mm/yyyy
                const dataFormatada = (() => {
                    const partes = dataRelatorio.split('-'); // yyyy-mm-dd
                    return `${partes[2]}/${partes[1]}/${partes[0]}`;
                })();

                // Oculta a paginação
                const paginacao = document.getElementById('paginacao');
                if (paginacao) paginacao.style.display = 'none';

                // Mostra todas as linhas
                const tabela = content.querySelector('table');
                const linhas = tabela ? Array.from(tabela.querySelectorAll('tbody tr')) : [];
                linhas.forEach(linha => linha.style.display = '');

                // Gera o PDF
                doc.html(content, {
                    callback: function(doc) {
                        // Adiciona a data do relatório no rodapé
                        const paginaAltura = doc.internal.pageSize.height;
                        doc.setFontSize(10);
                        doc.text(`Data do relatório: ${dataFormatada}`, 40, paginaAltura - 30);

                        doc.save('relatorio_comportamento.pdf');

                        // Restaura a paginação depois de salvar
                        if (paginacao) paginacao.style.display = 'flex';
                        aplicarPaginacao();
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

            document.getElementById('relatorioPorHorario').addEventListener('click', function() {
                const data = document.getElementById('data').value;

                fetch('relatorio_horario.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `data=${encodeURIComponent(data)}`
                    })
                    .then(response => response.text())
                    .then(html => {
                        relatorioResultado.innerHTML = html;
                        btnPdf.style.display = 'inline-block';
                        aplicarPaginacao();
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                    });
            });
        </script>
</body>

</html>