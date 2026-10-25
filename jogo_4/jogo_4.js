/**
 * jogo_4.js
 * Lógica do Jogo 4: Sucessores e Antecessores
 * Componente customizado de campos de escolha integrados ao texto, popover flutuante,
 * validação matemática, animação de correção de erros e barra de progresso.
 */

// ─── Constantes do Sistema ───────────────────────────────────────────────────
const CHAVE_PARAMS = 'mg_params_jogo';
const CHAVE_PROGRESSO = 'mg_progresso_fases';
const CHAVE_RECORDE_LIVRE = 'mg_recorde_livre';

const CONFIG_DIFICULDADE = {
    facil: { multiplicador: 1.0 },
    medio: { multiplicador: 1.5 },
    experiente: { multiplicador: 2.0 }
};

// ─── Estado Global do Jogo ────────────────────────────────────────────────────
let params = lerParams();
let questoesPartida = [];
let questaoAtualIndex = 0;
let questaoAtual = null;
let pontuacao = 0;
let acertos = 0;
let jogoAtivo = true;

// Estado da Questão Atual
let respostasUsuario = {}; // { campo_id: 'valor' }
let aguardandoAvancoAposErro = false;
let campoAbertoAtivo = null;

// ─── Inicialização ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    aplicarPreferenciasVisuais();
    configurarEventosGlobais();
    carregarPartida();
});

function aplicarPreferenciasVisuais() {
    const tema = localStorage.getItem('mg_tema');
    const daltonismo = localStorage.getItem('mg_daltonismo');
    const tonalidade = localStorage.getItem('mg_tonalidade');
    if (tema === 'escuro') document.body.classList.add('tema-escuro');
    if (daltonismo) document.body.classList.add(`daltonismo-${daltonismo}`);
    if (tonalidade && tonalidade !== 'padrao') document.body.classList.add(`tonalidade-${tonalidade}`);
}

function lerParams() {
    let p = { modo: 'livre', dificuldade: 'facil', ano: 6, fase: 1, jogoId: 4 };
    try {
        const raw = localStorage.getItem(CHAVE_PARAMS);
        if (raw) p = Object.assign(p, JSON.parse(raw));
    } catch (e) {
        // ignora
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('ano')) p.ano = parseInt(urlParams.get('ano'), 10);
    if (urlParams.has('fase')) {
        p.fase = parseInt(urlParams.get('fase'), 10);
        p.modo = 'trilha';
    }
    if (urlParams.has('dificuldade')) p.dificuldade = urlParams.get('dificuldade');
    if (urlParams.has('modo')) p.modo = urlParams.get('modo');

    return p;
}

// ─── Carregamento das Questões ────────────────────────────────────────────────
async function carregarPartida() {
    const dif = params.dificuldade || 'facil';
    try {
        const response = await fetch(`gerar_questao.php?dificuldade=${encodeURIComponent(dif)}`);
        const dados = await response.json();
        if (dados.ok && dados.questoes.length > 0) {
            questoesPartida = dados.questoes;
            questaoAtualIndex = 0;
            pontuacao = 0;
            acertos = 0;
            jogoAtivo = true;
            atualizarBarraProgresso(0);
            iniciarQuestao();
        } else {
            console.error('Erro ao carregar questões do jogo.');
            const enTexto = document.getElementById('enunciado-texto');
            if (enTexto) enTexto.textContent = 'Erro ao carregar questões do jogo. Tente recarregar a página.';
        }
    } catch (e) {
        console.error('Falha ao conectar ao servidor:', e);
    }
}

function iniciarQuestao() {
    dicaIndiceJogo4 = 0;
    ocultarDica();

    if (questaoAtualIndex >= questoesPartida.length) {
        finalizarJogo();
        return;
    }

    questaoAtual = questoesPartida[questaoAtualIndex];
    respostasUsuario = {};
    aguardandoAvancoAposErro = false;
    timerPontuacaoQuestao.iniciar();
    fecharPopover();

    const btnConfirmar = document.getElementById('btn-confirmar');
    btnConfirmar.disabled = true;
    btnConfirmar.textContent = 'Confirmar';

    renderizarEnunciadoComCampos();
}

// ─── Renderização do Enunciado com os Quadrados Interativos ───────────────────
function renderizarEnunciadoComCampos() {
    const elContainer = document.getElementById('enunciado-texto');
    elContainer.innerHTML = '';

    let template = questaoAtual.enunciadoTemplate;

    // Substitui cada {campo_id} pelo componente interativo
    questaoAtual.campos.forEach(campo => {
        const marcador = `{${campo.id}}`;
        const parts = template.split(marcador);
        if (parts.length === 2) {
            template = parts[0] + `<span class="campo-escolha vazio" id="${campo.id}" data-campo-id="${campo.id}" tabindex="0" role="button" aria-haspopup="listbox" aria-label="Selecione uma opção"></span>` + parts[1];
        }
    });

    elContainer.innerHTML = template;

    // Adiciona ouvintes de clique e teclado aos campos criados
    questaoAtual.campos.forEach(campo => {
        const el = document.getElementById(campo.id);
        if (el) {
            el.addEventListener('click', (e) => {
                e.stopPropagation();
                abrirMenuOpcoes(campo, el);
            });
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    abrirMenuOpcoes(campo, el);
                }
            });
        }
    });
}

// ─── Popover Flutuante de Escolha (Dropdown) ──────────────────────────────────
function abrirMenuOpcoes(campo, elementoDisparador) {
    if (!jogoAtivo || aguardandoAvancoAposErro) return;

    if (campoAbertoAtivo === elementoDisparador) {
        fecharPopover();
        return;
    }

    fecharPopover();

    campoAbertoAtivo = elementoDisparador;
    elementoDisparador.classList.add('aberto');

    const popover = document.getElementById('popover-escolha');
    const lista = document.getElementById('popover-lista');
    lista.innerHTML = '';

    campo.opcoes.forEach(opcao => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'popover-item';
        if (respostasUsuario[campo.id] === opcao) {
            btn.classList.add('selecionado');
        }
        btn.textContent = opcao;
        btn.onclick = (e) => {
            e.stopPropagation();
            selecionarOpcao(campo.id, opcao, elementoDisparador);
        };
        lista.appendChild(btn);
    });

    // Posiciona o popover logo abaixo do quadrado (usando boundingClientRect)
    const rect = elementoDisparador.getBoundingClientRect();
    popover.style.display = 'block';
    popover.setAttribute('aria-hidden', 'false');

    // Centraliza horizontalmente em relação ao elemento disparador
    const popoverWidth = popover.offsetWidth || 160;
    let leftPos = rect.left + (rect.width / 2) - (popoverWidth / 2);
    // Garante que não ultrapasse as bordas da tela
    leftPos = Math.max(12, Math.min(window.innerWidth - popoverWidth - 12, leftPos));

    popover.style.top = `${rect.bottom + 8}px`;
    popover.style.left = `${leftPos}px`;
}

function selecionarOpcao(campoId, opcao, elementoDisparador) {
    respostasUsuario[campoId] = opcao;

    elementoDisparador.textContent = opcao;
    elementoDisparador.classList.remove('vazio');
    elementoDisparador.classList.remove('piscar-erro');

    fecharPopover();
    verificarTodosPreenchidos();
}

function fecharPopover() {
    const popover = document.getElementById('popover-escolha');
    if (popover) {
        popover.style.display = 'none';
        popover.setAttribute('aria-hidden', 'true');
    }
    if (campoAbertoAtivo) {
        campoAbertoAtivo.classList.remove('aberto');
        campoAbertoAtivo = null;
    }
}

function configurarEventosGlobais() {
    // Fecha popover ao clicar fora
    document.addEventListener('click', (e) => {
        const popover = document.getElementById('popover-escolha');
        if (popover && !popover.contains(e.target)) {
            fecharPopover();
        }
    });

    // Fecha popover ao pressionar Esc
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            fecharPopover();
        }
    });

    // Reposiciona popover se a janela for redimensionada
    window.addEventListener('resize', () => {
        fecharPopover();
    });
}

function verificarTodosPreenchidos() {
    const btnConfirmar = document.getElementById('btn-confirmar');
    if (!questaoAtual) return;

    const todosPreenchidos = questaoAtual.campos.every(campo => {
        return Boolean(respostasUsuario[campo.id]);
    });

    btnConfirmar.disabled = !todosPreenchidos;
}

// ─── Fluxo de Confirmação e Validação ─────────────────────────────────────────
function confirmarResposta() {
    if (!jogoAtivo) return;

    // Se o jogador já errou, visualizou a correção automática e clicou em Confirmar novamente
    if (aguardandoAvancoAposErro) {
        avancarParaProximaQuestao();
        return;
    }

    const btnConfirmar = document.getElementById('btn-confirmar');
    btnConfirmar.disabled = true;

    // Validação de todos os campos contra a resposta matematicamente calculada
    let todosCorretos = true;
    const camposErrados = [];

    questaoAtual.campos.forEach(campo => {
        const respostaDada = respostasUsuario[campo.id];
        const correta = campo.respostaCorreta;

        if (respostaDada !== correta) {
            todosCorretos = false;
            camposErrados.push({
                elemento: document.getElementById(campo.id),
                correta: correta
            });
        }
    });

    const barra = document.getElementById('barra-progresso');

    if (todosCorretos) {
        // Acertou de primeira
        acertos++;
        const mult = CONFIG_DIFICULDADE[params.dificuldade]?.multiplicador || 1;
        const bonusTempo = timerPontuacaoQuestao.multiplicador();
        pontuacao += Math.round(100 * mult * bonusTempo);

        if (barra) {
            barra.classList.remove('erro');
            barra.classList.add('acerto');
        }

        // Destaca campos como corretos
        questaoAtual.campos.forEach(campo => {
            const el = document.getElementById(campo.id);
            if (el) el.classList.add('corrigido');
        });

        setTimeout(() => {
            avancarParaProximaQuestao();
        }, 800);

    } else {
        // Errou: campos errados piscam em vermelho e trocam automaticamente para a opção correta
        if (barra) {
            barra.classList.remove('acerto');
            barra.classList.add('erro');
        }

        camposErrados.forEach(item => {
            if (item.elemento) {
                item.elemento.classList.add('piscar-erro');
            }
        });

        // Após breve piscar, substitui automaticamente pela resposta correta
        setTimeout(() => {
            camposErrados.forEach(item => {
                if (item.elemento) {
                    item.elemento.classList.remove('piscar-erro');
                    item.elemento.classList.add('corrigido');
                    item.elemento.textContent = item.correta;
                }
            });

            // Libera o botão Confirmar para avançar
            aguardandoAvancoAposErro = true;
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = (questaoAtualIndex === questoesPartida.length - 1)
                ? 'Concluir Partida'
                : 'Confirmar e Avançar';
        }, 1100);
    }
}

function avancarParaProximaQuestao() {
    questaoAtualIndex++;
    atualizarBarraProgresso(questaoAtualIndex);

    const barra = document.getElementById('barra-progresso');
    if (barra) {
        barra.classList.remove('erro');
        barra.classList.add('acerto');
    }

    if (questaoAtualIndex >= questoesPartida.length) {
        finalizarJogo();
    } else {
        iniciarQuestao();
    }
}

function atualizarBarraProgresso(concluidas) {
    const barra = document.getElementById('barra-progresso');
    if (barra && questoesPartida.length > 0) {
        const pct = (concluidas / questoesPartida.length) * 100;
        barra.style.width = `${pct}%`;
    }
}

// ─── Dicas Flutuantes ────────────────────────────────────────────────────────
let dicaIndiceJogo4 = 0;
let dicaTimerJogo4 = null;

function solicitarDica() {
    if (!questaoAtual) return;

    const dicaEspecifica = questaoAtual.dica || 'O antecessor vem logo antes (subtrai 1) e o sucessor vem logo depois (soma 1).';
    const dicas = [
        dicaEspecifica,
        'Em números negativos, quanto mais próximo do zero na reta numérica, maior é o valor do número.',
        'Analise se a palavra "sucessor" ou "antecessor" combina com a justificativa de ser "maior" ou "menor".'
    ];

    const textoDica = dicas[dicaIndiceJogo4 % dicas.length];
    dicaIndiceJogo4++;
    exibirDica(textoDica);
}

const mostrarDica = solicitarDica;

function exibirDica(msg) {
    const el = document.getElementById('dica-flutuante');
    const texto = document.getElementById('dica-texto');
    if (el && texto) {
        texto.textContent = msg;
        el.style.display = 'flex';
        if (dicaTimerJogo4) clearTimeout(dicaTimerJogo4);
        dicaTimerJogo4 = setTimeout(() => {
            el.style.display = 'none';
        }, 5500);
    }
}

function ocultarDica() {
    const el = document.getElementById('dica-flutuante');
    if (el) el.style.display = 'none';
    if (dicaTimerJogo4) {
        clearTimeout(dicaTimerJogo4);
        dicaTimerJogo4 = null;
    }
}

// ─── Finalização do Jogo e Progresso ──────────────────────────────────────────
function finalizarJogo() {
    jogoAtivo = false;
    const total = questoesPartida.length;
    const estrelas = calcularEstrelas(acertos, total);

    exibirResultado(estrelas, total);
    verificarSessaoEGravar(estrelas);
}

async function verificarSessaoEGravar(estrelas) {
    if (params.modo === 'trilha') {
        gravarProgressoTrilha(estrelas);
    } else if (params.modo === 'livre') {
        gravarRecordeLivre();
    }
}

async function gravarProgressoTrilha(estrelas) {
    const ano = parseInt(params.ano, 10) || 6;
    const fase = parseInt(params.fase, 10) || 1;

    try {
        const progressoLocal = JSON.parse(localStorage.getItem(CHAVE_PROGRESSO)) || {};
        const chaveFase = `ano_${ano}_fase_${fase}`;
        if (!progressoLocal[chaveFase] || estrelas > (progressoLocal[chaveFase].estrelas || 0)) {
            progressoLocal[chaveFase] = { estrelas, pontuacao };
        }
        if (estrelas >= 1) {
            const chaveProx = `ano_${ano}_fase_${fase + 1}`;
            if (!progressoLocal[chaveProx]) {
                progressoLocal[chaveProx] = { estrelas: 0, pontuacao: 0, desbloqueada: true };
            }
        }
        localStorage.setItem(CHAVE_PROGRESSO, JSON.stringify(progressoLocal));
    } catch (e) {
        console.warn('Erro ao salvar progresso local:', e);
    }

    try {
        await fetch('../api/progresso/salvar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                ano: ano,
                fase: fase,
                fase_id: params.fase_id || 0,
                jogo_id: 4,
                pontuacao: pontuacao,
                estrelas: estrelas,
                tipo: 'trilha'
            })
        });
    } catch (e) {
        console.error('Erro ao salvar progresso na API', e);
    }
}

function calcularEstrelas(acertosQtd, total) {
    const pct = acertosQtd / total;
    if (pct >= 0.85) return 3;
    if (pct >= 0.5) return 2;
    if (pct > 0) return 1;
    return 0;
}

function exibirResultado(estrelas, total) {
    const modal = document.getElementById('modal-resultado');
    if (modal) {
        const elEstrelas = document.getElementById('modal-estrelas-display');
        const elPontos = document.getElementById('modal-pontuacao-valor');
        const elAcertos = document.getElementById('modal-acertos-valor');

        if (elEstrelas) {
            let svgHtml = '<div style="display:inline-flex; gap:8px; justify-content:center; align-items:center;">';
            for (let i = 1; i <= 3; i++) {
                if (i <= estrelas) {
                    svgHtml += '<svg width="34" height="34" viewBox="0 0 24 24" fill="#F59E0B" stroke="#D97706" stroke-width="1.5" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
                } else {
                    svgHtml += '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
                }
            }
            svgHtml += '</div>';
            elEstrelas.innerHTML = svgHtml;
            elEstrelas.setAttribute('aria-label', `${estrelas} de 3 estrelas`);
        }
        if (elPontos) elPontos.textContent = pontuacao;
        if (elAcertos) elAcertos.textContent = `${acertos}/${total}`;

        const comentario = document.getElementById('modal-comentario-valor');
        if (comentario) {
            if (estrelas === 3) comentario.textContent = 'Parabéns! Muito Bem!';
            else if (estrelas === 2) comentario.textContent = 'Boa tentativa, você está evoluindo!';
            else comentario.textContent = 'Você está no caminho para ser uma estrela!';
        }

        modal.showModal();
    } else {
        console.log(`Fim de jogo! Pontos: ${pontuacao}, Acertos: ${acertos}/${total}`);
        voltarPaginaAnterior();
    }
}

function gravarRecordeLivre() {
    try {
        const recordes = JSON.parse(localStorage.getItem(CHAVE_RECORDE_LIVRE)) || {};
        const jogoId = params.jogoId || 4;
        if (pontuacao > (recordes[jogoId] || 0)) {
            recordes[jogoId] = pontuacao;
            localStorage.setItem(CHAVE_RECORDE_LIVRE, JSON.stringify(recordes));
        }
    } catch (e) {
        console.error('Erro ao gravar recorde local:', e);
    }
}

function voltarPaginaAnterior() {
    if (params.modo === 'trilha') {
        window.location.href = '../conteudos/conteudos.php';
    } else {
        window.location.href = '../jogos/jogos.php';
    }
}
