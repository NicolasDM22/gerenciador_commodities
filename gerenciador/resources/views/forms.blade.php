<!-- resources/views/forms.blade.php -->
<!-- Modal -->
<div class="modal" id="formsModal">
    <div class="modal-dialog">

        <button class="close-btn" type="button" id="closeFormsModal">×</button>

        <form action="{{ route('forms.salvar') }}" method="POST">
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

    openFormsModalBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        toggleFormsModal(true);
    });

    closeFormsModalBtn?.addEventListener('click', () => toggleFormsModal(false));

    formsModal?.addEventListener('click', (e) => {
        if (e.target === formsModal) toggleFormsModal(false);
    });
});
</script>

<style>
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

.modal-dialog {
    position: relative;
    background: #fff;
    padding: 2rem 2.4rem;
    border-radius: 16px;
    width: 560px;      
    max-width: 90%;
    box-shadow: 0 6px 16px rgba(0,0,0,0.18);
    animation: fadeIn .2s ease-in-out;
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

.grid-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    column-gap: 14px;  /* espaço horizontal */
    row-gap: 20px;     /* espaço vertical maior */
}

.grid-inputs input {
    background: #f2f2f2;
    padding: .95rem;   /* inputs um pouco maiores */
    border-radius: 10px;
    border: 1px solid #d8d8d8;
    font-size: 0.95rem;

    /* sombra leve */
    box-shadow: 0 2px 3px rgba(0,0,0,0.12);
}

.center-btn {
    text-align: center;
    margin-top: 22px;
}

.button-primary {
    background: #3e3e3e;
    color: white;
    padding: .7rem 1.5rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    box-shadow: 0 3px 6px rgba(0,0,0,0.22);
}

.button-primary:hover {
    opacity: .9;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(.96); }
    to { opacity: 1; transform: scale(1); }
}
</style>
