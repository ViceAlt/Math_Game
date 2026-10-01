/**
 * login.js
 * Lógica da página de Login.
 * Faz requisição real para api/auth/login.php
 */

document.addEventListener('DOMContentLoaded', () => {
    aplicarPreferenciasVisuais();
    document.getElementById('form-login').addEventListener('submit', handleLogin);
});

async function handleLogin(e) {
    e.preventDefault();
    limparErros();

    const username = document.getElementById('login-username').value.trim();
    const senha    = document.getElementById('login-senha').value;
    const redirect = document.getElementById('redirect-url').value || 'home.php';

    const btnSubmit = document.getElementById('btn-entrar');
    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Entrando...';

    try {
        const resposta = await fetch('api/auth/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, senha })
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
    ['erro-login-username', 'erro-login-senha', 'erro-login-geral'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = '';
    });
}

function aplicarPreferenciasVisuais() {
    const tema = localStorage.getItem('mg_tema');
    const daltonismo = localStorage.getItem('mg_daltonismo');
    if (tema === 'escuro') document.body.classList.add('tema-escuro');
    if (daltonismo) document.body.classList.add(`daltonismo-${daltonismo}`);
}
