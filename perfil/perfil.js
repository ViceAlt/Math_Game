/**
 * perfil.js – Dashboard de Desempenho e Perfil do Aluno (Math Game)
 * Conexão assíncrona com api/progresso/obter.php + sincronização com localStorage.
 * Diagnóstico automatizado de conteúdos para evolução pedagógica.
 */

// ─── Constantes do Sistema ───────────────────────────────────────────────────
const CHAVE_PROGRESSO_FASES = 'mg_progresso_fases';
const CHAVE_PROGRESSO_OLD   = 'mg_progresso';
const TOTAL_FASES_SISTEMA   = 80;
const TOTAL_ESTRELAS_MAX    = 240; // 80 fases * 3 estrelas

// Mapeamento dos 5 Pilares de Conteúdo do Math Game
const PILARES_CONTEUDO = {
    1: {
        id: 1,
        jogoId: 1,
        nome: 'Frações & Divisão',
        jogoNome: 'Fatia Maluca (Frações com Pizza)',
        descricao: 'Partes do todo, equivalência fracionária e divisão geométrica',
        cor: 'var(--duo-orange)',
        corSombra: 'var(--duo-orange-shadow)',
        bgIcone: '#ff9600',
        sigla: 'FR',
        urlJogo: '../jogo_1/jogo_1.php',
        anoPadrao: 6
    },
    2: {
        id: 2,
        jogoId: 2,
        nome: 'Estatística & Gráficos',
        jogoNome: 'Gráfico em Ação (Gráficos de Barras)',
        descricao: 'Leitura, interpretação e montagem de gráficos de colunas',
        cor: 'var(--duo-green)',
        corSombra: 'var(--duo-green-shadow)',
        bgIcone: '#58cc02',
        sigla: 'ES',
        urlJogo: '../jogo_2/jogo_2.php',
        anoPadrao: 7
    },
    3: {
        id: 3,
        jogoId: 3,
        nome: 'Geometria & Formas',
        jogoNome: 'Formas & Fórmulas',
        descricao: 'Polígonos, cálculo de áreas, perímetros e propriedades visuais',
        cor: 'var(--duo-yellow-shadow)',
        corSombra: 'var(--duo-yellow-shadow)',
        bgIcone: '#ffc800',
        sigla: 'GEO',
        urlJogo: '../jogo_3/jogo_3.php',
        anoPadrao: 8
    },
    4: {
        id: 4,
        jogoId: 4,
        nome: 'Aritmética & Inteiros',
        jogoNome: 'Trilha Numérica (Sucessores e Antecessores)',
        descricao: 'Reta numérica, operações fundamentais e números inteiros',
        cor: 'var(--duo-blue)',
        corSombra: 'var(--duo-blue-shadow)',
        bgIcone: '#1cb0f6',
        sigla: 'AR',
        urlJogo: '../jogo_4/jogo_4.php',
        anoPadrao: 6
    },
    5: {
        id: 5,
        jogoId: 5,
        nome: 'Álgebra & Equações',
        jogoNome: 'A Balança de Equilíbrio',
        descricao: 'Equações de 1º grau, raciocínio algébrico e igualdades',
        cor: 'var(--duo-teal, #00cd9c)',
        corSombra: 'var(--duo-teal-shadow, #00a880)',
        bgIcone: '#00cd9c',
        sigla: 'ALG',
        urlJogo: '../jogo_5/jogo_5.php',
        anoPadrao: 7
    }
};

// Mapeamento dos 4 Mundos
const MUNDOS_CONFIG = {
    6: { nome: 'Floresta dos Números', ano: 6, icone: 'forest', cor: '#58cc02' },
    7: { nome: 'Deserto das Frações', ano: 7, icone: 'desert', cor: '#ff9600' },
    8: { nome: 'Galáxia da Geometria', ano: 8, icone: 'galaxy', cor: '#1cb0f6' },
    9: { nome: 'Cidadela dos Mestres', ano: 9, icone: 'citadel', cor: '#ff4b4b' }
};

// ─── Inicialização ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    aplicarPreferenciasVisuais();

    if (LOGADO) {
        await Promise.all([
            carregarDadosPerfil(),
            carregarLojaAvatares()
        ]);
    }
});

// ─── Carregamento e Processamento do Dashboard ──────────────────────────────
async function carregarDadosPerfil() {
    let dadosServidor = null;

    try {
        const resp = await fetch('../api/progresso/obter.php', {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });

        if (resp.ok) {
            dadosServidor = await resp.json();
            if (dadosServidor && dadosServidor.progresso) {
                // Sincroniza localmente para consistência offline
                localStorage.setItem(CHAVE_PROGRESSO_FASES, JSON.stringify(dadosServidor.progresso));
            }
        }
    } catch (e) {
        console.warn('MathGame Perfil: Modo offline ou falha ao consultar API. Usando dados locais.', e);
    }

    const mapaProgresso = obterProgressoConsolidado(dadosServidor ? dadosServidor.progresso : null);
    renderizarDashboardCompleto(mapaProgresso);
}

/**
 * Consolida o progresso entre a API e o localStorage.
 */
function obterProgressoConsolidado(progressoApi) {
    let progressoLocal = {};

    try {
        const rawLocal = localStorage.getItem(CHAVE_PROGRESSO_FASES) || localStorage.getItem(CHAVE_PROGRESSO_OLD);
        if (rawLocal) progressoLocal = JSON.parse(rawLocal);
    } catch (e) {
        progressoLocal = {};
    }

    // Se a API trouxe dados, usa a API mesclando com o local
    return { ...progressoLocal, ...(progressoApi || {}) };
}

/**
 * Renderiza todos os blocos do Dashboard do Aluno.
 */
function renderizarDashboardCompleto(progresso) {
    // 1. Estatísticas Globais
    let pontuacaoTotal = 0;
    let estrelasTotal  = 0;
    let fasesConcluidas = 0;
    let fasesPerfeitas  = 0;

    // Estatísticas por Pilar de Conteúdo (Jogos 1 a 5)
    const statsPilares = {
        1: { jogoId: 1, fasesTotal: 0, fasesConcluidas: 0, estrelas: 0, pontuacao: 0, fasesDetalhadas: [] },
        2: { jogoId: 2, fasesTotal: 0, fasesConcluidas: 0, estrelas: 0, pontuacao: 0, fasesDetalhadas: [] },
        3: { jogoId: 3, fasesTotal: 0, fasesConcluidas: 0, estrelas: 0, pontuacao: 0, fasesDetalhadas: [] },
        4: { jogoId: 4, fasesTotal: 0, fasesConcluidas: 0, estrelas: 0, pontuacao: 0, fasesDetalhadas: [] },
        5: { jogoId: 5, fasesTotal: 0, fasesConcluidas: 0, estrelas: 0, pontuacao: 0, fasesDetalhadas: [] }
    };

    // Estatísticas por Ano Letivo (6 ao 9)
    const statsMundos = {
        6: { fasesTotal: 20, fasesConcluidas: 0, estrelas: 0 },
        7: { fasesTotal: 20, fasesConcluidas: 0, estrelas: 0 },
        8: { fasesTotal: 20, fasesConcluidas: 0, estrelas: 0 },
        9: { fasesTotal: 20, fasesConcluidas: 0, estrelas: 0 }
    };

    // Percorre as chaves formatadas "ano_X_fase_Y"
    Object.keys(progresso).forEach(chave => {
        if (chave.startsWith('ano_')) {
            const fase = progresso[chave];
            if (fase && typeof fase === 'object') {
                const pts = Number(fase.pontuacao_max || 0);
                const est = Math.min(3, Math.max(0, Number(fase.estrelas || 0)));
                const jId = Number(fase.jogo_id || 1);
                const ano = Number(fase.ano_letivo || 6);

                pontuacaoTotal += pts;
                estrelasTotal  += est;

                if (est >= 1) fasesConcluidas++;
                if (est === 3) fasesPerfeitas++;

                // Registra no Pilar
                if (statsPilares[jId]) {
                    statsPilares[jId].fasesTotal++;
                    if (est >= 1) statsPilares[jId].fasesConcluidas++;
                    statsPilares[jId].estrelas += est;
                    statsPilares[jId].pontuacao += pts;
                    statsPilares[jId].fasesDetalhadas.push({
                        ano: ano,
                        numero: Number(fase.numero_fase || 1),
                        estrelas: est,
                        pontos: pts,
                        desbloqueado: Boolean(fase.desbloqueado)
                    });
                }

                // Registra no Mundo
                if (statsMundos[ano]) {
                    if (est >= 1) statsMundos[ano].fasesConcluidas++;
                    statsMundos[ano].estrelas += est;
                }
            }
        }
    });

    // 2. Atualiza KPIs do Topo
    atualizarKpis({
        pontuacaoTotal,
        estrelasTotal,
        fasesConcluidas,
        fasesPerfeitas
    });

    // 3. Atualiza Identidade e Patente do Jogador
    atualizarIdentidadeAluno(estrelasTotal, fasesConcluidas);

    // 4. Diagnóstico Inteligente: Onde o Aluno Precisa Evoluir
    renderizarDiagnosticoEvolucao(statsPilares, fasesConcluidas);

    // 5. Painel de Maestria por Conteúdo
    renderizarMaestriaPorConteudo(statsPilares);

    // 6. Desempenho nos Mundos (6º ao 9º Ano)
    renderizarMundosDesempenho(statsMundos);

    // 7. Missões de Reforço
    renderizarMissoesReforco(statsPilares, estrelasTotal, fasesConcluidas);
}

// ─── Atualização dos Cards de KPIs ──────────────────────────────────────────
function atualizarKpis({ pontuacaoTotal, estrelasTotal, fasesConcluidas, fasesPerfeitas }) {
    const elPontos = document.getElementById('perfil-pontuacao');
    const elEstrelas = document.getElementById('perfil-estrelas');
    const elEstrelasPct = document.getElementById('perfil-estrelas-percent');
    const elFases = document.getElementById('perfil-fases-concluidas');
    const elFasesPct = document.getElementById('perfil-fases-percent');
    const elPerfeitas = document.getElementById('perfil-fases-perfeitas');

    if (elPontos) elPontos.textContent = `${pontuacaoTotal.toLocaleString('pt-BR')} pts`;

    if (elEstrelas) {
        elEstrelas.textContent = `${estrelasTotal} / ${TOTAL_ESTRELAS_MAX}`;
    }
    const elSaldo = document.getElementById('perfil-estrelas-saldo');
    if (elSaldo) {
        elSaldo.textContent = `Saldo: ${stateLojaAvatares.saldo} ⭐ disponíveis`;
    }
    if (elEstrelasPct) {
        const pctEstrelas = Math.round((estrelasTotal / TOTAL_ESTRELAS_MAX) * 100);
        elEstrelasPct.textContent = `${pctEstrelas}% do total possível`;
    }

    if (elFases) {
        elFases.textContent = `${fasesConcluidas} / ${TOTAL_FASES_SISTEMA}`;
    }
    if (elFasesPct) {
        const pctFases = Math.round((fasesConcluidas / TOTAL_FASES_SISTEMA) * 100);
        elFasesPct.textContent = `${pctFases}% da trilha concluída`;
    }

    if (elPerfeitas) {
        elPerfeitas.textContent = `${fasesPerfeitas}`;
    }
}

// ─── Atualização de Patente e Identidade ─────────────────────────────────────
function atualizarIdentidadeAluno(estrelas, fases) {
    const elPatente = document.getElementById('perfil-patente-nome');
    const elAproveitamento = document.getElementById('perfil-aproveitamento-geral');

    let patente = 'Calouro dos Números';
    if (estrelas >= 60) {
        patente = 'Grão-Mestre Matemático';
    } else if (estrelas >= 35) {
        patente = 'Mestre dos Cálculos';
    } else if (estrelas >= 18) {
        patente = 'Estrategista Lógico';
    } else if (estrelas >= 8) {
        patente = 'Aprendiz dos Números';
    }

    if (elPatente) elPatente.textContent = patente;

    // Aproveitamento geral: baseado na média de estrelas nas fases que já jogou
    if (elAproveitamento) {
        if (fases === 0) {
            elAproveitamento.textContent = '0%';
        } else {
            const aproveitamento = Math.min(100, Math.round((estrelas / (fases * 3)) * 100));
            elAproveitamento.textContent = `${aproveitamento}%`;
        }
    }
}

// ─── Diagnóstico Inteligente: No que o Aluno Precisa Evoluir ─────────────────
function renderizarDiagnosticoEvolucao(statsPilares, fasesConcluidas) {
    const elTopico = document.getElementById('evolucao-topico-destaque');
    const elExplicacao = document.getElementById('evolucao-explicacao');
    const elTaxa = document.getElementById('evolucao-taxa-dominio');
    const elBtn = document.getElementById('btn-praticar-reforco');
    const elPontosLista = document.getElementById('evolucao-pontos-lista');

    if (!elTopico || !elExplicacao) return;

    // Caso 1: O aluno ainda não jogou nada
    if (fasesConcluidas === 0) {
        elTopico.textContent = 'Fundamentos da Aritmética & Números';
        elExplicacao.textContent = 'Você ainda não concluiu fases na plataforma. Comece pelo 6º Ano para diagnosticar seu desempenho e destravar sua maestria matemática!';
        if (elTaxa) elTaxa.textContent = '0%';
        if (elBtn) {
            elBtn.href = '../conteudos/conteudos.php?ano=6';
            elBtn.innerHTML = `<svg class="icone-svg icone-play-branco" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72a1 1 0 0 0 1.55.83l11-6.86a1 1 0 0 0 0-1.66l-11-6.86A1 1 0 0 0 8 5.14z"/></svg><span>Começar 6º Ano Agora</span>`;
        }
        if (elPontosLista) {
            elPontosLista.innerHTML = `
                <div class="ponto-atencao-item">
                    <div class="ponto-atencao-icone">!</div>
                    <div class="ponto-atencao-info">
                        <h4>Trilha Inicial Bloqueada</h4>
                        <p>Complete a Fase 1 para iniciar seu histórico.</p>
                    </div>
                </div>
                <div class="ponto-atencao-item">
                    <div class="ponto-atencao-icone">★</div>
                    <div class="ponto-atencao-info">
                        <h4>Conquiste até 3 Estrelas</h4>
                        <p>Quanto mais rápido e preciso, maior sua nota.</p>
                    </div>
                </div>
            `;
        }
        return;
    }

    // Caso 2: O aluno já jogou – calcula maestria de cada um dos 5 pilares
    const pilaresCalculados = Object.keys(statsPilares).map(jId => {
        const pilarConfig = PILARES_CONTEUDO[jId];
        const dados = statsPilares[jId];
        const fasesTotal = Math.max(1, dados.fasesTotal);
        const estrelasMaxPossiveis = fasesTotal * 3;
        const estrelasObtidas = dados.estrelas;
        const taxaDominio = Math.round((estrelasObtidas / estrelasMaxPossiveis) * 100);

        // Identifica fases pendentes ou com pontuação fraca
        const fasesComProblema = dados.fasesDetalhadas.filter(f => f.desbloqueado && f.estrelas < 3);

        return {
            ...pilarConfig,
            fasesTotal,
            fasesConcluidas: dados.fasesConcluidas,
            estrelasObtidas,
            taxaDominio,
            fasesComProblema
        };
    });

    // Ordena pelo menor percentual de domínio (o ponto mais fraco vem primeiro)
    pilaresCalculados.sort((a, b) => a.taxaDominio - b.taxaDominio);

    const pontoFraco = pilaresCalculados[0];

    elTopico.textContent = `${pontoFraco.nome}`;
    if (elTaxa) elTaxa.textContent = `${pontoFraco.taxaDominio}%`;

    let textoExplicativo = '';
    if (pontoFraco.taxaDominio < 25) {
        textoExplicativo = `Seu domínio em <strong>${pontoFraco.nome}</strong> está em apenas ${pontoFraco.taxaDominio}%. Praticar no jogo <em>${pontoFraco.jogoNome}</em> é a prioridade número 1 para elevar sua média geral e desbloquear novas fases.`;
    } else if (pontoFraco.taxaDominio < 60) {
        textoExplicativo = `Você já começou a desenvolver <strong>${pontoFraco.nome}</strong> (${pontoFraco.taxaDominio}% de domínio), mas ainda restam fases sem a pontuação máxima. Reforçar este conteúdo acelerará sua evolução!`;
    } else {
        textoExplicativo = `Parabéns pela dedicação! Seu menor índice atual é em <strong>${pontoFraco.nome}</strong> (${pontoFraco.taxaDominio}% de domínio). Ajustar os últimos detalhes aqui deixará seu perfil 100% dominado.`;
    }
    elExplicacao.innerHTML = textoExplicativo;

    // Configura o botão para ir direto ao jogo ou à trilha correspondente
    if (elBtn) {
        elBtn.href = `../conteudos/conteudos.php?ano=${pontoFraco.anoPadrao}`;
        elBtn.innerHTML = `
            <svg class="icone-svg icone-play-branco" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72a1 1 0 0 0 1.55.83l11-6.86a1 1 0 0 0 0-1.66l-11-6.86A1 1 0 0 0 8 5.14z"/></svg>
            <span>Praticar ${pontoFraco.nome}</span>
        `;
    }

    // Renderiza itens de recomendação prática
    if (elPontosLista) {
        const itensHtml = [];

        // Ponto de atenção 1: Fases não finalizadas com 3 estrelas
        if (pontoFraco.fasesComProblema.length > 0) {
            const primeira = pontoFraco.fasesComProblema[0];
            itensHtml.push(`
                <div class="ponto-atencao-item">
                    <div class="ponto-atencao-icone">1</div>
                    <div class="ponto-atencao-info">
                        <h4>Repetir Fase ${primeira.numero} (${primeira.ano}° Ano)</h4>
                        <p>Atualmente com ${primeira.estrelas} ★. Refaça para conquistar 3 estrelas.</p>
                    </div>
                </div>
            `);
        } else {
            itensHtml.push(`
                <div class="ponto-atencao-item">
                    <div class="ponto-atencao-icone">1</div>
                    <div class="ponto-atencao-info">
                        <h4>Avançar para novas fases</h4>
                        <p>Desbloqueie os próximos desafios deste conteúdo na trilha.</p>
                    </div>
                </div>
            `);
        }

        // Ponto de atenção 2: Segundo tópico mais fraco
        const segundoFraco = pilaresCalculados[1];
        if (segundoFraco && segundoFraco.taxaDominio < 80) {
            itensHtml.push(`
                <div class="ponto-atencao-item">
                    <div class="ponto-atencao-icone">2</div>
                    <div class="ponto-atencao-info">
                        <h4>Atenção Secundária: ${segundoFraco.nome}</h4>
                        <p>Aproveitamento atual de ${segundoFraco.taxaDominio}% (${segundoFraco.estrelasObtidas} estrelas).</p>
                    </div>
                </div>
            `);
        }

        // Ponto de atenção 3: Dica pedagógica
        itensHtml.push(`
            <div class="ponto-atencao-item">
                <div class="ponto-atencao-icone">3</div>
                <div class="ponto-atencao-info">
                    <h4>Meta de Maestria: 80%+</h4>
                    <p>Atinja 80% neste pilar para subir sua patente no Mantham.</p>
                </div>
            </div>
        `);

        elPontosLista.innerHTML = itensHtml.join('');
    }
}

// ─── Renderização dos 5 Pilares de Maestria ─────────────────────────────────
function renderizarMaestriaPorConteudo(statsPilares) {
    const container = document.getElementById('container-pilares-maestria');
    if (!container) return;

    let html = '';

    Object.keys(PILARES_CONTEUDO).forEach(id => {
        const pilar = PILARES_CONTEUDO[id];
        const dados = statsPilares[pilar.jogoId] || { fasesTotal: 16, fasesConcluidas: 0, estrelas: 0 };
        const totalFases = Math.max(1, dados.fasesTotal);
        const estrelasMax = totalFases * 3;
        const pctDominio = Math.min(100, Math.round((dados.estrelas / estrelasMax) * 100));

        let tagNivel = 'Iniciante';
        let corBarra = 'var(--duo-orange)';
        if (pctDominio >= 90) {
            tagNivel = 'Mestre';
            corBarra = 'var(--duo-green)';
        } else if (pctDominio >= 70) {
            tagNivel = 'Especialista';
            corBarra = 'var(--duo-blue)';
        } else if (pctDominio >= 40) {
            tagNivel = 'Praticante';
            corBarra = 'var(--duo-yellow-shadow)';
        } else if (pctDominio >= 20) {
            tagNivel = 'Aprendiz';
            corBarra = 'var(--duo-orange)';
        }

        html += `
            <article class="pilar-card" data-pilar-id="${pilar.id}">
                <div class="pilar-identificacao">
                    <div class="pilar-icone" style="background-color: ${pilar.bgIcone};">
                        <span>${pilar.sigla}</span>
                    </div>
                    <div class="pilar-textos">
                        <h3>${pilar.nome}</h3>
                        <span class="pilar-jogo-ref">${pilar.jogoNome}</span>
                    </div>
                </div>

                <div class="pilar-progresso-bloco">
                    <div class="pilar-progresso-info">
                        <span class="pilar-nivel-tag" style="color: ${corBarra};">${tagNivel}</span>
                        <span class="pilar-porcentagem">${pctDominio}% de Domínio</span>
                    </div>
                    <div class="barra-trilha-bg" role="progressbar" aria-valuenow="${pctDominio}" aria-valuemin="0" aria-valuemax="100">
                        <div class="barra-trilha-fill" style="width: ${pctDominio}%; background: ${corBarra};"></div>
                    </div>
                </div>

                <div class="pilar-acao-bloco">
                    <div class="pilar-estrelas-contagem">
                        <span>${dados.estrelas} / ${estrelasMax}</span>
                        <svg class="icone-inline icone-estrela" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    </div>
                    <a href="../conteudos/conteudos.php?ano=${pilar.anoPadrao}" class="btn-treinar-pilar" title="Praticar fases deste conteúdo">
                        <span>Treinar</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72a1 1 0 0 0 1.55.83l11-6.86a1 1 0 0 0 0-1.66l-11-6.86A1 1 0 0 0 8 5.14z"/></svg>
                    </a>
                </div>
            </article>
        `;
    });

    container.innerHTML = html;
}

// ─── Renderização do Progresso por Mundos (6º ao 9º Ano) ────────────────────
function renderizarMundosDesempenho(statsMundos) {
    const container = document.getElementById('container-mundos-desempenho');
    if (!container) return;

    let html = '';

    [6, 7, 8, 9].forEach(ano => {
        const conf = MUNDOS_CONFIG[ano];
        const dados = statsMundos[ano] || { fasesTotal: 20, fasesConcluidas: 0, estrelas: 0 };
        const pctMundo = Math.round((dados.fasesConcluidas / 20) * 100);

        html += `
            <div class="card-mundo-item">
                <div class="mundo-card-topo">
                    <div class="mundo-icone-circulo" style="background-color: ${conf.cor};">
                        ${obterIconeMundoSvg(conf.icone)}
                    </div>
                    <div class="mundo-ano-info">
                        <h4>${ano}° Ano</h4>
                        <span>${conf.nome}</span>
                    </div>
                </div>

                <div class="mundo-fases-status">
                    <span>Fases: ${dados.fasesConcluidas} / 20</span>
                    <span>${pctMundo}%</span>
                </div>

                <div class="mundo-barra-bg">
                    <div class="mundo-barra-fill" style="width: ${pctMundo}%; background-color: ${conf.cor};"></div>
                </div>

                <div class="mundo-estrelas-linha">
                    <span style="color: var(--duo-yellow-shadow);">★ ${dados.estrelas} estrelas</span>
                    <a href="../conteudos/conteudos.php?ano=${ano}" class="mundo-link-acessar">Ver Trilha →</a>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// ─── Renderização das Missões de Reforço ────────────────────────────────────
function renderizarMissoesReforco(statsPilares, totalEstrelas, totalFases) {
    const container = document.getElementById('container-missoes-reforco');
    if (!container) return;

    // Missão 1: Evolução no ponto mais fraco
    const pilarFracoes = statsPilares[1] || { estrelas: 0 };
    const pilarFracoesPct = Math.min(100, Math.round((pilarFracoes.estrelas / 24) * 100));

    // Missão 2: Conquistador de Estrelas (meta de 20 estrelas)
    const metaEstrelas = 20;
    const estrelasProgresso = Math.min(metaEstrelas, totalEstrelas);
    const estrelasPct = Math.min(100, Math.round((estrelasProgresso / metaEstrelas) * 100));

    // Missão 3: Desbravador de Fases (meta de 10 fases)
    const metaFases = 10;
    const fasesProgresso = Math.min(metaFases, totalFases);
    const fasesPct = Math.min(100, Math.round((fasesProgresso / metaFases) * 100));

    container.innerHTML = `
        <div class="card-missao">
            <div class="missao-header">
                <div class="missao-icone">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </div>
                <div class="missao-titulos">
                    <h4>Mestre das Frações</h4>
                    <p>Conquiste ao menos 24 estrelas nas fases de Fatia Maluca.</p>
                </div>
            </div>
            <div class="missao-progresso-linha">
                <div class="missao-progresso-textos">
                    <span>Progresso</span>
                    <span>${pilarFracoes.estrelas} / 24 ★</span>
                </div>
                <div class="missao-barra-bg">
                    <div class="missao-barra-fill" style="width: ${pilarFracoesPct}%;"></div>
                </div>
            </div>
        </div>

        <div class="card-missao">
            <div class="missao-header">
                <div class="missao-icone" style="background: rgba(255, 200, 0, 0.15); color: var(--duo-yellow-shadow);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <div class="missao-titulos">
                    <h4>Caçador de Estrelas</h4>
                    <p>Acumule 20 estrelas no total para subir sua patente.</p>
                </div>
            </div>
            <div class="missao-progresso-linha">
                <div class="missao-progresso-textos">
                    <span>Progresso</span>
                    <span>${estrelasProgresso} / ${metaEstrelas} ★</span>
                </div>
                <div class="missao-barra-bg">
                    <div class="missao-barra-fill" style="width: ${estrelasPct}%; background: var(--duo-yellow-shadow);"></div>
                </div>
            </div>
        </div>

        <div class="card-missao">
            <div class="missao-header">
                <div class="missao-icone" style="background: rgba(88, 204, 2, 0.15); color: var(--duo-green);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="missao-titulos">
                    <h4>Desbravador da Trilha</h4>
                    <p>Complete as primeiras 10 fases do Ensino Fundamental.</p>
                </div>
            </div>
            <div class="missao-progresso-linha">
                <div class="missao-progresso-textos">
                    <span>Progresso</span>
                    <span>${fasesProgresso} / ${metaFases} fases</span>
                </div>
                <div class="missao-barra-bg">
                    <div class="missao-barra-fill" style="width: ${fasesPct}%; background: var(--duo-green);"></div>
                </div>
            </div>
        </div>
    `;
}

// ─── Helpers de Ícones SVG ──────────────────────────────────────────────────
function obterIconeMundoSvg(tipo) {
    switch (tipo) {
        case 'forest':
            return `<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 2-6 8h3l-4 7h14l-4-7h3l-6-8zM11 19h2v4h-2z"/></svg>`;
        case 'desert':
            return `<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a5 5 0 0 0-5 5v5H5a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v4h10v-4h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2h-2V7a5 5 0 0 0-5-5z"/></svg>`;
        case 'galaxy':
            return `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="7" fill="currentColor"/><ellipse cx="12" cy="12" rx="11" ry="4" transform="rotate(-25 12 12)"/></svg>`;
        case 'citadel':
            return `<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 1 2 6v2h20V6L12 1zm-8 8v9h3V9H4zm5 0v9h3V9H9zm5 0v9h3V9h-3zm5 0v9h3V9h-3zM2 20v2h20v-2H2z"/></svg>`;
        default:
            return '';
    }
}

// ─── Logout Seguro ───────────────────────────────────────────────────────────
async function fazerLogout() {
    try {
        await fetch('../api/auth/logout.php', { method: 'POST', credentials: 'same-origin' });
        localStorage.removeItem(CHAVE_PROGRESSO_FASES);
        localStorage.removeItem(CHAVE_PROGRESSO_OLD);
        localStorage.removeItem('mg_usuario');
        window.location.href = '../home/home.php';
    } catch (e) {
        console.error('Erro ao fazer logout', e);
        window.location.href = '../home/home.php';
    }
}

// ─── Preferências Visuais ────────────────────────────────────────────────────
function aplicarPreferenciasVisuais() {
    const tema = localStorage.getItem('mg_tema');
    const daltonismo = localStorage.getItem('mg_daltonismo');
    const tonalidade = localStorage.getItem('mg_tonalidade');
    if (tema === 'escuro') document.body.classList.add('tema-escuro');
    if (daltonismo) document.body.classList.add(`daltonismo-${daltonismo}`);
    if (tonalidade && tonalidade !== 'padrao') document.body.classList.add(`tonalidade-${tonalidade}`);
}

// ─── Loja de Fotos de Perfil & Moeda de Estrelas (Alfred UI/UX) ─────────────
const CHAVE_AVATARES_DESBLOQUEADOS = 'mg_avatares_desbloqueados';
const CHAVE_AVATAR_EQUIPADO = 'mg_avatar_equipado';

const stateLojaAvatares = {
    saldo: 0,
    totalConquistadas: 0,
    totalGasto: 0,
    fotoAtual: 'foto1.webp',
    fotos: [],
    fotoSelecionadaId: null,
    carregado: false
};

/**
 * Carrega os dados da loja de avatares e da carteira de estrelas.
 */
async function carregarLojaAvatares() {
    let dadosOk = false;

    try {
        const resp = await fetch('../api/avatar/dados.php', {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });

        if (resp.ok) {
            const dados = await resp.json();
            if (dados && dados.ok) {
                stateLojaAvatares.saldo = Number(dados.saldo_estrelas || 0);
                stateLojaAvatares.totalConquistadas = Number(dados.total_estrelas_conquistadas || 0);
                stateLojaAvatares.totalGasto = Number(dados.total_gasto || 0);
                stateLojaAvatares.fotoAtual = dados.foto_atual || 'foto1.webp';
                stateLojaAvatares.fotos = dados.fotos || [];
                dadosOk = true;

                // Cache local para resiliência
                localStorage.setItem(CHAVE_AVATAR_EQUIPADO, stateLojaAvatares.fotoAtual);
                const desbloqueadas = stateLojaAvatares.fotos.filter(f => f.desbloqueada).map(f => f.id);
                localStorage.setItem(CHAVE_AVATARES_DESBLOQUEADOS, JSON.stringify(desbloqueadas));
            }
        }
    } catch (e) {
        console.warn('MathGame Loja Avatares: Usando fallback local para carteira de estrelas.', e);
    }

    if (!dadosOk) {
        carregarLojaAvataresLocal();
    }

    stateLojaAvatares.carregado = true;
    atualizarSaldoEstrelasUI();
    renderizarCarrosselAvatares();
    configurarControlesCarrossel();
}

/**
 * Fallback offline / local para gerenciar saldo e avatares se o servidor falhar.
 */
function carregarLojaAvataresLocal() {
    // 1. Calcula estrelas acumuladas a partir do progresso local
    let estrelasGanhas = 0;
    try {
        const rawProg = localStorage.getItem(CHAVE_PROGRESSO_FASES) || localStorage.getItem(CHAVE_PROGRESSO_OLD);
        if (rawProg) {
            const prog = JSON.parse(rawProg);
            Object.values(prog).forEach(item => {
                if (item && item.estrelas) estrelasGanhas += Math.min(3, Math.max(0, Number(item.estrelas)));
            });
        }
    } catch (e) {
        estrelasGanhas = 0;
    }

    // 2. Lê avatares comprados localmente
    let desbloqueadas = ['foto1.webp'];
    try {
        const rawDesb = localStorage.getItem(CHAVE_AVATARES_DESBLOQUEADOS);
        if (rawDesb) {
            const arr = JSON.parse(rawDesb);
            if (Array.isArray(arr)) desbloqueadas = Array.from(new Set(['foto1.webp', ...arr]));
        }
    } catch (e) {
        desbloqueadas = ['foto1.webp'];
    }

    const fotoAtualSalva = localStorage.getItem(CHAVE_AVATAR_EQUIPADO) || 'foto1.webp';
    stateLojaAvatares.fotoAtual = fotoAtualSalva;

    // Foto 1 é grátis, cada outra foto comprada gastou 10 estrelas
    const qtdCompradas = desbloqueadas.filter(f => f !== 'foto1.webp').length;
    const totalGasto = qtdCompradas * 10;
    const saldo = Math.max(0, estrelasGanhas - totalGasto);

    stateLojaAvatares.totalConquistadas = estrelasGanhas;
    stateLojaAvatares.totalGasto = totalGasto;
    stateLojaAvatares.saldo = saldo;

    // Monta lista de fotos (1 a 9)
    stateLojaAvatares.fotos = [];
    for (let i = 1; i <= 9; i++) {
        const nome = `foto${i}.webp`;
        const isPadrao = (i === 1);
        const desbloq = isPadrao || desbloqueadas.includes(nome);
        const equip = (nome === stateLojaAvatares.fotoAtual);

        stateLojaAvatares.fotos.push({
            id: nome,
            numero: i,
            titulo: isPadrao ? 'Foto 1 (Padrão)' : `Avatar #${i}`,
            url: `../fotos/${nome}`,
            preco: isPadrao ? 0 : 10,
            padrao: isPadrao,
            desbloqueada: desbloq,
            equipada: equip,
            disponivel: true
        });
    }
}

/**
 * Atualiza os valores visuais do saldo de estrelas na tela (Badge e KPI).
 */
function atualizarSaldoEstrelasUI() {
    const elSaldoValor = document.getElementById('avatar-saldo-valor');
    if (elSaldoValor) {
        elSaldoValor.textContent = stateLojaAvatares.saldo;
    }

    const elSaldoKpi = document.getElementById('perfil-estrelas-saldo');
    if (elSaldoKpi) {
        elSaldoKpi.textContent = `Saldo: ${stateLojaAvatares.saldo} ⭐ gastáveis`;
    }
}

/**
 * Renderiza os itens de avatar dentro do trilho do carrossel.
 */
function renderizarCarrosselAvatares() {
    const track = document.getElementById('carrossel-avatares-track');
    if (!track) return;

    track.innerHTML = '';

    // Se nenhuma foto estiver selecionada, seleciona a foto atualmente equipada
    if (!stateLojaAvatares.fotoSelecionadaId) {
        stateLojaAvatares.fotoSelecionadaId = stateLojaAvatares.fotoAtual;
    }

    stateLojaAvatares.fotos.forEach(foto => {
        const card = document.createElement('div');
        card.className = 'avatar-item-card';
        card.setAttribute('role', 'button');
        card.setAttribute('tabindex', '0');
        card.setAttribute('aria-label', `${foto.titulo}. ${foto.equipada ? 'Em uso' : (foto.desbloqueada ? 'Disponível' : 'Custa 10 estrelas')}`);
        card.dataset.fotoId = foto.id;

        if (foto.equipada) card.classList.add('equipada');
        if (foto.id === stateLojaAvatares.fotoSelecionadaId) card.classList.add('selecionado');

        // Badge de status
        let badgeHtml = '';
        if (foto.equipada) {
            badgeHtml = '<span class="badge-item-status badge-equipada">Uso</span>';
        } else if (foto.padrao) {
            badgeHtml = '<span class="badge-item-status badge-padrao">Grátis</span>';
        } else if (foto.desbloqueada) {
            badgeHtml = '<span class="badge-item-status badge-adquirida">✓</span>';
        } else {
            badgeHtml = '<span class="badge-item-status badge-preco">10 ⭐</span>';
        }

        card.innerHTML = `
            <div class="avatar-item-img-wrapper">
                <img src="${foto.url}" alt="${foto.titulo}" width="60" height="60" loading="lazy">
            </div>
            ${badgeHtml}
        `;

        card.addEventListener('click', () => {
            selecionarAvatar(foto.id);
        });

        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                selecionarAvatar(foto.id);
            }
        });

        track.appendChild(card);
    });

    // Atualiza a gaveta com o item selecionado
    atualizarDrawerAvatarSelecionado();
}

/**
 * Seleciona um avatar no carrossel, centraliza na visão e atualiza a gaveta de ação.
 */
function selecionarAvatar(fotoId) {
    stateLojaAvatares.fotoSelecionadaId = fotoId;

    const track = document.getElementById('carrossel-avatares-track');
    if (track) {
        const cards = track.querySelectorAll('.avatar-item-card');
        cards.forEach(c => {
            if (c.dataset.fotoId === fotoId) {
                c.classList.add('selecionado');
                c.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            } else {
                c.classList.remove('selecionado');
            }
        });
    }

    atualizarDrawerAvatarSelecionado();
}

/**
 * Atualiza os botões e informações da gaveta de ação para o avatar selecionado.
 */
function atualizarDrawerAvatarSelecionado() {
    const foto = stateLojaAvatares.fotos.find(f => f.id === stateLojaAvatares.fotoSelecionadaId);
    if (!foto) return;

    const elMini = document.getElementById('avatar-selecionado-mini');
    const elNome = document.getElementById('avatar-selecionado-nome');
    const elStatus = document.getElementById('avatar-selecionado-status');

    const btnComprar = document.getElementById('btn-comprar-avatar');
    const btnComprarTexto = document.getElementById('btn-comprar-texto');
    const btnEquipar = document.getElementById('btn-equipar-avatar');
    const btnEquiparTexto = document.getElementById('btn-equipar-texto');
    const badgeEmUso = document.getElementById('badge-avatar-em-uso');
    const elFeedback = document.getElementById('avatar-feedback-msg');

    if (elMini) {
        elMini.src = foto.url;
        elMini.alt = foto.titulo;
    }

    if (elNome) {
        elNome.textContent = foto.titulo;
    }

    if (elFeedback) {
        elFeedback.style.display = 'none';
        elFeedback.className = 'avatar-feedback-msg';
    }

    // 1. Caso a foto já seja a foto em uso
    if (foto.equipada) {
        if (elStatus) elStatus.textContent = 'Este é o seu avatar ativo no momento.';
        if (btnComprar) btnComprar.style.display = 'none';
        if (btnEquipar) btnEquipar.style.display = 'none';
        if (badgeEmUso) badgeEmUso.style.display = 'flex';
        return;
    }

    // 2. Caso a foto esteja desbloqueada (incluindo Foto 1 padrão ou foto comprada)
    if (foto.desbloqueada) {
        if (elStatus) {
            elStatus.textContent = foto.padrao
                ? 'Foto padrão do jogo (gratuita e ilimitada).'
                : 'Já adquirida em sua conta. Pronta para equipar!';
        }
        if (btnComprar) btnComprar.style.display = 'none';
        if (badgeEmUso) badgeEmUso.style.display = 'none';

        if (btnEquipar) {
            btnEquipar.style.display = 'flex';
            btnEquipar.disabled = false;
            if (btnEquiparTexto) {
                btnEquiparTexto.textContent = foto.padrao ? 'Voltar para Foto Padrão' : 'Equipar este Avatar';
            }
        }
        return;
    }

    // 3. Caso a foto esteja bloqueada (precisa de 10 estrelas para comprar)
    if (badgeEmUso) badgeEmUso.style.display = 'none';
    if (btnEquipar) btnEquipar.style.display = 'none';

    if (btnComprar) {
        btnComprar.style.display = 'flex';
        const temEstrelasSuficientes = stateLojaAvatares.saldo >= 10;

        if (temEstrelasSuficientes) {
            btnComprar.disabled = false;
            if (btnComprarTexto) btnComprarTexto.textContent = 'Comprar por 10 Estrelas';
            if (elStatus) elStatus.textContent = `Custo: 10 estrelas. Seu saldo: ${stateLojaAvatares.saldo} ⭐`;
        } else {
            btnComprar.disabled = true;
            const faltam = 10 - stateLojaAvatares.saldo;
            if (btnComprarTexto) btnComprarTexto.textContent = `Faltam ${faltam} ⭐ para comprar`;
            if (elStatus) elStatus.textContent = `Custo: 10 estrelas. Jogue fases para conquistar mais ${faltam} ⭐!`;
        }
    }
}

/**
 * Configura os botões de navegação e as ações de compra/equipar.
 */
function configurarControlesCarrossel() {
    const btnAnt = document.getElementById('btn-carrossel-ant');
    const btnProx = document.getElementById('btn-carrossel-prox');
    const track = document.getElementById('carrossel-avatares-track');

    if (btnAnt && track) {
        btnAnt.onclick = () => track.scrollBy({ left: -140, behavior: 'smooth' });
    }

    if (btnProx && track) {
        btnProx.onclick = () => track.scrollBy({ left: 140, behavior: 'smooth' });
    }

    const btnComprar = document.getElementById('btn-comprar-avatar');
    if (btnComprar) {
        btnComprar.onclick = executarCompraAvatar;
    }

    const btnEquipar = document.getElementById('btn-equipar-avatar');
    if (btnEquipar) {
        btnEquipar.onclick = executarEquiparAvatar;
    }
}

/**
 * Executa a compra de um avatar bloqueado por 10 estrelas.
 */
async function executarCompraAvatar() {
    const foto = stateLojaAvatares.fotos.find(f => f.id === stateLojaAvatares.fotoSelecionadaId);
    if (!foto || foto.desbloqueada) return;

    const btnComprar = document.getElementById('btn-comprar-avatar');
    const btnComprarTexto = document.getElementById('btn-comprar-texto');
    const elFeedback = document.getElementById('avatar-feedback-msg');

    if (btnComprar) btnComprar.disabled = true;
    if (btnComprarTexto) btnComprarTexto.textContent = 'Processando compra...';

    try {
        const resp = await fetch('../api/avatar/comprar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ foto: foto.id })
        });

        const dados = await resp.json();

        if (resp.ok && dados.ok) {
            // Sucesso na API
            stateLojaAvatares.saldo = Number(dados.saldo_estrelas !== undefined ? dados.saldo_estrelas : Math.max(0, stateLojaAvatares.saldo - 10));
            foto.desbloqueada = true;
            aplicarAvatarEquipado(foto.id);

            // Atualiza cache local
            const desbloqueadas = stateLojaAvatares.fotos.filter(f => f.desbloqueada).map(f => f.id);
            localStorage.setItem(CHAVE_AVATARES_DESBLOQUEADOS, JSON.stringify(desbloqueadas));
            localStorage.setItem(CHAVE_AVATAR_EQUIPADO, foto.id);

            exibirFeedbackAvatar(dados.mensagem || 'Avatar adquirido e equipado com sucesso!', 'sucesso');
        } else {
            // Se falhou no backend (ex: offline no local)
            if (!resp.ok && resp.status >= 500) {
                // Fallback offline resiliente
                if (stateLojaAvatares.saldo >= 10) {
                    stateLojaAvatares.saldo -= 10;
                    foto.desbloqueada = true;
                    aplicarAvatarEquipado(foto.id);

                    const desbloqueadas = stateLojaAvatares.fotos.filter(f => f.desbloqueada).map(f => f.id);
                    localStorage.setItem(CHAVE_AVATARES_DESBLOQUEADOS, JSON.stringify(desbloqueadas));
                    localStorage.setItem(CHAVE_AVATAR_EQUIPADO, foto.id);

                    exibirFeedbackAvatar('Avatar adquirido e equipado com sucesso (modo offline)!', 'sucesso');
                } else {
                    exibirFeedbackAvatar(dados.erro || 'Estrelas insuficientes para comprar este avatar.', 'erro');
                }
            } else {
                exibirFeedbackAvatar(dados.erro || 'Não foi possível concluir a compra.', 'erro');
            }
        }
    } catch (e) {
        console.warn('MathGame: Erro de rede ao comprar avatar. Tentando modo offline.', e);
        if (stateLojaAvatares.saldo >= 10) {
            stateLojaAvatares.saldo -= 10;
            foto.desbloqueada = true;
            aplicarAvatarEquipado(foto.id);

            const desbloqueadas = stateLojaAvatares.fotos.filter(f => f.desbloqueada).map(f => f.id);
            localStorage.setItem(CHAVE_AVATARES_DESBLOQUEADOS, JSON.stringify(desbloqueadas));
            localStorage.setItem(CHAVE_AVATAR_EQUIPADO, foto.id);

            exibirFeedbackAvatar('Avatar adquirido e equipado com sucesso!', 'sucesso');
        } else {
            exibirFeedbackAvatar('Falha ao conectar com o servidor para comprar o avatar.', 'erro');
        }
    }

    atualizarSaldoEstrelasUI();
    renderizarCarrosselAvatares();
}

/**
 * Equipa um avatar já adquirido (ou a Foto 1 padrão).
 */
async function executarEquiparAvatar() {
    const foto = stateLojaAvatares.fotos.find(f => f.id === stateLojaAvatares.fotoSelecionadaId);
    if (!foto || !foto.desbloqueada) return;

    const btnEquipar = document.getElementById('btn-equipar-avatar');
    const btnEquiparTexto = document.getElementById('btn-equipar-texto');

    if (btnEquipar) btnEquipar.disabled = true;
    if (btnEquiparTexto) btnEquiparTexto.textContent = 'Equipando...';

    try {
        const resp = await fetch('../api/avatar/equipar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ foto: foto.id })
        });

        const dados = await resp.json();

        if (resp.ok && dados.ok) {
            aplicarAvatarEquipado(foto.id);
            localStorage.setItem(CHAVE_AVATAR_EQUIPADO, foto.id);
            exibirFeedbackAvatar(dados.mensagem || 'Avatar equipado com sucesso!', 'sucesso');
        } else {
            // Fallback offline
            aplicarAvatarEquipado(foto.id);
            localStorage.setItem(CHAVE_AVATAR_EQUIPADO, foto.id);
            exibirFeedbackAvatar(foto.padrao ? 'Foto padrão restaurada!' : 'Avatar equipado com sucesso!', 'sucesso');
        }
    } catch (e) {
        console.warn('MathGame: Falha de rede ao equipar avatar. Aplicando localmente.', e);
        aplicarAvatarEquipado(foto.id);
        localStorage.setItem(CHAVE_AVATAR_EQUIPADO, foto.id);
        exibirFeedbackAvatar('Avatar equipado com sucesso!', 'sucesso');
    }

    renderizarCarrosselAvatares();
}

/**
 * Atualiza o estado da foto equipada na tela e nos componentes do site.
 */
function aplicarAvatarEquipado(fotoId) {
    stateLojaAvatares.fotoAtual = fotoId;

    stateLojaAvatares.fotos.forEach(f => {
        f.equipada = (f.id === fotoId);
    });

    const caminhoNovaFoto = `../fotos/${fotoId}`;

    // Atualiza a foto principal do perfil com animação suave
    const imgPerfil = document.getElementById('perfil-foto');
    if (imgPerfil) {
        imgPerfil.style.transform = 'scale(0.85) rotate(-5deg)';
        setTimeout(() => {
            imgPerfil.src = caminhoNovaFoto;
            imgPerfil.style.transform = 'scale(1) rotate(0deg)';
        }, 150);
    }

    // Atualiza a foto na navbar se estiver visível
    const imgNav = document.querySelector('#nav-perfil-chip img');
    if (imgNav) {
        imgNav.src = caminhoNovaFoto;
    }
}

/**
 * Exibe mensagem de feedback visual (sucesso ou erro).
 */
function exibirFeedbackAvatar(msg, tipo = 'sucesso') {
    const elFeedback = document.getElementById('avatar-feedback-msg');
    if (!elFeedback) return;

    elFeedback.textContent = msg;
    elFeedback.className = `avatar-feedback-msg ${tipo}`;
    elFeedback.style.display = 'block';

    setTimeout(() => {
        if (elFeedback && elFeedback.textContent === msg) {
            elFeedback.style.display = 'none';
        }
    }, 4500);
}

