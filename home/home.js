/**
 * home.js
 * Lógica da página Home.
 * Renderiza interface condicionalmente (LOGADO vs Visitante) baseado
 * nas variáveis globais fornecidas pelo PHP.
 */

// LOGADO e USUARIO são injetados via script inline pelo home.php

document.addEventListener('DOMContentLoaded', () => {
    aplicarPreferenciasVisuais();

    if (LOGADO && USUARIO) {
        // Estado: Logado
        const ultimoJogo = localStorage.getItem('mg_ultimo_jogo') || 'Nenhum jogo acessado ainda';
        document.getElementById('ultimo-jogo-nome').textContent = ultimoJogo;

        // Busca o progresso da API para mostrar na home
        carregarProgressoTrilhaHome();
    } else {
        // Estado: Visitante
        // Não precisamos fazer nada aqui, o HTML visitante já vem renderizado pelo PHP
    }
});

async function carregarProgressoTrilhaHome() {
    try {
        const res = await fetch('../api/progresso/obter.php');
        const data = await res.json();
        
        if (data.ok && data.progresso) {
            // Guarda em localStorage para uso das outras telas
            localStorage.setItem('mg_progresso', JSON.stringify(data.progresso));
            
            // Calcula qual a fase mais avançada jogada
            let maiorAno = 6;
            let maiorFase = 1;
            let totalEstrelas = 0;
            
            Object.values(data.progresso).forEach(p => {
                totalEstrelas += p.estrelas;
                if (p.jogada || p.estrelas > 0) {
                    if (p.ano_letivo > maiorAno) {
                        maiorAno = p.ano_letivo;
                        maiorFase = p.numero_fase;
                    } else if (p.ano_letivo === maiorAno && p.numero_fase > maiorFase) {
                        maiorFase = p.numero_fase;
                    }
                }
            });

            document.getElementById('trilha-ano').textContent     = `${maiorAno}° Ano`;
            document.getElementById('trilha-fase').textContent    = maiorFase;
            document.getElementById('trilha-estrelas').textContent = `${totalEstrelas} ⭐`;
        }
    } catch (e) {
        console.error('Erro ao carregar progresso:', e);
    }
}

// ─── Navegação ───────────────────────────────────────────────────────────────

function navegarPerfil() {
    if (LOGADO) {
        window.location.href = '../perfil/perfil.php';
    } else {
        abrirModal();
    }
}

// ─── Modal ───────────────────────────────────────────────────────────────────

function abrirModal() {
    document.getElementById('modal-login-sugestao').showModal();
}

function fecharModal() {
    document.getElementById('modal-login-sugestao').close();
}

document.getElementById('modal-login-sugestao').addEventListener('click', function (e) {
    if (e.target === this) fecharModal();
});

// ─── Preferências Visuais ─────────────────────────────────────────────────────

function aplicarPreferenciasVisuais() {
    const tema = localStorage.getItem('mg_tema');
    const daltonismo = localStorage.getItem('mg_daltonismo');

    if (tema === 'escuro') document.body.classList.add('tema-escuro');
    if (daltonismo) document.body.classList.add(`daltonismo-${daltonismo}`);
}
