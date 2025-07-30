document.addEventListener('DOMContentLoaded', function () {
    const horarios = ['08:00', '09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

    function calcularTotal() {
        let total = 0;
        const totalHorarioEl = document.getElementById('totalHorario');
        if (!totalHorarioEl) return; // evita erro se elemento não existir

        document.querySelectorAll('.quantidade').forEach(input => {
            total += parseInt(input.value) || 0;
        });
        totalHorarioEl.textContent = total;
    }

    function atualizarStatus() {
        const infoBox = document.querySelector('.info-box');
        if (!infoBox) return;

        const metaText = infoBox.querySelector('p:nth-child(3)');
        if (!metaText) return;

        const meta = parseInt(metaText.textContent.split(': ')[1]);
        if (isNaN(meta)) return;

        document.querySelectorAll('.quantidade').forEach(input => {
            const quantidade = parseInt(input.value) || 0;
            const row = input.closest('tr');
            if (!row) return;
            const statusCell = row.querySelector('.status');
            if (!statusCell) return;

            let status = '-';
            let statusClass = '';

            if (quantidade === 0) {
                status = '-';
            } else if (quantidade < meta * 0.5) {
                status = 'Fora da Meta';
                statusClass = 'status-baixo';
            } else if (quantidade < meta) {
                status = 'Razoável';
                statusClass = 'status-razoavel';
            } else {
                status = 'Meta Atingida';
                statusClass = 'status-meta';
            }

            statusCell.textContent = status;
            statusCell.className = 'status ' + statusClass;
        });
    }

    function salvarTodosDados() {
        const infoBox = document.querySelector('.info-box');
        if (!infoBox) return;

        const horario = infoBox.querySelector('p:nth-child(2)').textContent.split(': ')[1];
        const data = infoBox.querySelector('p:nth-child(1)').textContent.split(': ')[1];
        const linhas = document.querySelectorAll('tbody tr');

        const dados = [];
        let todosValidos = true;

        document.querySelectorAll('.quantidade').forEach(input => {
            input.classList.remove('invalid-input');
        });

        linhas.forEach(row => {
            const idFuncionario = row.dataset.funcionario;
            const quantidadeInput = row.querySelector('.quantidade');
            const quantidade = quantidadeInput.value;
            const justificativa = row.querySelector('.justificativa').value;

            if (quantidade === '' || isNaN(quantidade)) {
                todosValidos = false;
                quantidadeInput.classList.add('invalid-input');
            } else {
                dados.push({
                    id_funcionario: idFuncionario,
                    quantidade: quantidade,
                    justificativa: justificativa
                });
            }
        });

        if (!todosValidos) {
            alert('Por favor, preencha todos os campos de quantidade corretamente.');
            return;
        }

        const btnSalvarTudo = document.getElementById('salvarTudo');
        if (!btnSalvarTudo) return;

        const textoOriginal = btnSalvarTudo.textContent;
        btnSalvarTudo.textContent = 'Salvando...';
        btnSalvarTudo.disabled = true;

        fetch('processa.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `acao=salvar_tudo&horario=${encodeURIComponent(horario)}&data=${encodeURIComponent(data)}&dados=${encodeURIComponent(JSON.stringify(dados))}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Todos os dados foram salvos com sucesso!');
                    atualizarStatus();
                } else {
                    alert('Ocorreu um erro ao salvar alguns dados: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao conectar com o servidor.');
            })
            .finally(() => {
                btnSalvarTudo.textContent = textoOriginal;
                btnSalvarTudo.disabled = false;
            });
    }

    // --------- Eventos e funções do módulo MARCAÇÃO (se existir) ---------
    if (document.querySelector('.marcacao-container')) {
        document.querySelectorAll('.quantidade').forEach(input => {
            input.addEventListener('change', function () {
                calcularTotal();
                atualizarStatus();
            });
        });

        const proximoBtn = document.getElementById('proximoHorario');
        if (proximoBtn) {
            proximoBtn.addEventListener('click', function () {
                const horarioAtual = document.querySelector('.info-box p:nth-child(2)').textContent.split(': ')[1];
                const indexAtual = horarios.indexOf(horarioAtual);

                if (indexAtual < horarios.length - 1) {
                    document.querySelector('.info-box p:nth-child(2)').textContent = 'Horário Atual: ' + horarios[indexAtual + 1];

                    document.querySelectorAll('.quantidade').forEach(input => input.value = '0');
                    document.querySelectorAll('.justificativa').forEach(select => select.value = '');
                    document.querySelectorAll('.status').forEach(cell => {
                        cell.textContent = '-';
                        cell.className = 'status';
                    });
                    const totalHorarioEl = document.getElementById('totalHorario');
                    if (totalHorarioEl) totalHorarioEl.textContent = '0';
                } else {
                    alert('Todos os horários do dia já foram registrados!');
                }
            });
        }

        const encerrarBtn = document.getElementById('encerrarDia');
        if (encerrarBtn) {
            encerrarBtn.addEventListener('click', function () {
                if (confirm('Deseja realmente encerrar o dia? Salve os dados antes !!!')) {
                    alert('Dia encerrado com sucesso!');
                    window.location.href = '../index.php';
                }
            });
        }

        const salvarTudoBtn = document.getElementById('salvarTudo');
        if (salvarTudoBtn) {
            salvarTudoBtn.addEventListener('click', salvarTodosDados);
        }
    }

    // --------- Eventos e funções do módulo RELATÓRIO (se existir) ---------
    if (document.getElementById('relatorioForm')) {
        document.getElementById('relatorioForm').addEventListener('submit', function (e) {
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
                    const resultado = document.getElementById('relatorioResultado');
                    if (resultado) {
                        resultado.innerHTML = html;
                        aplicarPaginacao();
                        const btnPdf = document.getElementById('baixarPdf');
                        if (btnPdf) btnPdf.style.display = 'inline-block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });

        const relatorioHorarioBtn = document.getElementById('relatorioHorario');
        if (relatorioHorarioBtn) {
            relatorioHorarioBtn.addEventListener('click', function () {
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
                        const resultado = document.getElementById('relatorioResultado');
                        if (resultado) {
                            resultado.innerHTML = html;
                            aplicarPaginacao();
                            const btnPdf = document.getElementById('baixarPdf');
                            if (btnPdf) btnPdf.style.display = 'inline-block';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        }

        const baixarPdfBtn = document.getElementById('baixarPdf');
        if (baixarPdfBtn) {
            baixarPdfBtn.addEventListener('click', function () {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('p', 'pt', 'a4');
                const content = document.getElementById('relatorioResultado');
                const dataSelecionada = document.getElementById('data')?.value;

                if (!content || !dataSelecionada) return;

                const dataFormatada = (() => {
                    const partes = dataSelecionada.split('-');
                    return `${partes[2]}/${partes[1]}/${partes[0]}`;
                })();

                const textoRodape = `Data do relatório: ${dataFormatada}`;

                const tabela = content.querySelector('table');
                const linhas = tabela ? Array.from(tabela.querySelectorAll('tbody tr')) : [];

                const estadoOriginal = linhas.map(linha => linha.style.display);

                linhas.forEach(linha => linha.style.display = '');

                const paginacao = document.getElementById('paginacao');
                if (paginacao) paginacao.style.display = 'none';

                doc.html(content, {
                    callback: function (doc) {
                        const paginaAltura = doc.internal.pageSize.height;
                        doc.setFontSize(10);
                        doc.text(textoRodape, 40, paginaAltura - 30);

                        doc.save('relatorio_producao.pdf');
                        linhas.forEach((linha, index) => {
                            linha.style.display = estadoOriginal[index];
                        });
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
        }

        function aplicarPaginacao() {
            const linhasPorPagina = 7;
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
    }

    // --------- Funções para mover linhas e editar dados, inativar, alterar nome (mantidas do seu script) ---------
    function moverLinha(linha, direcao) {
        const tbody = linha.parentNode;
        const linhas = Array.from(tbody.children);
        const indexAtual = linhas.indexOf(linha);
        const novoIndex = direcao === 'up' ? indexAtual - 1 : indexAtual + 1;

        if (novoIndex >= 0 && novoIndex < linhas.length) {
            tbody.insertBefore(linha, linhas[novoIndex + (direcao === 'up' ? 0 : 1)]);
            atualizarOrdemVisual();
        }
    }

    function atualizarOrdemVisual() {
        document.querySelectorAll('tbody tr').forEach((tr, index) => {
            tr.dataset.ordem = index;
        });
    }

    function configurarAlterarCodigo() {
        document.querySelectorAll('.btn-alterar-codigo').forEach(btn => {
            btn.addEventListener('click', function () {
                const idFuncionario = this.dataset.id;
                const codigoAtual = this.dataset.codigo;
                btn.disabled = true;
                const row = btn.closest('tr');
                const tdCodigo = row.querySelector('.funcionario-codigo');
                const input = document.createElement('input');
                input.type = 'number';
                input.value = codigoAtual;
                input.style.width = '60px';
                input.classList.add('codigo-input');
                const salvarBtn = document.createElement('button');
                salvarBtn.textContent = '✔️';
                salvarBtn.classList.add('btn', 'btn-small');
                salvarBtn.style.marginLeft = '5px';
                tdCodigo.innerHTML = '';
                tdCodigo.appendChild(input);
                tdCodigo.appendChild(salvarBtn);

                salvarBtn.addEventListener('click', function () {
                    const novoCodigo = input.value;

                    fetch('processa.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `acao=alterar_codigo&id_funcionario=${idFuncionario}&novo_codigo=${novoCodigo}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                tdCodigo.textContent = `${novoCodigo}`;
                                btn.dataset.codigo = novoCodigo;
                            } else {
                                alert(data.message || "Erro ao alterar código");
                                tdCodigo.textContent = `${codigoAtual}`;
                            }
                        })
                        .catch(() => {
                            alert("Erro ao salvar código");
                            tdCodigo.textContent = `${codigoAtual}`;
                        })
                        .finally(() => {
                            btn.disabled = false;
                        });
                });
            });
        });
    }

    document.querySelectorAll('.btn-move-up').forEach(btn => {
        btn.addEventListener('click', function () {
            moverLinha(this.closest('tr'), 'up');
        });
    });

    document.querySelectorAll('.btn-move-down').forEach(btn => {
        btn.addEventListener('click', function () {
            moverLinha(this.closest('tr'), 'down');
        });
    });

    function configurarInativarFuncionario() {
        document.querySelectorAll('.btn-inativar').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const id = this.dataset.id;

                if (confirm('Deseja realmente inativar este funcionário?')) {
                    fetch('processa.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `acao=inativar_funcionario&id_funcionario=${id}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert('Funcionário inativado com sucesso!');
                                location.reload();
                            } else {
                                alert(data.message || 'Erro ao inativar funcionário.');
                            }
                        })
                        .catch(() => {
                            alert('Erro ao conectar com o servidor.');
                        });
                }
            });
        });
    }

    function configurarAlterarNome() {
        document.querySelectorAll('.btn-alterar-nome').forEach(btn => {
            btn.addEventListener('click', function () {
                const idFuncionario = this.dataset.id;
                const nomeAtual = this.dataset.nome;
                btn.disabled = true;
                const row = btn.closest('tr');
                const tdNome = row.querySelector('.funcionario-nome');
                const input = document.createElement('input');
                input.type = 'text';
                input.value = nomeAtual;
                input.style.width = '140px';
                input.classList.add('nome-input');
                const salvarBtn = document.createElement('button');
                salvarBtn.textContent = '✔️';
                salvarBtn.classList.add('btn', 'btn-small');
                salvarBtn.style.marginLeft = '5px';
                tdNome.innerHTML = '';
                tdNome.appendChild(input);
                tdNome.appendChild(salvarBtn);

                salvarBtn.addEventListener('click', function () {
                    const novoNome = input.value.trim();
                    const regex = /^[A-Za-zÀ-ÿ]+(?:\s[A-Za-zÀ-ÿ]+)*$/;

                    if (!regex.test(novoNome)) {
                        alert('Nome inválido! Use apenas letras, sem espaços, números ou símbolos.');
                        return;
                    }
                    if (novoNome.length < 2) {
                        alert('Nome muito curto');
                        return;
                    }

                    fetch('processa.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `acao=alterar_nome&id_funcionario=${idFuncionario}&novo_nome=${encodeURIComponent(novoNome)}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                tdNome.textContent = novoNome;
                                btn.dataset.nome = novoNome;
                            } else {
                                alert(data.message || "Erro ao alterar nome");
                                tdNome.textContent = nomeAtual;
                            }
                        })
                        .catch(() => {
                            alert("Erro ao salvar nome");
                            tdNome.textContent = nomeAtual;
                        })
                        .finally(() => {
                            btn.disabled = false;
                        });
                });
            });
        });
    }

    // Configurações iniciais
    configurarInativarFuncionario();
    configurarAlterarCodigo();
    configurarAlterarNome();
    calcularTotal();
    atualizarStatus();

});
