/**
 * jogo_1.js
 * Jogo de Frações com Pizza.
 * Recebe parâmetros de dificuldade/fase via localStorage (mg_params_jogo)
 * e, ao finalizar, calcula estrelas e grava o progresso se houver sessão ativa.
 */

// ─── Constantes ───────────────────────────────────────────────────────────────
const CHAVE_PARAMS    = 'mg_params_jogo';
const CHAVE_USUARIO   = 'mg_usuario';
const CHAVE_PROGRESSO = 'mg_progresso';
const CHAVE_RECORDE_LIVRE = 'mg_recorde_livre';

// ─── Configurações por dificuldade ────────────────────────────────────────────
const CONFIG_DIFICULDADE = {
    facil:      { rodadas: 3, tempoLimite: null, multiplicadorPontos: 1   },
    medio:      { rodadas: 3, tempoLimite: null, multiplicadorPontos: 1.5 },
    experiente: { rodadas: 5, tempoLimite: 60,   multiplicadorPontos: 2   },
};

// ─── Parâmetros recebidos ─────────────────────────────────────────────────────
const params = lerParams();

// ─── Estado do Jogo ───────────────────────────────────────────────────────────
let currentTarget = {};
let rodadaAtual   = 0;
let totalRodadas  = CONFIG_DIFICULDADE[params.dificuldade]?.rodadas || 3;
let pontuacao     = 0;
let acertos       = 0;
let jogoAtivo     = true;
let filaFases     = [];

// ─── Banco de Frações por dificuldade ─────────────────────────────────────────
const FRACOES = {
    facil: [
        { whole: 0, num: 1, den: 2, slicesPerPizza: 2,  pizzaCount: 1 },
        { whole: 0, num: 1, den: 4, slicesPerPizza: 4,  pizzaCount: 1 },
        { whole: 0, num: 3, den: 4, slicesPerPizza: 4,  pizzaCount: 1 },
        { whole: 0, num: 2, den: 4, slicesPerPizza: 4,  pizzaCount: 1 },
        { whole: 0, num: 1, den: 3, slicesPerPizza: 3,  pizzaCount: 1 },
        { whole: 0, num: 2, den: 3, slicesPerPizza: 3,  pizzaCount: 1 },
        { whole: 0, num: 1, den: 5, slicesPerPizza: 5,  pizzaCount: 1 },
        { whole: 0, num: 2, den: 5, slicesPerPizza: 5,  pizzaCount: 1 },
        { whole: 0, num: 3, den: 5, slicesPerPizza: 5,  pizzaCount: 1 },
        { whole: 0, num: 4, den: 5, slicesPerPizza: 5,  pizzaCount: 1 },
    ],
    medio: [
        { whole: 0, num: 2, den: 6,  slicesPerPizza: 6,  pizzaCount: 1 },
        { whole: 0, num: 3, den: 6,  slicesPerPizza: 6,  pizzaCount: 1 },
        { whole: 0, num: 4, den: 6,  slicesPerPizza: 6,  pizzaCount: 1 },
        { whole: 0, num: 5, den: 6,  slicesPerPizza: 6,  pizzaCount: 1 },
        { whole: 0, num: 3, den: 8,  slicesPerPizza: 8,  pizzaCount: 1 },
        { whole: 0, num: 5, den: 8,  slicesPerPizza: 8,  pizzaCount: 1 },
        { whole: 0, num: 7, den: 8,  slicesPerPizza: 8,  pizzaCount: 1 },
        { whole: 1, num: 1, den: 4,  slicesPerPizza: 4,  pizzaCount: 2 },
        { whole: 1, num: 1, den: 2,  slicesPerPizza: 2,  pizzaCount: 2 },
        { whole: 1, num: 1, den: 3,  slicesPerPizza: 3,  pizzaCount: 2 },
    ],
    experiente: [
        { whole: 0, num: 5, den: 12, slicesPerPizza: 12, pizzaCount: 1 },
        { whole: 0, num: 7, den: 12, slicesPerPizza: 12, pizzaCount: 1 },
        { whole: 0, num: 11, den: 12, slicesPerPizza: 12, pizzaCount: 1 },
        { whole: 1, num: 3, den: 8,  slicesPerPizza: 8,  pizzaCount: 2 },
        { whole: 1, num: 5, den: 8,  slicesPerPizza: 8,  pizzaCount: 2 },
        { whole: 1, num: 7, den: 8,  slicesPerPizza: 8,  pizzaCount: 2 },
        { whole: 2, num: 1, den: 6,  slicesPerPizza: 6,  pizzaCount: 3 },
        { whole: 2, num: 2, den: 6,  slicesPerPizza: 6,  pizzaCount: 3 },
        { whole: 2, num: 5, den: 6,  slicesPerPizza: 6,  pizzaCount: 3 },
        { whole: 2, num: 3, den: 4,  slicesPerPizza: 4,  pizzaCount: 3 },
    ],
};

// ─── Init ─────────────────────────────────────────────────────────────────────

aplicarPreferenciasVisuais();
iniciarJogo();

function aplicarPreferenciasVisuais() {
    const tema = localStorage.getItem('mg_tema');
    const daltonismo = localStorage.getItem('mg_daltonismo');
    const tonalidade = localStorage.getItem('mg_tonalidade');
    if (tema === 'escuro') document.body.classList.add('tema-escuro');
    if (daltonismo) document.body.classList.add(`daltonismo-${daltonismo}`);
    if (tonalidade && tonalidade !== 'padrao') document.body.classList.add(`tonalidade-${tonalidade}`);
}

function randomSort(arr) {
    return [...arr].sort(() => Math.random() - 0.5);
}

function gerarFilaDeFases(dificuldade) {
    if (dificuldade === 'facil') {
        return randomSort(FRACOES.facil).slice(0, 3);
    } else if (dificuldade === 'medio') {
        return randomSort(FRACOES.medio).slice(0, 3);
    } else if (dificuldade === 'experiente') {
        const medias = randomSort(FRACOES.medio).slice(0, 2);
        const exp    = randomSort(FRACOES.experiente).slice(0, 3);
        return randomSort([...medias, ...exp]);
    }
    return randomSort(FRACOES.facil).slice(0, 3);
}

function iniciarJogo() {
    rodadaAtual = 0;
    pontuacao   = 0;
    acertos     = 0;
    jogoAtivo   = true;
    totalRodadas = CONFIG_DIFICULDADE[params.dificuldade]?.rodadas || 3;
    filaFases = gerarFilaDeFases(params.dificuldade);
    
    atualizarBarraDeProgresso(0);
    
    proximaRodada();
}

function proximaRodada() {
    if (rodadaAtual >= totalRodadas) {
        finalizarJogo();
        return;
    }

    if (params.modo === 'trilha' && params.slicesPerPizza && !params.dificuldade) {
        currentTarget = {
            whole: 0, num: 1, den: 2,
            slicesPerPizza: params.slicesPerPizza,
            pizzaCount: params.pizzaCount || 1,
        };
    } else {
        currentTarget = filaFases[rodadaAtual];
    }

    rodadaAtual++;
    atualizarUI();
    renderPizzas();
}

// ─── UI ───────────────────────────────────────────────────────────────────────

function atualizarUI() {
    const elPergunta = document.getElementById('pergunta-texto') || document.querySelector('.pergunta');
    if (elPergunta) {
        const { whole, num, den } = currentTarget;
        elPergunta.textContent = whole > 0 ? `${whole} e ${num}/${den}` : `${num}/${den}`;
    }
}

/** Volta para a página que originou o jogo (conteudos ou jogos). */
function voltarPaginaAnterior() {
    const params = lerParams();
    if (params.modo === 'trilha') {
        window.location.href = '../conteudos/conteudos.php';
    } else {
        window.location.href = '../jogos/jogos.php';
    }
}

/** Desfaz a última fatia marcada como comida. */
function desfazerUltimaFatia() {
    if (!jogoAtivo) return;
    const fatias = document.querySelectorAll('.slice.eaten');
    if (fatias.length > 0) {
        fatias[fatias.length - 1].classList.remove('eaten');
    }
}

// ─── Lógica de Confirmação ────────────────────────────────────────────────────

function onDecay() {
    if (!jogoAtivo) return;

    const respostaCorreta = calcularFatiasSelecionadas();
    const fatiasSelecionadas = contarFatiasComidas();

    if (fatiasSelecionadas === respostaCorreta) {
        acertos++;
        pontuacao += Math.round(100 * (CONFIG_DIFICULDADE[params.dificuldade]?.multiplicadorPontos || 1));
    }

    atualizarBarraDeProgresso(rodadaAtual);

    proximaRodada();
}

function atualizarBarraDeProgresso(rodada) {
    const barra = document.getElementById('barra-progresso');
    if (barra) {
        const pct = (rodada / totalRodadas) * 100;
        barra.style.width = `${pct}%`;
    }
}

function calcularFatiasSelecionadas() {
    const { whole, num, den, slicesPerPizza } = currentTarget;
    return whole * slicesPerPizza + Math.round((num / den) * slicesPerPizza);
}

function contarFatiasComidas() {
    return document.querySelectorAll('.slice.eaten').length;
}

// ─── Finalização ──────────────────────────────────────────────────────────────

function finalizarJogo() {
    jogoAtivo = false;

    const estrelas = calcularEstrelas(acertos, totalRodadas);

    exibirResultado(estrelas);

    verificarSessaoEGravar(estrelas);
}

/** Verifica via API se há sessão ativa antes de gravar o progresso. */
async function verificarSessaoEGravar(estrelas) {
    if (params.modo === 'trilha') {
        try {
            const res = await fetch('../api/auth/session.php');
            const data = await res.json();
            if (data.ok && data.usuario) {
                gravarProgressoTrilha(estrelas);
            }
        } catch(e) {
            console.error('Erro ao verificar sessão:', e);
        }
    } else if (params.modo === 'livre') {
        gravarRecordeLivre();
    }
}

/**
 * Calcula de 1 a 3 estrelas baseado na proporção de acertos.
 * @param {number} acertos
 * @param {number} total
 * @returns {number}
 */
function calcularEstrelas(acertos, total) {
    const pct = acertos / total;
    if (pct >= 0.9) return 3;
    if (pct >= 0.6) return 2;
    if (pct >  0)   return 1;
    return 0;
}

function exibirResultado(estrelas) {
    const estrelasTxt = '⭐'.repeat(estrelas) + '☆'.repeat(3 - estrelas);
    alert(`Jogo finalizado!\nPontuação: ${pontuacao}\nEstrelas: ${estrelasTxt}\nAcertos: ${acertos}/${totalRodadas}`);
}

// ─── Gravação de Progresso (Trilha) ──────────────────────────────────────────

async function gravarProgressoTrilha(estrelas) {
    // Calculo do ID da fase (baseado nos IDs do DB: 601, 602, etc. ou usando um de/para simples)
    // No DB de seed, temos IDs 1 a 12.
    // Ano 6 = IDs 1,2,3
    // Ano 7 = IDs 4,5,6
    // Ano 8 = IDs 7,8,9
    // Ano 9 = IDs 10,11,12
    const anoOffset = (params.ano - 6) * 3;
    const faseId = anoOffset + params.fase;

    try {
        await fetch('../api/progresso/salvar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                fase_id: faseId,
                pontuacao: pontuacao,
                estrelas: estrelas
            })
        });
    } catch(e) {
        console.error('Erro ao salvar progresso na API', e);
    }
}

// ─── Gravação de Recorde Livre ────────────────────────────────────────────────

function gravarRecordeLivre() {
    const recordes = obterRecordesLivre();
    const jogoId = params.jogoId || 1;
    if (pontuacao > (recordes[jogoId] || 0)) {
        recordes[jogoId] = pontuacao;
        localStorage.setItem(CHAVE_RECORDE_LIVRE, JSON.stringify(recordes));
    }
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function lerParams() {
    try {
        const raw = localStorage.getItem(CHAVE_PARAMS);
        return raw ? JSON.parse(raw) : { modo: 'livre', dificuldade: 'facil' };
    } catch {
        return { modo: 'livre', dificuldade: 'facil' };
    }
}

function obterUsuarioLogado() {
    try { return JSON.parse(localStorage.getItem(CHAVE_USUARIO)); } catch { return null; }
}

function obterProgresso() {
    try { return JSON.parse(localStorage.getItem(CHAVE_PROGRESSO)) || {}; } catch { return {}; }
}

function salvarProgresso(progresso) {
    localStorage.setItem(CHAVE_PROGRESSO, JSON.stringify(progresso));
}

function obterRecordesLivre() {
    try { return JSON.parse(localStorage.getItem(CHAVE_RECORDE_LIVRE)) || {}; } catch { return {}; }
}

// ─── Renderização das Pizzas (mantida do original) ────────────────────────────

function renderPizzas() {
    const container = document.getElementById('pizzasArea');
    container.innerHTML = '';

    for (let p = 0; p < currentTarget.pizzaCount; p++) {
        const wrapper = document.createElement('div');
        wrapper.className = 'pizza-wrapper';

        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 260 260');
        svg.classList.add('pizza');

        // Prato (fica visível quando pedaços são comidos)
        const plate = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        plate.setAttribute('cx', '130');
        plate.setAttribute('cy', '130');
        plate.setAttribute('r', '122');
        plate.setAttribute('fill', '#e0d6c3');
        plate.setAttribute('stroke', '#c7b89d');
        plate.setAttribute('stroke-width', '3');
        svg.appendChild(plate);

        const totalSlices = currentTarget.slicesPerPizza;
        const angleStep   = 360 / totalSlices;

        for (let i = 0; i < totalSlices; i++) {
            const startAngle = i * angleStep;
            const endAngle   = (i + 1) * angleStep;

            const sliceGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            sliceGroup.classList.add('slice');
            sliceGroup.dataset.pizzaIndex = p;
            sliceGroup.dataset.sliceIndex = i;

            // Borda/Massa
            const crustSlice = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            crustSlice.setAttribute('d', getSlicePath(130, 130, 120, startAngle, endAngle));
            crustSlice.setAttribute('fill', '#d35400');
            crustSlice.setAttribute('stroke', '#a04000');
            crustSlice.setAttribute('stroke-width', '1.5');
            sliceGroup.appendChild(crustSlice);

            // Queijo
            const cheeseSlice = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            cheeseSlice.setAttribute('d', getSlicePath(130, 130, 110, startAngle, endAngle));
            cheeseSlice.setAttribute('fill', '#f1c40f');
            cheeseSlice.setAttribute('stroke', '#f39c12');
            cheeseSlice.setAttribute('stroke-width', '1.5');
            sliceGroup.appendChild(cheeseSlice);

            // Peperonis
            addPepperonisToSlice(sliceGroup, startAngle, endAngle, totalSlices);

            sliceGroup.onclick = () => eatSlice(sliceGroup);
            svg.appendChild(sliceGroup);
        }

        wrapper.appendChild(svg);
        container.appendChild(wrapper);
    }
}

function eatSlice(sliceGroup) {
    if (!jogoAtivo) return;
    sliceGroup.classList.toggle('eaten');
}

function addPepperonisToSlice(group, startAngle, endAngle, totalSlices) {
    const midAngle = (startAngle + endAngle) / 2;
    const radiusDistances = totalSlices > 8 ? [70] : [55, 88];

    radiusDistances.forEach((rDist, idx) => {
        const angleOffset = (idx % 2 === 0 ? -3 : 3);
        const rad = ((midAngle + angleOffset) - 90) * Math.PI / 180;
        const px = 130 + rDist * Math.cos(rad);
        const py = 130 + rDist * Math.sin(rad);

        const pep = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        pep.setAttribute('cx', px);
        pep.setAttribute('cy', py);
        pep.setAttribute('r', totalSlices > 8 ? '8' : '11');
        pep.setAttribute('fill', '#c0392b');
        pep.setAttribute('stroke', '#922b21');
        pep.setAttribute('stroke-width', '1.5');
        group.appendChild(pep);
    });
}

function getSlicePath(cx, cy, r, startAngle, endAngle) {
    const startRad = (startAngle - 90) * Math.PI / 180;
    const endRad   = (endAngle   - 90) * Math.PI / 180;

    const x1 = cx + r * Math.cos(startRad);
    const y1 = cy + r * Math.sin(startRad);
    const x2 = cx + r * Math.cos(endRad);
    const y2 = cy + r * Math.sin(endRad);

    const largeArc = (endAngle - startAngle) > 180 ? 1 : 0;
    return `M ${cx} ${cy} L ${x1} ${y1} A ${r} ${r} 0 ${largeArc} 1 ${x2} ${y2} Z`;
}
