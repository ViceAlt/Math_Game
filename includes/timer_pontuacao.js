(function () {
    let inicioQuestao = performance.now();

    window.timerPontuacaoQuestao = {
        iniciar() {
            inicioQuestao = performance.now();
        },
        multiplicador() {
            const segundos = (performance.now() - inicioQuestao) / 1000;
            const quedas = Math.floor(Math.max(0, segundos - 7) / 5);
            return Math.max(0, 3.5 - quedas * 0.5);
        }
    };
})();
