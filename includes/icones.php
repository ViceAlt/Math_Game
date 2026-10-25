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
        case 'trophy-fill':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M19 4h-2V3a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v1H5a3 3 0 0 0-3 3v2a4 4 0 0 0 4 4h1.1A6 6 0 0 0 11 15.9V18H8a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2h-3v-2.1A6 6 0 0 0 16.9 13H18a4 4 0 0 0 4-4V7a3 3 0 0 0-3-3zM4 9V7a1 1 0 0 1 1-1h2v4.8A2 2 0 0 1 4 9zm16 0a2 2 0 0 1-3 1.8V6h2a1 1 0 0 1 1 1v2z\"/></svg>";

        case 'home':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M12 2.09 2 11h3v10a1 1 0 0 0 1 1h4v-7h4v7h4a1 1 0 0 0 1-1V11h3L12 2.09z\"/></svg>";

        case 'map':
        case 'conteudos':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><polygon points=\"1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6\"/><line x1=\"8\" y1=\"2\" x2=\"8\" y2=\"18\"/><line x1=\"16\" y1=\"6\" x2=\"16\" y2=\"22\"/></svg>";

        case 'game':
        case 'gamepad':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path fill-rule=\"evenodd\" clip-rule=\"evenodd\" d=\"M17 5H7a5 5 0 0 0-4.9 6.08l1.2 5.5A4.5 4.5 0 0 0 7.7 20c1.5 0 2.9-.8 3.8-2l.5-.7.5.7c.9 1.2 2.3 2 3.8 2a4.5 4.5 0 0 0 4.4-3.42l1.2-5.5A5 5 0 0 0 17 5zM9 9a1 1 0 0 1 1 1v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1H7a1 1 0 1 1 0-2h1v-1a1 1 0 0 1 1-1zm6 4a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4zm2-2a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4zm0 4a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4zm2-2a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4z\"/></svg>";

        case 'settings':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\"><circle cx=\"12\" cy=\"12\" r=\"3\"/><path d=\"M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z\"/></svg>";

        case 'user':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z\"/></svg>";

        case 'key':
        case 'login':
            return "<svg {$dim} {$cls} viewBox=\"0 0 24 24\" fill=\"currentColor\" aria-hidden=\"true\"><path d=\"M4 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2v-2a1 1 0 1 0-2 0v2H4V5h7v2a1 1 0 1 0 2 0V5a2 2 0 0 0-2-2H4zm11.3 5.3a1 1 0 0 0-1.42 1.42L15.6 11H9a1 1 0 1 0 0 2h6.6l-1.7 1.7a1 1 0 0 0 1.42 1.4l3.4-3.4a1 1 0 0 0 0-1.4l-3.4-3.4z\"/></svg>";

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
