document.addEventListener('DOMContentLoaded', function () {
    const formAvaliacao = document.getElementById('avaliacaoForm');
    const formRelatorio = document.getElementById('relatorioForm');
    const relatorioResultado = document.getElementById('relatorioResultado');
    const btnRelatorioPorHorario = document.getElementById('relatorioPorHorario');
    const btnRelatorioHorario = document.getElementById('relatorioHorario');
    const btnPdf = document.getElementById('baixarPdf');

    // ✅ Função genérica
    function carregarRelatorio(url, data) {
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `data=${encodeURIComponent(data)}`
        })
        .then(res => res.text())
        .then(html => {
            relatorioResultado.innerHTML = html;
            if (btnPdf) btnPdf.style.display = 'inline-block';
        })
        .catch(err => {
            console.error('Erro ao carregar relatório:', err);
            relatorioResultado.innerHTML = `<p style="color:red;">Erro ao carregar relatório.</p>`;
            if (btnPdf) btnPdf.style.display = 'none';
        });
    }

    // ✅ Eventos para AVALIAÇÃO (relatório de comportamento)
    if (formAvaliacao) {
        formAvaliacao.addEventListener('submit', e => {
            e.preventDefault();
            const data = document.getElementById('data').value;
            carregarRelatorio('relatorio.php', data);
        });

        if (btnRelatorioPorHorario) {
            btnRelatorioPorHorario.addEventListener('click', () => {
                const data = document.getElementById('data').value;
                carregarRelatorio('relatorio_horario.php', data);
            });
        }
    }

    // ✅ Eventos para PRODUÇÃO (relatório de produção)
    if (formRelatorio) {
        formRelatorio.addEventListener('submit', e => {
            e.preventDefault();
            const data = document.getElementById('data').value;
            carregarRelatorio('relatorio.php', data);
        });

        if (btnRelatorioHorario) {
            btnRelatorioHorario.addEventListener('click', () => {
                const data = document.getElementById('data').value;
                carregarRelatorio('relatorio_horario.php', data);
            });
        }
    }

    // ✅ Botão PDF (com verificação)
    if (btnPdf) {
        btnPdf.addEventListener('click', () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'pt', 'a4');

            doc.html(relatorioResultado, {
                callback: doc => doc.save('relatorio.pdf'),
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
    // Marcação - Calcular total do horário
    function calcularTotal() {
        let total = 0;
        document.querySelectorAll('.quantidade').forEach(input => {
            total += parseInt(input.value) || 0;
        });
        document.getElementById('totalHorario').textContent = total;
    }

    // Marcação - Atualizar status baseado na meta
    function atualizarStatus() {
        const meta = parseInt(document.querySelector('.info-box p:nth-child(3)').textContent.split(': ')[1]);

        document.querySelectorAll('.quantidade').forEach(input => {
            const quantidade = parseInt(input.value) || 0;
            const row = input.closest('tr');
            const statusCell = row.querySelector('.status');

            let status = '';
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

    // Marcação - Event listeners
    if (document.querySelector('.marcacao-container')) {
        document.querySelectorAll('.quantidade').forEach(input => {
            input.addEventListener('change', function () {
                calcularTotal();
                atualizarStatus();
            });
        });

        document.querySelectorAll('.btn-alterar-nome').forEach(btn => {
            btn.addEventListener('click', function () {
                const idFuncionario = this.dataset.id;
                const nomeAtual = this.dataset.nome;
                const row = this.closest('tr');
                const tdNome = row.querySelector('td:nth-child(3)');

                // Cria input com nome atual
                const input = document.createElement('input');
                input.type = 'text';
                input.value = nomeAtual;
                input.style.width = '150px';
                input.classList.add('nome-input');

                // Cria botão de salvar
                const salvarBtn = document.createElement('button');
                salvarBtn.textContent = '✔️';
                salvarBtn.classList.add('btn', 'btn-small');
                salvarBtn.style.marginLeft = '5px';

                // Troca conteúdo da célula de nome
                tdNome.innerHTML = '';
                tdNome.appendChild(input);
                tdNome.appendChild(salvarBtn);

                salvarBtn.addEventListener('click', function () {
                    const novoNome = input.value.trim();

                    if (novoNome === '') {
                        alert('O nome não pode estar vazio.');
                        return;
                    }

                    fetch('processa.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `acao=alterar_nome&id_funcionario=${idFuncionario}&novo_nome=${encodeURIComponent(novoNome)}`
                    })
                        .then(response => response.text())
                        .then(data => {
                            if (data === 'sucesso') {
                                tdNome.textContent = novoNome;
                                btn.dataset.nome = novoNome;
                                alert('Nome alterado com sucesso!');
                            } else {
                                tdNome.textContent = nomeAtual;
                                alert('Erro ao alterar nome.');
                            }
                        })
                        .catch(() => {
                            tdNome.textContent = nomeAtual;
                            alert('Erro ao processar requisição.');
                        });
                });
            });
        });


        document.querySelectorAll('.salvar').forEach(button => {
            button.addEventListener('click', function () {
                const row = this.closest('tr');
                const idFuncionario = row.dataset.funcionario;
                const quantidade = row.querySelector('.quantidade').value;
                const justificativa = row.querySelector('.justificativa').value;
                const horario = document.querySelector('.info-box p:nth-child(2)').textContent.split(': ')[1];
                const data = document.querySelector('.info-box p:nth-child(1)').textContent.split(': ')[1];

                fetch('processa.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id_funcionario=${idFuncionario}&quantidade=${quantidade}&justificativa=${justificativa}&horario=${horario}&data=${data}`
                })
                    .then(response => response.text())
                    .then(data => {
                        alert('Dados salvos com sucesso!');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });

        document.getElementById('proximoHorario').addEventListener('click', function () {
            const horarioAtual = document.querySelector('.info-box p:nth-child(2)').textContent.split(': ')[1];
            const horarios = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'];
            const indexAtual = horarios.indexOf(horarioAtual);

            if (indexAtual < horarios.length - 1) {
                document.querySelector('.info-box p:nth-child(2)').textContent = 'Horário Atual: ' + horarios[indexAtual + 1];

                // Resetar valores para o próximo horário
                document.querySelectorAll('.quantidade').forEach(input => {
                    input.value = '0';
                });
                document.querySelectorAll('.justificativa').forEach(select => {
                    select.value = '';
                });
                document.querySelectorAll('.status').forEach(cell => {
                    cell.textContent = '-';
                    cell.className = 'status';
                });
                document.getElementById('totalHorario').textContent = '0';
            } else {
                alert('Todos os horários do dia já foram registrados!');
            }
        });

        document.getElementById('encerrarDia').addEventListener('click', function () {
            if (confirm('Deseja realmente encerrar o dia? Salve os dados antes !!!')) {
                alert('Dia encerrado com sucesso!');
                window.location.href = '../index.php';
            }
        });
    }

    // Auditoria - Carregar relatório
    if (document.getElementById('relatorioForm')) {
        document.getElementById('relatorioForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const data = document.getElementById('data').value;

            fetch('relatorio.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `data=${data}`
            })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('relatorioResultado').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    }
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

    // ✅ ALTERAR CÓDIGO - FUNÇÃO CORRIGIDA
    function configurarAlterarCodigo() {
        document.querySelectorAll('.btn-alterar-codigo').forEach(btn => {
            btn.addEventListener('click', function () {
                const idFuncionario = this.dataset.id;
                const codigoAtual = this.dataset.codigo;

                // ✅ ADICIONADO: LOG DE CLIQUE
                console.log('Clique detectado para alterar código!', idFuncionario);

                btn.disabled = true;

                const row = btn.closest('tr');
                const tdCodigo = row.querySelector('.funcionario-codigo');

                // Cria input com valor atual
                const input = document.createElement('input');
                input.type = 'number';
                input.value = codigoAtual;
                input.style.width = '60px';
                input.classList.add('codigo-input');

                // Cria botão de salvar
                const salvarBtn = document.createElement('button');
                salvarBtn.textContent = '✔️';
                salvarBtn.classList.add('btn', 'btn-small');
                salvarBtn.style.marginLeft = '5px';

                // Troca conteúdo
                tdCodigo.innerHTML = '';
                tdCodigo.appendChild(input);
                tdCodigo.appendChild(salvarBtn);

                // Ao salvar, faz o fetch
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
    // ✅ EXCLUIR FUNCIONÁRIO
    document.querySelectorAll('.excluir').forEach(btn => {
        btn.addEventListener('click', function () {
            const row = this.closest('tr');
            const idFuncionario = row.dataset.funcionario;

            if (confirm('Tem certeza que deseja excluir este funcionário?')) {
                fetch('processa.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `acao=excluir&id_funcionario=${idFuncionario}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            row.remove();
                            alert('Funcionário excluído com sucesso!');
                        } else {
                            alert(data.message || 'Erro ao excluir funcionário.');
                        }
                    })
                    .catch(() => {
                        alert('Erro ao processar a exclusão.');
                    });
            }
        });
    });

    // ✅ CONFIGURAR BOTÕES DE MOVER E ALTERAR CÓDIGO
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

    configurarAlterarCodigo(); // ✅ ESSENCIAL para funcionar corretamente
});