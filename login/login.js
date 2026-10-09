/**
 * login.js
 * Lógica da página de Login.
 * Faz requisição para api/auth/login.php com email + CSRF token.
 */

document.addEventListener('DOMContentLoaded', () => {
    aplicarPreferenciasVisuais();
    // Busca o CSRF token da sessão antes de exibir o form
    carregarCsrfToken();
    document.getElementById('form-login').addEventListener('submit', handleLogin);
});

/**
 * Carrega o token CSRF do servidor via session.php
 * e atualiza o campo hidden no formulário.
 */
async function carregarCsrfToken() {
    try {
        const resp = await fetch('../api/auth/session.php');
        const data = await resp.json();
        if (data.csrf_token) {
            const campo = document.getElementById('csrf-token');
            if (campo) campo.value = data.csrf_token;
        }
    } catch (_) {
        // Silencioso: o token já foi injetado pelo PHP no carregamento da página
    }
}

async function handleLogin(e) {
    e.preventDefault();
    limparErros();

    const email    = document.getElementById('login-email').value.trim();
    const senha    = document.getElementById('login-senha').value;
    const redirect = document.getElementById('redirect-url').value || '../home/home.php';
    const csrf     = document.getElementById('csrf-token')?.value || '';

    if (!email) {
        exibirErro('erro-login-email', 'Informe seu e-mail.');
        return;
    }

    const btnSubmit = document.getElementById('btn-entrar');
    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Entrando...';

    try {
        const resposta = await fetch('../api/auth/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, senha, csrf_token: csrf })
        });

        const data = await resposta.json();

        if (resposta.ok && data.ok) {
            window.location.href = redirect;
        } else {
            exibirErro('erro-login-geral', data.erro || 'Falha ao autenticar.');
        }
    } catch (err) {
        exibirErro('erro-login-geral', 'Erro de conexão com o servidor.');
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Entrar';
    }
}

function exibirErro(id, mensagem) {
    const el = document.getElementById(id);
    if (el) el.textContent = mensagem;
}

function limparErros() {
    ['erro-login-email', 'erro-login-senha', 'erro-login-geral'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = '';
    });
}

function aplicarPreferenciasVisuais() {
    const tema = localStorage.getItem('mg_tema');
    const daltonismo = localStorage.getItem('mg_daltonismo');
    const tonalidade = localStorage.getItem('mg_tonalidade');
    if (tema === 'escuro') document.body.classList.add('tema-escuro');
    if (daltonismo) document.body.classList.add(`daltonismo-${daltonismo}`);
    if (tonalidade && tonalidade !== 'padrao') document.body.classList.add(`tonalidade-${tonalidade}`);
}
