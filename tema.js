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
        'tonalidade-berry'
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

    // Executa IMEDIATAMENTE no carregamento do script (previne flash)
    aplicarPreferenciasVisuais();

    // Reexecuta no DOMContentLoaded para garantir que document.body receba as classes
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', aplicarPreferenciasVisuais);
    } else {
        aplicarPreferenciasVisuais();
    }

    // Sincroniza abas automaticamente quando o usuário mudar o tema em outra aba
    window.addEventListener('storage', (e) => {
        if ([CHAVE_TEMA, CHAVE_TONALIDADE, CHAVE_DALTONISMO].includes(e.key)) {
            aplicarPreferenciasVisuais();
        }
    });

    // Expõe globalmente para outras funções chamarem se necessário
    window.aplicarPreferenciasVisuais = aplicarPreferenciasVisuais;
})();
