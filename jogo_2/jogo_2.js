/**
 * jogo_2.js
 * Lógica interativa do Jogo 2 - Gráfico de Barras com SVG e Pointer Events.
 * Mantém total integração com preferências de temas, progresso do usuário e estrelas no perfil.
 */

// ─── Constantes de LocalStorage e Configuração ──────────────────────────────
const CHAVE_PARAMS = 'mg_params_jogo';
const CHAVE_PROGRESSO = 'mg_progresso_fases';
const CHAVE_RECORDE_LIVRE = 'mg_recorde_livre';

// Cores acessíveis com bom contraste para cada barra
const PALETA_CORES = [
    { cor: '#1cb0f6', sombra: '#1899d6', nome: 'Azul' },
    { cor: '#ff9600', sombra: '#e58700', nome: 'Laranja' },
    { cor: '#58cc02', sombra: '#46a302', nome: 'Verde' },
    { cor: '#ce82ff', sombra: '#b558f6', nome: 'Roxo' },
    { cor: '#ff4b4b', sombra: '#ea2b2b', nome: 'Vermelho' },
];

const CONFIG_DIFICULDADE = {
    facil: { rodadas: 3, multiplicador: 1 },
    medio: { rodadas: 3, multiplicador: 1.5 },
    experiente: { rodadas: 5, multiplicador: 2 },
};

// ─── Estado do Jogo ──────────────────────────────────────────────────────────
const params = lerParams();
let rodadaAtual = 0;
let totalRodadas = CONFIG_DIFICULDADE[params.dificuldade]?.rodadas || 3;
let pontuacao = 0;
let acertosTotais = 0;
let jogoAtivo = true;
let questaoBloqueada = false;

// Dados da rodada ativa
let questaoAtual = null;
let valoresAluno = {}; // { 'Bananas': 15, 'Maçãs': 5 }
let dragActive = false;
let itemSendoArrastado = null;

// ─── Inicialização ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    aplicarPreferenciasVisuais();
    iniciarJogo();
});

function aplicarPreferenciasVisuais() {
    const tema = localStorage.getItem('mg_tema');
    const daltonismo = localStorage.getItem('mg_daltonismo');
    const tonalidade = localStorage.getItem('mg_tonalidade');
    if (tema === 'escuro') document.body.classList.add('tema-escuro');
    if (daltonismo) document.body.classList.add(`daltonismo-${daltonismo}`);
    if (tonalidade && tonalidade !== 'padrao') document.body.classList.add(`tonalidade-${tonalidade}`);
}

function iniciarJogo() {
    rodadaAtual = 0;
    pontuacao = 0;
    acertosTotais = 0;
    jogoAtivo = true;
    totalRodadas = CONFIG_DIFICULDADE[params.dificuldade]?.rodadas || 3;

    atualizarBarraDeProgresso(0);
    carregarNovaQuestao();
}

/**
 * Consome o endpoint backend gerar_questao.php para obter a questão e o eixo Y.
 */
async function carregarNovaQuestao() {
    if (rodadaAtual >= totalRodadas) {
        finalizarJogo();
        return;
    }

    const btnConfirmar = document.getElementById('btn-confirmar');
    const feedbackResposta = document.getElementById('feedback-resposta');
    questaoBloqueada = false;
    if (feedbackResposta) {
        feedbackResposta.hidden = true;
        feedbackResposta.textContent = '';
    }
    if (btnConfirmar) {
        btnConfirmar.disabled = false;
        btnConfirmar.textContent = 'Verificar';
    }

    dicaIndiceJogo2 = 0;
    ocultarDica();

    try {
        const response = await fetch('gerar_questao.php');
        const data = await response.json();

        if (data.ok) {
            questaoAtual = data;
            valoresAluno = {};
            timerPontuacaoQuestao.iniciar();

            // Inicializa cada barra no valor 0
            questaoAtual.itens.forEach(item => {
                valoresAluno[item] = 0;
            });

            document.getElementById('pergunta-texto').textContent = questaoAtual.texto;
            renderizarGraficoSVG();
            renderizarLegenda();
        } else {
            console.error('Erro ao carregar questão.');
            const pTexto = document.getElementById('pergunta-texto');
            if (pTexto) pTexto.textContent = 'Erro ao carregar questão. Tente recarregar a página.';
        }
    } catch (err) {
        console.error('Erro na requisição da questão:', err);
    }
}

// ─── Renderização do Gráfico SVG ──────────────────────────────────────────────

function renderizarGraficoSVG() {
    const svg = document.getElementById('svgGrafico');
    if (!svg || !questaoAtual) return;

    svg.innerHTML = ''; // Limpa gráfico anterior

    const { min, max, passo, arrayEixo } = questaoAtual.eixo;
    const itens = questaoAtual.itens;
    const qtdItens = itens.length;

    // Dimensões do viewBox SVG
    const svgWidth = 700;
    const svgHeight = 450;
    const paddingLeft = 70;
    const paddingRight = 40;
    const paddingTop = 40;
    const paddingBottom = 75;

    const chartAreaWidth = svgWidth - paddingLeft - paddingRight;
    const chartAreaHeight = svgHeight - paddingTop - paddingBottom;
    const chartBottomY = svgHeight - paddingBottom;

    // Desena Linhas de Grade e Marcações do Eixo Y
    const numMarcacoes = arrayEixo.length;
    arrayEixo.forEach((val) => {
        // Posição Y proporcional ao valor no eixo
        const pct = (val - min) / (max - min);
        const yPos = chartBottomY - (pct * chartAreaHeight);

        // Linha de grade horizontal
        const gridLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        gridLine.setAttribute('x1', paddingLeft);
        gridLine.setAttribute('y1', yPos);
        gridLine.setAttribute('x2', svgWidth - paddingRight);
        gridLine.setAttribute('y2', yPos);
        gridLine.setAttribute('stroke', 'var(--grid-line)');
        gridLine.setAttribute('stroke-width', val === 0 ? '2' : '1');
        gridLine.setAttribute('stroke-dasharray', val === 0 ? 'none' : '4 4');
        svg.appendChild(gridLine);

        // Rótulo do Eixo Y
        const textY = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        textY.setAttribute('x', paddingLeft - 12);
        textY.setAttribute('y', yPos + 5);
        textY.setAttribute('text-anchor', 'end');
        textY.setAttribute('font-size', '14');
        textY.setAttribute('font-weight', '700');
        textY.setAttribute('fill', 'var(--axis-text)');
        textY.textContent = val;
        svg.appendChild(textY);
    });

    // Eixo Y principal (linha vertical)
    const yAxisLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    yAxisLine.setAttribute('x1', paddingLeft);
    yAxisLine.setAttribute('y1', paddingTop);
    yAxisLine.setAttribute('x2', paddingLeft);
    yAxisLine.setAttribute('y2', chartBottomY);
    yAxisLine.setAttribute('stroke', 'var(--axis-text)');
    yAxisLine.setAttribute('stroke-width', '2');
    svg.appendChild(yAxisLine);

    // Eixo X principal (linha horizontal)
    const xAxisLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    xAxisLine.setAttribute('x1', paddingLeft);
    xAxisLine.setAttribute('y1', chartBottomY);
    xAxisLine.setAttribute('x2', svgWidth - paddingRight);
    xAxisLine.setAttribute('y2', chartBottomY);
    xAxisLine.setAttribute('stroke', 'var(--axis-text)');
    xAxisLine.setAttribute('stroke-width', '2');
    svg.appendChild(xAxisLine);

    // Renderização das Barras dos Itens
    const slotWidth = chartAreaWidth / qtdItens;
    const barWidth = Math.min(80, slotWidth * 0.55);

    itens.forEach((item, index) => {
        const colorInfo = PALETA_CORES[index % PALETA_CORES.length];
        const valorAtual = valoresAluno[item] || 0;

        const slotCenterX = paddingLeft + (index + 0.5) * slotWidth;
        const barX = slotCenterX - (barWidth / 2);

        // Cálculo da altura da barra
        const pctValor = (valorAtual - min) / (max - min);
        const barHeight = pctValor * chartAreaHeight;
        const barY = chartBottomY - barHeight;

        // Grupo da Barra
        const gBarra = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        gBarra.dataset.item = item;
        gBarra.classList.add('grupo-barra');

        // Retângulo principal da Barra (<rect>)
        const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rect.setAttribute('x', barX);
        rect.setAttribute('y', barY);
        rect.setAttribute('width', barWidth);
        rect.setAttribute('height', Math.max(4, barHeight)); // Mínimo de 4px para fácil toque
        rect.setAttribute('rx', '6');
        rect.setAttribute('ry', '6');
        rect.setAttribute('fill', colorInfo.cor);
        rect.setAttribute('stroke', colorInfo.sombra);
        rect.setAttribute('stroke-width', '2');
        rect.classList.add('barra-rect');
        rect.id = `bar-rect-${index}`;

        // Alça superior da barra para facilitador de arraste em mobile
        const handle = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        handle.setAttribute('x', barX - 4);
        handle.setAttribute('y', barY - 6);
        handle.setAttribute('width', barWidth + 8);
        handle.setAttribute('height', '12');
        handle.setAttribute('rx', '6');
        handle.setAttribute('fill', colorInfo.sombra);
        handle.classList.add('barra-handle');
        handle.id = `bar-handle-${index}`;

        // Valor exibido acima da barra
        const textValor = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        textValor.setAttribute('x', slotCenterX);
        textValor.setAttribute('y', Math.max(paddingTop - 10, barY - 12));
        textValor.setAttribute('text-anchor', 'middle');
        textValor.setAttribute('font-size', '16');
        textValor.setAttribute('font-weight', '800');
        textValor.setAttribute('fill', colorInfo.sombra);
        textValor.id = `bar-val-${index}`;
        textValor.textContent = valorAtual;

        // Rótulo do Item no Eixo X
        const textRotulo = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        textRotulo.setAttribute('x', slotCenterX);
        textRotulo.setAttribute('y', chartBottomY + 30);
        textRotulo.setAttribute('text-anchor', 'middle');
        textRotulo.setAttribute('font-size', '15');
        textRotulo.setAttribute('font-weight', '700');
        textRotulo.setAttribute('fill', 'var(--axis-text)');
        textRotulo.textContent = item;

        // Monta os elementos no grupo
        gBarra.appendChild(rect);
        gBarra.appendChild(handle);
        gBarra.appendChild(textValor);
        gBarra.appendChild(textRotulo);

        // Eventos de Pointer para Arraste (Touch / Mouse / Stylus)
        const iniciarArraste = (e) => {
            if (!jogoAtivo || questaoBloqueada) return;
            dragActive = true;
            itemSendoArrastado = item;
            gBarra.setPointerCapture(e.pointerId);
            atualizarValorPeloPointer(e, index, item, chartBottomY, chartAreaHeight);
        };

        const moverArraste = (e) => {
            if (!dragActive || questaoBloqueada || itemSendoArrastado !== item) return;
            atualizarValorPeloPointer(e, index, item, chartBottomY, chartAreaHeight);
        };

        const finalizarArraste = (e) => {
            if (dragActive && itemSendoArrastado === item) {
                dragActive = false;
                itemSendoArrastado = null;
                try { gBarra.releasePointerCapture(e.pointerId); } catch (_) { }
            }
        };

        gBarra.addEventListener('pointerdown', iniciarArraste);
        gBarra.addEventListener('pointermove', moverArraste);
        gBarra.addEventListener('pointerup', finalizarArraste);
        gBarra.addEventListener('pointercancel', finalizarArraste);

        svg.appendChild(gBarra);
    });
}

/**
 * Converte a posição Y do ponteiro no SVG em um valor snapped no array do eixo.
 */
function atualizarValorPeloPointer(e, index, item, chartBottomY, chartAreaHeight) {
    if (questaoBloqueada) return;
    const svg = document.getElementById('svgGrafico');
    const pt = svg.createSVGPoint();
    pt.x = e.clientX;
    pt.y = e.clientY;

    const svgP = pt.matrixTransform(svg.getScreenCTM().inverse());
    const pointerY = svgP.y;

    const { min, max, passo, arrayEixo } = questaoAtual.eixo;

    // Mapeamento Y SVG para valor continuo
    const pct = (chartBottomY - pointerY) / chartAreaHeight;
    let valorBruto = min + pct * (max - min);

    // Clamping entre min e max
    valorBruto = Math.max(min, Math.min(max, valorBruto));

    // Snap para o múltiplo do passo (5)
    const valorSnap = Math.round(valorBruto / passo) * passo;

    // Atualiza estado do aluno
    valoresAluno[item] = valorSnap;

    // Atualiza rect, handle e texto da barra correspondente para alta performance
    const rect = document.getElementById(`bar-rect-${index}`);
    const handle = document.getElementById(`bar-handle-${index}`);
    const textVal = document.getElementById(`bar-val-${index}`);

    if (rect && textVal) {
        const pctSnap = (valorSnap - min) / (max - min);
        const barHeight = pctSnap * chartAreaHeight;
        const barY = chartBottomY - barHeight;

        rect.setAttribute('y', barY);
        rect.setAttribute('height', Math.max(4, barHeight));

        if (handle) {
            handle.setAttribute('y', barY - 6);
        }

        const paddingTop = 40;
        textVal.setAttribute('y', Math.max(paddingTop - 10, barY - 12));
        textVal.textContent = valorSnap;
    }
}

// ─── Legenda das Cores ────────────────────────────────────────────────────────

function renderizarLegenda() {
    const container = document.getElementById('legendaContainer');
    if (!container || !questaoAtual) return;

    container.innerHTML = '';

    questaoAtual.itens.forEach((item, idx) => {
        const colorInfo = PALETA_CORES[idx % PALETA_CORES.length];

        const itemDiv = document.createElement('div');
        itemDiv.className = 'legenda-item';

        const corBox = document.createElement('div');
        corBox.className = 'legenda-cor';
        corBox.style.backgroundColor = colorInfo.cor;

        const label = document.createElement('span');
        label.textContent = item;

        itemDiv.appendChild(corBox);
        itemDiv.appendChild(label);
        container.appendChild(itemDiv);
    });
}

// ─── Validação com Backend ────────────────────────────────────────────────────

async function verificarResposta() {
    if (!jogoAtivo || !questaoAtual || questaoBloqueada) return;

    questaoBloqueada = true;
    const btnConfirmar = document.getElementById('btn-confirmar');
    if (btnConfirmar) btnConfirmar.disabled = true;

    try {
        const response = await fetch('verificar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ respostas: valoresAluno })
        });

        const res = await response.json();

        if (res.ok) {
            aplicarFeedbackBarras(res.detalhes);
            const feedbackResposta = document.getElementById('feedback-resposta');
            if (feedbackResposta) {
                if (res.acertou) {
                    feedbackResposta.textContent = 'Muito bem! Todos os valores estão corretos.';
                } else {
                    const valoresCorretos = Object.entries(res.detalhes)
                        .map(([item, detalhe]) => `${item}: ${detalhe.valorEsperado}`)
                        .join(' · ');
                    feedbackResposta.textContent = `Resposta correta: ${valoresCorretos}.`;
                }
                feedbackResposta.hidden = false;
            }

            const barra = document.getElementById('barra-progresso');

            if (res.acertou) {
                acertosTotais++;
                const bonusTempo = timerPontuacaoQuestao.multiplicador();
                pontuacao += Math.round(100 * (CONFIG_DIFICULDADE[params.dificuldade]?.multiplicador || 1) * bonusTempo);
                if (barra) {
                    barra.classList.remove('erro');
                    barra.classList.add('acerto');
                }
            } else {
                if (barra) {
                    barra.classList.remove('acerto');
                    barra.classList.add('erro');
                }
            }

            // Registra a conclusão da questão atual e avança a barra
            rodadaAtual++;
            atualizarBarraDeProgresso(rodadaAtual);

            // Mantém a resposta correta visível antes de carregar a próxima questão.
            setTimeout(() => {
                if (barra) {
                    barra.classList.remove('erro');
                    barra.classList.add('acerto');
                }
                if (btnConfirmar) btnConfirmar.disabled = false;
                carregarNovaQuestao();
            }, res.acertou ? 1200 : 2800);
        } else {
            questaoBloqueada = false;
            if (btnConfirmar) btnConfirmar.disabled = false;
        }
    } catch (err) {
        console.error('Erro na verificação da resposta:', err);
        questaoBloqueada = false;
        if (btnConfirmar) btnConfirmar.disabled = false;
    }
}

/**
 * Destaca as barras com Verde (Acerto) ou Vermelho (Erro) temporariamente.
 */
function aplicarFeedbackBarras(detalhes) {
    questaoAtual.itens.forEach((item, index) => {
        const rect = document.getElementById(`bar-rect-${index}`);
        const info = detalhes[item];

        if (rect && info) {
            if (info.correto) {
                rect.setAttribute('fill', '#58cc02');
                rect.setAttribute('stroke', '#46a302');
            } else {
                rect.setAttribute('fill', '#ff4b4b');
                rect.setAttribute('stroke', '#ea2b2b');
            }
        }
    });
}

// ─── Auxiliares e Encerramento do Jogo ────────────────────────────────────────

let dicaIndiceJogo2 = 0;
let dicaTimerJogo2 = null;

function solicitarDica() {
    if (!questaoAtual) return;

    const primeiroItem = questaoAtual.itens && questaoAtual.itens.length > 0 ? questaoAtual.itens[0] : 'item';
    const dicas = [
        "Observe a escala numérica no eixo vertical (Y) e a legenda colorida para identificar cada barra.",
        "Clique ou arraste o topo de cada barra para ajustar sua altura até o valor numérico exato do enunciado.",
        `Verifique no texto o valor correspondente a '${primeiroItem}' e posicione a sua barra na linha certa!`
    ];

    const msg = dicas[dicaIndiceJogo2 % dicas.length];
    dicaIndiceJogo2++;
    exibirDica(msg);
}

const mostrarDica = solicitarDica;

function exibirDica(msg) {
    const el = document.getElementById('dica-flutuante');
    const texto = document.getElementById('dica-texto');
    if (el && texto) {
        texto.textContent = msg;
        el.style.display = 'flex';
        if (dicaTimerJogo2) clearTimeout(dicaTimerJogo2);
        dicaTimerJogo2 = setTimeout(() => {
            el.style.display = 'none';
        }, 5500);
    }
}

function ocultarDica() {
    const el = document.getElementById('dica-flutuante');
    if (el) el.style.display = 'none';
    if (dicaTimerJogo2) {
        clearTimeout(dicaTimerJogo2);
        dicaTimerJogo2 = null;
    }
}

function atualizarBarraDeProgresso(rodada) {
    const barra = document.getElementById('barra-progresso');
    if (barra) {
        const pct = (rodada / totalRodadas) * 100;
        barra.style.width = `${pct}%`;
    }
}

function finalizarJogo() {
    jogoAtivo = false;
    const estrelas = calcularEstrelas(acertosTotais, totalRodadas);

    exibirResultado(estrelas);
    verificarSessaoEGravar(estrelas);
}

function calcularEstrelas(acertos, total) {
    const pct = acertos / total;
    if (pct >= 0.9) return 3;
    if (pct >= 0.6) return 2;
    if (pct > 0) return 1;
    return 0;
}

function exibirResultado(estrelas) {
    const modal = document.getElementById('modal-resultado');
    if (modal) {
        const elEstrelas = document.getElementById('modal-estrelas-display');
        const elPontos = document.getElementById('modal-pontuacao-valor');
        const elAcertos = document.getElementById('modal-acertos-valor');

        if (elEstrelas) {
            let svgHtml = '<div style="display:inline-flex; gap:8px; justify-content:center; align-items:center;">';
            for (let i = 1; i <= 3; i++) {
                if (i <= estrelas) {
                    svgHtml += '<svg width="34" height="34" viewBox="0 0 24 24" fill="#F59E0B" stroke="#D97706" stroke-width="1.5" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
                } else {
                    svgHtml += '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
                }
            }
            svgHtml += '</div>';
            elEstrelas.innerHTML = svgHtml;
            elEstrelas.setAttribute('aria-label', `${estrelas} de 3 estrelas`);
        }
        if (elPontos) elPontos.textContent = pontuacao;
        if (elAcertos) elAcertos.textContent = `${acertosTotais}/${totalRodadas}`;

        const comentario = document.getElementById('modal-comentario-valor');
        if (comentario) {
            if (estrelas === 3) comentario.textContent = 'Parabéns! Muito Bem!';
            else if (estrelas === 2) comentario.textContent = 'Boa tentativa, você está evoluindo! ';
            else comentario.textContent = 'Você está no caminho para ser uma estrela!';
        }

        modal.showModal();
    } else {
        console.log(`Jogo finalizado! Pontuação: ${pontuacao}, Estrelas: ${estrelas}/3, Acertos: ${acertosTotais}/${totalRodadas}`);
        voltarPaginaAnterior();
    }
}

async function verificarSessaoEGravar(estrelas) {
    if (params.modo === 'trilha') {
        gravarProgressoTrilha(estrelas);
    } else if (params.modo === 'livre') {
        gravarRecordeLivre();
    }
}

async function gravarProgressoTrilha(estrelas) {
    const ano = parseInt(params.ano, 10) || 6;
    const fase = parseInt(params.fase, 10) || 1;

    try {
        const progressoLocal = JSON.parse(localStorage.getItem(CHAVE_PROGRESSO)) || {};
        const chaveFase = `ano_${ano}_fase_${fase}`;
        if (!progressoLocal[chaveFase] || estrelas > (progressoLocal[chaveFase].estrelas || 0)) {
            progressoLocal[chaveFase] = { estrelas, pontuacao };
        }
        if (estrelas >= 1) {
            const chaveProx = `ano_${ano}_fase_${fase + 1}`;
            if (!progressoLocal[chaveProx]) {
                progressoLocal[chaveProx] = { estrelas: 0, pontuacao: 0, desbloqueada: true };
            }
        }
        localStorage.setItem(CHAVE_PROGRESSO, JSON.stringify(progressoLocal));
    } catch (e) {
        console.warn('Erro ao salvar progresso local:', e);
    }

    try {
        await fetch('../api/progresso/salvar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                ano: ano,
                fase: fase,
                fase_id: params.fase_id || 0,
                jogo_id: 2,
                pontuacao: pontuacao,
                estrelas: estrelas,
                tipo: 'trilha'
            })
        });
    } catch (e) {
        console.error('Erro ao salvar progresso na API', e);
    }
}

function gravarRecordeLivre() {
    const recordes = obterRecordesLivre();
    const jogoId = params.jogoId || 2;
    if (pontuacao > (recordes[jogoId] || 0)) {
        recordes[jogoId] = pontuacao;
        localStorage.setItem(CHAVE_RECORDE_LIVRE, JSON.stringify(recordes));
    }
}

function lerParams() {
    let p = { modo: 'livre', dificuldade: 'facil', ano: 6, fase: 1, jogoId: 2 };
    try {
        const raw = localStorage.getItem(CHAVE_PARAMS);
        if (raw) p = Object.assign(p, JSON.parse(raw));
    } catch (e) {
        // ignora
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('ano')) p.ano = parseInt(urlParams.get('ano'), 10);
    if (urlParams.has('fase')) {
        p.fase = parseInt(urlParams.get('fase'), 10);
        p.modo = 'trilha';
    }
    if (urlParams.has('dificuldade')) p.dificuldade = urlParams.get('dificuldade');
    if (urlParams.has('modo')) p.modo = urlParams.get('modo');

    return p;
}

function obterRecordesLivre() {
    try { return JSON.parse(localStorage.getItem(CHAVE_RECORDE_LIVRE)) || {}; } catch { return {}; }
}

function voltarPaginaAnterior() {
    const params = lerParams();
    if (params.modo === 'trilha') {
        window.location.href = '../conteudos/conteudos.php';
    } else {
        window.location.href = '../jogos/jogos.php';
    }
}
