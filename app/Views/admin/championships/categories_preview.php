<?php
use Helpers\Csrf;

$id        = (int) $championship['id'];
$pageTitle = 'Prévia das categorias';
$e         = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''));
$new       = 0;

foreach ($groups as $byAge) {
    foreach ($byAge as $drafts) {
        foreach ($drafts as $d) {
            $new += isset($existing[$d->code]) ? 0 : 1;
        }
    }
}

require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <a href="/fgkirs-admin/championships/<?= $id ?>/categories" class="text-sm text-slate-500 hover:text-red-700 transition">← Voltar para as categorias</a>
</div>

<h1 class="text-2xl sm:text-3xl font-bold text-slate-900"><?= $e($championship['title']) ?></h1>
<p class="text-sm text-slate-500 mt-1 mb-6">
    Prévia: nada foi gravado. Desmarque o que o evento não terá e confirme.
</p>

<?php require __DIR__ . '/partials/flash.php'; ?>

<form method="POST" action="/fgkirs-admin/championships/<?= $id ?>/categories/store">
    <?= Csrf::field() ?>
    <?php foreach ($input as $key => $values): foreach ($values as $value): ?>
        <input type="hidden" name="<?= $e($key) ?>[]" value="<?= (int) $value ?>">
    <?php endforeach; endforeach; ?>

    <div class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur py-3 mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200">
        <p class="text-sm text-slate-700">
            <strong id="selected-count">0</strong> selecionadas de <?= (int) $total ?>
            <?php if ($total - $new > 0): ?>
                · <?= (int) ($total - $new) ?> já existem no evento e serão mantidas
            <?php endif; ?>
        </p>
        <div class="flex gap-2">
            <button type="button" id="mark-all" class="border border-slate-300 hover:bg-white text-slate-700 text-sm font-semibold py-2 px-4 rounded-lg">Marcar todas</button>
            <button type="button" id="mark-none" class="border border-slate-300 hover:bg-white text-slate-700 text-sm font-semibold py-2 px-4 rounded-lg">Limpar</button>
            <button type="submit" class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">Confirmar e gerar</button>
        </div>
    </div>

    <?php foreach ($groups as $disciplineName => $byAge): ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-5 overflow-hidden">
            <div class="bg-slate-900 text-white px-5 py-3 flex items-center justify-between">
                <h2 class="font-bold uppercase tracking-wide text-sm"><?= $e($disciplineName) ?></h2>
                <span class="text-xs text-slate-300"><?= array_sum(array_map('count', $byAge)) ?> categorias</span>
            </div>
            <?php foreach ($byAge as $ageLabel => $drafts): ?>
                <details class="border-t border-slate-100" open>
                    <summary class="px-5 py-3 cursor-pointer text-sm font-semibold text-slate-800 hover:bg-slate-50 flex justify-between">
                        <span><?= $e($ageLabel) ?></span>
                        <span class="text-xs font-normal text-slate-500"><?= count($drafts) ?></span>
                    </summary>
                    <ul class="px-5 pb-3 divide-y divide-slate-100">
                        <?php foreach ($drafts as $draft): $exists = isset($existing[$draft->code]); ?>
                            <li class="py-2">
                                <label class="flex items-start gap-3 text-sm <?= $exists ? 'text-slate-400' : 'text-slate-800 cursor-pointer' ?>">
                                    <input type="checkbox" name="codes[]" value="<?= $e($draft->code) ?>" data-code
                                        <?= $exists ? 'disabled' : 'checked' ?> class="mt-0.5 rounded border-slate-300 text-red-700">
                                    <span>
                                        <?= $e($draft->name) ?>
                                        <span class="block text-xs font-mono text-slate-500">
                                            <?= $e($draft->code) ?><?= $exists ? ' · já existe no evento' : '' ?>
                                        </span>
                                    </span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</form>

<script>
    (function () {
        const boxes = () => [...document.querySelectorAll('input[data-code]:not(:disabled)')];
        const counter = document.getElementById('selected-count');
        const refresh = () => { counter.textContent = boxes().filter(b => b.checked).length; };

        document.getElementById('mark-all').addEventListener('click', () => { boxes().forEach(b => b.checked = true); refresh(); });
        document.getElementById('mark-none').addEventListener('click', () => { boxes().forEach(b => b.checked = false); refresh(); });
        boxes().forEach(b => b.addEventListener('change', refresh));
        refresh();
    })();
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
