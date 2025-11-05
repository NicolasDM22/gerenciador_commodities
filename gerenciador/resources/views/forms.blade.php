<!-- resources/views/forms.blade.php -->
<!-- Modal -->
<div class="modal" id="formsModal">
    <div class="modal-dialog">

        <button class="close-btn" type="button" id="closeFormsModal">×</button>

        <form action="{{ route('forms.salvar') }}" method="POST" class="form-grid">
            @csrf

            <div class="grid-inputs">
                <input type="text" name="materia_prima" placeholder="Matéria-prima" required>
                <input type="text" name="volume" placeholder="Volume" required>

                <input type="text" name="preco_atual" placeholder="Preço atual de compra" required>
                <input type="text" name="unidade" placeholder="Unidade de medida" required>

                <input type="text" name="preco_alvo" placeholder="Preço alvo" required>
                <input type="text" name="cep" placeholder="CEP de entrega" required>
            </div>

            <div class="center-btn">
                <button class="button-primary" type="submit">Solicitar Análise</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const formsModal = document.getElementById('formsModal');
    const openFormsModalBtn = document.getElementById('openFormsModal');
    const closeFormsModalBtn = document.getElementById('closeFormsModal');

    const toggleFormsModal = (show) => {
        if (show) formsModal.classList.add('active');
        else formsModal.classList.remove('active');
    };

    // Abrir Modal
    openFormsModalBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        toggleFormsModal(true);
    });

    // Fechar pelo X
    closeFormsModalBtn?.addEventListener('click', () => toggleFormsModal(false));

    // Fechar clicando no fundo escuro
    formsModal?.addEventListener('click', (e) => {
        if (e.target === formsModal) toggleFormsModal(false);
    });
});
</script>

<style>
/* Fundo modal */
.modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.65);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 999;
}

.modal.active {
    display: flex;
}

/* Caixa branca */
.modal-dialog {
    position: relative;
    background: #fff;
    padding: 2.2rem 2.6rem;
    border-radius: 14px;
    width: 650px;      
    max-width: 90%;    
    animation: fadeIn .2s ease-in-out;
}

/* Botão Fechar */
.close-btn {
    position: absolute;
    top: 10px;
    right: 14px;
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
}

/* Grid de inputs */
.grid-inputs {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}

/* Inputs caixas */
.grid-inputs input {
    background: #f4f4f4;
    padding: .7rem;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 0.95rem;
}

/* Botão principal */
.center-btn {
    text-align: center;
    margin-top: 1.4rem;
}

.button-primary {
    background: #3e3e3e;
    color: white;
    padding: .75rem 1.7rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 1rem;
}

.button-primary:hover {
    opacity: .9;
}

/* Animação */
@keyframes fadeIn {
    from { opacity: 0; transform: scale(.94); }
    to { opacity: 1; transform: scale(1); }
}
</style>
