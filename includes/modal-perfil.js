(() => {
    const modal = document.getElementById('modal-login-sugestao');
    if (!modal) return;

    window.abrirModalPerfil = () => modal.showModal();
    window.fecharModalPerfil = () => modal.close();

    modal.querySelector('#btn-cancelar-login').addEventListener('click', window.fecharModalPerfil);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) window.fecharModalPerfil();
    });
})();
