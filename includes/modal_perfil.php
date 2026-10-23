<!-- ===== MODAL: SUGESTÃO DE LOGIN/CADASTRO (ESTILO DUOLINGO) ===== -->
<dialog id="modal-login-sugestao" aria-labelledby="modal-login-titulo" aria-modal="true">
    <article class="modal-sugestao-card">
        <header class="modal-sugestao-header">
            <div class="modal-sugestao-icone-wrap" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                </svg>
            </div>
            <button type="button" class="btn-fechar-modal-x" aria-label="Fechar janela" onclick="fecharModalPerfil()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </header>

        <div class="modal-sugestao-corpo">
            <h2 id="modal-login-titulo">Acesse Sua Conta</h2>
            <p>Faça login ou crie seu perfil para salvar o progresso das 20 fases de cada ano letivo e colecionar estrelas!</p>
        </div>

        <footer class="modal-sugestao-footer">
            <a href="../login/login.php" class="btn-duo btn-duo-green btn-modal-acao" id="btn-ir-login">
                Fazer Login
            </a>
            <a href="../cadastro/cadastro.php" class="btn-duo btn-duo-outline btn-modal-acao" id="btn-ir-cadastro">
                Criar Nova Conta
            </a>
            <button type="button" class="btn-duo btn-duo-neutral btn-modal-acao" id="btn-cancelar-login" onclick="fecharModalPerfil()">
                Continuar Como Visitante
            </button>
        </footer>
    </article>
</dialog>
