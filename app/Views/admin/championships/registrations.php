<?php
use Helpers\Csrf;

$id        = (int) $championship['id'];
$pageTitle = 'Inscritos – ' . $championship['title'];
$e         = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''));
$money     = static fn (?float $v): string => $v === null ? '—' : 'R$ ' . number_format($v, 2, ',', '.');

$statusStyle = ['draft' => 'bg-amber-100 text-amber-800', 'submitted' => 'bg-blue-100 text-blue-800', 'confirmed' => 'bg-green-100 text-green-800', 'cancelled' => 'bg-slate-200 text-slate-600'];
$statusLabel = ['draft' => 'Rascunho', 'submitted' => 'Enviada', 'confirmed' => 'Confirmada', 'cancelled' => 'Cancelada'];

$query = http_build_query(array_filter($filters));
$individualCategories = array_values(array_filter($categories, static fn ($c) => $c['format'] === 'individual'));

require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <a href="/fgkirs-admin/championships" class="text-sm text-slate-500 hover:text-red-700 transition">← Voltar para eventos</a>
</div>

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900"><?= $e($championship['title']) ?></h1>
        <p class="text-sm text-slate-500 mt-1"><?= date('d/m/Y', strtotime($championship['event_date'])) ?> · consolidado da federação</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="/fgkirs-admin/championships/<?= $id ?>/categories" class="border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold py-2 px-4 rounded-lg transition">Categorias</a>
        <a href="/fgkirs-admin/championships/<?= $id ?>/registrations/export<?= $query ? '?' . $query : '' ?>" class="border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold py-2 px-4 rounded-lg transition">Exportar CSV</a>
        <a href="/fgkirs-admin/championships/<?= $id ?>/registrations/print" target="_blank" class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold py-2 px-4 rounded-lg transition">Imprimir / PDF</a>
    </div>
</div>

<?php require __DIR__ . '/partials/flash.php'; ?>

<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
    <?php foreach ([
        'Dojos' => $totals['dojos'], 'Atletas' => $totals['athletes'], 'Inscrições' => $totals['entries'],
        'Equipes' => count($teams), 'Taxas enviadas' => $money($totals['fee'] > 0 ? $totals['fee'] : null),
    ] as $label => $value): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-2xl font-bold text-slate-900"><?= $e($value) ?></p>
            <p class="text-xs uppercase tracking-wider text-slate-500 mt-1"><?= $label ?></p>
        </div>
    <?php endforeach; ?>
</div>

<form method="GET" action="/fgkirs-admin/championships/<?= $id ?>/registrations"
    class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-5 grid gap-3 sm:grid-cols-4">
    <select name="status" class="px-3 py-2 border border-slate-300 rounded-lg bg-white text-sm">
        <option value="">Todos os status</option>
        <?php foreach ($statusLabel as $value => $label): ?>
            <option value="<?= $value ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
    <select name="dojo_id" class="px-3 py-2 border border-slate-300 rounded-lg bg-white text-sm">
        <option value="">Todos os dojos</option>
        <?php foreach ($dojos as $dojo): ?>
            <option value="<?= (int) $dojo['id'] ?>" <?= (int) $filters['dojo_id'] === (int) $dojo['id'] ? 'selected' : '' ?>><?= $e($dojo['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="category_id" class="px-3 py-2 border border-slate-300 rounded-lg bg-white text-sm">
        <option value="">Todas as categorias</option>
        <?php foreach ($individualCategories as $category): ?>
            <option value="<?= (int) $category['id'] ?>" <?= (int) $filters['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                <?= $e($category['name']) ?> (<?= (int) $category['enrolled'] ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <div class="flex gap-2">
        <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2 px-5 rounded-lg text-sm">Filtrar</button>
        <a href="/fgkirs-admin/championships/<?= $id ?>/registrations" class="border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2 px-5 rounded-lg text-sm">Limpar</a>
    </div>
</form>

<form method="POST" action="/fgkirs-admin/championships/<?= $id ?>/registrations/confirm"
    onsubmit="return confirm('Confirmar o pagamento das inscrições marcadas?');">
    <?= Csrf::field() ?>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" id="select-submitted" class="rounded border-slate-300 text-red-700">
            Marcar todas as enviadas desta lista
        </label>
        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-semibold py-2 px-5 rounded-lg transition text-sm">
            Confirmar pagamento dos marcados
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="px-3 py-3 w-10"></th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider">Atleta</th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider">Dojo</th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider">Categorias</th>
                        <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider">Situação</th>
                        <th class="px-3 py-3 text-right text-xs font-medium uppercase tracking-wider">Taxa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($registrations)): ?>
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Nenhuma inscrição encontrada.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($registrations as $r): ?>
                        <tr class="hover:bg-slate-50 align-top">
                            <td class="px-3 py-3">
                                <?php if ($r['status'] === 'submitted'): ?>
                                    <input type="checkbox" name="registrations[]" value="<?= (int) $r['id'] ?>" data-submitted class="rounded border-slate-300 text-red-700">
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-3">
                                <p class="text-sm font-medium text-slate-900"><?= $e($r['full_name']) ?></p>
                                <p class="text-xs text-slate-500"><?= (int) $r['age_at_event'] ?> anos · <?= $e($r['belt_name'] ?? 'sem graduação') ?> · <?= $e(\Helpers\Format::measure($r['declared_weight'], $r['declared_height'] ?? null)) ?></p>
                            </td>
                            <td class="px-3 py-3 text-sm text-slate-700"><?= $e($r['dojo_name']) ?></td>
                            <td class="px-3 py-3 text-sm text-slate-700">
                                <?php foreach ($r['entries'] as $entry): ?>
                                    <p><?= $e($entry['category_name']) ?><?= $entry['is_override'] ? ' <em class="text-xs text-amber-700" title="' . $e($entry['override_reason']) . '">(exceção)</em>' : '' ?></p>
                                <?php endforeach; ?>

                                <?php if ($r['status'] !== 'cancelled'): ?>
                                    <details class="mt-1">
                                        <summary class="cursor-pointer text-xs text-blue-600 hover:text-blue-700">Exceção / histórico</summary>
                                        <div class="mt-2 p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2">
                                            <select data-override-category class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs bg-white">
                                                <option value="">Categoria fora da regra…</option>
                                                <?php foreach ($individualCategories as $category): ?>
                                                    <option value="<?= (int) $category['id'] ?>"><?= $e($category['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" data-override-reason maxlength="255" placeholder="Motivo (obrigatório)" class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs">
                                            <button type="button" data-override-submit="<?= (int) $r['id'] ?>" class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold py-1.5 px-3 rounded">Registrar exceção</button>
                                            <button type="button" data-log="<?= (int) $r['id'] ?>" class="text-xs text-slate-600 hover:text-slate-900 underline ml-2">Ver histórico</button>
                                            <ul data-log-list class="text-xs text-slate-600 space-y-0.5"></ul>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-bold <?= $statusStyle[$r['status']] ?? '' ?>"><?= $statusLabel[$r['status']] ?? $e($r['status']) ?></span>
                                <?php if ($r['paid_at']): ?><p class="text-[11px] text-slate-500 mt-1">pago em <?= date('d/m/Y', strtotime($r['paid_at'])) ?></p><?php endif; ?>
                            </td>
                            <td class="px-3 py-3 text-right text-sm text-slate-700 whitespace-nowrap"><?= $money($r['fee_total'] !== null ? (float) $r['fee_total'] : null) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($teams)): ?>
        <h2 class="text-lg font-bold text-slate-900 mb-3">Equipes</h2>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 divide-y divide-slate-100 mb-6">
            <?php foreach ($teams as $team): ?>
                <div class="px-5 py-3 flex items-start gap-3 text-sm">
                    <?php if ($team['status'] === 'submitted'): ?>
                        <input type="checkbox" name="teams[]" value="<?= (int) $team['id'] ?>" data-submitted class="mt-1 rounded border-slate-300 text-red-700">
                    <?php else: ?>
                        <span class="w-4"></span>
                    <?php endif; ?>
                    <div>
                        <p class="font-medium text-slate-900"><?= $e($team['dojo_name']) ?> · <?= $e($team['category_name']) ?>
                            <span class="text-xs font-normal text-slate-500">· <?= $statusLabel[$team['status']] ?? $e($team['status']) ?></span></p>
                        <p class="text-slate-600"><?= $e(implode(', ', array_column($team['members'], 'name'))) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</form>

<script>
    (function () {
        const BASE = '/fgkirs-admin/championships/<?= $id ?>/registrations';
        const CSRF = <?= json_encode(Csrf::token()) ?>;
        const esc = t => { const d = document.createElement('div'); d.textContent = t ?? ''; return d.innerHTML; };

        document.getElementById('select-submitted').addEventListener('change', e => {
            document.querySelectorAll('input[data-submitted]').forEach(b => { b.checked = e.target.checked; });
        });

        document.addEventListener('click', async ev => {
            const logBtn = ev.target.closest('[data-log]');
            if (logBtn) {
                const list = logBtn.parentElement.querySelector('[data-log-list]');
                const res = await fetch(`${BASE}/${logBtn.dataset.log}/log`);
                const { log } = await res.json();
                list.innerHTML = log.map(l => `<li>${esc(l.created_at)} · ${esc(l.user_name)} · <strong>${esc(l.action)}</strong> ${l.details ? esc(l.details) : ''}</li>`).join('') || '<li>Sem registros.</li>';
                return;
            }

            const overrideBtn = ev.target.closest('[data-override-submit]');
            if (overrideBtn) {
                const box = overrideBtn.parentElement;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `${BASE}/${overrideBtn.dataset.overrideSubmit}/override`;
                form.innerHTML = `<input name="csrf_token" value="${CSRF}"><input name="category_id" value="${box.querySelector('[data-override-category]').value}"><input name="reason" value="${esc(box.querySelector('[data-override-reason]').value)}">`;
                form.style.display = 'none';
                document.body.appendChild(form);
                form.submit();
            }
        });
    })();
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
