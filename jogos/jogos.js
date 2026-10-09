/**
 * jogos.js
 * Lógica da página Jogos (modo livre / treino).
 * Pontuações são locais; não afetam o progresso da trilha.
 */

// ─── Constantes ───────────────────────────────────────────────────────────────
const CHAVE_USUARIO        = 'mg_usuario';
const CHAVE_RECORDE_LIVRE  = 'mg_recorde_livre';

/** Estado de dificuldade por jogo (jogo_id -> dificuldade) */
const dificuldadesPorJogo = { 1: 'facil' };

// ─── Inicialização ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    aplicarPreferenciasVisuais();
    exibirRecordes();
});

// ─── Recorde Local ────────────────────────────────────────────────────────────

function exibirRecordes() {
    const recordes = obterRecordes();
    const el = document.getElementById('recorde-jogo-1');
    if (el) el.textContent = `${recordes[1] || 0} pts`;
}

function obterRecordes() {
    try { return JSON.parse(localStorage.getItem(CHAVE_RECORDE_LIVRE)) || {}; } catch { return {}; }
}

// ─── Seleção de Dificuldade ───────────────────────────────────────────────────

function selecionarDificuldadeJogo(jogoId, dificuldade) {
    dificuldadesPorJogo[jogoId] = dificuldade;

    document.querySelectorAll(`#card-jogo-${jogoId} .btn-dificuldade`).forEach(btn => {
        btn.setAttribute('aria-pressed', btn.dataset.dificuldade === dificuldade ? 'true' : 'false');
    });
}

// ─── Iniciar Jogo Livre ───────────────────────────────────────────────────────

function iniciarJogoLivre(jogoId) {
    const dificuldade = dificuldadesPorJogo[jogoId] || 'facil';

    const params = {
        modo:       'livre',
        jogoId,
        dificuldade,
    };
    localStorage.setItem('mg_params_jogo', JSON.stringify(params));

    // Registra último jogo acessado
    localStorage.setItem('mg_ultimo_jogo', `Jogo ${jogoId} (modo livre)`);

    window.location.href = '../jogo_1/jogo_1.html';
}

// ─── Navegação de Perfil ──────────────────────────────────────────────────────

function navegarPerfil() {
    if (LOGADO) {
        window.location.href = '../perfil/perfil.php';
    } else {
        document.getElementById('modal-login-sugestao').showModal();
    }
}

function fecharModalLogin() {
    document.getElementById('modal-login-sugestao').close();
}

document.getElementById('modal-login-sugestao').addEventListener('click', function (e) {
    if (e.target === this) fecharModalLogin();
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function aplicarPreferenciasVisuais() {
    const tema = localStorage.getItem('mg_tema');
    const daltonismo = localStorage.getItem('mg_daltonismo');
    const tonalidade = localStorage.getItem('mg_tonalidade');
    if (tema === 'escuro') document.body.classList.add('tema-escuro');
    if (daltonismo) document.body.classList.add(`daltonismo-${daltonismo}`);
    if (tonalidade && tonalidade !== 'padrao') document.body.classList.add(`tonalidade-${tonalidade}`);
}
