<?php
use Helpers\Csrf;

/**
 * @var array $championship
 * @var array $bracketState (não chamar de $state: o cabeçalho das abas usa esse nome)
 * @var bool  $canEdit
 */
require_once __DIR__ . '/../layout/header.php';
require __DIR__ . '/../championships/partials/header.php';

$championshipId = (int) $championship['id'];
$base           = "/fgkirs-admin/championships/$championshipId/brackets";
$categoryId     = (int) $bracketState['category']['id'];
?>

<style>
    .rounds { display: flex; gap: 2.5rem; min-width: max-content; padding: .5rem .25rem 1rem; }
    .round { display: flex; flex-direction: column; width: 16rem; }
    .round-title { text-align: center; font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #64748b; padding-bottom: .5rem; }
    .round-body { display: flex; flex-direction: column; flex: 1; }
    .match-wrap { position: relative; flex: 1; display: flex; align-items: center; padding: .4rem 0; }
    .match { position: relative; width: 100%; background: #fff; border: 1px solid #cbd5e1; border-radius: .5rem; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); }
    .slot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .45rem .65rem; font-size: .8rem; border-bottom: 1px solid #e2e8f0; min-height: 2.6rem; }
    .slot:last-of-type { border-bottom: 0; }
    .slot .nm { min-width: 0; }
    .slot .nm b { display: block; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .slot .nm small { display: block; color: #94a3b8; font-size: .68rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .slot.win { background: #ecfdf5; }
    .slot.win .nm b { color: #047857; }
    .slot.lose { color: #94a3b8; }
    .slot.empty { color: #94a3b8; font-style: italic; }
    .slot.pick { cursor: pointer; }
    .slot.pick:hover { background: #fef2f2; }
    .slot.sel { outline: 2px solid #b91c1c; outline-offset: -2px; background: #fef2f2; }
    .score { font-weight: 700; font-size: .75rem; color: #475569; }
    .mini { border: 0; background: transparent; color: #94a3b8; cursor: pointer; padding: 0 .25rem; font-size: 1rem; line-height: 1; }
    .mini:hover { color: #b91c1c; }
    .match-foot { display: flex; justify-content: flex-end; padding: .2rem .5rem; border-top: 1px solid #e2e8f0; background: #f8fafc; border-radius: 0 0 .5rem .5rem; }
    .match-foot button { border: 0; background: transparent; color: #2563eb; font-size: .7rem; font-weight: 600; cursor: pointer; }
    /* Linhas que ligam uma luta à seguinte */
    .round:not(:last-child) .match-wrap::after { content: ""; position: absolute; right: -1.25rem; top: 50%; width: 1.25rem; border-top: 2px solid #cbd5e1; }
    .round:not(:last-child) .match-wrap:nth-child(odd)::before { content: ""; position: absolute; right: -1.25rem; top: 50%; height: 50%; border-right: 2px solid #cbd5e1; }
    .round:not(:last-child) .match-wrap:nth-child(even)::before { content: ""; position: absolute; right: -1.25rem; bottom: 50%; height: 50%; border-right: 2px solid #cbd5e1; }
    .round:not(:first-child) .match::before { content: ""; position: absolute; left: -1.25rem; top: 50%; width: 1.25rem; border-top: 2px solid #cbd5e1; }
    @media print {
        #sidebar, .no-print, nav { display: none !important; }
        main { padding: 0 !important; overflow: visible !important; }
        .rounds { min-width: 0; }
    }
</style>

<div id="app">
    <div class="no-print mb-4">
        <a href="<?= $base ?>" class="text-sm text-slate-500 hover:text-red-700 transition">← Todas as categorias</a>
    </div>

    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($bracketState['category']['shortName']) ?></h2>
            <p class="text-xs text-slate-500" id="subtitle"></p>
        </div>
        <div id="toolbar" class="no-print flex flex-wrap gap-2"></div>
    </div>

    <div id="feedback" class="no-print hidden mb-4 rounded-lg px-4 py-3 text-sm"></div>
    <div id="banners" class="no-print"></div>
    <div id="podium"></div>
    <div id="board" class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 overflow-x-auto"></div>
</div>

<!-- Resultado da luta -->
<div id="result-modal" class="no-print hidden fixed inset-0 z-50 bg-slate-900/60 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-1" id="result-title">Resultado da luta</h3>
            <p class="text-xs text-slate-500 mb-4">Toque no nome de quem venceu.</p>
            <div class="grid gap-3" id="result-buttons"></div>
            <div class="grid grid-cols-2 gap-3 mt-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1" id="score-a-label">Placar A (opcional)</label>
                    <input type="text" id="score-a" maxlength="20" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1" id="score-b-label">Placar B (opcional)</label>
                    <input type="text" id="score-b" maxlength="20" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>
            </div>
            <div class="flex flex-col-reverse sm:flex-row sm:justify-between gap-2 mt-5">
                <button type="button" id="result-undo" class="hidden text-red-600 hover:text-red-700 text-sm font-semibold">Desfazer resultado</button>
                <button type="button" data-close class="sm:ml-auto bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-5 rounded-lg transition text-sm">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Mudar de categoria -->
<div id="move-modal" class="no-print hidden fixed inset-0 z-50 bg-slate-900/60 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="min-h-full flex items-center justify-center p-4">
        <form id="move-form" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-1">Mudar de categoria</h3>
            <p class="text-sm text-slate-600 mb-4" id="move-who"></p>
            <label class="block text-sm font-medium text-slate-700 mb-1" for="move-to">Nova categoria</label>
            <select id="move-to" required class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white text-sm mb-3"></select>
            <label class="block text-sm font-medium text-slate-700 mb-1" for="move-reason">Motivo *</label>
            <input type="text" id="move-reason" required minlength="3" maxlength="255" placeholder="Ex.: pesagem acima do limite"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
            <p class="text-xs text-slate-500 mt-2">O atleta sai desta chave e entra na outra categoria. A mudança fica registrada no histórico da inscrição.</p>
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-5">
                <button type="button" data-close class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-5 rounded-lg transition text-sm">Cancelar</button>
                <button type="submit" class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-5 rounded-lg transition text-sm">Mudar de categoria</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const BASE = '<?= $base ?>/<?= $categoryId ?>';
    const CSRF = <?= json_encode(Csrf::token()) ?>;
    let state  = <?= json_encode($bracketState, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    let selected = null;   // vaga marcada para troca (índice na 1ª rodada)

    const $ = id => document.getElementById(id);
    const esc = t => { const d = document.createElement('div'); d.textContent = t ?? ''; return d.innerHTML; };

    const STATUS = {
        draft:    ['Sorteada', 'bg-blue-100 text-blue-800'],
        running:  ['Em andamento', 'bg-amber-100 text-amber-800'],
        finished: ['Encerrada', 'bg-green-100 text-green-800'],
    };

    function feedback(message, ok) {
        const box = $('feedback');
        box.textContent = message;
        box.className = 'no-print mb-4 rounded-lg px-4 py-3 text-sm ' + (ok ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800');
        box.classList.remove('hidden');
        if (!ok) box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    async function act(path, fields = {}) {
        const body = new FormData();
        body.append('csrf_token', CSRF);
        Object.entries(fields).forEach(([k, v]) => body.append(k, v));

        try {
            const res  = await fetch(`${BASE}/${path}`, { method: 'POST', body, headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!data.ok) { feedback(data.message || 'Não foi possível concluir.', false); return null; }

            state = data.state;
            selected = null;
            $('feedback').classList.add('hidden');
            render();
            return data;
        } catch (e) {
            feedback('Não foi possível falar com o servidor. Tente de novo.', false);
            return null;
        }
    }

    // ───────────── Desenho ─────────────

    function slotHtml(round, m, side) {
        const p      = side === 'a' ? m.a : m.b;
        const first  = round === 1;
        const index  = m.position * 2 + (side === 'a' ? 0 : 1);
        const canPick = state.canEdit && first && !state.bracket.hasResults;
        const decided = m.winner !== null;

        if (!p) {
            const text = first ? 'Vaga livre (passa direto)' : 'Aguardando';
            return `<div class="slot empty ${canPick ? 'pick' : ''} ${selected === index ? 'sel' : ''}" ${canPick ? `data-slot="${index}"` : ''}>${text}</div>`;
        }

        const cls = [
            'slot',
            decided && m.winner === p.id ? 'win' : '',
            decided && m.winner !== p.id ? 'lose' : '',
            canPick ? 'pick' : '',
            selected === index ? 'sel' : '',
        ].join(' ');

        const score = side === 'a' ? m.scoreA : m.scoreB;
        const move  = state.canEdit && first && state.type === 'registration' && state.moveTargets.length
            ? `<button type="button" class="mini no-print" title="Mudar de categoria" data-move="${p.id}" data-name="${esc(p.name)}">⋯</button>` : '';

        return `<div class="${cls}" ${canPick ? `data-slot="${index}"` : ''}>
            <div class="nm"><b title="${esc(p.name)}">${esc(p.name)}</b><small>${esc(p.dojo)}</small></div>
            <div class="flex items-center gap-1">${score ? `<span class="score">${esc(score)}</span>` : ''}${move}</div>
        </div>`;
    }

    function matchHtml(round, m) {
        const both  = m.a && m.b;
        const foot  = state.canEdit && both
            ? `<div class="match-foot no-print"><button type="button" data-result="${round}:${m.position}">${m.winner !== null ? 'Editar resultado' : 'Lançar resultado'}</button></div>` : '';

        return `<div class="match-wrap"><div class="match">${slotHtml(round, m, 'a')}${slotHtml(round, m, 'b')}${foot}</div></div>`;
    }

    function render() {
        const b = state.bracket;

        $('subtitle').textContent = b
            ? `${state.eligible} participante(s) · chave de ${b.size} vagas`
            : `${state.eligible} participante(s) com inscrição enviada ou confirmada`;

        renderToolbar();
        renderBanners();
        renderPodium();

        if (!b) {
            $('board').innerHTML = `<div class="text-center py-10 text-slate-500 text-sm">
                <p class="mb-1">Esta categoria ainda não tem chave.</p>
                ${state.eligible === 0 ? '<p>Nenhum atleta com inscrição enviada ou confirmada.</p>' : `<ul class="inline-block text-left mt-3 text-slate-700">${state.missing.map(p => `<li>• ${esc(p.name)} <span class="text-slate-400">· ${esc(p.dojo)}</span></li>`).join('')}</ul>`}
            </div>`;
            return;
        }

        $('board').innerHTML = `<div class="rounds">${state.rounds.map(r => `
            <div class="round">
                <div class="round-title">${esc(r.label)}</div>
                <div class="round-body">${r.matches.map(m => matchHtml(r.round, m)).join('')}</div>
            </div>`).join('')}</div>`;
    }

    function renderToolbar() {
        const b = state.bracket, t = [];
        const btn = (id, label, cls) => `<button type="button" id="${id}" class="${cls} font-semibold py-2 px-4 rounded-lg transition text-sm">${label}</button>`;
        const main = 'bg-red-700 hover:bg-red-800 text-white';
        const soft = 'border border-slate-300 hover:bg-slate-50 text-slate-700';

        if (state.canEdit) {
            if (!b && state.eligible > 0) t.push(btn('act-generate', 'Sortear chave', main));
            if (b) {
                t.push(btn('act-generate', 'Sortear de novo', soft));
                if (b.hasResults) t.push(btn('act-clear', 'Limpar resultados', soft));
                t.push(btn('act-delete', 'Excluir chave', 'border border-red-200 hover:bg-red-50 text-red-700'));
            }
        }
        if (b) t.push(btn('act-print', 'Imprimir', soft));

        const [label, cls] = b ? STATUS[b.status] : ['Sem chave', 'bg-slate-200 text-slate-600'];
        $('toolbar').innerHTML = `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold ${cls}">${label}</span>` + t.join('');
    }

    function renderBanners() {
        const b = state.bracket, out = [];

        if (b && (state.missing.length || state.extra.length)) {
            const parts = [];
            if (state.missing.length) parts.push(`<strong>${state.missing.length}</strong> inscrito(s) ainda fora da chave: ${state.missing.map(p => esc(p.name)).join(', ')}`);
            if (state.extra.length) parts.push(`<strong>${state.extra.length}</strong> na chave sem inscrição ativa: ${state.extra.map(p => esc(p.name)).join(', ')}`);

            out.push(`<div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>${parts.join('<br>')}</div>
                ${state.canEdit && !b.hasResults ? '<button type="button" id="act-sync" class="shrink-0 bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2 px-4 rounded-lg text-sm">Atualizar a chave</button>' : ''}
            </div>`);
        }

        if (b && state.canEdit && !b.hasResults) {
            out.push('<p class="mb-3 text-xs text-slate-500">Para trocar atletas de lugar, clique em um e depois no outro (vale também para uma vaga livre). O ⋯ muda o atleta de categoria, por exemplo por causa do peso.</p>');
        } else if (b && state.canEdit && b.hasResults) {
            out.push('<p class="mb-3 text-xs text-slate-500">Com resultados lançados o sorteio fica travado. Para trocar atletas, limpe os resultados.</p>');
        }

        $('banners').innerHTML = out.join('');
    }

    function renderPodium() {
        const p = state.podium;
        if (!p.length) { $('podium').innerHTML = ''; return; }

        const color = { 1: 'bg-yellow-100 text-yellow-800', 2: 'bg-slate-200 text-slate-700', 3: 'bg-orange-100 text-orange-800' };
        $('podium').innerHTML = `<div class="mb-4 bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Colocação</p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-2">${p.map(x => `
                <div class="flex items-center gap-3 rounded-lg border border-slate-100 px-3 py-2">
                    <span class="w-9 h-9 rounded-full flex items-center justify-center font-black text-sm ${color[x.place]}">${x.place}º</span>
                    <div class="min-w-0"><p class="font-semibold text-sm text-slate-900 truncate">${esc(x.name)}</p><p class="text-xs text-slate-400 truncate">${esc(x.dojo)}</p></div>
                </div>`).join('')}</div>
        </div>`;
    }

    // ───────────── Eventos ─────────────

    document.addEventListener('click', async e => {
        const t = e.target;

        const slot = t.closest('[data-slot]');
        if (slot && !t.closest('[data-move]')) {
            const index = Number(slot.dataset.slot);
            if (selected === null) { selected = index; render(); }
            else if (selected === index) { selected = null; render(); }
            else await act('swap', { slot_a: selected, slot_b: index });
            return;
        }

        const move = t.closest('[data-move]');
        if (move) { openMove(move.dataset.move, move.dataset.name); return; }

        const result = t.closest('[data-result]');
        if (result) { const [r, p] = result.dataset.result.split(':').map(Number); openResult(r, p); return; }

        if (t.closest('[data-close]')) { closeModals(); return; }

        switch (t.id) {
            case 'act-generate': {
                const has = state.bracket && state.bracket.hasResults;
                const msg = state.bracket
                    ? (has ? 'Esta chave já tem resultados. Sortear de novo APAGA todos os resultados desta categoria. Continuar?' : 'Sortear a chave de novo? A ordem atual será substituída.')
                    : null;
                if (msg && !confirm(msg)) return;
                await act('generate', has ? { reset: '1' } : {});
                break;
            }
            case 'act-clear':
                if (confirm('Limpar todos os resultados desta chave? O sorteio é mantido.')) await act('clear-results');
                break;
            case 'act-delete': {
                const has = state.bracket.hasResults;
                if (!confirm(has ? 'Excluir a chave e TODOS os resultados dela? Não tem volta.' : 'Excluir esta chave?')) return;
                const data = await act('delete', has ? { reset: '1' } : {});
                if (data) window.location.href = '<?= $base ?>';
                break;
            }
            case 'act-sync': await act('sync'); break;
            case 'act-print': window.print(); break;
        }
    });

    // ───────────── Resultado ─────────────

    let current = null;

    function openResult(round, position) {
        const r = state.rounds[round - 1].matches.find(m => m.position === position);
        current = { round, position, match: r };

        $('result-title').textContent = `${state.rounds[round - 1].label} · luta ${position + 1}`;
        $('score-a-label').textContent = `Placar de ${r.a.name} (opcional)`;
        $('score-b-label').textContent = `Placar de ${r.b.name} (opcional)`;
        $('score-a').value = r.scoreA ?? '';
        $('score-b').value = r.scoreB ?? '';

        $('result-buttons').innerHTML = [r.a, r.b].map(p => `
            <button type="button" data-winner="${p.id}" class="text-left rounded-lg border-2 px-4 py-3 transition ${r.winner === p.id ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 hover:border-red-400 hover:bg-red-50'}">
                <span class="block font-semibold text-slate-900">${esc(p.name)}</span>
                <span class="block text-xs text-slate-500">${esc(p.dojo)}${r.winner === p.id ? ' · vencedor' : ''}</span>
            </button>`).join('');

        $('result-undo').classList.toggle('hidden', r.winner === null);
        $('result-modal').classList.remove('hidden');
    }

    $('result-buttons').addEventListener('click', async e => {
        const btn = e.target.closest('[data-winner]');
        if (!btn || !current) return;

        const data = await act('winner', {
            round: current.round, position: current.position, winner: btn.dataset.winner,
            score_a: $('score-a').value, score_b: $('score-b').value,
        });
        if (data) closeModals();
    });

    $('result-undo').addEventListener('click', async () => {
        if (!current) return;
        const data = await act('clear-winner', { round: current.round, position: current.position });
        if (data) closeModals();
    });

    // ───────────── Mudar de categoria ─────────────

    let moving = null;

    function openMove(participantId, name) {
        moving = participantId;
        $('move-who').textContent = name;
        $('move-to').innerHTML = state.moveTargets.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
        $('move-reason').value = '';
        $('move-modal').classList.remove('hidden');
        $('move-reason').focus();
    }

    $('move-form').addEventListener('submit', async e => {
        e.preventDefault();
        const data = await act('move', { registration_id: moving, to_category_id: $('move-to').value, reason: $('move-reason').value });

        if (data) {
            closeModals();
            feedback(data.warnings && data.warnings.length ? data.warnings.join(' ') : 'Atleta mudou de categoria.', !(data.warnings && data.warnings.length));
        }
    });

    function closeModals() {
        $('result-modal').classList.add('hidden');
        $('move-modal').classList.add('hidden');
        current = null; moving = null;
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModals(); });

    render();
})();
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
