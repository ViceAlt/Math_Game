/**
 * conteudos.js – Trilha de Aprendizado Gamificada (Math Game)
 * Mapa vertical de fases estilo Duolingo / Candy Crush / Mario World.
 * 100% livre de emojis: ícones SVG nativos, estrelas em arco, conectores dinâmicos.
 */

// ─── Constantes do Sistema ───────────────────────────────────────────────────
const CHAVE_PROGRESSO   = 'mg_progresso_fases';
const CHAVE_ULTIMO_JOGO = 'mg_ultimo_jogo';

const JOGO_NOMES_CURTOS = {
    1: 'Fatia Maluca',
    2: 'Gráfico em Ação',
    3: 'Formas & Fórmulas',
    4: 'Trilha Numérica',
    5: 'Balança de Equilíbrio'
};

const JOGO_PATHS = {
    1: '../jogo_1/jogo_1.php',
    2: '../jogo_2/jogo_2.php',
    3: '../jogo_3/jogo_3.php',
    4: '../jogo_4/jogo_4.php',
    5: '../jogo_5/jogo_5.php'
};

const MUNDOS_INFO = {
    6: {
        unidade: 'Unidade 1',
        nome: '6° Ano – Floresta dos Números',
        icone: 'forest',
        descricao: 'Domine operações, frações, múltiplos, divisores e números inteiros.',
        corClass: 'mundo-6'
    },
    7: {
        unidade: 'Unidade 2',
        nome: '7° Ano – Deserto das Frações',
        icone: 'desert',
        descricao: 'Desvende números racionais, proporções, equações e porcentagens.',
        corClass: 'mundo-7'
    },
    8: {
        unidade: 'Unidade 3',
        nome: '8° Ano – Galáxia da Geometria',
        icone: 'galaxy',
        descricao: 'Conquiste áreas, perímetros, teoremas geométricos e álgebra.',
        corClass: 'mundo-8'
    },
    9: {
        unidade: 'Unidade 4',
        nome: '9° Ano – Cidadela dos Mestres',
        icone: 'citadel',
        descricao: 'Desafios avançados de funções, estatística e probabilidade.',
        corClass: 'mundo-9'
    }
};

// Padrão de offset horizontal para efeito zigue-zague suave
const OFFSET_PADRAO = [0, 50, 92, 50, 0, -50, -92, -50];

// ─── Estado Global ────────────────────────────────────────────────────────────
let anoAtual = 6;
let faseSelecionada = null;
let dificuldadeSelecionada = 'facil';
let fasesServidor = {};
let dadosCarregados = false;

// ─── Inicialização ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    aplicarPreferenciasVisuais();

    // Fecha modal clicando no backdrop
    const modalFase = document.getElementById('modal-fase');
    if (modalFase) {
        modalFase.addEventListener('click', (e) => {
            if (e.target === modalFase) fecharModalFase();
        });
    }

    // Lê parâmetros da URL
    const params = new URLSearchParams(window.location.search);
    if (params.has('ano')) {
        const anoParam = parseInt(params.get('ano'), 10);
        if ([6, 7, 8, 9].includes(anoParam)) anoAtual = anoParam;
    } else {
        anoAtual = determinarAnoInicial();
    }

    if (params.get('aviso') === 'fase_bloqueada') {
        exibirAviso('Esta fase está bloqueada! Conclua as fases anteriores para desbloquear.');
    }

    // Carrega dados do backend
    await carregarFasesDoServidor();

    // Renderiza o mundo atual
    selecionarAno(anoAtual);

    // Redesenha os conectores SVG no redimensionamento da janela
    window.addEventListener('resize', debounce(() => {
        desenharConectoresSvg();
    }, 150));
});

// ─── Comunicação com a API de Progresso ───────────────────────────────────────
async function carregarFasesDoServidor() {
    try {
        const resp = await fetch('../api/progresso/obter.php');
        const data = await resp.json();

        if (data.ok && data.progresso) {
            dadosCarregados = true;
            localStorage.setItem(CHAVE_PROGRESSO, JSON.stringify(data.progresso));

            fasesServidor = { 6: [], 7: [], 8: [], 9: [] };
            Object.values(data.progresso).forEach(fase => {
                const ano = parseInt(fase.ano_letivo, 10);
                if (fasesServidor[ano]) {
                    if (!fasesServidor[ano].some(f => f.numero_fase === fase.numero_fase)) {
                        fasesServidor[ano].push(fase);
                    }
                }
            });

            for (const a of [6, 7, 8, 9]) {
                fasesServidor[a].sort((x, y) => x.numero_fase - y.numero_fase);
            }
        } else {
            carregarFasesFallback();
        }
    } catch (e) {
        console.warn('Falha ao obter fases do servidor, usando fallback local:', e);
        carregarFasesFallback();
    }
}

function carregarFasesFallback() {
    fasesServidor = {};
    for (const ano of [6, 7, 8, 9]) {
        fasesServidor[ano] = [];
        for (let n = 1; n <= 20; n++) {
            let jogoId;
            if (ano === 6) jogoId = (n % 2 === 1) ? 1 : 4;
            else if (ano === 7) {
                if ([7, 9, 13, 15, 17, 19].includes(n)) jogoId = 5;
                else jogoId = (n % 2 === 1) ? 1 : 2;
            }
            else if (ano === 8) jogoId = (n % 2 === 1) ? 3 : 2;
            else jogoId = ((n - 1) % 4) + 1;

            const chave = `ano_${ano}_fase_${n}`;
            const progressoLocal = obterProgressoLocal();
            const dadoLocal = progressoLocal[chave] || {};

            fasesServidor[ano].push({
                fase_id: 0,
                ano_letivo: ano,
                numero_fase: n,
                jogo_id: jogoId,
                pontuacao_max: dadoLocal.pontuacao_max || 0,
                estrelas: dadoLocal.estrelas || 0,
                vezes_jogadas: dadoLocal.vezes_jogadas || 0,
                desbloqueado: n === 1 || (dadoLocal.desbloqueada === true),
                jogada: (dadoLocal.estrelas > 0)
            });
        }
    }
}

function determinarAnoInicial() {
    try {
        const prog = JSON.parse(localStorage.getItem(CHAVE_PROGRESSO) || '{}');
        if (prog.ultimo_ano && [6, 7, 8, 9].includes(prog.ultimo_ano)) {
            return prog.ultimo_ano;
        }
    } catch { /* ignora */ }
    return 6;
}

// ─── Seleção de Ano / Mundo ──────────────────────────────────────────────────
function selecionarAno(ano) {
    anoAtual = ano;

    const mainEl = document.getElementById('main-content');
    if (mainEl) {
        mainEl.className = `main conteudos-main mundo-${ano}`;
    }

    const infoMundo = MUNDOS_INFO[ano] || MUNDOS_INFO[6];

    // Atualiza ícone SVG do cabeçalho
    const iconeEl = document.getElementById('mundo-icone');
    if (iconeEl && typeof ICONES !== 'undefined' && ICONES[infoMundo.icone]) {
        iconeEl.innerHTML = ICONES[infoMundo.icone](32);
    }

    const tagEl = document.getElementById('mundo-tag');
    if (tagEl) {
        tagEl.textContent = infoMundo.unidade;
    }
    document.getElementById('mundo-titulo').textContent = infoMundo.nome;
    document.getElementById('mundo-descricao').textContent = infoMundo.descricao;

    // Atualiza abas do seletor
    document.querySelectorAll('.btn-mundo-aba').forEach(btn => {
        const isAtivo = parseInt(btn.dataset.ano) === ano;
        btn.classList.toggle('ativa', isAtivo);
        btn.setAttribute('aria-selected', String(isAtivo));
    });

    // Salva preferência
    try {
        const prog = JSON.parse(localStorage.getItem(CHAVE_PROGRESSO) || '{}');
        prog.ultimo_ano = ano;
        localStorage.setItem(CHAVE_PROGRESSO, JSON.stringify(prog));
    } catch { /* ignora */ }

    renderizarMapaVertical(ano);
}

// ─── Renderização do Mapa Vertical de Fases ──────────────────────────────────
function renderizarMapaVertical(ano) {
    const container = document.getElementById('trilha-fases');
    container.innerHTML = '';

    const fases = fasesServidor[ano] || [];
    if (fases.length === 0) {
        container.innerHTML = '<p style="color:var(--text-muted); text-align:center; padding:3rem;">Carregando fases da trilha...</p>';
        return;
    }

    // Identifica a "Fase Atual"
    let faseAtualNumero = 1;
    for (const f of fases) {
        const desbloq = f.desbloqueado || f.numero_fase === 1;
        if (desbloq && (!f.estrelas || f.estrelas === 0)) {
            faseAtualNumero = f.numero_fase;
            break;
        } else if (desbloq) {
            faseAtualNumero = f.numero_fase;
        }
    }

    // Estatísticas da Unidade
    const concluidas = fases.filter(f => (f.estrelas || 0) > 0).length;
    const totalEstrelas = fases.reduce((acc, f) => acc + (f.estrelas || 0), 0);
    const pct = Math.round((concluidas / 20) * 100);

    document.getElementById('mundo-progresso-texto').textContent = `${concluidas} / 20`;
    document.getElementById('mundo-estrelas-texto').textContent = `${totalEstrelas} / 60`;
    document.getElementById('barra-mundo-fill').style.width = `${pct}%`;
    document.getElementById('barra-progresso-container').setAttribute('aria-valuenow', concluidas);

    // Atualiza resumo lateral desktop
    const lateralEstrelas = document.getElementById('lateral-estrelas-mundo');
    const lateralFases = document.getElementById('lateral-fases-concluidas');
    if (lateralEstrelas) lateralEstrelas.textContent = totalEstrelas;
    if (lateralFases) lateralFases.textContent = concluidas;

    // Renderiza cada bolinha na coluna
    fases.forEach((fase, idx) => {
        const desbloqueada = fase.desbloqueado || fase.numero_fase === 1;
        const concluida = (fase.estrelas || 0) > 0;
        const isFaseAtual = (fase.numero_fase === faseAtualNumero) && desbloqueada;
        const estrelasQtd = fase.estrelas || 0;
        const nomeJogo = JOGO_NOMES_CURTOS[fase.jogo_id] || 'Desafio';

        const offsetX = OFFSET_PADRAO[idx % OFFSET_PADRAO.length];

        const wrapper = document.createElement('div');
        wrapper.className = 'fase-wrapper';
        wrapper.style.setProperty('--offset-x', `${offsetX}px`);
        wrapper.setAttribute('role', 'listitem');

        const btnBolinha = document.createElement('button');
        btnBolinha.type = 'button';
        btnBolinha.className = `fase-bolinha ${desbloqueada ? 'desbloqueada' : 'bloqueada'} ${concluida ? 'concluida' : ''} ${isFaseAtual ? 'atual' : ''}`;
        btnBolinha.setAttribute('data-fase-numero', fase.numero_fase);

        const labelAria = desbloqueada
            ? `Fase ${fase.numero_fase}: ${nomeJogo}, ${concluida ? `concluída com ${estrelasQtd} estrelas` : 'disponível para jogar'}`
            : `Fase ${fase.numero_fase}: ${nomeJogo}, bloqueada. Conclua a fase anterior para liberar.`;
        btnBolinha.setAttribute('aria-label', labelAria);

        // Ícone Central em SVG nativo
        const iconeHtml = desbloqueada ? ICONES.play(30) : ICONES.lock(24);
        const badgeComecar = isFaseAtual ? '<div class="badge-comecar-flutuante" aria-hidden="true">COMEÇAR</div>' : '';

        btnBolinha.innerHTML = `
            ${badgeComecar}
            <span class="fase-badge-num" aria-hidden="true">${fase.numero_fase}</span>
            <span class="fase-icone-centro">${iconeHtml}</span>
        `;

        if (desbloqueada) {
            btnBolinha.onclick = () => abrirModalFase(fase);
        } else {
            btnBolinha.onclick = () => {
                exibirAviso(`A Fase ${fase.numero_fase} está bloqueada! Conclua a Fase ${fase.numero_fase - 1} com pelo menos 1 estrela para avançar.`);
            };
        }

        // Informações e Estrelas Abaixo da Bolinha
        const infoDiv = document.createElement('div');
        infoDiv.className = 'fase-info-abaixo';

        let subtituloHtml = '';
        if (concluida) {
            // Estrelas em arco com leve rotação
            subtituloHtml = ICONES.renderEstrelas(estrelasQtd, 16, true);
        } else if (desbloqueada) {
            subtituloHtml = `<span class="fase-status-texto">Disponível</span>`;
        } else {
            subtituloHtml = `<span class="fase-status-texto bloqueado">Bloqueada</span>`;
        }

        infoDiv.innerHTML = `
            <span class="fase-nome-curto">${nomeJogo}</span>
            ${subtituloHtml}
        `;

        wrapper.appendChild(btnBolinha);
        wrapper.appendChild(infoDiv);
        container.appendChild(wrapper);
    });

    // Marcador de Troféu ao final da Fase 20
    const trofeuCard = document.createElement('div');
    trofeuCard.className = 'marcador-trofeu-mundo';
    trofeuCard.innerHTML = `
        <div class="trofeu-circulo-grande" aria-hidden="true">
            ${ICONES.trophy(36)}
        </div>
        <h4>Mestre da Unidade</h4>
        <p>Complete as 20 fases para dominar o ${anoAtual}° Ano!</p>
    `;
    container.appendChild(trofeuCard);

    // Desenha as conexões SVG nos pontos exatos
    requestAnimationFrame(() => {
        setTimeout(desenharConectoresSvg, 50);
    });
}

// ─── Desenho Dinâmico das Linhas de Conexão SVG ──────────────────────────────
function desenharConectoresSvg() {
    const svg = document.getElementById('trilha-svg');
    const wrapper = document.getElementById('trilha-wrapper');
    if (!svg || !wrapper) return;

    const bolinhas = wrapper.querySelectorAll('.fase-bolinha');
    if (bolinhas.length < 2) {
        svg.innerHTML = '';
        return;
    }

    const wrapperRect = wrapper.getBoundingClientRect();
    svg.setAttribute('width', wrapperRect.width);
    svg.setAttribute('height', wrapperRect.height);
    svg.style.width = `${wrapperRect.width}px`;
    svg.style.height = `${wrapperRect.height}px`;

    const pontos = [];
    bolinhas.forEach(b => {
        const rect = b.getBoundingClientRect();
        pontos.push({
            x: rect.left + rect.width / 2 - wrapperRect.left,
            y: rect.top + rect.height / 2 - wrapperRect.top,
            desbloqueada: b.classList.contains('desbloqueada')
        });
    });

    let dFundo = '';
    let dAtivo = '';

    for (let i = 0; i < pontos.length - 1; i++) {
        const p0 = pontos[i];
        const p1 = pontos[i + 1];

        const deltaY = p1.y - p0.y;
        const cpY0 = p0.y + deltaY * 0.5;
        const cpY1 = p1.y - deltaY * 0.5;

        const segmento = `M ${p0.x.toFixed(1)} ${p0.y.toFixed(1)} C ${p0.x.toFixed(1)} ${cpY0.toFixed(1)}, ${p1.x.toFixed(1)} ${cpY1.toFixed(1)}, ${p1.x.toFixed(1)} ${p1.y.toFixed(1)} `;

        dFundo += segmento;

        if (p0.desbloqueada && p1.desbloqueada) {
            dAtivo += segmento;
        }
    }

    svg.innerHTML = `
        <path d="${dFundo}" class="svg-path-trilha-fundo" />
        <path d="${dAtivo}" class="svg-path-trilha-ativo" />
    `;
}

// ─── Modal de Detalhes da Fase ───────────────────────────────────────────────
function abrirModalFase(fase) {
    faseSelecionada = fase;
    dificuldadeSelecionada = 'facil';

    const modal = document.getElementById('modal-fase');
    if (!modal) return;

    const nomeJogo = JOGO_NOMES_CURTOS[fase.jogo_id] || 'Desafio Matemático';
    const estrelas = fase.estrelas || 0;
    const jaJogou  = estrelas > 0 || (fase.vezes_jogadas || 0) > 0;

    document.getElementById('modal-fase-badge').textContent    = `Fase ${fase.numero_fase} • ${anoAtual}° Ano`;
    document.getElementById('modal-fase-titulo').textContent   = nomeJogo;
    document.getElementById('modal-pontuacao-max').textContent = `${fase.pontuacao_max || 0} pts`;

    // Renderiza estrelas em SVG no modal
    const containerEstrelas = document.getElementById('modal-estrelas');
    if (containerEstrelas) {
        containerEstrelas.innerHTML = ICONES.renderEstrelas(estrelas, 24, true);
    }

    const btnTextoEl = document.getElementById('btn-jogar-texto');
    if (btnTextoEl) {
        btnTextoEl.textContent = jaJogou ? 'Repetir' : 'Jogar';
    }

    // Reset seleção de dificuldade
    document.querySelectorAll('#modal-fase .btn-dificuldade').forEach(btn => {
        btn.setAttribute('aria-pressed', btn.dataset.dificuldade === 'facil' ? 'true' : 'false');
    });

    modal.showModal();
}

function fecharModalFase() {
    const modal = document.getElementById('modal-fase');
    if (modal) modal.close();
}

function selecionarDificuldade(dif) {
    dificuldadeSelecionada = dif;
    document.querySelectorAll('#modal-fase .btn-dificuldade').forEach(btn => {
        btn.setAttribute('aria-pressed', btn.dataset.dificuldade === dif ? 'true' : 'false');
    });
}

// ─── Iniciar Fase Selecionada ────────────────────────────────────────────────
function iniciarFase() {
    if (!faseSelecionada) return;

    const jogoId = faseSelecionada.jogo_id || 1;

    localStorage.setItem(CHAVE_ULTIMO_JOGO,
        `${JOGO_NOMES_CURTOS[jogoId]} – Fase ${faseSelecionada.numero_fase} (${anoAtual}° Ano)`
    );

    const progresso = obterProgressoLocal();
    progresso.ultimo_ano  = anoAtual;
    progresso.ultima_fase = faseSelecionada.numero_fase;
    salvarProgressoLocal(progresso);

    const params = {
        modo:           'trilha',
        ano:            anoAtual,
        fase:           faseSelecionada.numero_fase,
        fase_id:        faseSelecionada.fase_id || 0,
        jogoId:         jogoId,
        dificuldade:    dificuldadeSelecionada,
    };
    localStorage.setItem('mg_params_jogo', JSON.stringify(params));

    fecharModalFase();

    const path = JOGO_PATHS[jogoId] || JOGO_PATHS[1];
    const url  = `${path}?ano=${anoAtual}&fase=${faseSelecionada.numero_fase}&dificuldade=${dificuldadeSelecionada}`;
    window.location.href = url;
}

// ─── Aviso Toast Flutuante com SVG ───────────────────────────────────────────
function exibirAviso(mensagem) {
    const avisoExistente = document.querySelector('.aviso-bloqueio-toast');
    if (avisoExistente) avisoExistente.remove();

    const aviso = document.createElement('div');
    aviso.className = 'aviso-bloqueio-toast';
    aviso.setAttribute('role', 'alert');
    aviso.innerHTML = `
        <span class="toast-icone-svg" aria-hidden="true">${ICONES.warning(22)}</span>
        <span>${mensagem}</span>
    `;
    aviso.style.cssText = `
        position: fixed; top: 85px; left: 50%; transform: translateX(-50%);
        background: var(--duo-orange); color: #ffffff; padding: 0.85rem 1.6rem;
        border-radius: var(--radius-md); font-weight: 800; font-size: 0.95rem;
        box-shadow: 0 5px 0 var(--duo-orange-shadow); z-index: 1050; display: flex;
        align-items: center; gap: 0.65rem; max-width: 90%; text-align: center;
        animation: slideDownToast 0.25s ease;
    `;
    document.body.appendChild(aviso);

    setTimeout(() => {
        aviso.style.opacity = '0';
        aviso.style.transition = 'opacity 0.25s ease';
        setTimeout(() => aviso.remove(), 250);
    }, 4000);
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
function obterProgressoLocal() {
    try { return JSON.parse(localStorage.getItem(CHAVE_PROGRESSO)) || {}; } catch { return {}; }
}

function salvarProgressoLocal(progresso) {
    localStorage.setItem(CHAVE_PROGRESSO, JSON.stringify(progresso));
}

function aplicarPreferenciasVisuais() {
    const tema = localStorage.getItem('mg_tema');
    const daltonismo = localStorage.getItem('mg_daltonismo');
    const tonalidade = localStorage.getItem('mg_tonalidade');
    if (tema === 'escuro') document.body.classList.add('tema-escuro');
    if (daltonismo) document.body.classList.add(`daltonismo-${daltonismo}`);
    if (tonalidade && tonalidade !== 'padrao') document.body.classList.add(`tonalidade-${tonalidade}`);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
