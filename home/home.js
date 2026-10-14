/**
 * home.js – Lógica interativa da Home (Math Game)
 * Carrega progresso da API, último jogo acessado e últimas trilhas jogadas.
 * 100% livre de emojis: usa exclusivamente o sistema de ícones SVG.
 */

document.addEventListener('DOMContentLoaded', () => {
    aplicarPreferenciasVisuais();

    if (typeof LOGADO !== 'undefined' && LOGADO && USUARIO) {
        carregarDadosHome();
    }
});

// Mapa de caminhos dos jogos por ID
const JOGO_PATHS = {
    1: '../jogo_1/jogo_1.php',
    2: '../jogo_2/jogo_2.php',
    3: '../jogo_3/jogo_3.php',
    4: '../jogo_4/jogo_4.php'
};

const JOGO_NOMES = {
    1: 'Fatia Maluca (Frações)',
    2: 'Gráfico em Ação (Estatística)',
    3: 'Formas & Fórmulas (Geometria)',
    4: 'Trilha Numérica (Álgebra)'
};

const MUNDOS_ICONES_JS = {
    6: 'forest',
    7: 'desert',
    8: 'galaxy',
    9: 'citadel'
};

/**
 * Busca da API os dados de progresso consolidados e o histórico de acesso.
 */
async function carregarDadosHome() {
    try {
        const res = await fetch('../api/progresso/obter.php');
        const data = await res.json();

        if (data && data.ok) {
            // Guarda em cache local para uso de fallback
            if (data.progresso) {
                localStorage.setItem('mg_progresso_fases', JSON.stringify(data.progresso));
                atualizarEstatisticasPerfil(data.progresso);
            }

            renderizarUltimoJogo(data.ultimo_jogo);
            renderizarUltimasTrilhas(data.ultimas_trilhas);
        } else {
            renderizarFallbackOffline();
        }
    } catch (e) {
        console.warn('Erro ao buscar dados na API:', e);
        renderizarFallbackOffline();
    }
}

/**
 * Atualiza os chips de estatísticas do perfil na coluna lateral.
 */
function atualizarEstatisticasPerfil(progresso) {
    let totalEstrelas = 0;
    let fasesConcluidas = 0;

    Object.values(progresso).forEach(p => {
        // Se a chave for numérica (ID da fase) para não contar duplicado com chaves 'ano_X_fase_Y'
        if (typeof p.fase_id !== 'undefined' && p.numero_fase) {
            const e = parseInt(p.estrelas, 10) || 0;
            totalEstrelas += e;
            if (e > 0) fasesConcluidas++;
        }
    });

    const elEstrelas = document.getElementById('perfil-total-estrelas');
    const elFases = document.getElementById('perfil-total-fases');

    if (elEstrelas) elEstrelas.textContent = totalEstrelas;
    if (elFases) elFases.textContent = `${fasesConcluidas} / 80`;
}

/**
 * Renderiza a seção do último jogo acessado com botão Jogar/Repetir.
 */
function renderizarUltimoJogo(ultimo) {
    const elNome = document.getElementById('ultimo-jogo-nome');
    const elInfo = document.getElementById('ultimo-jogo-info');
    const btnAcao = document.getElementById('btn-ultimo-jogo');
    const txtBtn = document.getElementById('btn-ultimo-texto');

    if (!elNome || !elInfo || !btnAcao) return;

    if (ultimo && ultimo.jogo_id) {
        const nomeJogo = ultimo.nome_jogo || JOGO_NOMES[ultimo.jogo_id] || `Jogo ${ultimo.jogo_id}`;
        const ano = ultimo.ano_letivo || 6;
        const fase = ultimo.numero_fase || 1;
        const path = ultimo.pasta_path || JOGO_PATHS[ultimo.jogo_id] || JOGO_PATHS[1];

        elNome.textContent = `${nomeJogo} – Fase ${fase} (${ano}° Ano)`;
        
        let textoInfo = 'Modo Trilha';
        if (ultimo.acessado_em) {
            try {
                const dataObj = new Date(ultimo.acessado_em.replace(/-/g, '/'));
                textoInfo = `Último acesso: ${dataObj.toLocaleDateString('pt-BR')} às ${dataObj.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}`;
            } catch (_) {
                textoInfo = `Último acesso em ${ultimo.acessado_em}`;
            }
        }
        elInfo.textContent = textoInfo;

        if (txtBtn) txtBtn.textContent = 'Repetir';
        btnAcao.onclick = () => {
            window.location.href = `${path}?ano=${ano}&fase=${fase}&dificuldade=facil`;
        };
    } else {
        const ultimoLocal = localStorage.getItem('mg_ultimo_jogo');
        if (ultimoLocal) {
            elNome.textContent = ultimoLocal;
            elInfo.textContent = 'Atividade registrada anteriormente';
            if (txtBtn) txtBtn.textContent = 'Continuar';
            btnAcao.onclick = () => {
                window.location.href = '../conteudos/conteudos.php';
            };
        } else {
            elNome.textContent = 'Nenhum jogo acessado ainda';
            elInfo.textContent = 'Comece sua jornada matemática na Fase 1 do 6° Ano!';
            if (txtBtn) txtBtn.textContent = 'Iniciar Trilha';
            btnAcao.onclick = () => {
                window.location.href = '../conteudos/conteudos.php?ano=6&fase=1';
            };
        }
    }
}

/**
 * Renderiza de 1 a 3 trilhas mais recentes com barra de progresso.
 */
function renderizarUltimasTrilhas(trilhas) {
    const container = document.getElementById('trilhas-lista');
    if (!container) return;

    if (!trilhas || trilhas.length === 0) {
        const iconeForest = typeof ICONES !== 'undefined' ? ICONES.forest(20) : '';
        const iconeEstrela = typeof ICONES !== 'undefined' ? ICONES.starFilled(15) : '';

        container.innerHTML = `
            <div class="trilha-item-card">
                <div class="trilha-item-info">
                    <div class="trilha-item-topo">
                        <div class="trilha-item-nome-wrap">
                            ${iconeForest}
                            <span class="trilha-item-nome">Floresta dos Números (6° Ano)</span>
                        </div>
                        <span class="trilha-item-stats">0/20 fases • 0 ${iconeEstrela}</span>
                    </div>
                    <div class="barra-progresso-trilha">
                        <div class="barra-progresso-fill" style="width: 5%"></div>
                    </div>
                </div>
                <button type="button" class="btn-duo btn-duo-blue" onclick="window.location.href='../conteudos/conteudos.php?ano=6&fase=1'">
                    Iniciar
                </button>
            </div>
        `;
        return;
    }

    container.innerHTML = trilhas.map(t => {
        const pct = Math.max(5, Math.round((t.fases_concluidas / (t.total_fases || 20)) * 100));
        const btnTexto = t.fases_concluidas > 0 ? `Continuar (${t.fases_concluidas}/20)` : 'Iniciar';

        const iconeTipo = MUNDOS_ICONES_JS[t.ano_letivo] || 'forest';
        const iconeSvg = typeof ICONES !== 'undefined' && ICONES[iconeTipo] ? ICONES[iconeTipo](20) : '';
        const iconeEstrelaSvg = typeof ICONES !== 'undefined' ? ICONES.starFilled(15) : '';

        return `
            <div class="trilha-item-card">
                <div class="trilha-item-info">
                    <div class="trilha-item-topo">
                        <div class="trilha-item-nome-wrap">
                            ${iconeSvg}
                            <span class="trilha-item-nome">${t.nome_mundo}</span>
                        </div>
                        <span class="trilha-item-stats">${t.fases_concluidas}/${t.total_fases || 20} fases • ${t.total_estrelas} ${iconeEstrelaSvg}</span>
                    </div>
                    <div class="barra-progresso-trilha" role="progressbar" aria-valuenow="${t.fases_concluidas}" aria-valuemin="0" aria-valuemax="${t.total_fases || 20}">
                        <div class="barra-progresso-fill" style="width: ${pct}%"></div>
                    </div>
                </div>
                <button type="button" class="btn-duo btn-duo-blue" 
                        onclick="window.location.href='../conteudos/conteudos.php?ano=${t.ano_letivo}&fase=${t.proxima_fase || 1}'">
                    ${btnTexto}
                </button>
            </div>
        `;
    }).join('');
}

/**
 * Fallback simples quando a requisição offline/erro ocorrer.
 */
function renderizarFallbackOffline() {
    renderizarUltimoJogo(null);
    renderizarUltimasTrilhas(null);
}

/**
 * Aplica tema escuro, daltonismo e tonalidades salvas no localStorage.
 */
function aplicarPreferenciasVisuais() {
    const tema = localStorage.getItem('mg_tema');
    const daltonismo = localStorage.getItem('mg_daltonismo');
    const tonalidade = localStorage.getItem('mg_tonalidade');

    if (tema === 'escuro') document.body.classList.add('tema-escuro');
    if (daltonismo) document.body.classList.add(`daltonismo-${daltonismo}`);
    if (tonalidade && tonalidade !== 'padrao') document.body.classList.add(`tonalidade-${tonalidade}`);
}
