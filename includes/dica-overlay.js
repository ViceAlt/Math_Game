(() => {
    const imagens = [
        'Bob1.webp', 'Bob2.webp', 'Bob3.webp',
        'Bub1.webp', 'Bub2.webp', 'Bub3.webp',
        'Pep1.webp', 'Pep2.webp', 'Pep3.webp'
    ];
    let ultimaImagem = -1;
    let imagemPrecarregada = null;
    let carregamentoAtivo = false;
    const botaoDica = document.getElementById('btn-dica');

    function prepararProximaImagem() {
        if (imagemPrecarregada || carregamentoAtivo || imagens.length === 0) return;

        let indiceImagem;
        do {
            indiceImagem = Math.floor(Math.random() * imagens.length);
        } while (imagens.length > 1 && indiceImagem === ultimaImagem);

        carregamentoAtivo = true;
        const imagem = new Image();
        imagem.onload = () => {
            imagemPrecarregada = { indice: indiceImagem, arquivo: imagens[indiceImagem] };
            carregamentoAtivo = false;
            if (botaoDica) botaoDica.disabled = false;
        };
        imagem.onerror = () => {
            imagens.splice(indiceImagem, 1);
            carregamentoAtivo = false;
            prepararProximaImagem();
        };
        imagem.src = `../img_dica/${imagens[indiceImagem]}`;
    }

    if (botaoDica) botaoDica.disabled = true;
    prepararProximaImagem();

    window.abrirDicaOverlay = (mensagem) => {
        const dialogo = document.getElementById('dica-flutuante');
        const texto = document.getElementById('dica-texto');
        const personagem = document.getElementById('dica-personagem');
        if (!dialogo || !texto || !personagem || dialogo.open || !imagemPrecarregada) return false;

        ultimaImagem = imagemPrecarregada.indice;
        personagem.src = `../img_dica/${imagemPrecarregada.arquivo}`;
        imagemPrecarregada = null;
        if (botaoDica) botaoDica.disabled = true;
        texto.textContent = mensagem;
        dialogo.showModal();
        dialogo.querySelector('.dica-entendi')?.focus();
        prepararProximaImagem();
        return true;
    };

    window.fecharDicaOverlay = () => {
        const dialogo = document.getElementById('dica-flutuante');
        if (!dialogo?.open) return;
        dialogo.close();
        document.getElementById('btn-dica')?.focus();
    };
})();