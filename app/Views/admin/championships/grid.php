<?php
use Helpers\Auth;
use Helpers\Csrf;

$pageTitle = $championship['title'];
require_once __DIR__ . '/../layout/header.php';
require __DIR__ . '/partials/header.php';

$championshipId = (int) $championship['id'];
$dojoParam      = (Auth::isAdmin() && !empty($dojoId)) ? '?dojo_id=' . (int) $dojoId : '';
?>

<?php if (!empty($dojoId)): ?>

    <?php if (!$canWrite): ?>
        <div class="mb-4 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 px-4 py-3 text-sm">
            As inscrições deste evento não estão abertas: a grade está só para consulta.
        </div>
    <?php endif; ?>

    <div id="grid-errors" class="hidden mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm"></div>
    <div id="grid-notice" class="hidden mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm"></div>

    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <input type="search" id="grid-search" placeholder="Buscar atleta pelo nome"
            class="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700 bg-white border border-slate-300 rounded-lg px-4 py-2">
            <input type="checkbox" id="grid-only-checked" class="rounded border-slate-300 text-red-700">
            Só marcados
        </label>
        <button type="button" data-new-athlete
            class="inline-flex items-center justify-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">
            + Atleta do dojo
        </button>
        <button type="button" data-new-athlete data-guest="1" id="btn-new-guest"
            class="hidden items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">
            + Atleta convidado
        </button>
    </div>
    <p class="text-xs text-slate-500 -mt-2 mb-4">
        Comece a digitar o nome para achar o atleta na lista do dojo. Não está na lista? Cadastre aqui mesmo: <strong>atleta do dojo</strong> (aluno novo)
        ou <strong>atleta convidado</strong> (de outro dojo ou clube, inscrito só para esta competição). O lápis ao lado do nome edita os dados sem sair da grade.
    </p>

    <div id="grid-loading" class="bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-500">Carregando atletas…</div>
    <div id="grid-empty" class="hidden bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-500">
        Nenhum atleta ativo neste dojo. Clique em
        <button type="button" data-new-athlete class="text-red-700 font-semibold underline">Atleta do dojo</button> para cadastrar o primeiro.
    </div>

    <div id="grid" class="hidden bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-28">
        <div id="grid-head" class="hidden md:grid items-center bg-slate-900 text-white text-xs font-medium uppercase tracking-wider"></div>
        <div id="grid-rows" class="divide-y divide-slate-100"></div>
    </div>

    <!-- Barra de resumo fixa -->
    <div class="fixed bottom-0 inset-x-0 lg:left-64 z-30 bg-white border-t border-slate-200 shadow-[0_-4px_12px_rgba(0,0,0,0.06)]">
        <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex gap-6 text-sm text-slate-600">
                <span><strong id="sum-athletes" class="text-slate-900">0</strong> atleta(s)</span>
                <span><strong id="sum-entries" class="text-slate-900">0</strong> categoria(s)</span>
                <span id="sum-fee-wrap" class="hidden">Total <strong id="sum-fee" class="text-slate-900"></strong></span>
            </div>
            <?php if ($canWrite): ?>
                <div class="flex gap-2">
                    <button type="button" id="btn-save"
                        class="flex-1 sm:flex-none border border-slate-300 hover:bg-slate-50 text-slate-800 font-semibold py-2 px-5 rounded-lg transition disabled:opacity-50">
                        Salvar rascunho
                    </button>
                    <button type="button" id="btn-submit"
                        class="flex-1 sm:flex-none bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-5 rounded-lg transition disabled:opacity-50">
                        Enviar inscrições
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require __DIR__ . '/../athletes/partials/athlete_form_modal.php'; ?>

    <script>
        (function () {
            const BASE     = '/fgkirs-admin/championships/<?= $championshipId ?>';
            const DOJO_QS  = '<?= $dojoParam ?>';
            const DOJO_ID  = <?= (int) $dojoId ?>;
            const CSRF     = <?= json_encode(Csrf::token()) ?>;
            const CAN_WRITE = <?= $canWrite ? 'true' : 'false' ?>;

            const STATUS = {
                draft:     ['Rascunho',   'bg-amber-100 text-amber-800'],
                submitted: ['Enviada',    'bg-blue-100 text-blue-800'],
                confirmed: ['Confirmada', 'bg-green-100 text-green-800'],
                cancelled: ['Cancelada',  'bg-slate-200 text-slate-600'],
            };

            let data = null;
            const state = {}; // athleteId -> { weight, height, picks: { disciplineCode: categoryId|null } }
            const money = v => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            // Escapa também aspas: o texto vai para dentro de atributos (title, data-name, aria-label)
            const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const $ = id => document.getElementById(id);

            function cols() { return `minmax(14rem,2fr) 4.5rem 7rem repeat(${data.disciplines.length}, minmax(11rem,1.5fr)) 7rem`; }

            function locked(athlete) { return !CAN_WRITE || (athlete.status === 'confirmed' && !<?= Auth::isAdmin() ? 'true' : 'false' ?>); }

            function initState() {
                data.athletes.forEach(a => {
                    const picks = {};
                    data.disciplines.forEach(d => {
                        const c = a.cells[d.code];
                        picks[d.code] = c.checked ? c.categoryId : null;
                    });
                    state[a.id] = { weight: a.weight, height: a.height, picks };
                });
            }

            const norm = t => String(t ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

            function render() {
                const q = norm($('grid-search').value.trim());
                const only = $('grid-only-checked').checked;

                $('grid-head').style.gridTemplateColumns = cols();
                $('grid-head').innerHTML =
                    '<div class="px-4 py-3">Atleta</div><div class="px-2 py-3">Idade</div><div class="px-2 py-3">Peso / Altura</div>' +
                    data.disciplines.map(d => `<div class="px-2 py-3">${esc(d.name)}</div>`).join('') +
                    '<div class="px-2 py-3">Situação</div>';

                const rows = data.athletes.filter(a => {
                    if (q && !norm(a.name).includes(q)) return false;
                    if (only && !Object.values(state[a.id].picks).some(Boolean)) return false;
                    return true;
                });

                $('grid-rows').innerHTML = rows.length ? rows.map(rowHtml).join('') : noMatchHtml($('grid-search').value.trim());
                $('btn-new-guest').classList.toggle('hidden', !data.guests);
                $('btn-new-guest').classList.toggle('inline-flex', !!data.guests);
                updateSummary();
            }

            // Busca sem resultado: oferece cadastrar com o nome que acabou de ser digitado
            function noMatchHtml(typed) {
                if (!typed) return '<div class="p-8 text-center text-sm text-slate-500">Nenhum atleta marcado.</div>';

                return `<div class="p-8 text-center">
                    <p class="text-sm text-slate-600 mb-4">Nenhum atleta do dojo com “<strong>${esc(typed)}</strong>”.</p>
                    <div class="flex flex-wrap justify-center gap-2">
                        <button type="button" data-new-athlete data-name="${esc(typed)}" class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">Cadastrar como atleta do dojo</button>
                        ${data.guests ? `<button type="button" data-new-athlete data-guest="1" data-name="${esc(typed)}" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">Cadastrar como convidado</button>` : ''}
                    </div>
                </div>`;
            }

            function rowHtml(a) {
                const st = state[a.id];
                const disabled = locked(a) ? 'disabled' : '';
                const [label, style] = a.status ? STATUS[a.status] : ['', ''];

                const cells = data.disciplines.map(d => {
                    const c = a.cells[d.code];
                    if (c.categoryId) {
                        return `<label class="flex items-start gap-2 px-2 py-2 text-sm ${disabled ? 'text-slate-500' : 'cursor-pointer'}">
                            <input type="checkbox" data-athlete="${a.id}" data-disc="${d.code}" value="${c.categoryId}" ${disabled}
                                ${st.picks[d.code] === c.categoryId ? 'checked' : ''} class="mt-0.5 rounded border-slate-300 text-red-700 focus:ring-red-700">
                            <span><span class="md:hidden block text-[10px] uppercase text-slate-400">${esc(d.name)}</span>
                                ${esc(c.label)}${c.override ? ' <em class="text-xs text-amber-700">(exceção)</em>' : ''}${c.changed ? ' <em class="text-xs text-blue-700">(recalculada)</em>' : ''}</span>
                        </label>`;
                    }
                    return `<div class="px-2 py-2 text-xs text-slate-400"><span class="md:hidden block text-[10px] uppercase">${esc(d.name)}</span>${esc(c.reason ?? '—')}</div>`;
                }).join('');

                const dim = data.disciplines.every(d => !a.cells[d.code].categoryId && !['Informe o peso', 'Informe a altura'].includes(a.cells[d.code].reason)) ? 'opacity-60' : '';

                return `<div class="grid md:items-center gap-1 md:gap-0 p-3 md:p-0 ${dim}" style="grid-template-columns:${window.innerWidth >= 768 ? cols() : '1fr'}" data-row="${a.id}">
                    <div class="md:px-4 md:py-2">
                        <p class="font-semibold text-slate-900 text-sm flex items-center gap-1.5">
                            <span>${esc(a.name)}</span>
                            ${a.isGuest ? `<span class="shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800" title="${esc(a.guestOrigin || 'Convidado')}">Convidado</span>` : ''}
                            <button type="button" data-edit-athlete="${a.id}" title="Editar dados do atleta" aria-label="Editar dados de ${esc(a.name)}"
                                class="shrink-0 p-1 rounded text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
                            </button>
                        </p>
                        <p class="text-xs text-slate-500">${a.belt ? esc(a.belt) : 'Sem graduação'}${a.isPara ? ' · Para-karatê' : ''}${a.isGuest && a.guestOrigin ? ' · ' + esc(a.guestOrigin) : ''}</p>
                    </div>
                    <div class="md:px-2 text-sm text-slate-700"><span class="md:hidden text-[10px] uppercase text-slate-400">Idade </span>${a.age}</div>
                    <div class="md:px-2">
                        ${a.measure === 'height'
                            ? `<input type="text" inputmode="numeric" data-height="${a.id}" value="${st.height === null || st.height === undefined ? '' : String(st.height)}" ${disabled} placeholder="cm" title="Altura em centímetros" aria-label="Altura de ${esc(a.name)} em centímetros"
                                class="w-20 px-2 py-1 border border-slate-300 rounded text-sm focus:ring-2 focus:ring-red-700 focus:border-transparent">`
                            : `<input type="text" inputmode="decimal" data-weight="${a.id}" value="${st.weight === null || st.weight === undefined ? '' : String(st.weight).replace('.', ',')}" ${disabled} placeholder="kg" title="Peso em quilos" aria-label="Peso de ${esc(a.name)} em quilos"
                                class="w-20 px-2 py-1 border border-slate-300 rounded text-sm focus:ring-2 focus:ring-red-700 focus:border-transparent">`}
                    </div>
                    ${cells}
                    <div class="md:px-2 md:py-2">
                        ${a.status ? `<span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold ${style}">${label}</span>` : ''}
                        ${a.fee !== null ? `<span class="block text-xs text-slate-500 mt-0.5">${money(a.fee)}</span>` : ''}
                        ${a.registrationId && a.status !== 'cancelled' && !locked(a) ? `<button type="button" data-cancel="${a.registrationId}" class="block text-xs text-red-600 hover:text-red-700 mt-1">Cancelar</button>` : ''}
                    </div>
                </div>`;
            }

            function updateSummary() {
                let athletes = 0, entries = 0, total = 0;
                const { first, extra } = data.fees;

                data.athletes.forEach(a => {
                    const n = Object.values(state[a.id].picks).filter(Boolean).length;
                    if (n > 0) {
                        athletes++; entries += n;
                        if (first !== null) total += first + (n - 1) * (extra ?? first);
                    }
                });

                $('sum-athletes').textContent = athletes;
                $('sum-entries').textContent = entries;
                $('sum-fee-wrap').classList.toggle('hidden', first === null);
                $('sum-fee').textContent = money(total);
            }

            function showErrors(list) {
                const box = $('grid-errors');
                box.innerHTML = '<ul class="list-disc pl-5 space-y-0.5">' + list.map(m => `<li>${esc(m)}</li>`).join('') + '</ul>';
                box.classList.remove('hidden');
                $('grid-notice').classList.add('hidden');
                box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            function showNotice(text) {
                $('grid-notice').textContent = text;
                $('grid-notice').classList.remove('hidden');
                $('grid-errors').classList.add('hidden');
            }

            // keep: depois de salvar um atleta, o que o sensei já marcou e ainda não gravou continua na tela
            async function load(keep, focusId) {
                const previous = keep && data ? { data, state: JSON.parse(JSON.stringify(state)) } : null;
                const res = await fetch(`${BASE}/grid${DOJO_QS}`, { headers: { Accept: 'application/json' } });
                const json = await res.json();

                if (!res.ok) { $('grid-loading').textContent = (json.errors || ['Erro ao carregar']).join(' '); return; }

                data = json;
                initState();
                $('grid-loading').classList.add('hidden');

                if (previous) {
                    data.athletes.forEach(a => {
                        const old = previous.data.athletes.find(o => o.id === a.id);
                        if (!old) return; // atleta novo: vale o que veio do servidor

                        if (a.id === focusId) {
                            // Editado: mantém as marcações que continuam válidas com os dados novos
                            data.disciplines.forEach(d => {
                                const pick = previous.state[a.id] && previous.state[a.id].picks[d.code];
                                if (pick && a.cells[d.code].categoryId === pick) state[a.id].picks[d.code] = pick;
                            });
                            return;
                        }

                        a.cells = old.cells;
                        a.weight = old.weight;
                        a.height = old.height;
                        state[a.id] = previous.state[a.id];
                    });
                }

                $('grid-empty').classList.add('hidden');
                if (!data.athletes.length) { $('grid-empty').classList.remove('hidden'); return; }

                $('grid').classList.remove('hidden');
                render();

                if (focusId) {
                    const row = document.querySelector(`[data-row="${focusId}"]`);
                    if (row) {
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        row.classList.add('bg-emerald-50');
                        setTimeout(() => row.classList.remove('bg-emerald-50'), 2500);
                    }
                }
            }

            function athleteForm(id, extra = {}) {
                openAthleteForm(id, {
                    dojoId: DOJO_ID,
                    ...extra,
                    onSaved: async saved => {
                        await load(true, saved.id);
                        showNotice(id ? 'Dados do atleta atualizados. Salve o rascunho para refletir na inscrição.' : 'Atleta cadastrado. Marque as categorias dele na grade.');
                    },
                });
            }

            // Peso ou altura digitados: recalcula as sugestões do atleta no servidor (a regra mora num lugar só)
            let weightTimer = null;
            document.addEventListener('input', ev => {
                const el = ev.target;
                if (!el.matches('[data-weight], [data-height]')) return;

                const field = el.matches('[data-height]') ? 'height' : 'weight';
                const id = Number(el.dataset[field]);
                state[id][field] = el.value;
                clearTimeout(weightTimer);

                weightTimer = setTimeout(async () => {
                    const qs = new URLSearchParams({ athlete_id: id, weight: state[id].weight ?? '', height: state[id].height ?? '' });
                    if (DOJO_QS) qs.set('dojo_id', DOJO_ID);
                    const res = await fetch(`${BASE}/grid/suggest?${qs}`, { headers: { Accept: 'application/json' } });
                    if (!res.ok) return;

                    const { cells } = await res.json();
                    const athlete = data.athletes.find(a => a.id === id);
                    athlete.cells = cells;
                    athlete[field] = el.value === '' ? null : Number(el.value.replace(',', '.'));

                    data.disciplines.forEach(d => {
                        const stillValid = state[id].picks[d.code] === cells[d.code].categoryId;
                        if (!stillValid) state[id].picks[d.code] = null;
                    });

                    const row = document.querySelector(`[data-row="${id}"]`);
                    if (row) {
                        const keep = el.selectionStart;
                        row.outerHTML = rowHtml(athlete);
                        const again = document.querySelector(`[data-${field}="${id}"]`);
                        again.focus(); again.setSelectionRange(keep, keep);
                    }
                    updateSummary();
                }, 350);
            });

            document.addEventListener('change', ev => {
                const el = ev.target;
                if (el.matches('[data-athlete]')) {
                    state[el.dataset.athlete].picks[el.dataset.disc] = el.checked ? Number(el.value) : null;
                    updateSummary();
                } else if (el.id === 'grid-only-checked') {
                    render();
                }
            });
            $('grid-search').addEventListener('input', render);

            function payload() {
                const body = new URLSearchParams({ csrf_token: CSRF });
                if (DOJO_QS) body.set('dojo_id', DOJO_ID);

                data.athletes.forEach(a => {
                    if (locked(a)) return;
                    const st = state[a.id];
                    const picks = Object.values(st.picks).filter(Boolean);
                    const hadRegistration = a.registrationId && a.status !== 'cancelled';

                    if (!picks.length && !hadRegistration) return;

                    body.append(`selections[${a.id}][weight]`, st.weight ?? '');
                    body.append(`selections[${a.id}][height]`, st.height ?? '');
                    // Sem nenhuma categoria marcada, a ausência de categories[] cancela a inscrição existente
                    picks.forEach(id => body.append(`selections[${a.id}][categories][]`, id));
                });

                return body;
            }

            async function post(path, body) {
                const res = await fetch(`${BASE}${path}`, {
                    method: 'POST', body, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                return { ok: res.ok, json: await res.json().catch(() => ({ errors: ['Resposta inesperada do servidor.'] })) };
            }

            async function run(button, work) {
                const buttons = [...document.querySelectorAll('#btn-save, #btn-submit')];
                buttons.forEach(b => b.disabled = true);
                try { await work(); } finally { buttons.forEach(b => b.disabled = false); }
            }

            if (CAN_WRITE) {
                $('btn-save').addEventListener('click', () => run(null, async () => {
                    const { ok, json } = await post('/grid/save', payload());
                    if (!ok) return showErrors(json.errors || ['Não foi possível salvar.']);
                    showNotice(`Rascunho salvo (${json.saved} atleta(s)).`);
                    await load();
                }));

                $('btn-submit').addEventListener('click', () => run(null, async () => {
                    if (!confirm('Enviar as inscrições para a federação? Depois do envio, qualquer alteração exige enviar de novo.')) return;

                    const saved = await post('/grid/save', payload());
                    if (!saved.ok) return showErrors(saved.json.errors || ['Não foi possível salvar.']);

                    const { ok, json } = await post('/grid/submit', new URLSearchParams({ csrf_token: CSRF, ...(DOJO_QS ? { dojo_id: DOJO_ID } : {}) }));
                    if (!ok) { showErrors(json.errors || ['Não foi possível enviar.']); await load(); return; }
                    showNotice(`${json.submitted} inscrição(ões) enviada(s) para a federação.`);
                    await load();
                }));
            }

            document.addEventListener('click', ev => {
                const created = ev.target.closest('[data-new-athlete]');
                if (created) {
                    // Sem data-name no botão, usa o que estiver digitado na busca
                    const typed = created.dataset.name ?? $('grid-search').value.trim();
                    return athleteForm(null, { name: typed, guest: created.dataset.guest === '1' });
                }

                const edit = ev.target.closest('[data-edit-athlete]');
                if (edit) athleteForm(Number(edit.dataset.editAthlete));
            });

            document.addEventListener('click', async ev => {
                const btn = ev.target.closest('[data-cancel]');
                if (!btn) return;
                if (!confirm('Cancelar a inscrição deste atleta? As categorias dele saem do evento.')) return;

                const body = new URLSearchParams({ csrf_token: CSRF });
                if (DOJO_QS) body.set('dojo_id', DOJO_ID);
                const { ok, json } = await post(`/registrations/${btn.dataset.cancel}/cancel`, body);
                await load();
                ok ? showNotice('Inscrição cancelada.') : showErrors(json.errors || ['Não foi possível cancelar.']);
            });

            window.addEventListener('resize', () => { if (data) render(); });
            load();
        })();
    </script>

<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
