/**
 * tema.js - Gerenciador Central de Temas e Preferências Visuais
 * Aplica tema escuro/claro, daltonismo e tonalidades de cores instantaneamente
 * em todas as páginas, prevenindo "flashes" de cores incorretas.
 */

(function () {
    const CHAVE_TEMA       = 'mg_tema';
    const CHAVE_TONALIDADE = 'mg_tonalidade';
    const CHAVE_DALTONISMO = 'mg_daltonismo';

    const CLASSES_TONALIDADE = [
        'tonalidade-sunset',
        'tonalidade-ocean',
        'tonalidade-earth',
        'tonalidade-berry',
        'tonalidade-crimson',
        'tonalidade-arctic'
    ];

    const CLASSES_DALTONISMO = [
        'daltonismo-protanopia',
        'daltonismo-deuteranopia',
        'daltonismo-tritanopia'
    ];

    function aplicarPreferenciasVisuais() {
        try {
            const tema = localStorage.getItem(CHAVE_TEMA);
            const tonalidade = localStorage.getItem(CHAVE_TONALIDADE);
            const daltonismo = localStorage.getItem(CHAVE_DALTONISMO);

            const alvos = [document.documentElement];
            if (document.body) alvos.push(document.body);

            alvos.forEach(el => {
                if (!el) return;

                // 1. Tema Claro / Escuro
                if (tema === 'escuro') {
                    el.classList.add('tema-escuro');
                } else {
                    el.classList.remove('tema-escuro');
                }

                // 2. Tonalidade (Sunset, Ocean, Earth, Berry)
                CLASSES_TONALIDADE.forEach(cls => el.classList.remove(cls));
                if (tonalidade && tonalidade !== 'padrao') {
                    el.classList.add(`tonalidade-${tonalidade}`);
                }

                // 3. Daltonismo
                CLASSES_DALTONISMO.forEach(cls => el.classList.remove(cls));
                if (daltonismo && daltonismo !== 'nenhum') {
                    el.classList.add(`daltonismo-${daltonismo}`);
                }
            });
        } catch (e) {
            console.warn('[tema.js] Erro ao carregar preferências visuais:', e);
        }
    }

    // ─── Rotação Automática de Ícones da Página (Favicon e Marca) ──────────────
    const CHAVE_ULTIMO_ICONE = 'mg_ultimo_icone_idx';
    const ICONES_DISPONIVEIS = ['icon1.webp', 'icon2.webp', 'icon3.webp'];
    let urlIconeAtual = '';

    function obterProximoIcone() {
        let ultimoIdx = parseInt(sessionStorage.getItem(CHAVE_ULTIMO_ICONE) || localStorage.getItem(CHAVE_ULTIMO_ICONE), 10);
        if (isNaN(ultimoIdx) || ultimoIdx < 0 || ultimoIdx >= ICONES_DISPONIVEIS.length) {
            ultimoIdx = 0;
        }
        const proximoIdx = (ultimoIdx + 1) % ICONES_DISPONIVEIS.length;
        localStorage.setItem(CHAVE_ULTIMO_ICONE, proximoIdx.toString());
        sessionStorage.setItem(CHAVE_ULTIMO_ICONE, proximoIdx.toString());
        return ICONES_DISPONIVEIS[proximoIdx];
    }

    function aplicarIconePagina() {
        try {
            if (!urlIconeAtual) {
                const nomeIcone = obterProximoIcone();
                const scripts = document.getElementsByTagName('script');
                for (let i = 0; i < scripts.length; i++) {
                    const s = scripts[i];
                    if (s.src && s.src.includes('tema.js')) {
                        urlIconeAtual = new URL('icons/' + nomeIcone, s.src).href;
                        break;
                    }
                }
                if (!urlIconeAtual) {
                    const subpastas = ['/cadastro/', '/login/', '/home/', '/perfil/', '/conteudos/', '/jogos/', '/configuracoes/', '/jogo_1/', '/jogo_2/', '/jogo_3/', '/jogo_4/', '/jogo_5/'];
                    const pathname = window.location.pathname.toLowerCase();
                    const emSubpasta = subpastas.some(sub => pathname.includes(sub));
                    urlIconeAtual = (emSubpasta ? '../icons/' : 'icons/') + nomeIcone;
                }
            }

            // Atualiza tags de favicon no <head>
            definirLinkFavicon('icon', urlIconeAtual);
            definirLinkFavicon('shortcut icon', urlIconeAtual);
            definirLinkFavicon('apple-touch-icon', urlIconeAtual);

            // Atualiza badge da logo na página se presente
            atualizarBadgeLogo(urlIconeAtual);
        } catch (e) {
            console.warn('[tema.js] Erro ao alternar ícone da página:', e);
        }
    }

    function definirLinkFavicon(rel, url) {
        let link = document.querySelector(`link[rel="${rel}"]`);
        if (!link) {
            link = document.createElement('link');
            link.rel = rel;
            link.type = 'image/webp';
            document.head.appendChild(link);
        }
        link.href = url;
    }

    function atualizarBadgeLogo(url) {
        const badges = document.querySelectorAll('.brand-badge-icon, .brand-icon-box');
        badges.forEach(badge => {
            if (!badge) return;
            badge.style.overflow = 'hidden';
            let img = badge.querySelector('.brand-dyn-img');
            if (!img) {
                badge.innerHTML = `<img src="${url}" class="brand-dyn-img" alt="Ícone Mantham" width="38" height="38" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit; display: block;">`;
            } else {
                img.src = url;
            }
        });
    }

    // Executa IMEDIATAMENTE no carregamento do script (previne flash)
    aplicarPreferenciasVisuais();
    aplicarIconePagina();

    // Reexecuta no DOMContentLoaded para garantir que document.body receba as classes e badges
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            aplicarPreferenciasVisuais();
            aplicarIconePagina();
        });
    } else {
        aplicarPreferenciasVisuais();
        aplicarIconePagina();
    }

    // Sincroniza abas automaticamente quando o usuário mudar o tema em outra aba
    window.addEventListener('storage', (e) => {
        if ([CHAVE_TEMA, CHAVE_TONALIDADE, CHAVE_DALTONISMO].includes(e.key)) {
            aplicarPreferenciasVisuais();
        }
    });

    // Expõe globalmente para outras funções chamarem se necessário
    window.aplicarPreferenciasVisuais = aplicarPreferenciasVisuais;
    window.aplicarIconePagina = aplicarIconePagina;
})();
