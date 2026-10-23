/**
 * includes/icones.js – Biblioteca Central de Ícones SVG para JavaScript (Math Game)
 * Permite que componentes gerados no client-side (trilha, cards, estrelas) usem
 * exatamente os mesmos ícones SVG, sem nenhum emoji.
 */

const ICONES = {
    play(size = 24, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg ${extraClass}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72a1 1 0 0 0 1.55.83l11-6.86a1 1 0 0 0 0-1.66l-11-6.86A1 1 0 0 0 8 5.14z"/></svg>`;
    },

    lock(size = 24, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg ${extraClass}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>`;
    },

    starFilled(size = 20, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg icone-estrela preenchida ${extraClass}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`;
    },

    starEmpty(size = 20, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg icone-estrela vazia ${extraClass}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`;
    },

    check(size = 22, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg ${extraClass}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>`;
    },

    trophy(size = 24, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg ${extraClass}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 4h16v6a6 6 0 0 1-6 6h-4a6 6 0 0 1-6-6V4z"/><path d="M12 16v4"/><path d="M8 20h8"/></svg>`;
    },

    lightning(size = 20, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg ${extraClass}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>`;
    },

    map(size = 20, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg ${extraClass}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>`;
    },

    warning(size = 20, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg ${extraClass}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`;
    },

    forest(size = 22, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg ${extraClass}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 2-6 8h3l-4 7h14l-4-7h3l-6-8zM11 19h2v4h-2z"/></svg>`;
    },

    desert(size = 22, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg ${extraClass}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a5 5 0 0 0-5 5v5H5a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v4h10v-4h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2h-2V7a5 5 0 0 0-5-5z"/></svg>`;
    },

    galaxy(size = 22, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg ${extraClass}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="7" fill="currentColor"/><ellipse cx="12" cy="12" rx="11" ry="4" transform="rotate(-25 12 12)"/></svg>`;
    },

    citadel(size = 22, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg ${extraClass}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 1 2 6v2h20V6L12 1zm-8 8v9h3V9H4zm5 0v9h3V9H9zm5 0v9h3V9h-3zm5 0v9h3V9h-3zM2 20v2h20v-2H2z"/></svg>`;
    },

    mail(size = 20, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg ${extraClass}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>`;
    },

    bulb(size = 20, extraClass = '') {
        return `<svg width="${size}" height="${size}" class="icone-svg ${extraClass}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5.76.76 1.23 1.52 1.41 2.5"/></svg>`;
    },

    renderEstrelas(qtd = 0, size = 18, isArch = false) {
        qtd = Math.max(0, Math.min(3, parseInt(qtd, 10) || 0));
        let html = '<div class="estrelas-svg-grupo ' + (isArch ? 'estrelas-em-arco' : '') + '" aria-label="' + qtd + ' de 3 estrelas">';
        for (let i = 1; i <= 3; i++) {
            const isFilled = i <= qtd;
            const extra = 'estrela-pos-' + i + (isFilled ? ' preenchida' : ' vazia');
            html += isFilled ? ICONES.starFilled(size, extra) : ICONES.starEmpty(size, extra);
        }
        html += '</div>';
        return html;
    }
};
