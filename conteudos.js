/**
 * conteudos.js
 * Lógica da página Conteúdos.
 * Gerencia seleção de ano letivo, renderização da trilha de fases,
 * controle de desbloqueio sequencial e modal de detalhes/início de fase.
 */

// ─── Constantes ──────────────────────────────────────────────────────────────
const CHAVE_USUARIO   = 'mg_usuario';
const CHAVE_PROGRESSO = 'mg_progresso';
const CHAVE_ULTIMO_JOGO = 'mg_ultimo_jogo';

/** Definição das fases por ano letivo */
const FASES_CONFIG = {
    6: [
        { numero: 1, titulo: 'Frações Simples',       slicesPerPizza: 4, pizzaCount: 1 },
        { numero: 2, titulo: 'Frações Equivalentes',  slicesPerPizza: 6, pizzaCount: 1 },
        { numero: 3, titulo: 'Comparando Frações',    slicesPerPizza: 8, pizzaCount: 2 },
    ],
    7: [
        { numero: 1, titulo: 'Frações Impróprias',    slicesPerPizza: 6, pizzaCount: 1 },
        { numero: 2, titulo: 'Números Mistos',         slicesPerPizza: 8, pizzaCount: 2 },
        { numero: 3, titulo: 'Adição de Frações',      slicesPerPizza: 12, pizzaCount: 2 },
    ],
    8: [
        { numero: 1, titulo: 'Multiplicação Frações',  slicesPerPizza: 8,  pizzaCount: 1 },
        { numero: 2, titulo: 'Divisão de Frações',     slicesPerPizza: 10, pizzaCount: 2 },
        { numero: 3, titulo: 'Frações e Decimais',     slicesPerPizza: 12, pizzaCount: 2 },
    ],
    9: [
        { numero: 1, titulo: 'Fração e Porcentagem',   slicesPerPizza: 10, pizzaCount: 1 },
        { numero: 2, titulo: 'Razão e Proporção',      slicesPerPizza: 12, pizzaCount: 2 },
        { numero: 3, titulo: 'Desafio Final',           slicesPerPizza: 16, pizzaCount: 3 },
    ],
};

// ─── Estado local ─────────────────────────────────────────────────────────────
let anoAtual       = 6;
let faseSelecionada = null;
let dificuldadeSelecionada = 'facil';

// ─── Inicialização ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    aplicarPreferenciasVisuais();

    const progressoRaw = localStorage.getItem(CHAVE_PROGRESSO);
    const progresso = progressoRaw ? JSON.parse(progressoRaw) : null;
    const anoInicial = progresso?.ultimo_ano || 6;

    selecionarAno(anoInicial);
});

// ─── Seleção de Ano ──────────────────────────────────────────────────────────

function selecionarAno(ano) {
    anoAtual = ano;

    document.querySelectorAll('.btn-ano').forEach(btn => {
        const isAtivo = parseInt(btn.dataset.ano) === ano;
        btn.setAttribute('aria-pressed', String(isAtivo));
    });

    renderizarTrilha(ano);
}

// ─── Renderização da Trilha ───────────────────────────────────────────────────

function renderizarTrilha(ano) {
    const container = document.getElementById('trilha-fases');
    container.innerHTML = '';

    const fases      = FASES_CONFIG[ano];
    const progresso  = obterProgresso();
    const chaveAno   = `ano_${ano}`;

    // A fase 1 é sempre desbloqueada; as demais dependem da anterior
    fases.forEach((fase, idx) => {
        const chave = `${chaveAno}_fase_${fase.numero}`;
        const dadosFase = progresso[chave] || { pontuacao_max: 0, estrelas: 0, jogada: false };

        const chaveAnterior = idx > 0 ? `${chaveAno}_fase_${fases[idx - 1].numero}` : null;
        const anteriorJogada = idx === 0 || (progresso[chaveAnterior]?.jogada === true);

        const desbloqueada = anteriorJogada;

        const no = document.createElement('div');
        no.className = `fase-no ${desbloqueada ? 'desbloqueada' : 'bloqueada'}`;
        no.setAttribute('role', desbloqueada ? 'button' : 'listitem');
        no.setAttribute('aria-label',
            desbloqueada
                ? `Fase ${fase.numero}: ${fase.titulo}`
                : `Fase ${fase.numero} bloqueada – complete a fase anterior`
        );
        if (desbloqueada) {
            no.setAttribute('tabindex', '0');
            no.onclick    = () => abrirModalFase(fase, dadosFase);
            no.onkeydown  = (e) => { if (e.key === 'Enter') abrirModalFase(fase, dadosFase); };
        }

        no.innerHTML = `
            <span class="fase-numero">${fase.numero}</span>
            <span class="fase-titulo">${fase.titulo}</span>
            <span class="fase-estrelas">${renderizarEstrelas(dadosFase.estrelas)}</span>
            ${!desbloqueada ? '<span class="fase-cadeado" aria-hidden="true">🔒</span>' : ''}
        `;

        container.appendChild(no);
    });
}

function renderizarEstrelas(qtd) {
    const preenchida = '⭐';
    const vazia      = '☆';
    return preenchida.repeat(qtd) + vazia.repeat(3 - qtd);
}

// ─── Modal de Fase ───────────────────────────────────────────────────────────

function abrirModalFase(fase, dadosFase) {
    faseSelecionada = fase;
    dificuldadeSelecionada = 'facil';

    document.getElementById('modal-fase-numero').textContent   = fase.numero;
    document.getElementById('modal-pontuacao-max').textContent = dadosFase.pontuacao_max;
    document.getElementById('modal-estrelas').textContent      = renderizarEstrelas(dadosFase.estrelas);

    // Reset seleção de dificuldade
    document.querySelectorAll('.btn-dificuldade').forEach(btn => {
        btn.setAttribute('aria-pressed', btn.dataset.dificuldade === 'facil' ? 'true' : 'false');
    });

    document.getElementById('modal-fase').showModal();
}

function fecharModalFase() {
    document.getElementById('modal-fase').close();
}

document.getElementById('modal-fase').addEventListener('click', function (e) {
    if (e.target === this) fecharModalFase();
});

// ─── Seleção de Dificuldade ──────────────────────────────────────────────────

function selecionarDificuldade(dificuldade) {
    dificuldadeSelecionada = dificuldade;
    document.querySelectorAll('.btn-dificuldade').forEach(btn => {
        btn.setAttribute('aria-pressed', btn.dataset.dificuldade === dificuldade ? 'true' : 'false');
    });
}

// ─── Iniciar Fase ────────────────────────────────────────────────────────────

function iniciarFase() {
    if (!faseSelecionada) return;

    // Registra último jogo acessado
    localStorage.setItem(CHAVE_ULTIMO_JOGO, `Jogo 1 – Fase ${faseSelecionada.numero} (${anoAtual}° Ano)`);

    // Atualiza último ano no progresso
    const progresso = obterProgresso();
    progresso.ultimo_ano  = anoAtual;
    progresso.ultima_fase = faseSelecionada.numero;
    salvarProgresso(progresso);

    // Monta parâmetros para o jogo via localStorage (compatível com file://)
    const params = {
        modo:           'trilha',
        ano:            anoAtual,
        fase:           faseSelecionada.numero,
        dificuldade:    dificuldadeSelecionada,
        slicesPerPizza: faseSelecionada.slicesPerPizza,
        pizzaCount:     faseSelecionada.pizzaCount,
    };
    localStorage.setItem('mg_params_jogo', JSON.stringify(params));

    fecharModalFase();
    window.location.href = 'jogo_1/jogo_1.html';
}

// ─── Modal de Login ───────────────────────────────────────────────────────────

function navegarPerfil() {
    if (LOGADO) {
        window.location.href = 'perfil.php';
    } else {
        document.getElementById('modal-login-sugestao').showModal();
    }
}

function fecharModalLogin() {
    document.getElementById('modal-login-sugestao').close();
}

function obterProgresso() {
    try { return JSON.parse(localStorage.getItem(CHAVE_PROGRESSO)) || {}; } catch { return {}; }
}

function salvarProgresso(progresso) {
    localStorage.setItem(CHAVE_PROGRESSO, JSON.stringify(progresso));
}

function aplicarPreferenciasVisuais() {
    const tema = localStorage.getItem('mg_tema');
    const daltonismo = localStorage.getItem('mg_daltonismo');
    if (tema === 'escuro') document.body.classList.add('tema-escuro');
    if (daltonismo) document.body.classList.add(`daltonismo-${daltonismo}`);
}
