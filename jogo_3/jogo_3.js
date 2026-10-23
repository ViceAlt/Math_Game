/**
 * jogo_3.js
 * Lógica do Jogo 3: Desenhando Formas Geométricas & Fórmulas
 * Suporte a Pointer Events, Snap em Vértices, Desfazer, Preenchimento dinâmico,
 * Validação geométrica tolerante e Quiz de Fórmulas (Perímetro / Área).
 */

// ─── Constantes do Sistema ───────────────────────────────────────────────────
const CHAVE_PARAMS = 'mg_params_jogo';
const CHAVE_PROGRESSO = 'mg_progresso_fases';
const CHAVE_RECORDE_LIVRE = 'mg_recorde_livre';

// Paleta de cores vibrantes e amigáveis para as formas criadas (sorteada a cada rodada)
const CORES_FORMAS = [
    '#1cb0f6', // Duo Blue
    '#58cc02', // Duo Green
    '#ff9600', // Duo Orange
    '#ff4b4b', // Duo Red
    '#00b4d8', // Cyan
    '#2ec4b6', // Teal
    '#ff595e', // Coral
    '#ffca3a', // Gold
    '#1982c4', // Royal Blue
    '#06d6a0'  // Mint
];

// Configuração por dificuldade
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
let etapaAtual = 1; // 1: Desenho, 2: Múltipla Escolha
let jogoAtivo = true;

// Estado do Desenho SVG
let corAtual = CORES_FORMAS[0];
let linhasDesenhadas = []; // Array de { p1: {x,y}, p2: {x,y} }
let isDrawing = false;
let startPoint = null;
let currentPoint = null;
let activeSnapPoint = null;
const SNAP_DISTANCE = 22; // Raio em pixels para snap no vértice

// ─── Inicialização ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    aplicarPreferenciasVisuais();
    configurarEventosDesenho();
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
    let p = { modo: 'livre', dificuldade: 'facil', ano: 6, fase: 1, jogoId: 3 };
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

// ─── Carregamento das Questões do Backend ──────────────────────────────────────
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
            const pTexto = document.getElementById('pergunta-texto');
            if (pTexto) pTexto.textContent = 'Erro ao carregar questões do jogo. Tente recarregar a página.';
        }
    } catch (e) {
        console.error('Falha ao conectar ao servidor:', e);
    }
}

function iniciarQuestao() {
    dicaIndiceJogo3 = 0;
    ocultarDica();

    if (questaoAtualIndex >= questoesPartida.length) {
        finalizarJogo();
        return;
    }

    questaoAtual = questoesPartida[questaoAtualIndex];
    etapaAtual = 1;
    sortearNovaCor();
    limparDesenho();

    // Renderiza Retângulo Delimitador se existir (Nível Médio e Experiente)
    renderizarRetanguloLimite(questaoAtual.retangulo);

    // Atualiza interface para a Etapa 1
    const elInstrucao = document.getElementById('perguntaInstrucao');
    const elPergunta = document.getElementById('pergunta-texto');
    const elBadgeNome = document.getElementById('nomeFormaAlvo');
    const elMsg = document.getElementById('mensagemValidacao');
    const btnConfirmar = document.getElementById('btn-confirmar');
    const btnProxima = document.getElementById('btn-proxima');
    const altsContainer = document.getElementById('alternativasContainer');

    elInstrucao.textContent = 'Etapa 1: Crie a forma geométrica';
    elBadgeNome.textContent = questaoAtual.nome;

    if (questaoAtual.retangulo) {
        elPergunta.innerHTML = `Desenhe um <strong>${questaoAtual.nome}</strong> dentro do retângulo demarcado no plano.`;
    } else {
        elPergunta.innerHTML = `Desenhe um <strong>${questaoAtual.nome}</strong> no plano de desenho.`;
    }

    elMsg.style.display = 'none';
    elMsg.className = 'mensagem-validacao';
    altsContainer.style.display = 'none';
    altsContainer.innerHTML = '';

    btnConfirmar.style.display = 'block';
    btnConfirmar.disabled = true;
    btnProxima.style.display = 'none';

    document.getElementById('statusTexto').textContent = 'Clique e arraste para desenhar cada lado da forma.';
}

function sortearNovaCor() {
    corAtual = CORES_FORMAS[Math.floor(Math.random() * CORES_FORMAS.length)];
    const elIndicador = document.getElementById('corIndicador');
    if (elIndicador) elIndicador.style.backgroundColor = corAtual;
}

// ─── Renderização do Retângulo Delimitador (Médio / Experiente) ────────────────
function renderizarRetanguloLimite(rectData) {
    const layer = document.getElementById('layerRetanguloLimite');
    layer.innerHTML = '';
    if (!rectData) return;

    const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
    rect.setAttribute('x', rectData.x);
    rect.setAttribute('y', rectData.y);
    rect.setAttribute('width', rectData.largura);
    rect.setAttribute('height', rectData.altura);
    rect.classList.add('retangulo-limite');

    const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    label.setAttribute('x', rectData.x + 10);
    label.setAttribute('y', rectData.y + 22);
    label.classList.add('retangulo-limite-label');
    label.textContent = 'Área Delimitada';

    layer.appendChild(rect);
    layer.appendChild(label);
}

// ─── Motor de Desenho SVG com Pointer Events & Snap ───────────────────────────
function configurarEventosDesenho() {
    const svg = document.getElementById('svgPlano');

    svg.addEventListener('pointerdown', onPointerDown);
    svg.addEventListener('pointermove', onPointerMove);
    svg.addEventListener('pointerup', onPointerUp);
    svg.addEventListener('pointercancel', onPointerCancel);
}

function getSvgCoordinates(e) {
    const svg = document.getElementById('svgPlano');
    const pt = svg.createSVGPoint();
    pt.x = e.clientX;
    pt.y = e.clientY;
    const cursorPt = pt.matrixTransform(svg.getScreenCTM().inverse());
    return { x: Math.round(cursorPt.x), y: Math.round(cursorPt.y) };
}

function obterTodosVertices() {
    const vertices = [];
    linhasDesenhadas.forEach(l => {
        vertices.push(l.p1);
        vertices.push(l.p2);
    });
    return vertices;
}

function encontrarSnap(ponto, distanciaLimite = SNAP_DISTANCE, ignorarPonto = null) {
    const vertices = obterTodosVertices();
    let menorDist = Infinity;
    let melhorSnap = null;

    for (const v of vertices) {
        if (ignorarPonto && Math.hypot(v.x - ignorarPonto.x, v.y - ignorarPonto.y) < 1) {
            continue;
        }
        const d = Math.hypot(v.x - ponto.x, v.y - ponto.y);
        if (d < distanciaLimite && d < menorDist) {
            menorDist = d;
            melhorSnap = { x: v.x, y: v.y };
        }
    }
    return melhorSnap;
}

function onPointerDown(e) {
    if (etapaAtual !== 1 || !jogoAtivo) return;

    // Se o polígono já estiver fechado, não permite novas linhas até desfazer
    if (verificarPoligonoFechado()) return;

    const coords = getSvgCoordinates(e);
    const svg = document.getElementById('svgPlano');
    svg.setPointerCapture(e.pointerId);

    // Tenta fazer snap no ponto inicial se estiver perto de vértice existente
    const snap = encontrarSnap(coords);
    startPoint = snap || coords;
    currentPoint = { ...startPoint };
    isDrawing = true;

    const preview = document.getElementById('linhaPreview');
    preview.setAttribute('x1', startPoint.x);
    preview.setAttribute('y1', startPoint.y);
    preview.setAttribute('x2', startPoint.x);
    preview.setAttribute('y2', startPoint.y);
    preview.setAttribute('stroke', corAtual);
    preview.style.display = 'block';
}

function onPointerMove(e) {
    if (!isDrawing) return;

    const coords = getSvgCoordinates(e);
    // Tenta snap no ponto final (ignorando o ponto de partida caso seja o mesmo)
    const snap = encontrarSnap(coords, SNAP_DISTANCE, startPoint);
    currentPoint = snap || coords;

    const preview = document.getElementById('linhaPreview');
    preview.setAttribute('x2', currentPoint.x);
    preview.setAttribute('y2', currentPoint.y);

    const indicator = document.getElementById('snapIndicator');
    if (snap) {
        indicator.setAttribute('cx', snap.x);
        indicator.setAttribute('cy', snap.y);
        indicator.style.display = 'block';
    } else {
        indicator.style.display = 'none';
    }
}

function onPointerUp(e) {
    if (!isDrawing) return;
    isDrawing = false;

    const svg = document.getElementById('svgPlano');
    try { svg.releasePointerCapture(e.pointerId); } catch { }

    const preview = document.getElementById('linhaPreview');
    preview.style.display = 'none';
    document.getElementById('snapIndicator').style.display = 'none';

    // Evita criar linhas com comprimento quase zero
    const dist = Math.hypot(currentPoint.x - startPoint.x, currentPoint.y - startPoint.y);
    if (dist < 15) return;

    // Adiciona nova linha
    linhasDesenhadas.push({
        p1: { x: startPoint.x, y: startPoint.y },
        p2: { x: currentPoint.x, y: currentPoint.y }
    });

    renderizarLinhasEVertices();
    verificarEstadoForma();
}

function onPointerCancel(e) {
    isDrawing = false;
    document.getElementById('linhaPreview').style.display = 'none';
    document.getElementById('snapIndicator').style.display = 'none';
}

// ─── Renderização de Linhas, Vértices e Preenchimento Automático ──────────────
function renderizarLinhasEVertices() {
    const layerLinhas = document.getElementById('layerLinhas');
    const layerVertices = document.getElementById('layerVertices');
    layerLinhas.innerHTML = '';
    layerVertices.innerHTML = '';

    linhasDesenhadas.forEach(linha => {
        const l = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        l.setAttribute('x1', linha.p1.x);
        l.setAttribute('y1', linha.p1.y);
        l.setAttribute('x2', linha.p2.x);
        l.setAttribute('y2', linha.p2.y);
        l.setAttribute('stroke', corAtual);
        l.classList.add('linha-desenhada');
        layerLinhas.appendChild(l);
    });

    // Vértices distintos
    const verticesUnicos = [];
    obterTodosVertices().forEach(pt => {
        const jaExiste = verticesUnicos.some(v => Math.hypot(v.x - pt.x, v.y - pt.y) < 1);
        if (!jaExiste) verticesUnicos.push(pt);
    });

    verticesUnicos.forEach(pt => {
        const c = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        c.setAttribute('cx', pt.x);
        c.setAttribute('cy', pt.y);
        c.setAttribute('stroke', corAtual);
        c.classList.add('vertice-ponto');
        layerVertices.appendChild(c);
    });
}

function desfazerUltimoMovimento() {
    if (etapaAtual !== 1 || !jogoAtivo) return;
    if (linhasDesenhadas.length > 0) {
        linhasDesenhadas.pop();
        renderizarLinhasEVertices();
        verificarEstadoForma();
        const elMsg = document.getElementById('mensagemValidacao');
        if (elMsg) elMsg.style.display = 'none';
    }
}

function limparDesenho() {
    linhasDesenhadas = [];
    isDrawing = false;
    startPoint = null;
    currentPoint = null;

    const preview = document.getElementById('linhaPreview');
    if (preview) preview.style.display = 'none';

    const snapIndicator = document.getElementById('snapIndicator');
    if (snapIndicator) snapIndicator.style.display = 'none';

    renderizarLinhasEVertices();
    atualizarPoligonoPreenchido(null);

    const btnConfirmar = document.getElementById('btn-confirmar');
    if (btnConfirmar) btnConfirmar.disabled = true;

    const elMsg = document.getElementById('mensagemValidacao');
    if (elMsg) elMsg.style.display = 'none';

    const statusTexto = document.getElementById('statusTexto');
    if (statusTexto) {
        statusTexto.textContent = 'Clique e arraste para desenhar cada lado da forma.';
    }
}

// ─── Análise de Topologia do Polígono (Ciclo Fechado) ──────────────────────────
function extrairCicloPoligono() {
    if (linhasDesenhadas.length < 3) return null;

    // Constrói grafo de adjacências
    const n = linhasDesenhadas.length;
    // Tenta encontrar um percurso hamiltoniano ou ciclo conexo simples
    const visitadas = new Array(n).fill(false);
    const sequenciaPontos = [linhasDesenhadas[0].p1, linhasDesenhadas[0].p2];
    visitadas[0] = true;

    for (let passo = 1; passo < n; passo++) {
        const ultimoPonto = sequenciaPontos[sequenciaPontos.length - 1];
        let achouProxima = false;

        for (let i = 0; i < n; i++) {
            if (visitadas[i]) continue;
            const l = linhasDesenhadas[i];
            const d1 = Math.hypot(l.p1.x - ultimoPonto.x, l.p1.y - ultimoPonto.y);
            const d2 = Math.hypot(l.p2.x - ultimoPonto.x, l.p2.y - ultimoPonto.y);

            if (d1 < 4) {
                sequenciaPontos.push(l.p2);
                visitadas[i] = true;
                achouProxima = true;
                break;
            } else if (d2 < 4) {
                sequenciaPontos.push(l.p1);
                visitadas[i] = true;
                achouProxima = true;
                break;
            }
        }
        if (!achouProxima) return null; // Grafo desconexo
    }

    // Verifica se o último ponto fecha com o primeiro
    const primeiroPonto = sequenciaPontos[0];
    const ultimoPonto = sequenciaPontos[sequenciaPontos.length - 1];
    const fecha = Math.hypot(primeiroPonto.x - ultimoPonto.x, primeiroPonto.y - ultimoPonto.y) < 4;

    if (fecha) {
        // Remove ponto duplicado do final para ter array de vértices simples
        return sequenciaPontos.slice(0, sequenciaPontos.length - 1);
    }
    return null;
}

function verificarPoligonoFechado() {
    return extrairCicloPoligono() !== null;
}

function verificarEstadoForma() {
    const verticesCiclo = extrairCicloPoligono();
    const btnConfirmar = document.getElementById('btn-confirmar');
    const statusTexto = document.getElementById('statusTexto');

    if (verticesCiclo) {
        atualizarPoligonoPreenchido(verticesCiclo);
        btnConfirmar.disabled = false;
        statusTexto.textContent = `Polígono fechado com ${verticesCiclo.length} vértices! Clique em "Confirmar Forma" para validar.`;
    } else {
        atualizarPoligonoPreenchido(null);
        btnConfirmar.disabled = true;
        statusTexto.textContent = `Linhas desenhadas: ${linhasDesenhadas.length}. Conecte os vértices para fechar o polígono.`;
    }
}

function atualizarPoligonoPreenchido(vertices) {
    const poly = document.getElementById('poligonoPreenchido');
    if (!vertices || vertices.length < 3) {
        poly.setAttribute('points', '');
        poly.style.display = 'none';
        return;
    }

    const pointsStr = vertices.map(v => `${v.x},${v.y}`).join(' ');
    poly.setAttribute('points', pointsStr);
    poly.setAttribute('fill', corAtual);
    poly.setAttribute('stroke', corAtual);
    poly.style.display = 'block';
}

// ─── Motor de Validação Geométrica Tolerante ─────────────────────────────────
function segmentosSeCruzam(p1, p2, p3, p4) {
    function ccw(A, B, C) {
        return (C.y - A.y) * (B.x - A.x) > (B.y - A.y) * (C.x - A.x);
    }
    // Ignora se compartilham vértices
    if ((p1.x === p3.x && p1.y === p3.y) || (p1.x === p4.x && p1.y === p4.y) ||
        (p2.x === p3.x && p2.y === p3.y) || (p2.x === p4.x && p2.y === p4.y)) {
        return false;
    }
    return (ccw(p1, p3, p4) !== ccw(p2, p3, p4)) && (ccw(p1, p2, p3) !== ccw(p1, p2, p4));
}

function validarPoligonoSemAutoIntersecao(vertices) {
    const n = vertices.length;
    for (let i = 0; i < n; i++) {
        const a1 = vertices[i];
        const a2 = vertices[(i + 1) % n];
        for (let j = i + 1; j < n; j++) {
            const b1 = vertices[j];
            const b2 = vertices[(j + 1) % n];
            if (segmentosSeCruzam(a1, a2, b1, b2)) return false;
        }
    }
    return true;
}

function calcularComprimentosLados(vertices) {
    const n = vertices.length;
    const lados = [];
    for (let i = 0; i < n; i++) {
        const v1 = vertices[i];
        const v2 = vertices[(i + 1) % n];
        lados.push(Math.hypot(v2.x - v1.x, v2.y - v1.y));
    }
    return lados;
}

function calcularAngulosInternos(vertices) {
    const n = vertices.length;
    const angulos = [];
    for (let i = 0; i < n; i++) {
        const prev = vertices[(i - 1 + n) % n];
        const curr = vertices[i];
        const next = vertices[(i + 1) % n];

        const v1 = { x: prev.x - curr.x, y: prev.y - curr.y };
        const v2 = { x: next.x - curr.x, y: next.y - curr.y };

        const dot = v1.x * v2.x + v1.y * v2.y;
        const mag1 = Math.hypot(v1.x, v1.y);
        const mag2 = Math.hypot(v2.x, v2.y);
        let cosTheta = dot / (mag1 * mag2);
        cosTheta = Math.max(-1, Math.min(1, cosTheta));
        const rad = Math.acos(cosTheta);
        angulos.push((rad * 180) / Math.PI);
    }
    return angulos;
}

function saoLadosAproximadamenteIguais(lados, tolRel = 0.22) {
    const media = lados.reduce((a, b) => a + b, 0) / lados.length;
    return lados.every(l => Math.abs(l - media) / media <= tolRel);
}

function saoAngulosAproximadamenteIguais(angulos, anguloEsperado, margem = 18) {
    return angulos.every(a => Math.abs(a - anguloEsperado) <= margem);
}

function saoLinhasParalelas(p1, p2, p3, p4, margemDeg = 15) {
    const ang1 = (Math.atan2(p2.y - p1.y, p2.x - p1.x) * 180) / Math.PI;
    const ang2 = (Math.atan2(p4.y - p3.y, p4.x - p3.x) * 180) / Math.PI;
    let diff = Math.abs(ang1 - ang2) % 180;
    if (diff > 90) diff = 180 - diff;
    return diff <= margemDeg;
}

function validarFormaGeometrica(formaId, vertices, retanguloLimite) {
    // 1. Verificação do retângulo delimitador
    if (retanguloLimite) {
        const dentro = vertices.every(v =>
            v.x >= retanguloLimite.x - 6 &&
            v.x <= (retanguloLimite.x + retanguloLimite.largura + 6) &&
            v.y >= retanguloLimite.y - 6 &&
            v.y <= (retanguloLimite.y + retanguloLimite.altura + 6)
        );
        if (!dentro) {
            return { valido: false, mensagem: 'A forma desenhada saiu dos limites do retângulo demarcado!' };
        }
    }

    // 2. Não pode ter linhas que se cruzam
    if (!validarPoligonoSemAutoIntersecao(vertices)) {
        return { valido: false, mensagem: 'As linhas da forma estão se cruzando. Crie um polígono simples.' };
    }

    const n = vertices.length;
    const lados = calcularComprimentosLados(vertices);
    const angulos = calcularAngulosInternos(vertices);

    switch (formaId) {
        case 'triangulo_equilatero': {
            if (n !== 3) return { valido: false, mensagem: `Um triângulo deve ter 3 lados (o seu tem ${n}).` };
            if (!saoLadosAproximadamenteIguais(lados, 0.25)) {
                return { valido: false, mensagem: 'Os 3 lados do triângulo equilátero devem ter tamanhos iguais.' };
            }
            if (!saoAngulosAproximadamenteIguais(angulos, 60, 20)) {
                return { valido: false, mensagem: 'Os ângulos internos do triângulo equilátero devem ser próximos de 60°.' };
            }
            return { valido: true };
        }

        case 'quadrado': {
            if (n !== 4) return { valido: false, mensagem: `O quadrado precisa ter 4 lados (o seu tem ${n}).` };
            if (!saoLadosAproximadamenteIguais(lados, 0.22)) {
                return { valido: false, mensagem: 'Todos os 4 lados do quadrado devem ter o mesmo comprimento.' };
            }
            if (!saoAngulosAproximadamenteIguais(angulos, 90, 16)) {
                return { valido: false, mensagem: 'Os ângulos do quadrado devem ser ângulos retos (próximos de 90°).' };
            }
            return { valido: true };
        }

        case 'retangulo': {
            if (n !== 4) return { valido: false, mensagem: `O retângulo precisa ter 4 lados (o seu tem ${n}).` };
            if (!saoAngulosAproximadamenteIguais(angulos, 90, 16)) {
                return { valido: false, mensagem: 'Os 4 ângulos do retângulo devem ser ângulos retos (cerca de 90°).' };
            }
            const paresOpostosIguais =
                (Math.abs(lados[0] - lados[2]) / Math.max(lados[0], lados[2]) <= 0.25) &&
                (Math.abs(lados[1] - lados[3]) / Math.max(lados[1], lados[3]) <= 0.25);
            if (!paresOpostosIguais) {
                return { valido: false, mensagem: 'Os lados opostos do retângulo devem ter comprimentos iguais.' };
            }
            return { valido: true };
        }

        case 'losango': {
            if (n !== 4) return { valido: false, mensagem: `O losango precisa ter 4 lados (o seu tem ${n}).` };
            if (!saoLadosAproximadamenteIguais(lados, 0.24)) {
                return { valido: false, mensagem: 'Os 4 lados do losango devem ter medidas iguais.' };
            }
            const op1 = saoLinhasParalelas(vertices[0], vertices[1], vertices[3], vertices[2], 18);
            const op2 = saoLinhasParalelas(vertices[1], vertices[2], vertices[0], vertices[3], 18);
            if (!op1 || !op2) {
                return { valido: false, mensagem: 'Os lados opostos do losango devem ser paralelos.' };
            }
            return { valido: true };
        }

        case 'paralelogramo': {
            if (n !== 4) return { valido: false, mensagem: `O paralelogramo deve ter 4 lados (o seu tem ${n}).` };
            const op1 = saoLinhasParalelas(vertices[0], vertices[1], vertices[3], vertices[2], 18);
            const op2 = saoLinhasParalelas(vertices[1], vertices[2], vertices[0], vertices[3], 18);
            if (!op1 || !op2) {
                return { valido: false, mensagem: 'Os pares de lados opostos do paralelogramo devem ser paralelos.' };
            }
            return { valido: true };
        }

        case 'trapezio': {
            if (n !== 4) return { valido: false, mensagem: `O trapézio deve ter 4 lados (o seu tem ${n}).` };
            const par1 = saoLinhasParalelas(vertices[0], vertices[1], vertices[3], vertices[2], 18);
            const par2 = saoLinhasParalelas(vertices[1], vertices[2], vertices[0], vertices[3], 18);
            if (!par1 && !par2) {
                return { valido: false, mensagem: 'O trapézio precisa ter ao menos um par de lados paralelos (bases).' };
            }
            return { valido: true };
        }

        case 'triangulo_retangulo': {
            if (n !== 3) return { valido: false, mensagem: `O triângulo retângulo deve ter 3 lados (o seu tem ${n}).` };
            const temReto = angulos.some(a => Math.abs(a - 90) <= 16);
            if (!temReto) {
                return { valido: false, mensagem: 'O triângulo retângulo precisa ter um ângulo reto (cerca de 90°).' };
            }
            return { valido: true };
        }

        case 'pentagono_regular': {
            if (n !== 5) return { valido: false, mensagem: `O pentágono precisa ter 5 lados (o seu tem ${n}).` };
            if (!saoLadosAproximadamenteIguais(lados, 0.28)) {
                return { valido: false, mensagem: 'Os 5 lados do pentágono regular devem ter comprimentos semelhantes.' };
            }
            return { valido: true };
        }

        case 'hexagono_regular': {
            if (n !== 6) return { valido: false, mensagem: `O hexágono precisa ter 6 lados (o seu tem ${n}).` };
            if (!saoLadosAproximadamenteIguais(lados, 0.28)) {
                return { valido: false, mensagem: 'Os 6 lados do hexágono regular devem ter comprimentos semelhantes.' };
            }
            return { valido: true };
        }

        default:
            return { valido: (n >= 3) };
    }
}

// ─── Fluxo da Etapa 1: Confirmação e Validação ────────────────────────────────
function confirmarForma() {
    if (etapaAtual !== 1 || !jogoAtivo) return;

    const vertices = extrairCicloPoligono();
    const elMsg = document.getElementById('mensagemValidacao');

    if (!vertices) {
        elMsg.textContent = 'O polígono não está completamente fechado. Conecte as pontas!';
        elMsg.className = 'mensagem-validacao erro';
        elMsg.style.display = 'flex';
        return;
    }

    const resultado = validarFormaGeometrica(questaoAtual.id, vertices, questaoAtual.retangulo);

    if (!resultado.valido) {
        elMsg.textContent = resultado.mensagem || 'A forma desenhada não corresponde à forma pedida.';
        elMsg.className = 'mensagem-validacao erro';
        elMsg.style.display = 'flex';
        return;
    }

    // Forma aprovada com sucesso! Avança para a Etapa 2
    elMsg.textContent = `Excelente! Você criou um ${questaoAtual.nome} perfeito!`;
    elMsg.className = 'mensagem-validacao sucesso';
    elMsg.style.display = 'flex';

    setTimeout(() => {
        iniciarEtapa2();
    }, 900);
}

// ─── Fluxo da Etapa 2: Múltipla Escolha sobre Perímetro ou Área ───────────────
function iniciarEtapa2() {
    etapaAtual = 2;

    const elInstrucao = document.getElementById('perguntaInstrucao');
    const elPergunta = document.getElementById('pergunta-texto');
    const btnConfirmar = document.getElementById('btn-confirmar');
    const altsContainer = document.getElementById('alternativasContainer');
    const statusTexto = document.getElementById('statusTexto');

    elInstrucao.textContent = 'Etapa 2: Responda sobre a forma';
    elPergunta.textContent = questaoAtual.enunciadoPergunta;
    statusTexto.textContent = `Forma criada: ${questaoAtual.nome}. Escolha a fórmula correta na lateral.`;

    btnConfirmar.style.display = 'none';

    altsContainer.innerHTML = '';
    altsContainer.style.display = 'flex';

    const letras = ['A', 'B', 'C', 'D'];
    questaoAtual.alternativas.forEach((alt, idx) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-alternativa';
        btn.innerHTML = `
            <span class="letra-alt">${letras[idx] || ''}</span>
            <span class="texto-alt">${alt}</span>
        `;
        btn.onclick = () => responderPergunta(alt, btn);
        altsContainer.appendChild(btn);
    });
}

function responderPergunta(respostaSelecionada, botaoClicado) {
    if (etapaAtual !== 2 || !jogoAtivo) return;

    // Desativa todas as opções
    const botoes = document.querySelectorAll('.btn-alternativa');
    botoes.forEach(b => b.disabled = true);

    const acertou = (respostaSelecionada === questaoAtual.respostaCorreta);
    const barra = document.getElementById('barra-progresso');
    const elMsg = document.getElementById('mensagemValidacao');

    // Destaca a correta e a errada
    botoes.forEach(btn => {
        const texto = btn.querySelector('.texto-alt').textContent.trim();
        if (texto === questaoAtual.respostaCorreta.trim()) {
            btn.classList.add('correta');
        } else if (btn === botaoClicado && !acertou) {
            btn.classList.add('errada');
        }
    });

    if (acertou) {
        acertos++;
        const mult = CONFIG_DIFICULDADE[params.dificuldade]?.multiplicador || 1;
        pontuacao += Math.round(100 * mult);
        elMsg.textContent = 'Correto! Você acertou a fórmula!';
        elMsg.className = 'mensagem-validacao sucesso';
        elMsg.style.display = 'flex';
        if (barra) {
            barra.classList.remove('erro');
            barra.classList.add('acerto');
        }
    } else {
        elMsg.textContent = 'Ops, resposta incorreta! Observe a alternativa correta destacada em verde.';
        elMsg.className = 'mensagem-validacao erro';
        elMsg.style.display = 'flex';
        if (barra) {
            barra.classList.remove('acerto');
            barra.classList.add('erro');
        }
    }

    // Exibe o botão de Próxima Questão
    const btnProxima = document.getElementById('btn-proxima');
    btnProxima.style.display = 'block';

    const isUltima = (questaoAtualIndex === questoesPartida.length - 1);
    btnProxima.textContent = isUltima ? 'Concluir Partida' : 'Próxima Questão';
}

function avancarQuestao() {
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
let dicaIndiceJogo3 = 0;
let dicaTimerJogo3 = null;

function solicitarDica() {
    if (!questaoAtual) return;
    let dicas = [];

    if (etapaAtual === 1) {
        let dicaDef = '';
        switch (questaoAtual.id) {
            case 'triangulo_equilatero':
                dicaDef = 'Um triângulo equilátero possui 3 lados de mesmo tamanho e ângulos de 60°.';
                break;
            case 'quadrado':
                dicaDef = 'O quadrado tem 4 lados de medidas iguais e 4 cantos retos (90°).';
                break;
            case 'retangulo':
                dicaDef = 'O retângulo tem 4 cantos retos e lados opostos de mesmo tamanho.';
                break;
            case 'losango':
                dicaDef = 'O losango tem 4 lados iguais, parecendo um quadrado inclinado!';
                break;
            case 'paralelogramo':
                dicaDef = 'O paralelogramo tem lados opostos paralelos e de comprimentos iguais.';
                break;
            case 'trapezio':
                dicaDef = 'O trapézio tem um par de lados paralelos chamados de base maior e base menor.';
                break;
            case 'triangulo_retangulo':
                dicaDef = 'O triângulo retângulo possui um ângulo reto (canto em "L" perfeito de 90°).';
                break;
            case 'pentagono_regular':
                dicaDef = 'O pentágono regular tem 5 lados iguais que formam uma forma harmônica.';
                break;
            case 'hexagono_regular':
                dicaDef = 'O hexágono regular tem 6 lados iguais, lembrando o formato de um favo de mel!';
                break;
            default:
                dicaDef = `Desenhe a forma ${questaoAtual.nome} conectando os vértices.`;
        }
        dicas = [
            dicaDef,
            'Clique nos pontos da grade para traçar os segmentos de reta e criar os lados.',
            'Para fechar a forma e confirmar, conecte a última linha de volta ao primeiro ponto desenhado!'
        ];
    } else {
        const isPerimetro = (questaoAtual.tipoPergunta === 'perimetro');
        dicas = [
            isPerimetro
                ? 'O perímetro é o contorno total da figura (a soma de todos os seus lados).'
                : 'A área mede o espaço interno ocupado pela figura no plano.',
            isPerimetro
                ? 'Some as medidas de cada lado da figura para chegar ao perímetro correto.'
                : 'Conte a quantidade de quadradinhos unitários dentro da figura para calcular a área.',
            'Analise as fórmulas nas alternativas e escolha a expressão correspondente à forma desenhada!'
        ];
    }

    const textoDica = dicas[dicaIndiceJogo3 % dicas.length];
    dicaIndiceJogo3++;
    exibirDica(textoDica);
}

const mostrarDica = solicitarDica;

function exibirDica(msg) {
    const el = document.getElementById('dica-flutuante');
    const texto = document.getElementById('dica-texto');
    if (el && texto) {
        texto.textContent = msg;
        el.style.display = 'flex';
        if (dicaTimerJogo3) clearTimeout(dicaTimerJogo3);
        dicaTimerJogo3 = setTimeout(() => {
            el.style.display = 'none';
        }, 5500);
    }
}

function ocultarDica() {
    const el = document.getElementById('dica-flutuante');
    if (el) el.style.display = 'none';
    if (dicaTimerJogo3) {
        clearTimeout(dicaTimerJogo3);
        dicaTimerJogo3 = null;
    }
}

// ─── Finalização da Partida e Gravação de Progresso ───────────────────────────
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
                jogo_id: 3,
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
            if (estrelas === 3) comentario.textContent = 'Parabéns! Muito Bem! ';
            else if (estrelas === 2) comentario.textContent = 'Boa tentativa, você está evoluindo! ';
            else comentario.textContent = 'Você está no caminho para ser uma estrela! ';
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
        const jogoId = params.jogoId || 3;
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
