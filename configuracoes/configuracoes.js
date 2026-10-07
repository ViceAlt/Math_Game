/**
 * configuracoes.js
 * Lógica da página Configurações.
 * Persiste preferências de tema e modo de daltonismo no localStorage
 * e aplica as classes correspondentes ao <body>.
 */

// ─── Constantes ───────────────────────────────────────────────────────────────
const CHAVE_TEMA       = 'mg_tema';
const CHAVE_DALTONISMO = 'mg_daltonismo';
const CHAVE_USUARIO    = 'mg_usuario';

/** Classes de daltonismo aplicáveis ao body */
const CLASSES_DALTONISMO = ['daltonismo-protanopia', 'daltonismo-deuteranopia', 'daltonismo-tritanopia'];

// ─── Inicialização ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    carregarPreferencias();
});

// ─── Carregar e Aplicar Preferências ──────────────────────────────────────────

function carregarPreferencias() {
    const tema = localStorage.getItem(CHAVE_TEMA);
    const daltonismo = localStorage.getItem(CHAVE_DALTONISMO) || 'nenhum';

    // Aplica tema
    const toggleTema = document.getElementById('toggle-tema');
    if (tema === 'escuro') {
        document.body.classList.add('tema-escuro');
        toggleTema.checked = true;
    } else {
        document.body.classList.remove('tema-escuro');
        toggleTema.checked = false;
    }

    // Aplica daltonismo
    aplicarClasseDaltonismo(daltonismo);

    // Marca radio correto
    const radioEl = document.getElementById(`dal-${daltonismo}`);
    if (radioEl) radioEl.checked = true;
}

// ─── Alternância de Tema ──────────────────────────────────────────────────────

/**
 * Alterna entre modo claro e escuro.
 * @param {boolean} escuro
 */
function alternarTema(escuro) {
    if (escuro) {
        document.body.classList.add('tema-escuro');
        localStorage.setItem(CHAVE_TEMA, 'escuro');
    } else {
        document.body.classList.remove('tema-escuro');
        localStorage.setItem(CHAVE_TEMA, 'claro');
    }
}

// ─── Seleção de Daltonismo ────────────────────────────────────────────────────

/**
 * Aplica o modo de daltonismo selecionado.
 * @param {string} modo - 'nenhum' | 'protanopia' | 'deuteranopia' | 'tritanopia'
 */
function selecionarDaltonismo(modo) {
    aplicarClasseDaltonismo(modo);

    if (modo === 'nenhum') {
        localStorage.removeItem(CHAVE_DALTONISMO);
    } else {
        localStorage.setItem(CHAVE_DALTONISMO, modo);
    }
}

function aplicarClasseDaltonismo(modo) {
    // Remove todas as classes de daltonismo antes de aplicar a nova
    CLASSES_DALTONISMO.forEach(cls => document.body.classList.remove(cls));

    if (modo !== 'nenhum') {
        document.body.classList.add(`daltonismo-${modo}`);
    }
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
