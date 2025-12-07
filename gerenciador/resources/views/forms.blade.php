<!--by Gustavo Cavalheiro, Matias Amma e João Bortoloti-->

<div class="modal" id="formsModal">
    <div class="modal-dialog">

        <div class="modal-header">
            <h3 class="modal-title">Nova análise</h3>
            
            <button type="button" id="closeFormsModal" class="button button-secondary button-icon">
                &times;
            </button>
        </div>

        <form id="analiseForm" action="{{ route('forms.salvar') }}" method="POST">
            @csrf

            <div class="grid-inputs">
                <select name="materia_prima" required>
                    <option value="" disabled selected hidden>Selecione uma matéria-prima</option>
                    <option value="soja">Soja</option>
                    <option value="açúcar">Açúcar</option>
                    <option value="milho">Milho</option>
                    <option value="cacau">Cacau</option>
                </select>

                <input type="text" id="volume" name="volume" placeholder="Volume (em kg)" required>
                <input type="text" id="preco_alvo" name="preco_alvo" placeholder="Custo atual de compra (em R$)" required>
                <input type="text" id="cep" name="cep" placeholder="CEP de entrega" maxlength="9" required>
            </div>

            <div class="center-btn">
                <button class="button-primary" type="submit">Solicitar Análise</button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
    <p class="loading-text">Processando análise...</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ==========================================================
    // 1. LÓGICA DE ABRIR/FECHAR MODAL
    // ==========================================================
    const formsModal = document.getElementById('formsModal');
    const closeFormsModalBtn = document.getElementById('closeFormsModal');
    const openFormsModalBtn = document.getElementById('btnOpenFormsModal');
    const loadingOverlay = document.getElementById('loadingOverlay');

    const closeForms = () => {
        if(formsModal) formsModal.classList.remove('active');
    };

    const openForms = (e) => {
        if (e) e.preventDefault();
        if(formsModal) formsModal.classList.add('active');
    };

    const showLoading = () => {
        if(loadingOverlay) loadingOverlay.classList.add('active');
    };

    const hideLoading = () => {
        if(loadingOverlay) loadingOverlay.classList.remove('active');
    };

    // Eventos
    closeFormsModalBtn?.addEventListener('click', closeForms);
    
    formsModal?.addEventListener('click', (e) => {
        if (e.target === formsModal) closeForms();
    });

    openFormsModalBtn?.addEventListener('click', openForms);


    // ==========================================================
    // 2. MÁSCARAS E VALIDAÇÃO
    // ==========================================================
    const form = document.getElementById('analiseForm');
    const volumeInput = document.getElementById('volume');
    const precoInput = document.getElementById('preco_alvo');
    const cepInput = document.getElementById('cep');

    if (form) {
        // --- Máscara Volume (apenas números e vírgula) ---
        volumeInput?.addEventListener('input', (e) => {
            let value = e.target.value.replace(/[^0-9,]/g, '');
            if ((value.match(/,/g) || []).length > 1) {
                 value = value.substring(0, value.lastIndexOf(','));
            }
            e.target.value = value;
        });

        // --- Máscara Preço (R$ 0.000,00) ---
        precoInput?.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, ''); 
            if (value === '') {
                e.target.value = '';
                return;
            }
            value = (parseInt(value) / 100).toFixed(2) + '';
            value = value.replace('.', ',');
            value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            e.target.value = 'R$ ' + value;
        });

        // --- Máscara CEP (XXXXX-XXX) ---
        cepInput?.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.substring(0, 5) + '-' + value.substring(5, 8);
            }
            e.target.value = value;
        });

        // --- Validação ao Enviar (Submit) ---
        form.addEventListener('submit', (e) => {
            let valid = true;
            let mensagens = [];

            // Valida Volume
            let volumeRaw = volumeInput.value.replace(/\./g, '').replace(',', '.');
            if (!volumeRaw || parseFloat(volumeRaw) <= 0) {
                valid = false;
                volumeInput.classList.add('input-error');
                mensagens.push("O volume deve ser maior que zero.");
            } else {
                volumeInput.classList.remove('input-error');
            }

            // Valida Preço
            let precoRaw = precoInput.value.replace('R$ ', '').replace(/\./g, '').replace(',', '.');
            if (!precoRaw || parseFloat(precoRaw) <= 0) {
                valid = false;
                precoInput.classList.add('input-error');
                mensagens.push("O custo atual de compra deve ser maior que zero.");
            } else {
                precoInput.classList.remove('input-error');
            }

            // Valida CEP
            if (cepInput.value.length < 9) {
                valid = false;
                cepInput.classList.add('input-error');
                mensagens.push("Informe um CEP completo.");
            } else {
                cepInput.classList.remove('input-error');
            }

            // Se inválido, bloqueia e mostra Toast
            if (!valid) {
                e.preventDefault();
                if (typeof showToast === 'function') {
                    showToast("Corrija os erros:\n- " + mensagens.join("\n- "), "error");
                } else {
                    alert("Corrija os erros:\n- " + mensagens.join("\n- "));
                }
            } else {
                // Se válido, mostra o loading
                showLoading();
            }
        });
    }
});
</script>

<style>
/* ========================
   MODAL LAYOUT
   ======================== */
.modal {
    position: fixed; inset: 0; 
    background: rgba(0,0,0,.65); 
    display: none; 
    justify-content: center; align-items: center; 
    z-index: 999;
}

.modal.active { display: flex; }

.modal-dialog {
    background: #fff; 
    padding: 2rem 2.4rem; 
    border-radius: 16px; 
    width: 560px; max-width: 90%; 
    box-shadow: 0 6px 16px rgba(0,0,0,0.18); 
    animation: fadeIn .2s ease-in-out;
    display: flex; flex-direction: column;
}

/* ========================
   LOADING OVERLAY
   ======================== */
.loading-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(8px);
    display: none;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    z-index: 9999;
}

.loading-overlay.active {
    display: flex;
}

.loading-spinner {
    width: 60px;
    height: 60px;
    border: 5px solid rgba(255, 255, 255, 0.2);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.loading-text {
    color: #fff;
    font-size: 1.1rem;
    font-weight: 500;
    margin-top: 1.5rem;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* ========================
   HEADER & BOTÃO X
   ======================== */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
}

.button-secondary {
    background: #ffffff;
    border: 1px solid #d1d5db;
    color: #374151;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}
.button-secondary:hover {
    background: #f9fafb;
    transform: translateY(-1px);
}
.button-icon {
    padding: 0.6rem 0.8rem;
    line-height: 1;
    font-size: 1.2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* ========================
   FORMULÁRIO E INPUTS
   ======================== */
.grid-inputs {
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    column-gap: 14px; 
    row-gap: 20px;
}

.grid-inputs input, select {
    background: #f2f2f2; 
    padding: .95rem; 
    border-radius: 10px; 
    border: 1px solid #d8d8d8; 
    font-size: 0.95rem;
    box-shadow: 0 2px 3px rgba(0,0,0,0.12);
    width: 100%;
    outline: none;
    transition: border 0.2s;
}

select { color: #000; cursor: pointer; }
select:invalid { color: #757575; }
option { color: #000; }
option[value=""] { display: none; }

.center-btn { text-align: center; margin-top: 22px; }

.button-primary {
    background: #3e3e3e; color: white; 
    padding: .7rem 1.5rem; 
    border-radius: 8px; border: none; cursor: pointer; 
    font-size: 1rem; 
    box-shadow: 0 3px 6px rgba(0,0,0,0.22);
}
.button-primary:hover { opacity: .9; }

/* ========================
   ESTADOS DE ERRO
   ======================== */
.input-error {
    border: 1px solid #ff4d4d !important;
    background-color: #fff0f0 !important;
    animation: shake 0.3s;
}

@keyframes shake {
  0% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  50% { transform: translateX(5px); }
  75% { transform: translateX(-5px); }
  100% { transform: translateX(0); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(.96); }
    to { opacity: 1; transform: scale(1); }
}
</style>