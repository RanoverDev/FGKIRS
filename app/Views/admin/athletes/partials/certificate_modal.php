<?php
use Helpers\Csrf;
?>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
<style>
    #certEditor { font-family: 'Open Sans', sans-serif; font-size: 21.6px; line-height: 1.5625; text-align: justify; width: 715px; color: #000; }
    #certEditor p, #certEditor div { margin: 0; }
    #certEditor:focus { outline: 2px solid #b91c1c; outline-offset: 4px; }
    #certEditor [data-var] { background: #fef9c3; border-radius: 2px; }
</style>

<div id="certModal" class="hidden fixed inset-0 z-[60] bg-slate-900/60 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="certTitle">
    <div class="min-h-full flex items-start sm:items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl">
            <div class="flex items-start justify-between gap-4 px-6 pt-5">
                <div>
                    <h2 id="certTitle" class="text-xl font-bold text-slate-900">Gerar certificado de graduação</h2>
                    <p id="certAthlete" class="text-sm text-slate-500 mt-0.5"></p>
                </div>
                <button type="button" data-cert-close class="text-slate-400 hover:text-slate-700 text-2xl leading-none" aria-label="Fechar">&times;</button>
            </div>

            <div class="px-6 py-4">
                <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm mb-4">
                    <p class="font-semibold mb-1">Atualize os dados do atleta antes de gerar o certificado.</p>
                    <p>
                        O texto é montado com o cadastro do atleta (nome completo, estilo e graduação atual) e com a cidade do dojo.
                        Se algo estiver desatualizado, corrija o cadastro e use “Restaurar texto padrão”.
                    </p>
                    <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                        <a id="certEditLink" href="#" target="_blank" rel="noopener" class="font-semibold text-red-700 hover:text-red-800 underline">Atualizar cadastro do atleta ↗</a>
                        <button type="button" id="certReload" class="font-semibold text-red-700 hover:text-red-800 underline">Restaurar texto padrão</button>
                    </p>
                </div>

                <div id="certError" class="hidden rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm mb-4"></div>
                <p id="certLoading" class="text-sm text-slate-500 py-8 text-center">Carregando…</p>

                <div id="certForm" class="hidden">
                    <div class="grid sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="certDate" class="block text-sm font-medium text-slate-700 mb-1">Data da graduação *</label>
                            <input type="date" id="certDate" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                        </div>
                        <div>
                            <label for="certLabel" class="block text-sm font-medium text-slate-700 mb-1">Graduação ao lado do kanji *</label>
                            <input type="text" id="certLabel" maxlength="60" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                        </div>
                    </div>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Texto do certificado</label>
                    <div class="flex flex-wrap items-center gap-1.5 mb-2 p-2 bg-slate-50 border border-slate-200 rounded-lg">
                        <button type="button" data-cert-cmd="bold" title="Negrito" class="w-9 h-9 rounded border border-slate-300 bg-white hover:bg-slate-100 font-bold">N</button>
                        <button type="button" data-cert-cmd="italic" title="Itálico" class="w-9 h-9 rounded border border-slate-300 bg-white hover:bg-slate-100 italic">I</button>
                        <button type="button" data-cert-cmd="underline" title="Sublinhado" class="w-9 h-9 rounded border border-slate-300 bg-white hover:bg-slate-100 underline">S</button>
                        <select id="certSize" title="Tamanho da fonte" class="h-9 px-2 rounded border border-slate-300 bg-white text-sm">
                            <option value="">Tamanho</option>
                            <?php foreach ([14, 16, 18, 20, 22, 24, 28, 32] as $px): ?>
                                <option value="<?= $px ?>"><?= $px ?> px</option>
                            <?php endforeach; ?>
                        </select>
                        <label class="flex items-center gap-1.5 text-sm text-slate-600 ml-1">
                            Cor
                            <input type="color" id="certColor" value="#000000" title="Cor do texto" class="w-9 h-9 p-0.5 rounded border border-slate-300 bg-white cursor-pointer">
                        </label>
                        <span class="text-xs text-slate-500 ml-auto">Selecione um trecho e aplique o formato.</span>
                    </div>

                    <div class="overflow-x-auto border border-slate-300 rounded-lg bg-white p-4">
                        <div id="certEditor" contenteditable="true" spellcheck="true"></div>
                    </div>
                    <p class="text-xs text-slate-500 mt-1.5">Trechos em amarelo vêm do cadastro; o destaque não aparece no certificado impresso.</p>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 px-6 pb-5">
                <button type="button" data-cert-close class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition">Cancelar</button>
                <button type="button" id="certSubmit" disabled class="bg-red-700 hover:bg-red-800 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold py-2 px-6 rounded-lg transition">Gerar certificado</button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const csrf = <?= json_encode(Csrf::token()) ?>;
    const $ = id => document.getElementById(id);
    const modal = $('certModal'), editor = $('certEditor'), form = $('certForm');
    const dateInput = $('certDate'), labelInput = $('certLabel'), submit = $('certSubmit');
    let athleteId = null, savedRange = null;

    const showError = msg => { $('certError').textContent = msg || ''; $('certError').classList.toggle('hidden', !msg); };

    const brDate = iso => iso ? iso.split('-').reverse().join('/') : '';

    async function load() {
        showError('');
        form.classList.add('hidden');
        $('certLoading').classList.remove('hidden');
        submit.disabled = true;

        try {
            const res = await fetch(`/fgkirs-admin/athletes/certificate-draft/${athleteId}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!data.ok) throw new Error(data.message);

            editor.innerHTML = data.body_html;
            dateInput.value = data.date;
            dateInput.max = data.date;
            labelInput.value = data.label;
            form.classList.remove('hidden');
            submit.disabled = false;
        } catch (e) {
            showError(e.message || 'Não foi possível carregar os dados do atleta.');
        } finally {
            $('certLoading').classList.add('hidden');
        }
    }

    function open(btn) {
        athleteId = btn.dataset.certificateAthlete;
        $('certAthlete').textContent = btn.dataset.certificateName || '';
        $('certEditLink').href = `/fgkirs-admin/athletes/edit/${athleteId}`;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        load();
    }

    function close() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    document.querySelectorAll('[data-certificate-athlete]').forEach(btn => btn.addEventListener('click', () => open(btn)));
    modal.querySelectorAll('[data-cert-close]').forEach(btn => btn.addEventListener('click', close));
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) close(); });

    $('certReload').addEventListener('click', () => {
        if (confirm('Restaurar o texto padrão? As edições feitas na caixa de texto serão perdidas.')) load();
    });

    // Data e graduação digitadas atualizam os trechos correspondentes do texto
    dateInput.addEventListener('input', () => editor.querySelectorAll('[data-var="date"]').forEach(s => s.textContent = brDate(dateInput.value)));
    labelInput.addEventListener('input', () => editor.querySelectorAll('[data-var="graduation"]').forEach(s => s.textContent = labelInput.value));

    // Seleção guardada: o seletor de tamanho e o de cor tiram o foco do editor
    document.addEventListener('selectionchange', () => {
        const sel = window.getSelection();
        if (sel.rangeCount && editor.contains(sel.anchorNode)) savedRange = sel.getRangeAt(0).cloneRange();
    });

    function restoreSelection() {
        editor.focus();
        if (!savedRange) return;
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(savedRange);
    }

    modal.querySelectorAll('[data-cert-cmd]').forEach(btn => {
        btn.addEventListener('mousedown', e => e.preventDefault());
        btn.addEventListener('click', () => { restoreSelection(); document.execCommand(btn.dataset.certCmd); });
    });

    $('certColor').addEventListener('input', e => {
        restoreSelection();
        document.execCommand('styleWithCSS', false, true);
        document.execCommand('foreColor', false, e.target.value);
    });

    $('certSize').addEventListener('change', e => {
        const px = e.target.value;
        e.target.value = '';
        if (!px) return;

        restoreSelection();
        document.execCommand('styleWithCSS', false, false);
        document.execCommand('fontSize', false, '7');
        editor.querySelectorAll('font[size="7"]').forEach(f => {
            const span = document.createElement('span');
            span.style.fontSize = px + 'px';
            span.innerHTML = f.innerHTML;
            f.replaceWith(span);
        });
        document.execCommand('styleWithCSS', false, true);
    });

    function cleanHtml() {
        const copy = editor.cloneNode(true);
        copy.querySelectorAll('[data-var]').forEach(el => el.removeAttribute('data-var'));
        copy.querySelectorAll('font').forEach(f => {
            const span = document.createElement('span');
            if (f.color) span.style.color = f.color;
            span.innerHTML = f.innerHTML;
            f.replaceWith(span);
        });
        return copy.innerHTML;
    }

    submit.addEventListener('click', async () => {
        showError('');
        submit.disabled = true;
        submit.textContent = 'Gerando…';

        const body = new FormData();
        body.append('csrf_token', csrf);
        body.append('body_html', cleanHtml());
        body.append('graduation_label', labelInput.value);
        body.append('graduation_date', dateInput.value);

        try {
            const res = await fetch(`/fgkirs-admin/athletes/certificate-store/${athleteId}`, { method: 'POST', body, headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!data.ok) throw new Error(data.message);
            window.location.href = data.url;
        } catch (e) {
            showError(e.message || 'Não foi possível gerar o certificado.');
            submit.disabled = false;
            submit.textContent = 'Gerar certificado';
        }
    });
})();
</script>
