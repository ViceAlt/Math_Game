/**
 * jogo_5.js
 * A Balança de Equilíbrio — Manipulação Livre de Pesos
 * Conceito: arrastar um peso para o outro lado muda seu sinal (passa com sinal oposto).
 * A gangorra reage com física suave (mola/bouncing).
 */

// ─── Constantes ──────────────────────────────────────────────────────────────
const CHAVE_PARAMS        = 'mg_params_jogo';
const CHAVE_PROGRESSO     = 'mg_progresso_fases';
const CHAVE_RECORDE_LIVRE = 'mg_recorde_livre';
const CHAVE_ULTIMO_JOGO   = 'mg_ultimo_jogo';

const CONFIG_DIFICULDADE = {
    facil:      { multiplicador: 1.0, rodadasPadrao: 3, nome: 'Fácil' },
    medio:      { multiplicador: 1.5, rodadasPadrao: 3, nome: 'Médio' },
    experiente: { multiplicador: 2.0, rodadasPadrao: 5, nome: 'Experiente' }
};

// ─── Estado Global ───────────────────────────────────────────────────────────
const params = lerParams();

let bancoEquacoes = [];
let equacoesRodadas = [];
let rodadaAtual = 0;
let totalRodadas = 3;
let pontuacao = 0;
let acertosTotais = 0;
let jogoAtivo = true;

// Estado da rodada
let equacaoAtual = null;
let estadoAtual = null;
let historicoAcoes = [];
let respostaSelecionada = null;
let respostaCorretaAtual = null;

// Métricas para estrelas
let passosRealizadosTotal = 0;
let passosEsperadosTotal = 0;
let acoesDesequilibrantesTotal = 0;

// Física da gangorra
let anguloAtual = 0;
let velocidadeAngular = 0;
let anguloAlvo = 0;
let animandoFisica = false;

// Drag & Drop
let dragItem = null;
let dragData = null;
let dragGhost = null;
let dragStartPos = { x: 0, y: 0 };
let arrastandoAtivo = false;

// ─── Inicialização ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    aplicarPreferenciasVisuais();
    await carregarBancoEquacoes();
    inicializarDropZones();
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

function lerParams() {
    let p = { modo: 'livre', dificuldade: 'facil', ano: 7, fase: 7, jogoId: 5, fase_id: 0 };
    try {
        const raw = localStorage.getItem(CHAVE_PARAMS);
        if (raw) p = Object.assign(p, JSON.parse(raw));
    } catch { /* ignora */ }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('ano')) p.ano = parseInt(urlParams.get('ano'), 10);
    if (urlParams.has('fase')) {
        p.fase = parseInt(urlParams.get('fase'), 10);
        p.modo = 'trilha';
    }
    if (urlParams.has('fase_id')) p.fase_id = parseInt(urlParams.get('fase_id'), 10);
    if (urlParams.has('dificuldade')) p.dificuldade = urlParams.get('dificuldade');
    if (urlParams.has('modo')) p.modo = urlParams.get('modo');

    return p;
}

// ─── Banco de Equações ───────────────────────────────────────────────────────
async function carregarBancoEquacoes() {
    try {
        const resp = await fetch('equacoes.json');
        bancoEquacoes = await resp.json();
    } catch (e) {
        console.error('Falha ao carregar equacoes.json:', e);
        bancoEquacoes = [];
    }
}

function iniciarJogo() {
    rodadaAtual = 0;
    pontuacao = 0;
    acertosTotais = 0;
    passosRealizadosTotal = 0;
    passosEsperadosTotal = 0;
    acoesDesequilibrantesTotal = 0;
    jogoAtivo = true;

    selecionarEquacoesSessao();
    totalRodadas = equacoesRodadas.length || (CONFIG_DIFICULDADE[params.dificuldade]?.rodadasPadrao || 3);

    const badgeNivel = document.getElementById('info-nivel-badge');
    if (badgeNivel) {
        badgeNivel.textContent = params.modo === 'trilha'
            ? `Fase ${params.fase} • 7° Ano`
            : (CONFIG_DIFICULDADE[params.dificuldade]?.nome || 'Fácil');
    }

    carregarNovaRodada();
}

function selecionarEquacoesSessao() {
    let candidatas = [];

    if (params.modo === 'trilha' && params.fase) {
        candidatas = bancoEquacoes.filter(eq => eq.fases && eq.fases.includes(params.fase));
        if (candidatas.length === 0) {
            const nivelFase = [7, 9].includes(params.fase) ? 'facil' : ([13, 15].includes(params.fase) ? 'medio' : 'experiente');
            candidatas = bancoEquacoes.filter(eq => eq.nivel === nivelFase);
        }
    } else {
        const nivel = params.dificuldade || 'facil';
        candidatas = bancoEquacoes.filter(eq => eq.nivel === nivel);
    }

    candidatas = [...candidatas].sort(() => Math.random() - 0.5);
    const qtd = params.modo === 'trilha' ? 3 : (CONFIG_DIFICULDADE[params.dificuldade]?.rodadasPadrao || 3);
    equacoesRodadas = candidatas.slice(0, qtd);
}

// ─── Rodada ──────────────────────────────────────────────────────────────────
function carregarNovaRodada() {
    if (rodadaAtual >= totalRodadas) {
        finalizarJogo();
        return;
    }

    equacaoAtual = equacoesRodadas[rodadaAtual];
    if (!equacaoAtual) {
        finalizarJogo();
        return;
    }

    passosEsperadosTotal += (equacaoAtual.passos_minimos || 1);
    historicoAcoes = [];

    // Cada item é rastreado individualmente com sua origem e tipo
    // items: array de { id, tipo: 'x'|'bloco'|'balao', valor, ladoOrigem, ladoAtual }
    estadoAtual = {
        items: [],
        x_real: equacaoAtual.solucao
    };

    let itemId = 0;

    // Montar itens do lado esquerdo
    for (let i = 0; i < equacaoAtual.esq.x; i++) {
        estadoAtual.items.push({
            id: itemId++, tipo: 'x', valor: 1,
            ladoOrigem: 'esq', ladoAtual: 'esq'
        });
    }
    if (equacaoAtual.esq.blocos > 0) {
        estadoAtual.items.push({
            id: itemId++, tipo: 'bloco', valor: equacaoAtual.esq.blocos,
            ladoOrigem: 'esq', ladoAtual: 'esq'
        });
    }
    if (equacaoAtual.esq.baloes > 0) {
        estadoAtual.items.push({
            id: itemId++, tipo: 'balao', valor: equacaoAtual.esq.baloes,
            ladoOrigem: 'esq', ladoAtual: 'esq'
        });
    }

    // Montar itens do lado direito
    for (let i = 0; i < equacaoAtual.dir.x; i++) {
        estadoAtual.items.push({
            id: itemId++, tipo: 'x', valor: 1,
            ladoOrigem: 'dir', ladoAtual: 'dir'
        });
    }
    if (equacaoAtual.dir.blocos > 0) {
        estadoAtual.items.push({
            id: itemId++, tipo: 'bloco', valor: equacaoAtual.dir.blocos,
            ladoOrigem: 'dir', ladoAtual: 'dir'
        });
    }
    if (equacaoAtual.dir.baloes > 0) {
        estadoAtual.items.push({
            id: itemId++, tipo: 'balao', valor: equacaoAtual.dir.baloes,
            ladoOrigem: 'dir', ladoAtual: 'dir'
        });
    }

    atualizarBarraDeProgresso();

    const areaEscolha = document.getElementById('area-resposta-escolha');
    if (areaEscolha) areaEscolha.style.display = 'none';

    const containerConfirmar = document.getElementById('container-btn-confirmar');
    if (containerConfirmar) containerConfirmar.style.display = 'none';

    const painelDivisao = document.getElementById('painel-divisao-balanca');
    if (painelDivisao) painelDivisao.style.display = 'none';

    respostaSelecionada = null;
    respostaCorretaAtual = null;

    const btnDesfazer = document.getElementById('btn-desfazer');
    if (btnDesfazer) btnDesfazer.disabled = true;

    ocultarDica();
    renderizarBalanca();
    atualizarPainelLateral();
    iniciarFisicaGangorra();
}

function atualizarBarraDeProgresso() {
    const barra = document.getElementById('barra-progresso');
    const infoRodada = document.getElementById('info-rodada');
    const pct = (rodadaAtual / totalRodadas) * 100;

    if (barra) barra.style.width = `${pct}%`;
    if (infoRodada) infoRodada.textContent = `${rodadaAtual + 1} / ${totalRodadas}`;
}

// ─── Cálculo de Pesos por Lado ───────────────────────────────────────────────
function calcularPesosLado(lado) {
    const x = estadoAtual.x_real;
    let peso = 0;

    estadoAtual.items.forEach(item => {
        // Se este item está sendo arrastado (no ar), ele não pesa na balança!
        if (dragData && item.id === dragData.id) return;
        if (item.ladoAtual !== lado) return;

        const noLadoOrigem = (item.ladoAtual === item.ladoOrigem);

        if (item.tipo === 'x') {
            // x sempre contribui positivamente no lado de origem, negativo no oposto
            peso += noLadoOrigem ? x : -x;
        } else if (item.tipo === 'bloco') {
            peso += noLadoOrigem ? item.valor : -item.valor;
        } else if (item.tipo === 'balao') {
            // Balão é naturalmente negativo (subtrai) no lado de origem
            peso += noLadoOrigem ? -item.valor : item.valor;
        }
    });

    return peso;
}

function calcularPesos() {
    const pesoEsq = calcularPesosLado('esq');
    const pesoDir = calcularPesosLado('dir');
    return { pesoEsq, pesoDir, delta: pesoDir - pesoEsq };
}

// ─── Renderização da Balança ─────────────────────────────────────────────────
function renderizarBalanca() {
    const containerEsq = document.getElementById('itens-esquerdo');
    const containerDir = document.getElementById('itens-direito');

    if (containerEsq) renderizarPrato(containerEsq, 'esq');
    if (containerDir) renderizarPrato(containerDir, 'dir');
}

function renderizarPrato(container, lado) {
    container.innerHTML = '';

    const itensNoLado = estadoAtual.items.filter(i => i.ladoAtual === lado);

    const blocosNumericos = itensNoLado.filter(i => i.tipo === 'bloco' || i.tipo === 'balao');
    const itensX = itensNoLado.filter(i => i.tipo === 'x');

    // Detectar X com sinais opostos no mesmo lado (podem ser subtraídos)
    const xPositivos = itensX.filter(i => i.ladoAtual === i.ladoOrigem);
    const xNegativos = itensX.filter(i => i.ladoAtual !== i.ladoOrigem);
    const temXOpostos = xPositivos.length > 0 && xNegativos.length > 0;

    const podeSubtrairBlocos = blocosNumericos.length >= 2;

    // Itens combináveis: blocos numéricos + (se houver x com sinais opostos, x também)
    const podeSubtrairAlgo = podeSubtrairBlocos || temXOpostos;

    if (podeSubtrairAlgo) {
        const wrapper = document.createElement('div');
        wrapper.className = 'subtrair-wrapper ativo';

        const btnSubtrair = document.createElement('button');
        btnSubtrair.type = 'button';
        btnSubtrair.className = 'btn-subtrair-lado';
        btnSubtrair.textContent = 'Somar';
        btnSubtrair.addEventListener('click', (e) => {
            e.stopPropagation();
            executarSubtracaoLado(lado);
        });
        wrapper.appendChild(btnSubtrair);

        // X opostos entram no wrapper de subtração
        if (temXOpostos) {
            itensX.forEach(item => {
                const el = criarElementoPeso(item, lado);
                wrapper.appendChild(el);
            });
        } else {
            // X sem opostos ficam fora do wrapper
            itensX.forEach(item => {
                const el = criarElementoPeso(item, lado);
                container.appendChild(el);
            });
        }

        blocosNumericos.forEach(item => {
            const el = criarElementoPeso(item, lado);
            wrapper.appendChild(el);
        });

        container.appendChild(wrapper);
    } else {
        itensX.forEach(item => {
            const el = criarElementoPeso(item, lado);
            container.appendChild(el);
        });
        blocosNumericos.forEach(item => {
            const el = criarElementoPeso(item, lado);
            container.appendChild(el);
        });
    }
}

function criarElementoPeso(item, lado) {
    const noLadoOrigem = (item.ladoAtual === item.ladoOrigem);
    const el = document.createElement('div');

    el.dataset.itemId = item.id;
    el.draggable = true;
    el.setAttribute('role', 'button');
    el.setAttribute('tabindex', '0');

    if (item.tipo === 'x') {
        el.className = `peso-item tipo-incognita ${!noLadoOrigem ? 'peso-negativo' : ''}`;
        el.textContent = noLadoOrigem ? 'x' : '−x';
        el.title = noLadoOrigem ? 'Incógnita x' : '−x (oposto)';
        el.setAttribute('aria-label',
            `${noLadoOrigem ? 'Incógnita x' : 'Negativo de x'} no lado ${lado === 'esq' ? 'esquerdo' : 'direito'}. Arraste para mover.`
        );
    } else if (item.tipo === 'bloco') {
        el.className = `peso-item tipo-bloco ${!noLadoOrigem ? 'peso-negativo' : ''}`;
        const displayVal = noLadoOrigem ? item.valor : -item.valor;
        const absVal = item.valor;

        // Tamanho proporcional
        const dimW = Math.min(76, Math.max(42, 38 + absVal * 2.2));
        const dimH = Math.min(68, Math.max(38, 36 + absVal * 1.8));
        el.style.width = `${dimW}px`;
        el.style.height = `${dimH}px`;

        el.innerHTML = `
            <span class="peso-num">${displayVal > 0 ? absVal : '−' + absVal}</span>
            <span class="peso-unidade">kg</span>
        `;
        el.title = `${displayVal > 0 ? '+' : '−'}${absVal} kg`;
        el.setAttribute('aria-label',
            `Peso de ${displayVal > 0 ? '' : 'menos '}${absVal} kg no lado ${lado === 'esq' ? 'esquerdo' : 'direito'}. Arraste para mover.`
        );
    } else if (item.tipo === 'balao') {
        // Agora possui forma de bloco quadrado idêntico aos outros pesos da mesma cor
        el.className = `peso-item tipo-bloco tipo-balao ${noLadoOrigem ? 'peso-negativo' : ''}`;
        const absVal = item.valor;
        const displayVal = noLadoOrigem ? -absVal : absVal;

        // Tamanho proporcional idêntico aos outros blocos
        const dimW = Math.min(76, Math.max(42, 38 + absVal * 2.2));
        const dimH = Math.min(68, Math.max(38, 36 + absVal * 1.8));
        el.style.width = `${dimW}px`;
        el.style.height = `${dimH}px`;

        el.innerHTML = `
            <span class="peso-num">${displayVal > 0 ? absVal : '−' + absVal}</span>
            <span class="peso-unidade">kg</span>
        `;
        el.title = `${displayVal > 0 ? '+' : '−'}${absVal} kg`;
        el.setAttribute('aria-label',
            `Peso de ${displayVal > 0 ? '' : 'menos '}${absVal} kg no lado ${lado === 'esq' ? 'esquerdo' : 'direito'}. Arraste para mover.`
        );
    }

    // Drag events
    el.addEventListener('dragstart', (e) => handleDragStart(e, item));
    el.addEventListener('dragend', handleDragEnd);

    // Touch support
    el.addEventListener('touchstart', (e) => handleTouchStart(e, item), { passive: false });
    el.addEventListener('touchmove', handleTouchMove, { passive: false });
    el.addEventListener('touchend', handleTouchEnd, { passive: false });

    // Clique para mover alternativo (acessibilidade e mobile ágil)
    el.addEventListener('click', () => {
        if (arrastandoAtivo || !jogoAtivo) return;
        const outroLado = item.ladoAtual === 'esq' ? 'dir' : 'esq';
        moverPesoParaLado(item.id, outroLado);
    });

    return el;
}

// ─── Drop Zones da Arena e Prancha ──────────────────────────────────────────
function inicializarDropZones() {
    const zonas = [
        document.getElementById('zona-drop-esq'),
        document.getElementById('zona-drop-dir'),
        document.getElementById('prato-esquerdo'),
        document.getElementById('prato-direito')
    ];

    zonas.forEach(zona => {
        if (!zona) return;
        zona.addEventListener('dragover', handleDragOver);
        zona.addEventListener('drop', handleDrop);
        zona.addEventListener('dragenter', handleDragEnter);
        zona.addEventListener('dragleave', handleDragLeave);
    });
}

function destacarLado(lado, ativar) {
    if (!lado) {
        document.querySelectorAll('.drop-hover').forEach(el => el.classList.remove('drop-hover'));
        return;
    }
    document.querySelectorAll(`[data-lado="${lado}"]`).forEach(el => {
        if (ativar) el.classList.add('drop-hover');
        else el.classList.remove('drop-hover');
    });
}

// ─── Drag & Drop ─────────────────────────────────────────────────────────────
function handleDragStart(e, item) {
    if (!jogoAtivo) return;
    arrastandoAtivo = true;
    dragData = item;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', item.id);

    // Estilizar o elemento sendo arrastado
    requestAnimationFrame(() => {
        e.target.classList.add('arrastando');
    });

    // Impulso de saída na gangorra (ao sair o peso, o prato sobe)
    impulsionarGangorra(item.ladoAtual, 'saida');
}

function handleDragEnd(e) {
    e.target.classList.remove('arrastando');
    destacarLado(null, false);

    // Se o arraste foi cancelado sem drop num alvo válido, o peso retorna e equilibra suave
    if (dragData) {
        dragData = null;
        calcularAnguloAlvo();
        if (!animandoFisica) {
            animandoFisica = true;
            requestAnimationFrame(loopFisica);
        }
    }

    setTimeout(() => { arrastandoAtivo = false; }, 120);
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleDragEnter(e) {
    e.preventDefault();
    const lado = e.currentTarget.dataset.lado;
    if (lado && dragData && lado !== dragData.ladoAtual) {
        destacarLado(lado, true);
    }
}

function handleDragLeave(e) {
    const rect = e.currentTarget.getBoundingClientRect();
    const x = e.clientX;
    const y = e.clientY;
    if (x <= rect.left || x >= rect.right || y <= rect.top || y >= rect.bottom) {
        destacarLado(e.currentTarget.dataset.lado, false);
    }
}

function handleDrop(e) {
    e.preventDefault();
    destacarLado(null, false);

    if (!dragData || !jogoAtivo) return;

    const zona = e.currentTarget;
    const ladoDestino = zona.dataset.lado;
    if (!ladoDestino || ladoDestino === dragData.ladoAtual) {
        dragData = null;
        calcularAnguloAlvo();
        return;
    }

    const id = dragData.id;
    dragData = null;
    moverPesoParaLado(id, ladoDestino);
}

// ─── Touch Support para Mobile ───────────────────────────────────────────────
function handleTouchStart(e, item) {
    if (!jogoAtivo) return;
    e.preventDefault();
    arrastandoAtivo = false;

    const touch = e.touches[0];
    dragData = item;
    dragStartPos = { x: touch.clientX, y: touch.clientY };

    // Criar ghost visual
    dragGhost = e.target.cloneNode(true);
    dragGhost.classList.add('drag-ghost');
    dragGhost.style.left = `${touch.clientX}px`;
    dragGhost.style.top = `${touch.clientY}px`;
    document.body.appendChild(dragGhost);

    e.target.classList.add('arrastando');
    impulsionarGangorra(item.ladoAtual, 'saida');
}

function handleTouchMove(e) {
    if (!dragData || !dragGhost) return;
    e.preventDefault();
    arrastandoAtivo = true;

    const touch = e.touches[0];
    dragGhost.style.left = `${touch.clientX}px`;
    dragGhost.style.top = `${touch.clientY}px`;

    // Highlight drop zones sob o toque
    const elemAbaixo = document.elementFromPoint(touch.clientX, touch.clientY);
    destacarLado(null, false);

    if (elemAbaixo) {
        const zona = elemAbaixo.closest('[data-lado]');
        if (zona && zona.dataset.lado !== dragData.ladoAtual) {
            destacarLado(zona.dataset.lado, true);
        }
    }
}

function handleTouchEnd(e) {
    if (!dragData) return;
    e.preventDefault();

    // Remover ghost
    if (dragGhost) {
        dragGhost.remove();
        dragGhost = null;
    }

    destacarLado(null, false);
    document.querySelectorAll('.arrastando').forEach(el => el.classList.remove('arrastando'));

    const touch = e.changedTouches[0];
    const elemAbaixo = document.elementFromPoint(touch.clientX, touch.clientY);

    let moveu = false;
    if (elemAbaixo) {
        const zona = elemAbaixo.closest('[data-lado]');
        if (zona && zona.dataset.lado !== dragData.ladoAtual) {
            moverPesoParaLado(dragData.id, zona.dataset.lado);
            moveu = true;
        }
    }

    if (!moveu) {
        // Se foi toque simples (tap) sem arrastar, move diretamente
        if (!arrastandoAtivo) {
            const outroLado = dragData.ladoAtual === 'esq' ? 'dir' : 'esq';
            moverPesoParaLado(dragData.id, outroLado);
        } else {
            dragData = null;
            calcularAnguloAlvo();
            if (!animandoFisica) {
                animandoFisica = true;
                requestAnimationFrame(loopFisica);
            }
        }
    } else {
        dragData = null;
    }

    setTimeout(() => { arrastandoAtivo = false; }, 120);
}

// ─── Mover Peso entre Lados ──────────────────────────────────────────────────
function moverPesoParaLado(itemId, novoLado) {
    const item = estadoAtual.items.find(i => i.id === itemId);
    if (!item || item.ladoAtual === novoLado) return;

    // Salvar estado para desfazer
    salvarEstadoHistorico();
    passosRealizadosTotal++;

    const ladoAnterior = item.ladoAtual;
    item.ladoAtual = novoLado;

    // Impulso de entrada na gangorra
    impulsionarGangorra(novoLado, 'entrada');

    // Feedback visual
    renderizarBalanca();
    atualizarPainelLateral();

    // Verificar se o aluno desequilibrou (ação pedagógica incorreta)
    const { delta } = calcularPesos();
    if (Math.abs(delta) > 0.01) {
        // Mover sem manter equilíbrio pode contar como ação desequilibrante
        // Mas como é livre, deixamos o aluno explorar sem penalizar aqui
    }

    checarIsolamentoIncognita();
}

// ─── Física da Gangorra (Simulação de Mola com Oscilação Suave) ─────────────
const MOLA_RIGIDEZ = 0.08;        // mola suave e natural
const MOLA_AMORTECIMENTO = 0.88;  // balanço agradável que oscila de 2 a 3 vezes
const MOLA_THRESHOLD = 0.02;      // precisão para estabilizar
const MAX_ANGULO = 14;            // graus máximos de inclinação

function iniciarFisicaGangorra() {
    calcularAnguloAlvo();
    anguloAtual = anguloAlvo;
    velocidadeAngular = 0;
    aplicarAnguloGangorra(anguloAtual);

    if (!animandoFisica) {
        animandoFisica = true;
        requestAnimationFrame(loopFisica);
    }
}

function calcularAnguloAlvo() {
    const { delta } = calcularPesos();
    anguloAlvo = Math.max(-MAX_ANGULO, Math.min(MAX_ANGULO, delta * 3.5));
}

function impulsionarGangorra(lado, tipo) {
    // Clockwise (+): lado esquerdo sobe, lado direito desce
    // Counter-clockwise (-): lado esquerdo desce, lado direito sobe
    // Saída: o lado que perde peso sobe
    // Entrada: o lado que ganha peso desce
    const forca = 6.0;
    let impulso = 0;
    if (tipo === 'saida') {
        impulso = (lado === 'esq') ? forca : -forca;
    } else {
        impulso = (lado === 'esq') ? -forca : forca;
    }

    velocidadeAngular += impulso;
    calcularAnguloAlvo();

    if (!animandoFisica) {
        animandoFisica = true;
        requestAnimationFrame(loopFisica);
    }
}

function loopFisica(timestamp) {
    // Força da mola: puxa para o ângulo alvo
    const forcaMola = (anguloAlvo - anguloAtual) * MOLA_RIGIDEZ;

    velocidadeAngular += forcaMola;
    velocidadeAngular *= MOLA_AMORTECIMENTO;
    anguloAtual += velocidadeAngular;

    // Clamp nos limites
    if (anguloAtual > MAX_ANGULO + 5) {
        anguloAtual = MAX_ANGULO + 5;
        velocidadeAngular *= -0.3;
    }
    if (anguloAtual < -(MAX_ANGULO + 5)) {
        anguloAtual = -(MAX_ANGULO + 5);
        velocidadeAngular *= -0.3;
    }

    aplicarAnguloGangorra(anguloAtual);

    // Continuar animando se não convergiu
    if (Math.abs(velocidadeAngular) > MOLA_THRESHOLD || Math.abs(anguloAlvo - anguloAtual) > MOLA_THRESHOLD) {
        requestAnimationFrame(loopFisica);
    } else {
        anguloAtual = anguloAlvo;
        aplicarAnguloGangorra(anguloAtual);
        animandoFisica = false;
    }
}

function aplicarAnguloGangorra(angulo) {
    const prancha = document.getElementById('gangorra-prancha');
    if (prancha) {
        prancha.style.transform = `translateX(-50%) rotate(${angulo}deg)`;
    }

    // Atualizar badge
    const badge = document.getElementById('badge-status-equilibrio');
    const { delta } = calcularPesos();

    if (badge) {
        if (Math.abs(delta) < 0.01) {
            badge.textContent = 'Em Equilíbrio ✓';
            badge.classList.remove('desequilibrado');
            badge.classList.add('equilibrado');
        } else {
            badge.textContent = delta > 0 ? '→ Direita mais pesada' : '← Esquerda mais pesada';
            badge.classList.add('desequilibrado');
            badge.classList.remove('equilibrado');
        }
    }

    const srStatus = document.getElementById('sr-status-balanca');
    if (srStatus) {
        if (Math.abs(delta) < 0.01) {
            srStatus.textContent = 'Gangorra em perfeito equilíbrio.';
        } else if (delta > 0) {
            srStatus.textContent = 'Gangorra inclinada: lado direito está mais pesado.';
        } else {
            srStatus.textContent = 'Gangorra inclinada: lado esquerdo está mais pesado.';
        }
    }
}

// ─── Painel Lateral (Equação + Divisão + Instruções) ─────────────────────────
function atualizarPainelLateral() {
    atualizarTextoEquacao();
    atualizarBotaoDivisao();
}

function atualizarTextoEquacao() {
    const el = document.getElementById('pergunta-texto') || document.getElementById('equacao-texto');
    if (!el) return;

    const textoEsq = formatarLadoEquacaoVisual('esq');
    const textoDir = formatarLadoEquacaoVisual('dir');
    el.textContent = `${textoEsq} = ${textoDir}`;
}

function formatarLadoEquacaoVisual(lado) {
    const itensDoLado = estadoAtual.items.filter(i => i.ladoAtual === lado);
    if (itensDoLado.length === 0) return '0';

    const partes = [];

    // Termos x positivos (origem no lado)
    const xOrigem = itensDoLado.filter(i => i.tipo === 'x' && i.ladoAtual === i.ladoOrigem).length;
    // Termos x negativos (vieram do outro lado)
    const xOpostos = itensDoLado.filter(i => i.tipo === 'x' && i.ladoAtual !== i.ladoOrigem).length;

    if (xOrigem > 0) partes.push(xOrigem === 1 ? 'x' : `${xOrigem}x`);
    if (xOpostos > 0) partes.push(xOpostos === 1 ? (partes.length === 0 ? '−x' : '− x') : (partes.length === 0 ? `−${xOpostos}x` : `− ${xOpostos}x`));

    // Blocos positivos (origem no lado)
    const blocosPositivos = itensDoLado.filter(i => i.tipo === 'bloco' && i.ladoAtual === i.ladoOrigem);
    blocosPositivos.forEach(b => {
        if (partes.length === 0) partes.push(`${b.valor}`);
        else partes.push(`+ ${b.valor}`);
    });

    // Blocos que vieram do outro lado (agora negativos!)
    const blocosNegativos = itensDoLado.filter(i => i.tipo === 'bloco' && i.ladoAtual !== i.ladoOrigem);
    blocosNegativos.forEach(b => {
        if (partes.length === 0) partes.push(`−${b.valor}`);
        else partes.push(`− ${b.valor}`);
    });

    // Balões (negativos na origem, positivos se vieram do outro lado)
    const baloesOrigem = itensDoLado.filter(i => i.tipo === 'balao' && i.ladoAtual === i.ladoOrigem);
    baloesOrigem.forEach(b => {
        if (partes.length === 0) partes.push(`−${b.valor}`);
        else partes.push(`− ${b.valor}`);
    });

    const baloesOpostos = itensDoLado.filter(i => i.tipo === 'balao' && i.ladoAtual !== i.ladoOrigem);
    baloesOpostos.forEach(b => {
        if (partes.length === 0) partes.push(`${b.valor}`);
        else partes.push(`+ ${b.valor}`);
    });

    return partes.join(' ');
}

function formatarLadoEquacao(d) {
    const partes = [];

    // Parte x
    if (d.xCount > 0) partes.push(d.xCount === 1 ? 'x' : `${d.xCount}x`);
    if (d.xCount < 0) partes.push(d.xCount === -1 ? '−x' : `−${Math.abs(d.xCount)}x`);

    // Parte numérica (positivos - negativos)
    const numNet = d.outrosPositivos - d.outrosNegativos;
    if (numNet > 0) {
        if (partes.length === 0) partes.push(`${numNet}`);
        else partes.push(`+ ${numNet}`);
    } else if (numNet < 0) {
        if (partes.length === 0) partes.push(`${numNet}`);
        else partes.push(`− ${Math.abs(numNet)}`);
    }

    return partes.length > 0 ? partes.join(' ') : '0';
}

function atualizarBotaoDivisao() {
    const painel = document.getElementById('painel-divisao-balanca');
    const textoContainer = document.getElementById('divisao-texto-container');
    const btnDividir = document.getElementById('btn-dividir-balanca');
    const sidebarContainer = document.getElementById('operacoes-botoes-container');
    const ehExperiente = (params.dificuldade === 'experiente');

    if (sidebarContainer) {
        sidebarContainer.innerHTML = '';
        const instrucao = document.createElement('p');
        instrucao.className = 'instrucao-arraste';
        if (ehExperiente) {
            instrucao.innerHTML = '📝 <strong>Arraste</strong> os pesos e resolva a conta no papel!';
        } else {
            instrucao.innerHTML = '🖐️ <strong>Arraste</strong> ou clique nos pesos para equilibrar';
        }
        sidebarContainer.appendChild(instrucao);
    }

    if (!painel || !textoContainer || !btnDividir) return;

    // No modo experiente, não mostrar divisão mecânica — o jogador calcula no papel
    if (ehExperiente) {
        painel.style.display = 'none';
        return;
    }

    const dados = reconstruirDadosReais();

    // Verificar se x está isolado em um lado com coeficiente > 1 e pode dividir
    const podeDividirEsq = (
        dados.esq.xCount > 1 &&
        dados.esq.outrosPositivos === 0 && dados.esq.outrosNegativos === 0 &&
        dados.dir.xCount === 0 &&
        dados.dir.outrosNegativos === 0 &&
        dados.dir.outrosPositivos > 0 &&
        dados.dir.outrosPositivos % dados.esq.xCount === 0
    );

    const podeDividirDir = (
        dados.dir.xCount > 1 &&
        dados.dir.outrosPositivos === 0 && dados.dir.outrosNegativos === 0 &&
        dados.esq.xCount === 0 &&
        dados.esq.outrosNegativos === 0 &&
        dados.esq.outrosPositivos > 0 &&
        dados.esq.outrosPositivos % dados.dir.xCount === 0
    );

    if (podeDividirEsq || podeDividirDir) {
        const ladoX = podeDividirEsq ? 'esq' : 'dir';
        const divisor = podeDividirEsq ? dados.esq.xCount : dados.dir.xCount;

        painel.style.display = 'flex';

        textoContainer.innerHTML = `
            <p class="divisao-texto">
                Dividir ambos os lados por <strong class="destaque-divisor">${divisor}</strong>
            </p>
        `;
        btnDividir.onclick = () => {
            painel.style.display = 'none';
            executarDivisao(ladoX, divisor);
        };
    } else {
        painel.style.display = 'none';
    }
}

function reconstruirDadosReais() {
    const esq = { xCount: 0, outrosPositivos: 0, outrosNegativos: 0 };
    const dir = { xCount: 0, outrosPositivos: 0, outrosNegativos: 0 };

    estadoAtual.items.forEach(item => {
        const dado = item.ladoAtual === 'esq' ? esq : dir;
        const noOrigem = (item.ladoAtual === item.ladoOrigem);

        if (item.tipo === 'x') {
            if (noOrigem) dado.xCount++;
            else dado.xCount--; // x negativo
        } else if (item.tipo === 'bloco') {
            if (noOrigem) dado.outrosPositivos += item.valor;
            else dado.outrosNegativos += item.valor;
        } else if (item.tipo === 'balao') {
            if (noOrigem) dado.outrosNegativos += item.valor;
            else dado.outrosPositivos += item.valor;
        }
    });

    // Simplificar positivos vs negativos
    ['esq', 'dir'].forEach(chave => {
        const d = chave === 'esq' ? esq : dir;
        const net = d.outrosPositivos - d.outrosNegativos;
        if (net >= 0) {
            d.outrosPositivos = net;
            d.outrosNegativos = 0;
        } else {
            d.outrosPositivos = 0;
            d.outrosNegativos = Math.abs(net);
        }
    });

    return { esq, dir };
}

function executarDivisao(ladoX, divisor) {
    salvarEstadoHistorico();
    passosRealizadosTotal++;

    // Manter apenas 1 x nesse lado
    const xItems = estadoAtual.items.filter(i => i.tipo === 'x' && i.ladoAtual === ladoX && i.ladoAtual === i.ladoOrigem);
    // Remover x extras
    const xARemover = xItems.slice(1);
    xARemover.forEach(xi => {
        const idx = estadoAtual.items.indexOf(xi);
        if (idx > -1) estadoAtual.items.splice(idx, 1);
    });

    // Dividir os blocos do lado oposto
    const outroLado = ladoX === 'esq' ? 'dir' : 'esq';
    estadoAtual.items.forEach(item => {
        if (item.ladoAtual === outroLado && item.tipo === 'bloco') {
            item.valor = item.valor / divisor;
        }
        // Balões invertidos (que viraram positivos)
        if (item.ladoAtual === outroLado && item.tipo === 'balao' && item.ladoAtual !== item.ladoOrigem) {
            item.valor = item.valor / divisor;
        }
    });

    impulsionarGangorra(ladoX, 'saida');
    renderizarBalanca();
    atualizarPainelLateral();
    checarIsolamentoIncognita();
}

// ─── Subtração de Blocos e X com Sinais Opostos no Mesmo Lado ────────────────
function executarSubtracaoLado(lado) {
    const itensSubtraiveis = estadoAtual.items.filter(
        i => i.ladoAtual === lado
    );

    // Separar X e blocos numéricos
    const itensX = itensSubtraiveis.filter(i => i.tipo === 'x');
    const blocosNoLado = itensSubtraiveis.filter(i => i.tipo === 'bloco' || i.tipo === 'balao');

    // Verificar se há X com sinais opostos
    const xPositivos = itensX.filter(i => i.ladoAtual === i.ladoOrigem);
    const xNegativos = itensX.filter(i => i.ladoAtual !== i.ladoOrigem);
    const temXOpostos = xPositivos.length > 0 && xNegativos.length > 0;

    const temBlocosSubtraiveis = blocosNoLado.length >= 2;

    if (!temXOpostos && !temBlocosSubtraiveis) return;

    salvarEstadoHistorico();
    passosRealizadosTotal++;

    // 1. Subtrair X com sinais opostos
    if (temXOpostos) {
        const pares = Math.min(xPositivos.length, xNegativos.length);
        // Remover pares de x que se cancelam
        for (let i = 0; i < pares; i++) {
            const idxPos = estadoAtual.items.indexOf(xPositivos[i]);
            if (idxPos > -1) estadoAtual.items.splice(idxPos, 1);
            const idxNeg = estadoAtual.items.indexOf(xNegativos[i]);
            if (idxNeg > -1) estadoAtual.items.splice(idxNeg, 1);
        }
    }

    // 2. Subtrair blocos numéricos (se 2+)
    // Recalcular pois itens podem ter mudado
    const blocosRestantes = estadoAtual.items.filter(
        i => i.ladoAtual === lado && (i.tipo === 'bloco' || i.tipo === 'balao')
    );

    if (blocosRestantes.length >= 2) {
        let valorLiquido = 0;
        blocosRestantes.forEach(item => {
            const noOrigem = (item.ladoAtual === item.ladoOrigem);
            if (item.tipo === 'bloco') {
                valorLiquido += noOrigem ? item.valor : -item.valor;
            } else if (item.tipo === 'balao') {
                valorLiquido += noOrigem ? -item.valor : item.valor;
            }
        });

        // Remover todos os blocos numéricos desse lado
        blocosRestantes.forEach(item => {
            const idx = estadoAtual.items.indexOf(item);
            if (idx > -1) estadoAtual.items.splice(idx, 1);
        });

        // Se o valor líquido não for zero, criar um bloco resultante
        if (Math.abs(valorLiquido) > 0.001) {
            const novoId = estadoAtual.items.length > 0
                ? Math.max(...estadoAtual.items.map(i => i.id)) + 1
                : 0;

            estadoAtual.items.push({
                id: novoId,
                tipo: 'bloco',
                valor: Math.abs(valorLiquido),
                ladoOrigem: valorLiquido > 0 ? lado : (lado === 'esq' ? 'dir' : 'esq'),
                ladoAtual: lado
            });
        }
    }

    // Atualizar gangorra com efeito de mola suave
    impulsionarGangorra(lado, 'saida');
    renderizarBalanca();
    atualizarPainelLateral();
    checarIsolamentoIncognita();
}

// ─── Verificação de Isolamento da Incógnita ──────────────────────────────────
function checarIsolamentoIncognita() {
    const area = document.getElementById('area-resposta-escolha');
    const containerConfirmar = document.getElementById('container-btn-confirmar');
    const { delta } = calcularPesos();
    const ehExperiente = (params.dificuldade === 'experiente');

    if (Math.abs(delta) > 0.01) {
        if (area) area.style.display = 'none';
        if (containerConfirmar) containerConfirmar.style.display = 'none';
        return;
    }

    const dados = reconstruirDadosReais();

    // Isolamento perfeito: x = número (coeficiente 1, sem outros termos)
    const isoladoEsq = (
        dados.esq.xCount === 1 &&
        dados.esq.outrosPositivos === 0 && dados.esq.outrosNegativos === 0 &&
        dados.dir.xCount === 0 &&
        dados.dir.outrosNegativos === 0 && dados.dir.outrosPositivos > 0
    );

    const isoladoDir = (
        dados.dir.xCount === 1 &&
        dados.dir.outrosPositivos === 0 && dados.dir.outrosNegativos === 0 &&
        dados.esq.xCount === 0 &&
        dados.esq.outrosNegativos === 0 && dados.esq.outrosPositivos > 0
    );

    // Modo Experiente: permite responder mesmo com coeficiente > 1 (jogador calcula no papel)
    // Condição: x isolado de um lado (com qualquer coeficiente), sem numéricos, outro lado só numéricos
    const semiIsoladoEsq = ehExperiente && (
        dados.esq.xCount >= 1 &&
        dados.esq.outrosPositivos === 0 && dados.esq.outrosNegativos === 0 &&
        dados.dir.xCount === 0 &&
        dados.dir.outrosNegativos === 0 && dados.dir.outrosPositivos > 0
    );

    const semiIsoladoDir = ehExperiente && (
        dados.dir.xCount >= 1 &&
        dados.dir.outrosPositivos === 0 && dados.dir.outrosNegativos === 0 &&
        dados.esq.xCount === 0 &&
        dados.esq.outrosNegativos === 0 && dados.esq.outrosPositivos > 0
    );

    if (isoladoEsq || isoladoDir || semiIsoladoEsq || semiIsoladoDir) {
        exibirEscolhaMultipla(estadoAtual.x_real);
    } else {
        if (area) area.style.display = 'none';
        if (containerConfirmar) containerConfirmar.style.display = 'none';
    }
}

// ─── Múltipla Escolha e Confirmação de Resposta ─────────────────────────────
function exibirEscolhaMultipla(xCorreto) {
    const area = document.getElementById('area-resposta-escolha');
    const grid = document.getElementById('alternativas-grid');
    const containerConfirmar = document.getElementById('container-btn-confirmar');
    const btnConfirmar = document.getElementById('btn-confirmar');

    if (!area || !grid) return;

    respostaCorretaAtual = xCorreto;
    respostaSelecionada = null;

    grid.innerHTML = '';

    const alternativasSet = new Set();
    alternativasSet.add(xCorreto);
    alternativasSet.add(-xCorreto);
    alternativasSet.add(xCorreto + 1);
    alternativasSet.add(xCorreto - 1);

    let offset = 2;
    while (alternativasSet.size < 4) {
        alternativasSet.add(xCorreto + offset);
        offset++;
    }

    const alternativas = Array.from(alternativasSet).sort(() => Math.random() - 0.5);

    alternativas.forEach(val => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-alternativa';
        btn.textContent = `x = ${val}`;
        btn.onclick = () => selecionarAlternativa(val, btn);
        grid.appendChild(btn);
    });

    if (containerConfirmar) containerConfirmar.style.display = 'block';
    if (btnConfirmar) {
        btnConfirmar.disabled = true;
        btnConfirmar.classList.remove('sucesso', 'erro');
        btnConfirmar.textContent = 'Confirmar';
    }

    area.style.display = 'block';
    area.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function selecionarAlternativa(val, botaoClicado) {
    respostaSelecionada = val;

    const todosBotoes = document.querySelectorAll('.btn-alternativa');
    todosBotoes.forEach(b => b.classList.remove('selecionada'));
    botaoClicado.classList.add('selecionada');

    const btnConfirmar = document.getElementById('btn-confirmar');
    if (btnConfirmar) {
        btnConfirmar.disabled = false;
        btnConfirmar.classList.remove('erro');
    }
}

function confirmarResposta() {
    if (respostaSelecionada === null || !jogoAtivo) return;

    const btnConfirmar = document.getElementById('btn-confirmar');
    const todosBotoes = document.querySelectorAll('.btn-alternativa');
    const botaoSelecionado = document.querySelector('.btn-alternativa.selecionada');

    if (respostaSelecionada === respostaCorretaAtual) {
        // Resposta Correta!
        if (botaoSelecionado) botaoSelecionado.classList.add('correta');
        if (btnConfirmar) {
            btnConfirmar.classList.add('sucesso');
            btnConfirmar.textContent = 'Correto! ✓';
            btnConfirmar.disabled = true;
        }
        todosBotoes.forEach(b => b.disabled = true);

        acertosTotais++;
        pontuacao += Math.round(100 * (CONFIG_DIFICULDADE[params.dificuldade]?.multiplicador || 1));

        setTimeout(() => {
            rodadaAtual++;
            carregarNovaRodada();
        }, 900);
    } else {
        // Resposta Incorreta
        if (botaoSelecionado) botaoSelecionado.classList.add('incorreta');
        if (btnConfirmar) {
            btnConfirmar.classList.add('erro');
            btnConfirmar.textContent = 'Tente Novamente';
        }
        acoesDesequilibrantesTotal++;
        exibirDica(`Quase! Observe a balança: temos x de um lado e ${respostaCorretaAtual} kg do outro.`);

        setTimeout(() => {
            if (botaoSelecionado) botaoSelecionado.classList.remove('incorreta', 'selecionada');
            if (btnConfirmar) {
                btnConfirmar.classList.remove('erro');
                btnConfirmar.textContent = 'Confirmar';
                btnConfirmar.disabled = true;
            }
            respostaSelecionada = null;
        }, 1100);
    }
}
// ─── Desfazer ────────────────────────────────────────────────────────────────
function salvarEstadoHistorico() {
    historicoAcoes.push(JSON.parse(JSON.stringify(estadoAtual)));
    const btnDesfazer = document.getElementById('btn-desfazer');
    if (btnDesfazer) btnDesfazer.disabled = false;
}

function desfazerUltimaAcao() {
    if (historicoAcoes.length === 0) return;

    estadoAtual = historicoAcoes.pop();

    const btnDesfazer = document.getElementById('btn-desfazer');
    if (btnDesfazer) btnDesfazer.disabled = (historicoAcoes.length === 0);

    renderizarBalanca();
    atualizarPainelLateral();
    checarIsolamentoIncognita();
    calcularAnguloAlvo();
    velocidadeAngular = 3;
    if (!animandoFisica) {
        animandoFisica = true;
        requestAnimationFrame(loopFisica);
    }
}

// ─── Dicas ───────────────────────────────────────────────────────────────────
function solicitarDica() {
    exibirDica("Dica: passe os números para o outro lado trocando o sinal (arraste ou clique neles) para isolar o x!");
}

function exibirDica(msg) {
    const el = document.getElementById('dica-flutuante');
    const texto = document.getElementById('dica-texto');
    if (el && texto) {
        texto.textContent = msg;
        el.style.display = 'flex';
        setTimeout(() => {
            el.style.display = 'none';
        }, 5500);
    }
}

function ocultarDica() {
    const el = document.getElementById('dica-flutuante');
    if (el) el.style.display = 'none';
}

// ─── Fim de Jogo ─────────────────────────────────────────────────────────────
function finalizarJogo() {
    jogoAtivo = false;

    let estrelas = 0;
    if (acertosTotais >= totalRodadas) {
        const passosExcedentes = Math.max(0, passosRealizadosTotal - passosEsperadosTotal);

        if (acoesDesequilibrantesTotal === 0 && passosExcedentes <= 1) {
            estrelas = 3;
        } else if (acoesDesequilibrantesTotal <= 2 || passosExcedentes <= 3) {
            estrelas = 2;
        } else {
            estrelas = 1;
        }
    }

    exibirModalResultado(estrelas);
    gravarResultado(estrelas);
}

function exibirModalResultado(estrelas) {
    const modal = document.getElementById('modal-resultado');
    if (!modal) return;

    const displayEstrelas = document.getElementById('modal-estrelas-display');
    const valorPontos = document.getElementById('modal-pontuacao-valor');
    const valorAcertos = document.getElementById('modal-acertos-valor');
    const comentario = document.getElementById('modal-comentario-valor');

    if (displayEstrelas) {
        let svg = '<div style="display:inline-flex; gap:8px; justify-content:center; align-items:center;">';
        for (let i = 1; i <= 3; i++) {
            if (i <= estrelas) {
                svg += '<svg width="36" height="36" viewBox="0 0 24 24" fill="#F59E0B" stroke="#D97706" stroke-width="1.5" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
            } else {
                svg += '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
            }
        }
        svg += '</div>';
        displayEstrelas.innerHTML = svg;
    }

    if (valorPontos) valorPontos.textContent = pontuacao;
    if (valorAcertos) valorAcertos.textContent = `${acertosTotais}/${totalRodadas}`;

    if (comentario) {
        if (estrelas === 3) comentario.textContent = 'Perfeito! Você dominou o equilíbrio das equações!';
        else if (estrelas === 2) comentario.textContent = 'Muito bem! Ótimo raciocínio algébrico!';
        else if (estrelas === 1) comentario.textContent = 'Parabéns por concluir! Treine mais para alcançar 3 estrelas!';
        else comentario.textContent = 'Tente novamente para aperfeiçoar suas jogadas!';
    }

    modal.showModal();
}

// ─── Progresso ───────────────────────────────────────────────────────────────
async function gravarResultado(estrelas) {
    if (params.modo === 'trilha') {
        await salvarProgressoTrilha(estrelas);
    } else {
        salvarRecordeLivre();
    }
}

async function salvarProgressoTrilha(estrelas) {
    const ano = parseInt(params.ano, 10) || 7;
    const fase = parseInt(params.fase, 10) || 7;

    try {
        const progressoLocal = JSON.parse(localStorage.getItem(CHAVE_PROGRESSO)) || {};
        const chaveFase = `ano_${ano}_fase_${fase}`;

        if (!progressoLocal[chaveFase] || estrelas > (progressoLocal[chaveFase].estrelas || 0)) {
            progressoLocal[chaveFase] = { estrelas, pontuacao_max: pontuacao, desbloqueada: true };
        }

        if (estrelas >= 1 && fase < 20) {
            const chaveProx = `ano_${ano}_fase_${fase + 1}`;
            if (!progressoLocal[chaveProx]) {
                progressoLocal[chaveProx] = { estrelas: 0, pontuacao_max: 0, desbloqueada: true };
            } else {
                progressoLocal[chaveProx].desbloqueada = true;
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
                jogo_id: 5,
                pontuacao: pontuacao,
                estrelas: estrelas,
                tipo: 'trilha',
                titulo_fase: `Balança de Equilíbrio – Fase ${fase}`
            })
        });
    } catch (e) {
        console.error('Erro na comunicação com api/progresso/salvar.php:', e);
    }
}

function salvarRecordeLivre() {
    try {
        const recordes = JSON.parse(localStorage.getItem(CHAVE_RECORDE_LIVRE)) || {};
        if (pontuacao > (recordes[5] || 0)) {
            recordes[5] = pontuacao;
            localStorage.setItem(CHAVE_RECORDE_LIVRE, JSON.stringify(recordes));
        }

        fetch('../api/progresso/salvar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                jogo_id: 5,
                pontuacao: pontuacao,
                tipo: 'jogo_livre',
                titulo_fase: 'A Balança de Equilíbrio'
            })
        }).catch(() => {});
    } catch { /* ignora */ }
}

function voltarPaginaAnterior() {
    if (params.modo === 'trilha') {
        window.location.href = `../conteudos/conteudos.php?ano=${params.ano || 7}`;
    } else {
        window.location.href = '../jogos/jogos.php';
    }
}
