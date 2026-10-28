<?php
/**
 * Modal de cadastro/edição de atleta, reaproveitado pela lista de atletas e pela grade de inscrição do evento.
 *
 * Uso: openAthleteForm(id|null, { dojoId, name, guest, onSaved(data) }). Sem onSaved, recarrega a página.
 * name e guest pré-preenchem o cadastro novo (busca da grade de inscrição).
 * O formulário vem de athletes/fragment/{new,edit/{id}} e salva por fetch (JSON).
 */
?>
<div id="athleteFormModal" class="hidden fixed inset-0 z-[55] bg-slate-900/60 overflow-y-auto" role="dialog" aria-modal="true" aria-label="Cadastro do atleta">
    <div class="min-h-full flex items-start sm:items-center justify-center p-4">
        <div id="athleteFormBody" class="bg-white rounded-xl shadow-xl w-full max-w-3xl"></div>
    </div>
</div>

<script src="/assets/js/athlete-form.js"></script>
<script>
(() => {
    const modal = document.getElementById('athleteFormModal'), body = document.getElementById('athleteFormBody');
    let onSaved = null;

    const isOpen = () => !modal.classList.contains('hidden');

    function hide() {
        modal.classList.add('hidden');
        const other = document.getElementById('athleteViewModal');
        if (!other || other.classList.contains('hidden')) document.body.classList.remove('overflow-hidden');
    }

    window.openAthleteForm = async function (id, options = {}) {
        onSaved = options.onSaved || (() => window.location.reload());
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        body.innerHTML = '<p class="p-10 text-center text-sm text-slate-500">Carregando…</p>';

        const query = new URLSearchParams();
        if (options.dojoId) query.set('dojo_id', options.dojoId);
        if (options.name) query.set('name', options.name);
        if (options.guest) query.set('guest', '1');

        const url = id ? `/fgkirs-admin/athletes/fragment/edit/${id}` : '/fgkirs-admin/athletes/fragment/new' + (query.toString() ? `?${query}` : '');

        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
            body.innerHTML = await res.text();
        } catch (e) {
            body.innerHTML = '<p class="p-6 text-sm text-red-700">Não foi possível carregar. Tente novamente.</p>';
            return;
        }

        const form = body.querySelector('[data-athlete-form]');
        if (!form) return;

        initAthleteForm(form);
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const err = form.querySelector('[data-form-error]'), btn = form.querySelector('button[type="submit"]');
            err.classList.add('hidden');
            btn.disabled = true;

            try {
                const res  = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (!data.ok) throw new Error(data.message);
                hide();
                onSaved(data);
            } catch (ex) {
                err.textContent = ex instanceof SyntaxError ? 'Não foi possível salvar. Tente novamente.' : (ex.message || 'Não foi possível salvar.');
                err.classList.remove('hidden');
                btn.disabled = false;
            }
        });
    };

    modal.addEventListener('click', e => { if (e.target.closest('[data-modal-close]')) hide(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && isOpen()) hide(); });
})();
</script>
