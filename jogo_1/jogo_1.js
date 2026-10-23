/**
 * jogo_1.js
 * Jogo de Frações com Pizza.
 * Recebe parâmetros de dificuldade/fase via localStorage (mg_params_jogo)
 * e, ao finalizar, calcula estrelas e grava o progresso se houver sessão ativa.
 */

// ─── Constantes ───────────────────────────────────────────────────────────────
const CHAVE_PARAMS = 'mg_params_jogo';
const CHAVE_USUARIO = 'mg_usuario';
const CHAVE_PROGRESSO = 'mg_progresso_fases';
const CHAVE_RECORDE_LIVRE = 'mg_recorde_livre';

// ─── Configurações por dificuldade ────────────────────────────────────────────
const CONFIG_DIFICULDADE = {
    facil: { rodadas: 3, tempoLimite: null, multiplicadorPontos: 1 },
    medio: { rodadas: 3, tempoLimite: null, multiplicadorPontos: 1.5 },
    experiente: { rodadas: 5, tempoLimite: 60, multiplicadorPontos: 2 },
};

// ─── Parâmetros recebidos ─────────────────────────────────────────────────────
const params = lerParams();

// ─── Estado do Jogo ───────────────────────────────────────────────────────────
let currentTarget = {};
let rodadaAtual = 0;
let totalRodadas = CONFIG_DIFICULDADE[params.dificuldade]?.rodadas || 3;
let pontuacao = 0;
let acertos = 0;
let jogoAtivo = true;
let filaFases = [];

// ─── Banco de Frações por dificuldade ─────────────────────────────────────────
const FRACOES = {
    facil: [
        { whole: 0, num: 1, den: 2, slicesPerPizza: 2, pizzaCount: 1 },
        { whole: 0, num: 1, den: 4, slicesPerPizza: 4, pizzaCount: 1 },
        { whole: 0, num: 3, den: 4, slicesPerPizza: 4, pizzaCount: 1 },
        { whole: 0, num: 2, den: 4, slicesPerPizza: 4, pizzaCount: 1 },
        { whole: 0, num: 1, den: 3, slicesPerPizza: 3, pizzaCount: 1 },
        { whole: 0, num: 2, den: 3, slicesPerPizza: 3, pizzaCount: 1 },
        { whole: 0, num: 1, den: 5, slicesPerPizza: 5, pizzaCount: 1 },
        { whole: 0, num: 2, den: 5, slicesPerPizza: 5, pizzaCount: 1 },
        { whole: 0, num: 3, den: 5, slicesPerPizza: 5, pizzaCount: 1 },
        { whole: 0, num: 4, den: 5, slicesPerPizza: 5, pizzaCount: 1 },
    ],
    medio: [
        { whole: 0, num: 2, den: 6, slicesPerPizza: 6, pizzaCount: 1 },
        { whole: 0, num: 3, den: 6, slicesPerPizza: 6, pizzaCount: 1 },
        { whole: 0, num: 4, den: 6, slicesPerPizza: 6, pizzaCount: 1 },
        { whole: 0, num: 5, den: 6, slicesPerPizza: 6, pizzaCount: 1 },
        { whole: 0, num: 3, den: 8, slicesPerPizza: 8, pizzaCount: 1 },
        { whole: 0, num: 5, den: 8, slicesPerPizza: 8, pizzaCount: 1 },
        { whole: 0, num: 7, den: 8, slicesPerPizza: 8, pizzaCount: 1 },
        { whole: 1, num: 1, den: 4, slicesPerPizza: 4, pizzaCount: 2 },
        { whole: 1, num: 1, den: 2, slicesPerPizza: 2, pizzaCount: 2 },
        { whole: 1, num: 1, den: 3, slicesPerPizza: 3, pizzaCount: 2 },
    ],
    experiente: [
        { whole: 0, num: 5, den: 12, slicesPerPizza: 12, pizzaCount: 1 },
        { whole: 0, num: 7, den: 12, slicesPerPizza: 12, pizzaCount: 1 },
        { whole: 0, num: 11, den: 12, slicesPerPizza: 12, pizzaCount: 1 },
        { whole: 1, num: 3, den: 8, slicesPerPizza: 8, pizzaCount: 2 },
        { whole: 1, num: 5, den: 8, slicesPerPizza: 8, pizzaCount: 2 },
        { whole: 1, num: 7, den: 8, slicesPerPizza: 8, pizzaCount: 2 },
        { whole: 2, num: 1, den: 6, slicesPerPizza: 6, pizzaCount: 3 },
        { whole: 2, num: 2, den: 6, slicesPerPizza: 6, pizzaCount: 3 },
        { whole: 2, num: 5, den: 6, slicesPerPizza: 6, pizzaCount: 3 },
        { whole: 2, num: 3, den: 4, slicesPerPizza: 4, pizzaCount: 3 },
    ],
};

// ─── Init ─────────────────────────────────────────────────────────────────────

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
        const exp = randomSort(FRACOES.experiente).slice(0, 3);
        return randomSort([...medias, ...exp]);
    }
    return randomSort(FRACOES.facil).slice(0, 3);
}

function iniciarJogo() {
    rodadaAtual = 0;
    pontuacao = 0;
    acertos = 0;
    jogoAtivo = true;
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

    const btnConfirmar = document.getElementById('btn-confirmar');
    if (btnConfirmar) btnConfirmar.disabled = true;

    const respostaCorreta = calcularFatiasSelecionadas();
    const fatiasSelecionadas = contarFatiasComidas();
    const acertou = (fatiasSelecionadas === respostaCorreta);

    const barra = document.getElementById('barra-progresso');

    if (acertou) {
        acertos++;
        pontuacao += Math.round(100 * (CONFIG_DIFICULDADE[params.dificuldade]?.multiplicadorPontos || 1));
        if (barra) {
            barra.classList.remove('erro');
            barra.classList.add('acerto');
        }
    } else {
        // Mostra a quantidade correta na pizza para aprendizado
        ajustarPizzaParaQuantidadeCerta(respostaCorreta);

        if (barra) {
            barra.classList.remove('acerto');
            barra.classList.add('erro');
        }
    }

    // Registra a conclusão da questão atual e avança a barra
    rodadaAtual++;
    atualizarBarraDeProgresso(rodadaAtual);

    // Aguarda o feedback visual antes de avançar para a próxima rodada
    setTimeout(() => {
        if (barra) {
            barra.classList.remove('erro');
            barra.classList.add('acerto');
        }
        if (btnConfirmar) btnConfirmar.disabled = false;
        proximaRodada();
    }, 1500);
}

/**
 * Ajusta visualmente a pizza para exibir exatamente a quantidade de fatias esperada.
 */
function ajustarPizzaParaQuantidadeCerta(quantidadeCerta) {
    const fatias = document.querySelectorAll('.slice');
    fatias.forEach((slice, index) => {
        if (index < quantidadeCerta) {
            slice.classList.add('eaten');
        } else {
            slice.classList.remove('eaten');
        }
    });
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

async function verificarSessaoEGravar(estrelas) {
    if (params.modo === 'trilha') {
        gravarProgressoTrilha(estrelas);
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
        if (elAcertos) elAcertos.textContent = `${acertos}/${totalRodadas}`;

        const comentario = document.getElementById('modal-comentario-valor');
        if (comentario) {
            if (estrelas === 3) comentario.textContent = 'Parabéns! Muito Bem! ';
            else if (estrelas === 2) comentario.textContent = 'Boa tentativa, você está evoluindo! ';
            else comentario.textContent = 'Você está no caminho para ser uma estrela! ';
        }

        modal.showModal();
    } else {
        alert(`Jogo finalizado!\nPontuação: ${pontuacao}\nEstrelas: ${estrelas}/3\nAcertos: ${acertos}/${totalRodadas}`);
        voltarPaginaAnterior();
    }
}

// ─── Gravação de Progresso (Trilha) ──────────────────────────────────────────

async function gravarProgressoTrilha(estrelas) {
    const ano = parseInt(params.ano, 10) || 6;
    const fase = parseInt(params.fase, 10) || 1;

    // Salva localmente (fallback para visitantes e cache)
    try {
        const progressoLocal = obterProgresso();
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
        salvarProgresso(progressoLocal);
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
                jogo_id: 1,
                pontuacao: pontuacao,
                estrelas: estrelas,
                tipo: 'trilha'
            })
        });
    } catch (e) {
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
    let p = { modo: 'livre', dificuldade: 'facil', ano: 6, fase: 1, jogoId: 1 };
    try {
        const raw = localStorage.getItem(CHAVE_PARAMS);
        if (raw) p = Object.assign(p, JSON.parse(raw));
    } catch {
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

// ─── Sabores Visuais de Pizza ────────────────────────────────────────────────
// ─── Sabores Visuais de Pizza (Estética Vibrante e Apetitosa) ──────────────────
const SABORES_PIZZA = [
    {
        id: 'pepperoni',
        nome: 'Pepperoni Especial',
        corQueijo: '#FFCA28',       // Dourado queijo derretido vibrante
        bordaQueijo: '#FFA000',
        ingredientes: ['pepperoni', 'oregano']
    },
    {
        id: 'queijo',
        nome: 'Mussarela Dourada',
        corQueijo: '#FFD54F',
        bordaQueijo: '#FFB300',
        ingredientes: ['oregano']
    },
    {
        id: 'champignon',
        nome: 'Champignon com Manjericão',
        corQueijo: '#FFE082',
        bordaQueijo: '#FFC107',
        ingredientes: ['champignon', 'manjericao', 'oregano']
    },
    {
        id: 'frango',
        nome: 'Frango com Catupiry e Milho',
        corQueijo: '#FFF59D',
        bordaQueijo: '#FBC02D',
        ingredientes: ['frango', 'milho', 'catupiry']
    },
    {
        id: 'portuguesa',
        nome: 'Portuguesa Tradicional',
        corQueijo: '#FFD54F',
        bordaQueijo: '#FFA000',
        ingredientes: ['presunto', 'ovo', 'azeitona', 'cebola', 'oregano']
    },
    {
        id: 'margherita',
        nome: 'Margherita Fresca',
        corQueijo: '#FFCA28',
        bordaQueijo: '#FF8F00',
        ingredientes: ['tomate', 'manjericao', 'oregano']
    }
];

let saborAtual = SABORES_PIZZA[0];

function sortearNovoSabor() {
    saborAtual = SABORES_PIZZA[Math.floor(Math.random() * SABORES_PIZZA.length)];
}

// ─── Renderização das Pizzas Dinâmicas e com Sabores ─────────────────────────

function renderPizzas() {
    sortearNovoSabor();

    const container = document.getElementById('pizzasArea');
    if (!container) return;

    container.innerHTML = '';

    // Define classe CSS para ajuste proporcional de tamanho (1, 2 ou 3 pizzas)
    container.className = `pizzas-area pizzas-qtd-${currentTarget.pizzaCount || 1}`;

    for (let p = 0; p < currentTarget.pizzaCount; p++) {
        const wrapper = document.createElement('div');
        wrapper.className = 'pizza-wrapper';

        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 260 260');
        svg.classList.add('pizza');

        // Definições de gradientes e efeitos no SVG
        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        defs.innerHTML = `
            <radialGradient id="gradCrust-${p}" cx="50%" cy="50%" r="50%">
                <stop offset="70%" stop-color="#E59833" />
                <stop offset="95%" stop-color="#C87214" />
                <stop offset="100%" stop-color="#9E4D05" />
            </radialGradient>
            <radialGradient id="gradSauce-${p}" cx="50%" cy="50%" r="50%">
                <stop offset="85%" stop-color="#D32F2F" />
                <stop offset="100%" stop-color="#B71C1C" />
            </radialGradient>
            <radialGradient id="gradPepperoni-${p}" cx="40%" cy="40%" r="50%">
                <stop offset="0%" stop-color="#E53935" />
                <stop offset="75%" stop-color="#C62828" />
                <stop offset="100%" stop-color="#8E0000" />
            </radialGradient>
        `;
        svg.appendChild(defs);

        // Prato externo com borda cartoon
        const plateOuter = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        plateOuter.setAttribute('cx', '130');
        plateOuter.setAttribute('cy', '130');
        plateOuter.setAttribute('r', '125');
        plateOuter.classList.add('pizza-plate');
        svg.appendChild(plateOuter);

        // Prato interno (rebaixo)
        const plateInner = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        plateInner.setAttribute('cx', '130');
        plateInner.setAttribute('cy', '130');
        plateInner.setAttribute('r', '118');
        plateInner.classList.add('pizza-plate-inner');
        svg.appendChild(plateInner);

        const totalSlices = currentTarget.slicesPerPizza;
        const angleStep = 360 / totalSlices;

        for (let i = 0; i < totalSlices; i++) {
            const startAngle = i * angleStep;
            const endAngle = (i + 1) * angleStep;

            const sliceGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            sliceGroup.classList.add('slice');
            sliceGroup.dataset.pizzaIndex = p;
            sliceGroup.dataset.sliceIndex = i;

            // 1. Borda / Massa fofinha dourada da pizza
            const crustSlice = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            crustSlice.setAttribute('d', getSlicePath(130, 130, 118, startAngle, endAngle));
            crustSlice.setAttribute('fill', `url(#gradCrust-${p})`);
            crustSlice.setAttribute('stroke', '#8A3B00');
            crustSlice.setAttribute('stroke-width', '2');
            crustSlice.setAttribute('stroke-linejoin', 'round');
            sliceGroup.appendChild(crustSlice);

            // 2. Camadinha de Molho de Tomate rústico (aparece levemente na borda)
            const sauceSlice = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            sauceSlice.setAttribute('d', getSlicePath(130, 130, 107, startAngle, endAngle));
            sauceSlice.setAttribute('fill', `url(#gradSauce-${p})`);
            sliceGroup.appendChild(sauceSlice);

            // 3. Queijo derretido cremoso e dourado
            const cheeseSlice = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            cheeseSlice.setAttribute('d', getSlicePath(130, 130, 102, startAngle, endAngle));
            cheeseSlice.setAttribute('fill', saborAtual.corQueijo);
            cheeseSlice.setAttribute('stroke', saborAtual.bordaQueijo);
            cheeseSlice.setAttribute('stroke-width', '2');
            cheeseSlice.setAttribute('stroke-linejoin', 'round');
            sliceGroup.appendChild(cheeseSlice);

            // Manchinhas de tostado no queijo para dar textura apetitosa
            renderizarTostadinhosQueijo(sliceGroup, startAngle, endAngle, totalSlices);

            // 4. Ingredientes de topo
            adicionarIngredientesAFatia(sliceGroup, startAngle, endAngle, totalSlices, saborAtual.ingredientes, p);

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

/**
 * Textura sutil de queijo levemente gratinado/tostado
 */
function renderizarTostadinhosQueijo(group, startAngle, endAngle, totalSlices) {
    if (totalSlices > 8) return; // Em muitas fatias fica poluído
    const midAngle = (startAngle + endAngle) / 2;
    const rad = ((midAngle - 2) - 90) * Math.PI / 180;
    const px = 130 + 64 * Math.cos(rad);
    const py = 130 + 64 * Math.sin(rad);

    const tost = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    tost.setAttribute('cx', px);
    tost.setAttribute('cy', py);
    tost.setAttribute('r', '4');
    tost.setAttribute('fill', '#F57C00');
    tost.setAttribute('opacity', '0.28');
    group.appendChild(tost);
}

/**
 * Adiciona graficamente os ingredientes à fatia com base no sabor sorteado.
 */
function adicionarIngredientesAFatia(group, startAngle, endAngle, totalSlices, ingredientes, pizzaIdx) {
    const midAngle = (startAngle + endAngle) / 2;

    ingredientes.forEach(ing => {
        switch (ing) {
            case 'pepperoni':
                renderizarPepperoni(group, midAngle, totalSlices, pizzaIdx);
                break;
            case 'champignon':
                renderizarChampignon(group, midAngle, totalSlices);
                break;
            case 'manjericao':
                renderizarManjericao(group, midAngle, totalSlices);
                break;
            case 'frango':
                renderizarFrango(group, midAngle, totalSlices);
                break;
            case 'milho':
                renderizarMilho(group, midAngle, totalSlices);
                break;
            case 'catupiry':
                renderizarCatupiry(group, startAngle, endAngle);
                break;
            case 'presunto':
                renderizarPresunto(group, midAngle, totalSlices);
                break;
            case 'ovo':
                renderizarOvo(group, midAngle, totalSlices);
                break;
            case 'azeitona':
                renderizarAzeitona(group, midAngle, totalSlices);
                break;
            case 'cebola':
                renderizarCebola(group, midAngle, totalSlices);
                break;
            case 'tomate':
                renderizarTomate(group, midAngle, totalSlices);
                break;
            case 'oregano':
                renderizarOregano(group, midAngle, totalSlices);
                break;
        }
    });
}

function renderizarPepperoni(group, midAngle, totalSlices, pizzaIdx) {
    const radiusDistances = totalSlices > 8 ? [68] : [52, 85];
    radiusDistances.forEach((rDist, idx) => {
        const angleOffset = (idx % 2 === 0 ? -4 : 4);
        const rad = ((midAngle + angleOffset) - 90) * Math.PI / 180;
        const px = 130 + rDist * Math.cos(rad);
        const py = 130 + rDist * Math.sin(rad);
        const raio = totalSlices > 8 ? 8.5 : 12;

        // Base com gradiente e borda tostada
        const pep = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        pep.setAttribute('cx', px);
        pep.setAttribute('cy', py);
        pep.setAttribute('r', raio);
        pep.setAttribute('fill', `url(#gradPepperoni-${pizzaIdx})`);
        pep.setAttribute('stroke', '#6A0000');
        pep.setAttribute('stroke-width', '2');
        group.appendChild(pep);

        // Pontinhos de tempero/óleo típicos de pepperoni
        const spot1 = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        spot1.setAttribute('cx', px - raio * 0.3);
        spot1.setAttribute('cy', py - raio * 0.3);
        spot1.setAttribute('r', '1.6');
        spot1.setAttribute('fill', '#FF8A80');
        group.appendChild(spot1);

        const spot2 = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        spot2.setAttribute('cx', px + raio * 0.25);
        spot2.setAttribute('cy', py + raio * 0.2);
        spot2.setAttribute('r', '1.4');
        spot2.setAttribute('fill', '#FFE082');
        group.appendChild(spot2);
    });
}

function renderizarChampignon(group, midAngle, totalSlices) {
    const distancias = totalSlices > 8 ? [70] : [54, 84];
    distancias.forEach((dist, idx) => {
        const offset = idx % 2 === 0 ? -4 : 4;
        const rad = ((midAngle + offset) - 90) * Math.PI / 180;
        const px = 130 + dist * Math.cos(rad);
        const py = 130 + dist * Math.sin(rad);

        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('transform', `rotate(${midAngle + idx * 25}, ${px}, ${py})`);

        // Chapéu do cogumelo
        const chapeu = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        chapeu.setAttribute('d', `M ${px - 8} ${py} A 8 7 0 0 1 ${px + 8} ${py} Z`);
        chapeu.setAttribute('fill', '#ECE0D1');
        chapeu.setAttribute('stroke', '#8D6E63');
        chapeu.setAttribute('stroke-width', '1.8');
        g.appendChild(chapeu);

        // Talo do cogumelo
        const talo = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        talo.setAttribute('d', `M ${px - 3.5} ${py} L ${px - 4.5} ${py + 7} L ${px + 4.5} ${py + 7} L ${px + 3.5} ${py} Z`);
        talo.setAttribute('fill', '#DFD3C3');
        talo.setAttribute('stroke', '#8D6E63');
        talo.setAttribute('stroke-width', '1.8');
        g.appendChild(talo);

        group.appendChild(g);
    });
}

function renderizarManjericao(group, midAngle, totalSlices) {
    const rad = (midAngle - 90) * Math.PI / 180;
    const px = 130 + 74 * Math.cos(rad);
    const py = 130 + 74 * Math.sin(rad);

    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    g.setAttribute('transform', `rotate(${midAngle + 10}, ${px}, ${py})`);

    // Folha viva e cartunesca com cor verde vibrante do site (#58cc02)
    const folha = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    folha.setAttribute('d', `M ${px} ${py - 10} C ${px + 9} ${py - 4} ${px + 9} ${py + 6} ${px} ${py + 10} C ${px - 9} ${py + 6} ${px - 9} ${py - 4} Z`);
    folha.setAttribute('fill', '#46B400');
    folha.setAttribute('stroke', '#2E7D00');
    folha.setAttribute('stroke-width', '1.8');
    g.appendChild(folha);

    // Nervura central da folha
    const nervura = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    nervura.setAttribute('x1', px);
    nervura.setAttribute('y1', py - 7);
    nervura.setAttribute('x2', px);
    nervura.setAttribute('y2', py + 7);
    nervura.setAttribute('stroke', '#78D800');
    nervura.setAttribute('stroke-width', '1.4');
    g.appendChild(nervura);

    group.appendChild(g);
}

function renderizarFrango(group, midAngle, totalSlices) {
    const distancias = totalSlices > 8 ? [68] : [52, 82];
    distancias.forEach((dist, idx) => {
        const offset = idx % 2 === 0 ? -5 : 5;
        const rad = ((midAngle + offset) - 90) * Math.PI / 180;
        const px = 130 + dist * Math.cos(rad);
        const py = 130 + dist * Math.sin(rad);

        const frango = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        // Pedaço rústico/arredondado de frango desfiado douradinho
        frango.setAttribute('d', `M ${px - 6} ${py - 4} Q ${px} ${py - 6} ${px + 7} ${py - 3} Q ${px + 8} ${py + 4} ${px + 2} ${py + 5} Q ${px - 5} ${py + 4} Z`);
        frango.setAttribute('fill', '#FFA726');
        frango.setAttribute('stroke', '#E65100');
        frango.setAttribute('stroke-width', '1.6');
        frango.setAttribute('transform', `rotate(${midAngle + idx * 35}, ${px}, ${py})`);
        group.appendChild(frango);
    });
}

function renderizarMilho(group, midAngle, totalSlices) {
    // Grupo de grãozinhos de milho dourados
    const offsets = [
        { dist: 40, ang: -4 },
        { dist: 48, ang: 5 }
    ];

    offsets.forEach(pos => {
        const rad = ((midAngle + pos.ang) - 90) * Math.PI / 180;
        const px = 130 + pos.dist * Math.cos(rad);
        const py = 130 + pos.dist * Math.sin(rad);

        const milho = document.createElementNS('http://www.w3.org/2000/svg', 'ellipse');
        milho.setAttribute('cx', px);
        milho.setAttribute('cy', py);
        milho.setAttribute('rx', '4.5');
        milho.setAttribute('ry', '3.5');
        milho.setAttribute('fill', '#FFEE58');
        milho.setAttribute('stroke', '#F57F17');
        milho.setAttribute('stroke-width', '1.4');
        milho.setAttribute('transform', `rotate(${midAngle}, ${px}, ${py})`);
        group.appendChild(milho);
    });
}

function renderizarCatupiry(group, startAngle, endAngle) {
    const midAngle = (startAngle + endAngle) / 2;
    const rad = (midAngle - 90) * Math.PI / 180;
    const px = 130 + 92 * Math.cos(rad);
    const py = 130 + 92 * Math.sin(rad);

    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    g.setAttribute('transform', `rotate(${midAngle}, ${px}, ${py})`);

    // Espiral cremosa volumosa e brilhante
    const c1 = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    c1.setAttribute('cx', px);
    c1.setAttribute('cy', py);
    c1.setAttribute('r', '8.5');
    c1.setAttribute('fill', '#FFFFFF');
    c1.setAttribute('stroke', '#E0E0E0');
    c1.setAttribute('stroke-width', '2');
    g.appendChild(c1);

    const swirl = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    swirl.setAttribute('d', `M ${px - 4} ${py} Q ${px} ${py - 4} ${px + 4} ${py} Q ${px} ${py + 4} ${px - 1} ${py}`);
    swirl.setAttribute('fill', 'none');
    swirl.setAttribute('stroke', '#FFF9C4');
    swirl.setAttribute('stroke-width', '2.5');
    swirl.setAttribute('stroke-linecap', 'round');
    g.appendChild(swirl);

    group.appendChild(g);
}

function renderizarPresunto(group, midAngle, totalSlices) {
    const rad = (midAngle - 90) * Math.PI / 180;
    const px = 130 + 58 * Math.cos(rad);
    const py = 130 + 58 * Math.sin(rad);

    const presunto = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
    presunto.setAttribute('x', px - 8);
    presunto.setAttribute('y', py - 6);
    presunto.setAttribute('width', '16');
    presunto.setAttribute('height', '12');
    presunto.setAttribute('rx', '3');
    presunto.setAttribute('fill', '#FF8A80');
    presunto.setAttribute('stroke', '#C2185B');
    presunto.setAttribute('stroke-width', '1.8');
    presunto.setAttribute('transform', `rotate(${midAngle + 12}, ${px}, ${py})`);
    group.appendChild(presunto);
}

function renderizarOvo(group, midAngle, totalSlices) {
    const rad = ((midAngle - 3) - 90) * Math.PI / 180;
    const px = 130 + 80 * Math.cos(rad);
    const py = 130 + 80 * Math.sin(rad);

    // Clara macia
    const clara = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    clara.setAttribute('cx', px);
    clara.setAttribute('cy', py);
    clara.setAttribute('r', '10');
    clara.setAttribute('fill', '#FFFFFF');
    clara.setAttribute('stroke', '#BDBDBD');
    clara.setAttribute('stroke-width', '1.8');
    group.appendChild(clara);

    // Gema dourada viva
    const gema = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    gema.setAttribute('cx', px + 1);
    gema.setAttribute('cy', py);
    gema.setAttribute('r', '5.5');
    gema.setAttribute('fill', '#FFB300');
    gema.setAttribute('stroke', '#F57C00');
    gema.setAttribute('stroke-width', '1.4');
    group.appendChild(gema);
}

function renderizarAzeitona(group, midAngle, totalSlices) {
    const rad = ((midAngle + 6) - 90) * Math.PI / 180;
    const px = 130 + 44 * Math.cos(rad);
    const py = 130 + 44 * Math.sin(rad);

    // Rodela de azeitona preta com furo central
    const corpo = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    corpo.setAttribute('cx', px);
    corpo.setAttribute('cy', py);
    corpo.setAttribute('r', '6');
    corpo.setAttribute('fill', '#212121');
    corpo.setAttribute('stroke', '#000000');
    corpo.setAttribute('stroke-width', '1.8');
    group.appendChild(corpo);

    const furo = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    furo.setAttribute('cx', px);
    furo.setAttribute('cy', py);
    furo.setAttribute('r', '2.5');
    furo.setAttribute('fill', '#FFCA28'); // Mostra o queijo por dentro do furo
    group.appendChild(furo);
}

function renderizarCebola(group, midAngle, totalSlices) {
    const rad = (midAngle - 90) * Math.PI / 180;
    const px = 130 + 66 * Math.cos(rad);
    const py = 130 + 66 * Math.sin(rad);

    // Arco de anel de cebola cartunesco
    const cebola = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    cebola.setAttribute('d', `M ${px - 8} ${py + 2} A 9 9 0 0 1 ${px + 8} ${py + 2}`);
    cebola.setAttribute('fill', 'none');
    cebola.setAttribute('stroke', '#FFFFFF');
    cebola.setAttribute('stroke-width', '3');
    cebola.setAttribute('stroke-linecap', 'round');
    cebola.setAttribute('transform', `rotate(${midAngle + 20}, ${px}, ${py})`);
    group.appendChild(cebola);
}

function renderizarTomate(group, midAngle, totalSlices) {
    const rad = ((midAngle - 3) - 90) * Math.PI / 180;
    const px = 130 + 62 * Math.cos(rad);
    const py = 130 + 62 * Math.sin(rad);

    const tomate = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    tomate.setAttribute('cx', px);
    tomate.setAttribute('cy', py);
    tomate.setAttribute('r', '9.5');
    tomate.setAttribute('fill', '#F44336');
    tomate.setAttribute('stroke', '#B71C1C');
    tomate.setAttribute('stroke-width', '2');
    group.appendChild(tomate);

    // Sementinhas amarelas no tomate
    const sem1 = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    sem1.setAttribute('cx', px - 2.5);
    sem1.setAttribute('cy', py - 1.5);
    sem1.setAttribute('r', '1.2');
    sem1.setAttribute('fill', '#FFF59D');
    group.appendChild(sem1);

    const sem2 = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    sem2.setAttribute('cx', px + 2.5);
    sem2.setAttribute('cy', py + 1.5);
    sem2.setAttribute('r', '1.2');
    sem2.setAttribute('fill', '#FFF59D');
    group.appendChild(sem2);
}

function renderizarOregano(group, midAngle, totalSlices) {
    const qtd = totalSlices > 8 ? 4 : 7;
    for (let o = 0; o < qtd; o++) {
        const offset = (o - 3) * 5;
        const dist = 38 + o * 10;
        const rad = ((midAngle + offset) - 90) * Math.PI / 180;
        const px = 130 + dist * Math.cos(rad);
        const py = 130 + dist * Math.sin(rad);

        // Pontinhos nítidos de orégano verde fresco
        const pingo = document.createElementNS('http://www.w3.org/2000/svg', 'ellipse');
        pingo.setAttribute('cx', px);
        pingo.setAttribute('cy', py);
        pingo.setAttribute('rx', '1.8');
        pingo.setAttribute('ry', '1.2');
        pingo.setAttribute('fill', '#33691E');
        pingo.setAttribute('transform', `rotate(${o * 25}, ${px}, ${py})`);
        group.appendChild(pingo);
    }
}

function getSlicePath(cx, cy, r, startAngle, endAngle) {
    const startRad = (startAngle - 90) * Math.PI / 180;
    const endRad = (endAngle - 90) * Math.PI / 180;

    const x1 = cx + r * Math.cos(startRad);
    const y1 = cy + r * Math.sin(startRad);
    const x2 = cx + r * Math.cos(endRad);
    const y2 = cy + r * Math.sin(endRad);

    const largeArc = (endAngle - startAngle) > 180 ? 1 : 0;
    return `M ${cx} ${cy} L ${x1} ${y1} A ${r} ${r} 0 ${largeArc} 1 ${x2} ${y2} Z`;
}
