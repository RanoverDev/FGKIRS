<?php
$id        = (int) $meta['id'];
$pageTitle = 'Pré-visualizar categorias';
$e         = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''));

$chosen = static function (array $items, array $selected): array {
    return array_map(static fn ($i) => $i->id, $selected);
};
$selectedDisciplines = $chosen($ruleset->disciplines, $request->disciplines);
$selectedAges        = $chosen($ruleset->ageDivisions, $request->ageDivisions);
$selectedBelts       = $chosen($ruleset->beltDivisions, $request->beltDivisions);

$limits = static function ($d): string {
    $parts = [];
    if ($d->ageMin !== null || $d->ageMax !== null) {
        $parts[] = 'idade ' . ($d->ageMin ?? '0') . ($d->ageMax !== null ? '–' . $d->ageMax : '+');
    }
    if ($d->beltLevelMin !== null) {
        $parts[] = "nível {$d->beltLevelMin}–{$d->beltLevelMax}";
    }
    if ($d->weightMin !== null || $d->weightMax !== null) {
        $parts[] = 'peso ' . ($d->weightMin !== null ? '> ' . (float) $d->weightMin : '')
            . ($d->weightMin !== null && $d->weightMax !== null ? ' e ' : '')
            . ($d->weightMax !== null ? '≤ ' . (float) $d->weightMax : '');
    }

    return implode(' · ', $parts);
};

require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <a href="/fgkirs-admin/rulesets/edit/<?= $id ?>" class="text-sm text-slate-500 hover:text-red-700 transition">← Voltar para o regulamento</a>
</div>

<h1 class="text-2xl sm:text-3xl font-bold text-slate-900"><?= $e($meta['name']) ?></h1>
<p class="text-sm text-slate-500 mt-1 mb-6">
    Prévia do que o gerador criaria para um evento. Só leitura: nada é gravado.
</p>

<form method="GET" action="/fgkirs-admin/rulesets/<?= $id ?>/preview" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <input type="hidden" name="go" value="1">
    <div class="grid md:grid-cols-3 gap-6">
        <fieldset>
            <legend class="text-sm font-bold text-slate-800 mb-2">Disciplinas</legend>
            <?php foreach ($ruleset->disciplines as $d): ?>
                <label class="flex items-center gap-2 text-sm text-slate-700 mb-1.5">
                    <input type="checkbox" name="disciplines[]" value="<?= (int) $d->id ?>" <?= in_array($d->id, $selectedDisciplines, true) ? 'checked' : '' ?> class="rounded border-slate-300 text-red-700">
                    <?= $e($d->name) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <fieldset>
            <legend class="text-sm font-bold text-slate-800 mb-2">Divisões etárias</legend>
            <?php foreach ($ruleset->ageDivisions as $a): ?>
                <label class="flex items-center gap-2 text-sm text-slate-700 mb-1.5">
                    <input type="checkbox" name="ages[]" value="<?= (int) $a->id ?>" <?= in_array($a->id, $selectedAges, true) ? 'checked' : '' ?> class="rounded border-slate-300 text-red-700">
                    <?= $e($a->label()) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <fieldset>
            <legend class="text-sm font-bold text-slate-800 mb-2">Divisões de faixa</legend>
            <?php foreach ($ruleset->beltDivisions as $b): ?>
                <label class="flex items-center gap-2 text-sm text-slate-700 mb-1.5">
                    <input type="checkbox" name="belts[]" value="<?= (int) $b->id ?>" <?= in_array($b->id, $selectedBelts, true) ? 'checked' : '' ?> class="rounded border-slate-300 text-red-700">
                    <?= $e($b->name) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
    </div>
    <button type="submit" class="mt-5 bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">Atualizar prévia</button>
</form>

<?php if ($error): ?>
    <div class="mb-6 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm"><?= $e($error) ?></div>
<?php else: ?>
    <p class="text-lg font-bold text-slate-900 mb-4" id="total">
        <?= (int) $total ?> <?= $total === 1 ? 'categoria' : 'categorias' ?>
    </p>

    <?php if (empty($groups)): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500">Nenhuma categoria com a seleção atual.</div>
    <?php endif; ?>

    <?php foreach ($groups as $disciplineName => $byAge): ?>
        <?php $disciplineTotal = array_sum(array_map('count', $byAge)); ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-5 overflow-hidden">
            <div class="bg-slate-900 text-white px-5 py-3 flex items-center justify-between">
                <h2 class="font-bold uppercase tracking-wide text-sm"><?= $e($disciplineName) ?></h2>
                <span class="text-xs text-slate-300"><?= (int) $disciplineTotal ?> categorias</span>
            </div>
            <?php foreach ($byAge as $ageLabel => $drafts): ?>
                <details class="border-t border-slate-100">
                    <summary class="px-5 py-3 cursor-pointer text-sm font-semibold text-slate-800 hover:bg-slate-50 flex justify-between">
                        <span><?= $e($ageLabel) ?></span>
                        <span class="text-xs font-normal text-slate-500"><?= count($drafts) ?></span>
                    </summary>
                    <ul class="px-5 pb-3 divide-y divide-slate-100">
                        <?php foreach ($drafts as $draft): ?>
                            <li class="py-2 text-sm">
                                <span class="text-slate-900"><?= $e($draft->name) ?></span>
                                <span class="block text-xs text-slate-500">
                                    <span class="font-mono"><?= $e($draft->code) ?></span>
                                    <?php if ($limits($draft)): ?> · <?= $e($limits($draft)) ?><?php endif; ?>
                                    <?php if ($draft->teamSize): ?> · <?= (int) $draft->teamSize ?> integrantes<?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
