<?php
$pageTitle = 'Estrutura Administrativa';
require_once __DIR__ . '/../layout/header.php';

$tierLabel = [
    'primary'   => 'Diretoria Principal',
    'secondary' => 'Diretores',
    'list'      => 'Conselhos e Comissões',
];
$currentTier = null;
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Estrutura Administrativa</h1>
    <a href="/a-fgkirs" target="_blank"
        class="text-sm text-slate-500 hover:text-red-700 flex items-center gap-1 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
        </svg>
        Ver página pública
    </a>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
<?php unset($_SESSION['success']); endif; ?>

<p class="text-sm text-slate-500 mb-6">
    Selecione qual sensei ocupa cada cargo. Cargos marcados com <span class="font-semibold">+ múltiplos</span> aceitam
    mais de uma pessoa. Salve ao final para publicar as alterações.
</p>

<form action="/fgkirs-admin/board/save" method="POST">
<div class="space-y-2">
<?php foreach ($positions as $pos):
    $tier = $pos['tier'];

    // Print section header when tier changes
    if ($tier !== $currentTier):
        $currentTier = $tier;
?>
    <div class="pt-4 pb-1">
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400"><?= $tierLabel[$tier] ?></p>
    </div>
<?php endif; ?>

    <?php
    $assigned = $assignedByPosition[$pos['id']] ?? [];
    $isMultiple = (bool) $pos['allow_multiple'];
    ?>
    <div class="bg-white rounded-lg shadow-sm border border-slate-100 px-5 py-4 flex flex-col sm:flex-row sm:items-start gap-4">

        <!-- Cargo -->
        <div class="sm:w-64 shrink-0">
            <span class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($pos['title']) ?></span>
            <?php if ($isMultiple): ?>
                <span class="ml-2 text-[10px] text-slate-400 font-medium">+ múltiplos</span>
            <?php endif; ?>
        </div>

        <!-- Selects -->
        <div class="flex-1" id="slot-<?= $pos['id'] ?>">
            <?php if (!$isMultiple): ?>
                <!-- Single select -->
                <select name="assignments[<?= $pos['id'] ?>][]"
                    class="w-full sm:max-w-xs border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-700">
                    <option value="">— Não atribuído —</option>
                    <?php foreach ($senseis as $s): ?>
                        <option value="<?= $s['id'] ?>"
                            <?= (!empty($assigned) && (int)$assigned[0]['user_id'] === (int)$s['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <!-- Multiple: list of current + add button -->
                <div class="space-y-2" id="rows-<?= $pos['id'] ?>">
                    <?php foreach ($assigned as $i => $a): ?>
                        <div class="flex items-center gap-2 assignee-row">
                            <select name="assignments[<?= $pos['id'] ?>][]"
                                class="flex-1 sm:max-w-xs border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-700">
                                <option value="">— Não atribuído —</option>
                                <?php foreach ($senseis as $s): ?>
                                    <option value="<?= $s['id'] ?>"
                                        <?= ((int)$a['user_id'] === (int)$s['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" onclick="removeRow(this)"
                                class="text-slate-400 hover:text-red-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($assigned)): ?>
                        <div class="flex items-center gap-2 assignee-row">
                            <select name="assignments[<?= $pos['id'] ?>][]"
                                class="flex-1 sm:max-w-xs border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-700">
                                <option value="">— Não atribuído —</option>
                                <?php foreach ($senseis as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" onclick="removeRow(this)"
                                class="text-slate-400 hover:text-red-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="addRow(<?= $pos['id'] ?>)"
                    class="mt-2 text-xs text-red-700 hover:text-red-900 font-semibold flex items-center gap-1 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Adicionar pessoa
                </button>
            <?php endif; ?>
        </div>
    </div>

<?php endforeach; ?>
</div>

<div class="mt-6 flex justify-end">
    <button type="submit"
        class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2.5 px-8 rounded-lg transition">
        Salvar Estrutura
    </button>
</div>
</form>

<script>
// Options HTML snippet shared by all selects (built from PHP)
const SENSEIS = <?= json_encode(array_map(fn($s) => ['id' => $s['id'], 'name' => $s['name']], $senseis), JSON_UNESCAPED_UNICODE) ?>;

function buildOptions() {
    return '<option value="">— Não atribuído —</option>' +
        SENSEIS.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
}

function addRow(posId) {
    const container = document.getElementById('rows-' + posId);
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 assignee-row';
    div.innerHTML = `
        <select name="assignments[${posId}][]"
            class="flex-1 sm:max-w-xs border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-700">
            ${buildOptions()}
        </select>
        <button type="button" onclick="removeRow(this)" class="text-slate-400 hover:text-red-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>`;
    container.appendChild(div);
}

function removeRow(btn) {
    const row = btn.closest('.assignee-row');
    const container = row.parentElement;
    // Keep at least one row
    if (container.querySelectorAll('.assignee-row').length > 1) {
        row.remove();
    } else {
        row.querySelector('select').value = '';
    }
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
