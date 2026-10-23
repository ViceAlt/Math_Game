<?php
/**
 * includes/icones.php – Biblioteca Central de Ícones SVG (Math Game)
 * Garante estilo visual 100% consistente, sem emojis em todo o sistema.
 * Traço arredondado, proporções perfeitas e total compatibilidade com CSS color/fill.
 */

function icone_svg(string $nome, string $classes = '', int $size = 24): string {
    $dim = "width=\"{$size}\" height=\"{$size}\"";
    $cls = $classes ? "class=\"icone-svg {$classes}\"" : 'class="icone-svg"';

    switch ($nome) {
        case 'play':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M8 5.14v13.72a1 1 0 0 0 1.55.83l11-6.86a1 1 0 0 0 0-1.66l-11-6.86A1 1 0 0 0 8 5.14z\"/></svg>";

        case 'lock':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z\"/></svg>";

        case 'star':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z\"/></svg>";

        case 'star-empty':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><path d=\"m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z\"/></svg>";

        case 'check':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"3\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><polyline points=\"20 6 9 17 4 12\"/></svg>";

        case 'trophy':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><path d=\"M6 9H4.5a2.5 2.5 0 0 1 0-5H6\"/><path d=\"M18 9h1.5a2.5 2.5 0 0 0 0-5H18\"/><path d=\"M4 4h16v6a6 6 0 0 1-6 6h-4a6 6 0 0 1-6-6V4z\"/><path d=\"M12 16v4\"/><path d=\"M8 20h8\"/></svg>";

        case 'trophy-fill':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M19 4h-2V2H7v2H5c-1.1 0-2 .9-2 2v2c0 2.55 1.92 4.63 4.39 4.94.63 1.5 1.98 2.63 3.61 2.96V19H8v2h8v-2h-3v-3.1c1.63-.33 2.98-1.46 3.61-2.96C19.08 12.63 21 10.55 21 8V6c0-1.1-.9-2-2-2zM5 8V6h2v3.82C5.84 9.4 5 8.79 5 8zm14 0c0 .79-.84 1.4-2 1.82V6h2v2z\"/></svg>";

        case 'home':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M12 2.09 2 11h3v10a1 1 0 0 0 1 1h4v-7h4v7h4a1 1 0 0 0 1-1V11h3L12 2.09z\"/></svg>";

        case 'map':
        case 'conteudos':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><polygon points=\"1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6\"/><line x1=\"8\" y1=\"2\" x2=\"8\" y2=\"18\"/><line x1=\"16\" y1=\"6\" x2=\"16\" y2=\"22\"/></svg>";

        case 'game':
        case 'gamepad':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M21.58 7.19C21.05 6.45 20.2 6 19.3 6H4.7c-.9 0-1.75.45-2.28 1.19-.53.73-.67 1.66-.38 2.52l1.86 5.58C4.38 16.74 5.76 18 7.3 18h.42c.86 0 1.65-.43 2.11-1.16L12 13.5l2.17 3.34c.46.73 1.25 1.16 2.11 1.16h.42c1.54 0 2.92-1.26 3.4-2.71l1.86-5.58c.29-.86.15-1.79-.38-2.52zM10 10.5H8.5V12H7.5v-1.5H6v-1h1.5V8h1v1.5H10v1zm6.5 2c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm2-2c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z\"/></svg>";

        case 'settings':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><circle cx=\"12\" cy=\"12\" r=\"3\"/><path d=\"M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z\"/></svg>";

        case 'user':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z\"/></svg>";

        case 'key':
        case 'login':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><path d=\"m21 2-2 2m-1.5 1.5L16 7l-1.5-1.5-2 2 1.5 1.5L12 11l-1.5-1.5-2 2 1.5 1.5L8 15l-1.5-1.5-2 2 1.5 1.5-2 2a4 4 0 1 1-5.66-5.66l9-9a4 4 0 0 1 5.66 0l8 8a1 1 0 0 1 0 1.41z\"/></svg>";

        case 'close':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><line x1=\"18\" y1=\"6\" x2=\"6\" y2=\"18\"/><line x1=\"6\" y1=\"6\" x2=\"18\" y2=\"18\"/></svg>";

        case 'lightning':
        case 'raio':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M7 2v11h3v9l7-12h-4l4-8z\"/></svg>";

        case 'warning':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><path d=\"m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z\"/><line x1=\"12\" y1=\"9\" x2=\"12\" y2=\"13\"/><line x1=\"12\" y1=\"17\" x2=\"12.01\" y2=\"17\"/></svg>";

        case 'forest':
        case 'ano-6':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"m12 2-6 8h3l-4 7h14l-4-7h3l-6-8zM11 19h2v4h-2z\"/></svg>";

        case 'desert':
        case 'ano-7':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M12 2a5 5 0 0 0-5 5v5H5a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v4h10v-4h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2h-2V7a5 5 0 0 0-5-5z\"/></svg>";

        case 'galaxy':
        case 'ano-8':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><circle cx=\"12\" cy=\"12\" r=\"7\" fill=\"currentColor\"/><ellipse cx=\"12\" cy=\"12\" rx=\"11\" ry=\"4\" transform=\"rotate(-25 12 12)\"/></svg>";

        case 'citadel':
        case 'ano-9':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M12 1 2 6v2h20V6L12 1zm-8 8v9h3V9H4zm5 0v9h3V9H9zm5 0v9h3V9h-3zm5 0v9h3V9h-3zM2 20v2h20v-2H2z\"/></svg>";

        case 'menu':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><line x1=\"3\" y1=\"12\" x2=\"21\" y2=\"12\"/><line x1=\"3\" y1=\"6\" x2=\"21\" y2=\"6\"/><line x1=\"3\" y1=\"18\" x2=\"21\" y2=\"18\"/></svg>";

        case 'mail':
        case 'email':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><rect width=\"20\" height=\"16\" x=\"2\" y=\"4\" rx=\"2\"/><path d=\"m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7\"/></svg>";

        case 'bulb':
        case 'dica':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><path d=\"M9 18h6\"/><path d=\"M10 22h4\"/><path d=\"M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5.76.76 1.23 1.52 1.41 2.5\"/></svg>";

        default:
            return '';
    }
}
