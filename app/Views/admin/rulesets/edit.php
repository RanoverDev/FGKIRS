<?php
use Helpers\Csrf;

$id        = (int) $meta['id'];
$pageTitle = 'Regulamento — ' . $meta['name'];

$in  = 'w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-red-700 focus:border-transparent';
$btn = 'text-slate-400 hover:text-slate-700 px-1.5 text-lg leading-none';
$e   = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''));
$num = static fn (?float $v): string => $v === null ? '' : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');

/** Controles comuns de cada linha: mover para cima/baixo e remover. */
$controls = static function (string $warn = '') use ($btn): string {
    return '<div class="flex items-center gap-1 shrink-0">'
        . "<button type=\"button\" data-up class=\"$btn\" title=\"Subir\">↑</button>"
        . "<button type=\"button\" data-down class=\"$btn\" title=\"Descer\">↓</button>"
        . '<button type="button" data-remove data-warn="' . htmlspecialchars($warn) . '" class="text-red-500 hover:text-red-700 px-1.5 text-sm font-semibold">Remover</button>'
        . '</div>';
};

$ageRow = static function (string $i, ?object $a) use ($in, $e, $controls): string {
    ob_start(); ?>
    <div class="row-item flex flex-wrap items-end gap-3 py-3 border-b border-slate-100">
        <input type="hidden" name="rows[<?= $i ?>][id]" value="<?= $e($a?->id) ?>">
        <label class="w-24 text-xs text-slate-500">Código
            <input type="text" name="rows[<?= $i ?>][code]" required maxlength="20" value="<?= $e($a?->code) ?>" class="<?= $in ?> uppercase"></label>
        <label class="flex-1 min-w-[10rem] text-xs text-slate-500">Nome
            <input type="text" name="rows[<?= $i ?>][name]" required maxlength="60" value="<?= $e($a?->name) ?>" class="<?= $in ?>"></label>
        <label class="w-24 text-xs text-slate-500">Idade mín.
            <input type="number" min="0" max="120" name="rows[<?= $i ?>][age_min]" value="<?= $e($a?->ageMin) ?>" class="<?= $in ?>"></label>
        <label class="w-24 text-xs text-slate-500">Idade máx.
            <input type="number" min="0" max="120" name="rows[<?= $i ?>][age_max]" value="<?= $e($a?->ageMax) ?>" class="<?= $in ?>"></label>
        <?= $controls('Remover esta divisão etária também remove as divisões de peso dela e a tira das disciplinas.') ?>
    </div>
    <?php return ob_get_clean();
};

$beltRow = static function (string $i, ?object $b) use ($in, $e, $controls): string {
    ob_start(); ?>
    <div class="row-item flex flex-wrap items-end gap-3 py-3 border-b border-slate-100">
        <input type="hidden" name="rows[<?= $i ?>][id]" value="<?= $e($b?->id) ?>">
        <label class="w-24 text-xs text-slate-500">Código
            <input type="text" name="rows[<?= $i ?>][code]" required maxlength="20" value="<?= $e($b?->code) ?>" class="<?= $in ?> uppercase"></label>
        <label class="flex-1 min-w-[10rem] text-xs text-slate-500">Nome
            <input type="text" name="rows[<?= $i ?>][name]" required maxlength="60" value="<?= $e($b?->name) ?>" class="<?= $in ?>"></label>
        <label class="w-28 text-xs text-slate-500">Nível mín. (1–20)
            <input type="number" min="1" max="20" required name="rows[<?= $i ?>][level_min]" value="<?= $e($b?->levelMin) ?>" class="<?= $in ?>"></label>
        <label class="w-28 text-xs text-slate-500">Nível máx. (1–20)
            <input type="number" min="1" max="20" required name="rows[<?= $i ?>][level_max]" value="<?= $e($b?->levelMax) ?>" class="<?= $in ?>"></label>
        <?= $controls() ?>
    </div>
    <?php return ob_get_clean();
};

$weightRow = static function (string $i, int|string $ageId, string $gender, ?object $w) use ($in, $e, $num, $controls): string {
    ob_start(); ?>
    <div class="row-item flex flex-wrap items-end gap-3 py-2 border-b border-slate-100">
        <input type="hidden" name="rows[<?= $i ?>][id]" value="<?= $e($w?->id) ?>">
        <input type="hidden" name="rows[<?= $i ?>][age_division_id]" value="<?= $e($ageId) ?>">
        <input type="hidden" name="rows[<?= $i ?>][gender]" value="<?= $e($gender) ?>">
        <label class="flex-1 min-w-[8rem] text-xs text-slate-500">Nome
            <input type="text" name="rows[<?= $i ?>][name]" required maxlength="60" value="<?= $e($w?->name) ?>" placeholder="Ex: até 45kg" class="<?= $in ?>"></label>
        <label class="w-24 text-xs text-slate-500">Acima de (kg)
            <input type="text" inputmode="decimal" name="rows[<?= $i ?>][weight_min]" value="<?= $num($w?->weightMin) ?>" class="<?= $in ?>"></label>
        <label class="w-24 text-xs text-slate-500">Até (kg)
            <input type="text" inputmode="decimal" name="rows[<?= $i ?>][weight_max]" value="<?= $num($w?->weightMax) ?>" class="<?= $in ?>"></label>
        <?= $controls() ?>
    </div>
    <?php return ob_get_clean();
};

$disciplineRow = static function (string $i, ?object $d) use ($in, $e, $controls, $ruleset): string {
    ob_start(); ?>
    <div class="row-item py-4 border-b border-slate-100">
        <input type="hidden" name="rows[<?= $i ?>][id]" value="<?= $e($d?->id) ?>">
        <div class="flex flex-wrap items-end gap-3">
            <label class="w-24 text-xs text-slate-500">Código
                <input type="text" name="rows[<?= $i ?>][code]" required maxlength="20" value="<?= $e($d?->code) ?>" class="<?= $in ?> uppercase"></label>
            <label class="flex-1 min-w-[10rem] text-xs text-slate-500">Nome
                <input type="text" name="rows[<?= $i ?>][name]" required maxlength="80" value="<?= $e($d?->name) ?>" class="<?= $in ?>"></label>
            <label class="w-32 text-xs text-slate-500">Modalidade
                <select name="rows[<?= $i ?>][modality]" class="<?= $in ?> bg-white">
                    <option value="kata" <?= ($d?->modality ?? 'kata') === 'kata' ? 'selected' : '' ?>>Kata</option>
                    <option value="kumite" <?= ($d?->modality ?? '') === 'kumite' ? 'selected' : '' ?>>Kumite</option>
                </select></label>
            <label class="w-32 text-xs text-slate-500">Formato
                <select name="rows[<?= $i ?>][format]" class="<?= $in ?> bg-white">
                    <option value="individual" <?= ($d?->format ?? 'individual') === 'individual' ? 'selected' : '' ?>>Individual</option>
                    <option value="team" <?= ($d?->format ?? '') === 'team' ? 'selected' : '' ?>>Equipe</option>
                </select></label>
            <label class="w-24 text-xs text-slate-500">Integrantes
                <input type="number" min="2" max="20" name="rows[<?= $i ?>][team_size]" value="<?= $e($d?->teamSize) ?>" class="<?= $in ?>"></label>
            <label class="w-24 text-xs text-slate-500">Reservas
                <input type="number" min="0" max="20" name="rows[<?= $i ?>][team_reserves]" value="<?= $e($d?->teamReserves) ?>" class="<?= $in ?>"></label>
            <?= $controls('Remover esta disciplina não altera eventos já criados.') ?>
        </div>
        <div class="flex flex-wrap gap-x-6 gap-y-2 mt-3 text-sm text-slate-700">
            <label class="flex items-center gap-2"><input type="checkbox" name="rows[<?= $i ?>][split_gender]" value="1" <?= ($d?->splitGender ?? true) ? 'checked' : '' ?> class="rounded border-slate-300 text-red-700"> Separa por sexo</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="rows[<?= $i ?>][split_belt]" value="1" <?= ($d?->splitBelt ?? true) ? 'checked' : '' ?> class="rounded border-slate-300 text-red-700"> Separa por faixa</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="rows[<?= $i ?>][split_weight]" value="1" <?= ($d?->splitWeight ?? false) ? 'checked' : '' ?> class="rounded border-slate-300 text-red-700"> Separa por peso</label>
        </div>
        <div class="mt-3">
            <p class="text-xs text-slate-500 mb-1.5">Divisões etárias desta disciplina (sem nenhuma marcada, ela não gera categorias):</p>
            <div class="flex flex-wrap gap-x-5 gap-y-1.5 text-sm text-slate-700">
                <?php foreach ($ruleset->ageDivisions as $age): ?>
                    <label class="flex items-center gap-1.5">
                        <input type="checkbox" name="rows[<?= $i ?>][ages][]" value="<?= (int) $age->id ?>"
                            <?= $d !== null && in_array($age->id, $d->ageDivisionIds, true) ? 'checked' : '' ?>
                            class="rounded border-slate-300 text-red-700">
                        <?= $e($age->name) ?>
                    </label>
                <?php endforeach; ?>
                <?php if (empty($ruleset->ageDivisions)): ?>
                    <span class="text-xs text-slate-400">Cadastre as divisões etárias primeiro.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
};

/** Bloco de linhas editáveis: lista, modelo para linhas novas e botão de adicionar. */
$saveButton = 'bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-5 rounded-lg transition';
$addButton  = 'border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2 px-4 rounded-lg transition text-sm';

require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <a href="/fgkirs-admin/rulesets" class="text-sm text-slate-500 hover:text-red-700 transition">← Voltar para regulamentos</a>
</div>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900"><?= $e($meta['name']) ?></h1>
    <div class="flex flex-wrap gap-2">
        <a href="/fgkirs-admin/rulesets/<?= $id ?>/preview"
            class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2 px-5 rounded-lg transition">Pré-visualizar categorias</a>
        <form method="POST" action="/fgkirs-admin/rulesets/duplicate/<?= $id ?>"
            onsubmit="return confirm('Duplicar este regulamento? A cópia nasce sem ser o padrão.');">
            <?= Csrf::field() ?>
            <button type="submit" class="border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2 px-5 rounded-lg transition">Duplicar regulamento</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../championships/partials/flash.php'; ?>

<!-- Dados gerais -->
<form method="POST" action="/fgkirs-admin/rulesets/update/<?= $id ?>" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <?= Csrf::field() ?>
    <h2 class="text-lg font-bold text-slate-900 mb-4">Dados gerais</h2>
    <div class="grid sm:grid-cols-2 gap-4 mb-4">
        <label class="text-sm font-medium text-slate-700">Nome
            <input type="text" name="name" required maxlength="120" value="<?= $e($meta['name']) ?>" class="<?= $in ?> mt-1"></label>
        <label class="text-sm font-medium text-slate-700">Como a idade é calculada
            <select name="age_policy" class="<?= $in ?> bg-white mt-1">
                <option value="event_date" <?= $meta['age_policy'] === 'event_date' ? 'selected' : '' ?>>Idade na data do evento</option>
                <option value="year_end" <?= $meta['age_policy'] === 'year_end' ? 'selected' : '' ?>>Idade que completa no ano</option>
            </select></label>
    </div>
    <div class="flex flex-wrap gap-6 mb-5 text-sm text-slate-700">
        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" <?= $meta['is_active'] ? 'checked' : '' ?> class="rounded border-slate-300 text-red-700"> Ativo</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_default" value="1" <?= $meta['is_default'] ? 'checked' : '' ?> class="rounded border-slate-300 text-red-700"> Regulamento padrão para novos eventos</label>
    </div>
    <button type="submit" class="<?= $saveButton ?>">Salvar dados gerais</button>
</form>

<!-- Divisões etárias -->
<form id="ages" method="POST" action="/fgkirs-admin/rulesets/<?= $id ?>/ages" data-block class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <?= Csrf::field() ?>
    <h2 class="text-lg font-bold text-slate-900">Divisões etárias</h2>
    <p class="text-xs text-slate-500 mb-3">Deixe a idade mínima ou a máxima em branco para faixa aberta (ex.: Master, 36 em diante). A ordem aqui é a da prévia.</p>
    <div data-list>
        <div data-rows>
            <?php foreach ($ruleset->ageDivisions as $n => $a): echo $ageRow((string) $n, $a); endforeach; ?>
        </div>
        <template><?= $ageRow('__i__', null) ?></template>
        <div class="flex gap-3 mt-4">
            <button type="button" data-add class="<?= $addButton ?>">+ Adicionar divisão</button>
            <button type="submit" class="<?= $saveButton ?>">Salvar divisões etárias</button>
        </div>
    </div>
</form>

<!-- Divisões de faixa -->
<form id="belts" method="POST" action="/fgkirs-admin/rulesets/<?= $id ?>/belts" data-block class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <?= Csrf::field() ?>
    <h2 class="text-lg font-bold text-slate-900">Divisões de faixa</h2>
    <p class="text-xs text-slate-500 mb-3">
        Cada divisão cobre um intervalo do nível comum das graduações (tela Graduações), que vale para qualquer estilo.
        Os intervalos não devem se sobrepor.
    </p>
    <div data-list>
        <div data-rows>
            <?php foreach ($ruleset->beltDivisions as $n => $b): echo $beltRow((string) $n, $b); endforeach; ?>
        </div>
        <template><?= $beltRow('__i__', null) ?></template>
        <div class="flex gap-3 mt-4">
            <button type="button" data-add class="<?= $addButton ?>">+ Adicionar divisão</button>
            <button type="submit" class="<?= $saveButton ?>">Salvar divisões de faixa</button>
        </div>
    </div>
</form>

<!-- Divisões de peso -->
<form id="weights" method="POST" action="/fgkirs-admin/rulesets/<?= $id ?>/weights" data-block class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <?= Csrf::field() ?>
    <h2 class="text-lg font-bold text-slate-900">Divisões de peso</h2>
    <p class="text-xs text-slate-500 mb-4">
        Uma tabela por idade e sexo, usada pelas disciplinas que separam por peso. "Acima de" é exclusivo e "Até" é inclusivo.
        Idade e sexo sem divisão geram a categoria sem limite de peso.
    </p>

    <?php if (empty($ruleset->ageDivisions)): ?>
        <p class="text-sm text-slate-500">Cadastre as divisões etárias primeiro.</p>
    <?php endif; ?>

    <div class="grid lg:grid-cols-2 gap-5">
        <?php foreach ($ruleset->ageDivisions as $age): ?>
            <?php foreach (['M' => 'Masculino', 'F' => 'Feminino'] as $gender => $genderLabel): ?>
                <div data-list class="border border-slate-200 rounded-lg p-4">
                    <h3 class="text-sm font-bold text-slate-800 mb-1"><?= $e($age->label()) ?> — <?= $genderLabel ?></h3>
                    <div data-rows>
                        <?php foreach ($ruleset->weightClassesFor($age, $gender) as $w):
                            echo $weightRow('w' . $w->id, $age->id, $gender, $w);
                        endforeach; ?>
                    </div>
                    <template><?= $weightRow('__i__', $age->id, $gender, null) ?></template>
                    <button type="button" data-add class="<?= $addButton ?> mt-3">+ Adicionar peso</button>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <button type="submit" class="<?= $saveButton ?> mt-5">Salvar divisões de peso</button>
</form>

<!-- Disciplinas -->
<form id="disciplines" method="POST" action="/fgkirs-admin/rulesets/<?= $id ?>/disciplines" data-block class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <?= Csrf::field() ?>
    <h2 class="text-lg font-bold text-slate-900">Disciplinas</h2>
    <p class="text-xs text-slate-500 mb-3">Define como cada modalidade se divide. É isto que alimenta o gerador de categorias.</p>
    <div data-list>
        <div data-rows>
            <?php foreach ($ruleset->disciplines as $n => $d): echo $disciplineRow((string) $n, $d); endforeach; ?>
        </div>
        <template><?= $disciplineRow('__i__', null) ?></template>
        <div class="flex gap-3 mt-4">
            <button type="button" data-add class="<?= $addButton ?>">+ Adicionar disciplina</button>
            <button type="submit" class="<?= $saveButton ?>">Salvar disciplinas</button>
        </div>
    </div>
</form>

<script>
    // Editor de linhas: adicionar, remover e reordenar. A ordem do DOM é a ordem gravada.
    document.querySelectorAll('form[data-block]').forEach(form => {
        let counter = 1000;

        form.addEventListener('click', ev => {
            const target = ev.target;
            const row = target.closest('.row-item');

            if (target.matches('[data-add]')) {
                const list = target.closest('[data-list]');
                const html = list.querySelector('template').innerHTML.replaceAll('__i__', 'n' + (counter++));
                list.querySelector('[data-rows]').insertAdjacentHTML('beforeend', html);
            } else if (row && target.matches('[data-remove]')) {
                const isSaved = row.querySelector('input[name$="[id]"]').value !== '';
                const warn = target.dataset.warn;
                if (isSaved && warn && !confirm(warn + ' Continuar? (só vale depois de salvar)')) return;
                row.remove();
            } else if (row && target.matches('[data-up]')) {
                if (row.previousElementSibling) row.parentNode.insertBefore(row, row.previousElementSibling);
            } else if (row && target.matches('[data-down]')) {
                if (row.nextElementSibling) row.parentNode.insertBefore(row.nextElementSibling, row);
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
