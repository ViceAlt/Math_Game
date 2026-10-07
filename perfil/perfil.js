/**
 * perfil.js
 * Lógica da página Perfil.
 * Exibe dados do usuário logado; redireciona visitantes.
 */

// ─── Constantes ───────────────────────────────────────────────────────────────
const CHAVE_USUARIO   = 'mg_usuario';
const CHAVE_PROGRESSO = 'mg_progresso';

// ─── Inicialização ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    aplicarPreferenciasVisuais();
    if (LOGADO) {
        // As informações básicas (nome, email, foto, data)
        // já foram geradas no HTML pelo PHP! (home.php / perfil.php)
        // Só precisamos calcular a pontuação/estrelas a partir do localStorage
        const { pontuacaoTotal, estrelasTotal } = calcularEstatisticas();
        const elPontos = document.getElementById('perfil-pontuacao');
        const elEstrelas = document.getElementById('perfil-estrelas');
        
        if (elPontos) elPontos.textContent = `${pontuacaoTotal} pts`;
        if (elEstrelas) elEstrelas.textContent = `${estrelasTotal} ⭐`;
    }
});

/**
 * Percorre todas as fases salvas no progresso e soma pontuações e estrelas.
 * @returns {{ pontuacaoTotal: number, estrelasTotal: number }}
 */
function calcularEstatisticas() {
    const progresso = obterProgresso();
    let pontuacaoTotal = 0;
    let estrelasTotal  = 0;

    Object.keys(progresso).forEach(chave => {
        if (chave.startsWith('ano_')) {
            const fase = progresso[chave];
            if (fase && typeof fase === 'object') {
                pontuacaoTotal += fase.pontuacao_max || 0;
                estrelasTotal  += fase.estrelas      || 0;
            }
        }
    });

    return { pontuacaoTotal, estrelasTotal };
}

// ─── Logout ───────────────────────────────────────────────────────────────────

async function fazerLogout() {
    try {
        await fetch('../api/auth/logout.php', { method: 'POST' });
        localStorage.removeItem('mg_usuario');
        localStorage.removeItem('mg_progresso');
        window.location.href = '../home/home.php';
    } catch(e) {
        console.error('Erro ao fazer logout', e);
        window.location.href = '../home/home.php';
    }
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function obterProgresso() {
    try { return JSON.parse(localStorage.getItem(CHAVE_PROGRESSO)) || {}; } catch { return {}; }
}

function aplicarPreferenciasVisuais() {
    const tema = localStorage.getItem('mg_tema');
    const daltonismo = localStorage.getItem('mg_daltonismo');
    if (tema === 'escuro') document.body.classList.add('tema-escuro');
    if (daltonismo) document.body.classList.add(`daltonismo-${daltonismo}`);
}
