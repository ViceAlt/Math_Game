const cards = document.querySelectorAll('[data-reveal]');

if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.body.classList.add('revelacoes-ativas');

    const observador = new IntersectionObserver((entradas, observer) => {
        entradas.forEach((entrada) => {
            if (entrada.isIntersecting) {
                entrada.target.classList.add('visivel');
                observer.unobserve(entrada.target);
            }
        });
    }, { threshold: 0.15 });

    cards.forEach((card) => observador.observe(card));
} else {
    cards.forEach((card) => card.classList.add('visivel'));
}
