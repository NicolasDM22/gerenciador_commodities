<!--by Gustavo e Nicolas-->

<div class="modal" id="profileModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Atualizar perfil</h3>
            <button class="close-btn" type="button" id="closeProfileModal">x</button>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="form-grid">
            @csrf
            <div class="form-group">
                <label for="usuario">Usuário</label>
                <input id="usuario" name="usuario" type="text" value="{{ old('usuario', $user->usuario ?? '') }}" required>
            </div>

            <div class="form-group">
                <label for="nome">Nome completo</label>
                <input id="nome" name="nome" type="text" value="{{ old('nome', $user->nome ?? '') }}">
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required>
            </div>

            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input id="telefone" name="telefone" type="tel"
                       value="{{ old('telefone', $user->telefone ?? '') }}"
                       pattern="[\d\s\-\(\)\+]{10,20}" required>
            </div>

            <div class="form-group">
                <label for="endereco">Endereço</label>
                <input id="endereco" name="endereco" type="text"
                       value="{{ old('endereco', $user->endereco ?? '') }}"
                       maxlength="255" required>
            </div>

            <div class="form-group">
                <label for="nova_senha">Nova senha</label>
                <input id="nova_senha" name="nova_senha" type="password" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="nova_senha_confirmation">Confirmar nova senha</label>
                <input id="nova_senha_confirmation" name="nova_senha_confirmation" type="password" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="foto">Foto de perfil</label>
                <input id="foto" name="foto" type="file" accept="image/*">
            </div>

            <div class="modal-footer">
                <button class="button button-outline" type="button" id="cancelProfileModal">Cancelar</button>
                <button class="button button-primary" type="submit">Salvar alterações</button>
            </div>
        </form>
    </div>
</div>

<style>
    .modal {
        position: fixed;
        inset: 0;
        background: rgba(17, 24, 39, 0.4);
        backdrop-filter: blur(2px);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        z-index: 10;
    }

    .modal.active {
        display: flex;
    }

    .modal-dialog {
        background: var(--white);
        border-radius: 22px;
        width: min(720px, 95%);
        padding: 2rem;
        display: grid;
        gap: 1.5rem;
        box-shadow: 0 30px 65px -28px rgba(15, 23, 42, 0.45);
        animation: modalFadeIn 0.25s ease;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--gray-800);
    }

    /* GRID EM DUAS COLUNAS */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem 1.2rem;
    }

    .form-group {
        display: grid;
        gap: 0.4rem;
    }

    .form-group label {
        font-size: 0.9rem;
        color: var(--gray-600);
        font-weight: 500;
    }

    .form-group input {
        padding: 0.7rem 1rem;
        border-radius: 12px;
        border: 1px solid var(--gray-300);
        font-size: 0.95rem;
        background: #f9fafb;
        transition: border-color 0.2s, background-color 0.2s;
    }

    .form-group input:focus {
        outline: none;
        border-color: #2563eb;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    /* CAMPOS LARGOS */
    .form-group:has(input[type="file"]),
    .form-group:has(#endereco) {
        grid-column: span 2;
    }

    /* CAMPOS DE SENHA LADO A LADO */
    .form-group:has(#nova_senha),
    .form-group:has(#confirmar_senha) {
        grid-column: span 1;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        grid-column: span 2;
    }

    /* === BOTÕES DA TOP BAR E MODAL === */
    .button {
        cursor: pointer;
        border-radius: 10px;
        padding: 0.6rem 1.2rem;
        font-size: 0.95rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    /* Atualizar perfil — azul com borda */
    .button-outline {
        background: #fff;
        color: #2563eb;
        border: 1px solid #2563eb;
    }

    .button-outline:hover {
        background-color: #ebf2ff;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.15);
    }

    /* Sair — fundo escuro */
    .button-dark {
        background-color: #2f2f2f;
        color: white;
        border: none;
    }

    .button-dark:hover {
        background-color: #1f1f1f;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }

    .close-btn {
        position: absolute;
        top: 10px;
        right: 14px;
        background: none;
        border: none;
        font-size: 22px;
        cursor: pointer;
    }

    /* Animação */
    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsivo */
    @media (max-width: 640px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        const profileModal = document.getElementById('profileModal');
        const openBtn = document.getElementById('openProfileModal');
        const closeBtn = document.getElementById('closeProfileModal');
        const cancelBtn = document.getElementById('cancelProfileModal');

        const toggleModal = (show) => {
            profileModal?.classList.toggle('active', show);
        };

        openBtn?.addEventListener('click', () => toggleModal(true));
        closeBtn?.addEventListener('click', () => toggleModal(false));
        cancelBtn?.addEventListener('click', () => toggleModal(false));

        profileModal?.addEventListener('click', (event) => {
            if (event.target === profileModal) toggleModal(false);
        });
    });
</script

