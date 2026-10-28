<?php
use Helpers\Csrf;
?>
<div id="athleteViewModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 overflow-y-auto" role="dialog" aria-modal="true" aria-label="Dados do atleta">
    <div class="min-h-full flex items-start sm:items-center justify-center p-4">
        <div id="athleteViewBody" class="bg-white rounded-xl shadow-xl w-full max-w-3xl"></div>
    </div>
</div>

<?php require __DIR__ . '/athlete_form_modal.php'; ?>

<script>
(() => {
    const csrf = <?= json_encode(Csrf::token()) ?>;
    const base = '/fgkirs-admin/athletes';
    const viewModal = document.getElementById('athleteViewModal'), viewBody = document.getElementById('athleteViewBody');
        let viewId = null, dirty = false;

    const isOpen = m => !m.classList.contains('hidden');
    const lockScroll = () => document.body.classList.toggle('overflow-hidden', isOpen(viewModal) || isOpen(document.getElementById('athleteFormModal')));

    function show(modal) { modal.classList.remove('hidden'); lockScroll(); }
    function hide(modal) { modal.classList.add('hidden'); lockScroll(); }

    async function load(body, url) {
        body.innerHTML = '<p class="p-10 text-center text-sm text-slate-500">Carregando…</p>';
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
            body.innerHTML = await res.text();
        } catch (e) {
            body.innerHTML = '<p class="p-6 text-sm text-red-700">Não foi possível carregar. Tente novamente.</p>';
        }
    }

    async function openView(id) {
        viewId = id; dirty = false;
        show(viewModal);
        await load(viewBody, `${base}/fragment/view/${id}`);
    }

    function closeView() {
        hide(viewModal);
        viewId = null;
        if (dirty) window.location.reload();
    }

    const openForm = id => window.openAthleteForm(id);

    async function post(url, fields) {
        const body = new FormData();
        body.append('csrf_token', csrf);
        Object.entries(fields || {}).forEach(([k, v]) => body.append(k, v));
        const res = await fetch(url, { method: 'POST', body, headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (!data.ok) throw new Error(data.message);
        return data;
    }

    function viewError(msg) {
        const box = viewBody.querySelector('[data-view-error]');
        if (box) { box.textContent = msg; box.classList.remove('hidden'); }
    }

    document.addEventListener('click', async e => {
        const t = e.target;
        const viewBtn = t.closest('[data-athlete-view]');
        const editBtn = t.closest('[data-athlete-edit]');

        if (viewBtn) return openView(viewBtn.dataset.athleteView);
        if (editBtn) return openForm(editBtn.dataset.athleteEdit);
        if (t.closest('[data-athlete-new]')) return openForm(null);

        const closeBtn = t.closest('[data-modal-close]');
        if (closeBtn && viewModal.contains(closeBtn)) {
            closeView();
            return;
        }

        const action = t.closest('[data-action]');
        if (!action || !viewModal.contains(action)) return;

        const wrap = viewBody.querySelector('[data-athlete-id]');
        const athleteId = wrap ? wrap.dataset.athleteId : viewId;

        try {
            if (action.dataset.action === 'edit') {
                openForm(athleteId);
            } else if (action.dataset.action === 'toggle-status') {
                if (action.dataset.confirm && !confirm(action.dataset.confirm)) return;
                await post(`${base}/toggle-status/${athleteId}`, { query: window.location.search.slice(1) });
                dirty = true;
                await load(viewBody, `${base}/fragment/view/${athleteId}`);
            } else if (action.dataset.action === 'delete-certificate') {
                if (!confirm(action.dataset.confirm)) return;
                await post(`/fgkirs-admin/certificates/delete/${action.dataset.certificateId}`);
                await load(viewBody, `${base}/fragment/view/${athleteId}`);
            }
        } catch (ex) {
            viewError(ex.message || 'Não foi possível concluir a ação.');
        }
    });

    // Certificado gerado pelo pop-up: atualiza a lista de certificados da ficha aberta
    document.addEventListener('certificate:issued', () => { if (viewId) load(viewBody, `${base}/fragment/view/${viewId}`); });

    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        const cert = document.getElementById('certModal');
        if (cert && isOpen(cert)) return;
        if (isOpen(document.getElementById('athleteFormModal'))) return;
        if (isOpen(viewModal)) closeView();
    });
})();
</script>
