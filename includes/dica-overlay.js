(() => {
    const imagens = [
        'Bob1.webp', 'Bob2.webp', 'Bob3.webp',
        'Bub1.webp', 'Bub2.webp', 'Bub3.webp',
        'Pep1.webp', 'Pep2.webp', 'Pep3.webp'
    ];
    let ultimaImagem = -1;

    window.abrirDicaOverlay = (mensagem) => {
        const dialogo = document.getElementById('dica-flutuante');
        const texto = document.getElementById('dica-texto');
        const personagem = document.getElementById('dica-personagem');
        if (!dialogo || !texto || !personagem || dialogo.open) return false;

        let indiceImagem;
        do {
            indiceImagem = Math.floor(Math.random() * imagens.length);
        } while (imagens.length > 1 && indiceImagem === ultimaImagem);

        ultimaImagem = indiceImagem;
        personagem.src = `../img_dica/${imagens[indiceImagem]}`;
        texto.textContent = mensagem;
        dialogo.showModal();
        dialogo.querySelector('.dica-entendi')?.focus();
        return true;
    };

    window.fecharDicaOverlay = () => {
        const dialogo = document.getElementById('dica-flutuante');
        if (!dialogo?.open) return;
        dialogo.close();
        document.getElementById('btn-dica')?.focus();
    };
})();