/**
 * cadastro.js
 * Lógica da página de Cadastro.
 * Faz requisição real para api/auth/register.php
 */

document.addEventListener('DOMContentLoaded', () => {
    aplicarPreferenciasVisuais();
    document.getElementById('form-cadastro').addEventListener('submit', handleCadastro);
});

async function handleCadastro(e) {
    e.preventDefault();
    limparErros();

    const username = document.getElementById('cadastro-username').value.trim();
    const email    = document.getElementById('cadastro-email').value.trim();
    const senha    = document.getElementById('cadastro-senha').value;
    const redirect = document.getElementById('redirect-url').value || 'home.php';

    // Disable button to prevent double submit
    const btnSubmit = document.getElementById('btn-cadastrar');
    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Criando conta...';

    try {
        const resposta = await fetch('api/auth/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, email, senha })
        });

        const data = await resposta.json();

        if (resposta.ok && data.ok) {
            window.location.href = redirect;
        } else {
            if (data.erros) {
                // Erros de validação
                if (data.erros.username) exibirErro('erro-username', data.erros.username);
                if (data.erros.email)    exibirErro('erro-email', data.erros.email);
                if (data.erros.senha)    exibirErro('erro-senha', data.erros.senha);
            } else if (data.erro) {
                exibirErro('erro-geral', data.erro);
            } else {
                exibirErro('erro-geral', 'Ocorreu um erro desconhecido.');
            }
        }
    } catch (err) {
        exibirErro('erro-geral', 'Erro de conexão com o servidor.');
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Criar Conta';
    }
}

function exibirErro(id, mensagem) {
    const el = document.getElementById(id);
    if (el) el.textContent = mensagem;
}

function limparErros() {
    ['erro-username', 'erro-email', 'erro-senha', 'erro-geral'].forEach(id => {
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
