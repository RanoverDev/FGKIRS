<?php
use Helpers\Csrf;
use Models\CompetitionCategory;

$isEdit    = !empty($category);
$pageTitle = $isEdit ? 'Editar Categoria' : 'Nova Categoria';
$action    = $isEdit
    ? '/fgkirs-admin/categories/update/' . (int) $category['id']
    : '/fgkirs-admin/categories/store';

$value = static fn(string $field, string $default = ''): string
    => htmlspecialchars((string) ($category[$field] ?? $default));

require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <a href="/fgkirs-admin/categories" class="text-sm text-slate-500 hover:text-red-700 transition">
        ← Voltar para categorias
    </a>
</div>

<h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-6"><?= $pageTitle ?></h1>

<?php require __DIR__ . '/../championships/partials/flash.php'; ?>

<form method="POST" action="<?= $action ?>" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <?= Csrf::field() ?>

    <div class="mb-5">
        <label for="name" class="block text-sm font-medium text-slate-700 mb-2">Nome da categoria *</label>
        <input type="text" id="name" name="name" required maxlength="180"
            value="<?= $value('name') ?>"
            placeholder="Ex: Kumite Individual — Masculino — Juvenil (15 a 17) — Faixa Preta — até 50kg"
            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        <p class="text-xs text-slate-500 mt-1.5">
            É este nome que aparece para o sensei na hora de inscrever e na ficha impressa.
        </p>
    </div>

    <div class="grid md:grid-cols-3 gap-5 mb-5">
        <div>
            <label for="modality" class="block text-sm font-medium text-slate-700 mb-2">Modalidade *</label>
            <select id="modality" name="modality"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                <?php foreach (CompetitionCategory::MODALITIES as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($category['modality'] ?? '') === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="entry_type" class="block text-sm font-medium text-slate-700 mb-2">Tipo *</label>
            <select id="entry_type" name="entry_type"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                <?php foreach (CompetitionCategory::ENTRY_TYPES as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($category['entry_type'] ?? '') === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="gender" class="block text-sm font-medium text-slate-700 mb-2">Sexo *</label>
            <select id="gender" name="gender"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                <?php foreach (CompetitionCategory::GENDERS as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($category['gender'] ?? 'X') === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-5 mb-5">
        <div>
            <label for="age_min" class="block text-sm font-medium text-slate-700 mb-2">Idade mínima</label>
            <input type="number" id="age_min" name="age_min" min="0" max="120"
                value="<?= $value('age_min') ?>" placeholder="sem limite"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div>
            <label for="age_max" class="block text-sm font-medium text-slate-700 mb-2">Idade máxima</label>
            <input type="number" id="age_max" name="age_max" min="0" max="120"
                value="<?= $value('age_max') ?>" placeholder="sem limite"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div>
            <label for="age_label" class="block text-sm font-medium text-slate-700 mb-2">Nome da faixa etária</label>
            <input type="text" id="age_label" name="age_label" maxlength="60"
                value="<?= $value('age_label') ?>" placeholder="Ex: Infanto Juvenil"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-5 mb-5">
        <div>
            <label for="belt_group" class="block text-sm font-medium text-slate-700 mb-2">Graduação *</label>
            <select id="belt_group" name="belt_group"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                <?php foreach (CompetitionCategory::BELT_GROUPS as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($category['belt_group'] ?? 'any') === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="team_size" class="block text-sm font-medium text-slate-700 mb-2">Integrantes por equipe</label>
            <input type="number" id="team_size" name="team_size" min="2" max="10"
                value="<?= $value('team_size') ?>" placeholder="Ex: 3"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            <p class="text-xs text-slate-500 mt-1.5">Usado apenas em categorias de equipe.</p>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-5 mb-5">
        <div>
            <label for="weight_max" class="block text-sm font-medium text-slate-700 mb-2">Peso máximo (kg)</label>
            <input type="number" step="0.01" id="weight_max" name="weight_max" min="0"
                value="<?= $value('weight_max') ?>" placeholder="Ex: 50"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            <p class="text-xs text-slate-500 mt-1.5">Inclusivo: aceita pesos <strong>até</strong> este valor.</p>
        </div>

        <div>
            <label for="weight_min" class="block text-sm font-medium text-slate-700 mb-2">Peso mínimo (kg)</label>
            <input type="number" step="0.01" id="weight_min" name="weight_min" min="0"
                value="<?= $value('weight_min') ?>" placeholder="Ex: 50"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            <p class="text-xs text-slate-500 mt-1.5">Exclusivo: aceita pesos <strong>acima</strong> deste valor.</p>
        </div>
    </div>

    <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-xs text-slate-600 mb-5">
        O peso vale só para Kumite individual. Deixe os dois campos em branco para categorias sem divisão de peso —
        é assim que “até 50 kg” e “acima de 50 kg” cobrem todos os atletas sem sobreposição.
    </div>

    <div class="grid md:grid-cols-2 gap-5 mb-6">
        <div>
            <label for="sort_order" class="block text-sm font-medium text-slate-700 mb-2">Ordem de exibição</label>
            <input type="number" id="sort_order" name="sort_order"
                value="<?= $value('sort_order', '0') ?>"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div class="flex items-end">
            <label class="flex items-center gap-2 text-sm text-slate-700 pb-2">
                <input type="checkbox" name="is_active" value="1"
                    <?= (!$isEdit || $category['is_active']) ? 'checked' : '' ?>
                    class="rounded border-slate-300 text-red-700 focus:ring-red-700">
                Categoria ativa (aparece nos eventos)
            </label>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 pt-5 border-t border-slate-100">
        <button type="submit"
            class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2.5 px-8 rounded-lg transition">
            <?= $isEdit ? 'Salvar alterações' : 'Criar categoria' ?>
        </button>
        <a href="/fgkirs-admin/categories"
            class="border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2.5 px-8 rounded-lg transition">
            Cancelar
        </a>
    </div>
</form>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
