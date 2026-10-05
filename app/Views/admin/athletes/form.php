<?php
use Helpers\Auth;
use Helpers\Csrf;
use Helpers\Format;
use Models\Athlete;

$isEdit    = !empty($athlete['id']);
$pageTitle = $isEdit ? 'Editar Atleta' : 'Novo Atleta';
$action    = $isEdit ? '/fgkirs-admin/athletes/update/' . (int) $athlete['id'] : '/fgkirs-admin/athletes/store';

$value = static fn(string $field, string $default = ''): string
    => htmlspecialchars((string) ($athlete[$field] ?? $default));

$input = 'w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent';

require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <a href="/fgkirs-admin/athletes" class="text-sm text-slate-500 hover:text-red-700 transition">← Voltar para atletas</a>
</div>

<h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-6"><?= $pageTitle ?></h1>

<?php require __DIR__ . '/../championships/partials/flash.php'; ?>

<form method="POST" action="<?= $action ?>" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-3xl">
    <?= Csrf::field() ?>

    <?php if (Auth::isAdmin()): ?>
        <div class="mb-5">
            <label for="dojo_id" class="block text-sm font-medium text-slate-700 mb-2">Dojo *</label>
            <select id="dojo_id" name="dojo_id" required class="<?= $input ?> bg-white">
                <option value="">Selecione o dojo...</option>
                <?php foreach ($dojos as $dojo): ?>
                    <option value="<?= (int) $dojo['id'] ?>" <?= (int) ($athlete['dojo_id'] ?? 0) === (int) $dojo['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dojo['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="mb-5">
        <label for="full_name" class="block text-sm font-medium text-slate-700 mb-2">Nome completo *</label>
        <input type="text" id="full_name" name="full_name" required maxlength="255" value="<?= $value('full_name') ?>" class="<?= $input ?>">
    </div>

    <div class="grid sm:grid-cols-3 gap-5 mb-5">
        <div>
            <label for="birth_date" class="block text-sm font-medium text-slate-700 mb-2">Data de nascimento *</label>
            <input type="date" id="birth_date" name="birth_date" required value="<?= $value('birth_date') ?>" class="<?= $input ?>">
        </div>

        <div>
            <label for="gender" class="block text-sm font-medium text-slate-700 mb-2">Sexo *</label>
            <select id="gender" name="gender" required class="<?= $input ?> bg-white">
                <option value="">Selecione...</option>
                <?php foreach (Athlete::GENDERS as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($athlete['gender'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="document" class="block text-sm font-medium text-slate-700 mb-2">CPF</label>
            <input type="text" id="document" name="document" inputmode="numeric" maxlength="14" placeholder="000.000.000-00"
                value="<?= htmlspecialchars(Format::cpf($athlete['document'] ?? '')) ?>" class="<?= $input ?>">
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-5 mb-5">
        <div>
            <label for="style_id" class="block text-sm font-medium text-slate-700 mb-2">Estilo</label>
            <select id="style_id" name="style_id" class="<?= $input ?> bg-white">
                <option value="">Selecione...</option>
                <?php foreach ($styles as $style): ?>
                    <option value="<?= (int) $style['id'] ?>" <?= (int) ($athlete['style_id'] ?? 0) === (int) $style['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($style['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="graduation_id" class="block text-sm font-medium text-slate-700 mb-2">Graduação</label>
            <select id="graduation_id" name="graduation_id" class="<?= $input ?> bg-white">
                <option value="">Selecione...</option>
                <?php foreach ($graduations as $g): ?>
                    <option value="<?= (int) $g['id'] ?>" data-style="<?= (int) $g['style_id'] ?>"
                        <?= (int) ($athlete['graduation_id'] ?? 0) === (int) $g['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['belt_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-5 mb-5">
        <div>
            <label for="weight_kg" class="block text-sm font-medium text-slate-700 mb-2">Peso (kg)</label>
            <input type="number" id="weight_kg" name="weight_kg" step="0.1" min="1" max="299" value="<?= $value('weight_kg') ?>" class="<?= $input ?>">
        </div>

        <div>
            <label for="height_cm" class="block text-sm font-medium text-slate-700 mb-2">Altura (cm)</label>
            <input type="number" id="height_cm" name="height_cm" min="50" max="250" value="<?= $value('height_cm') ?>" class="<?= $input ?>">
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-5 mb-5">
        <div>
            <label for="fgkirs_registration" class="block text-sm font-medium text-slate-700 mb-2">Matrícula FGKIRS</label>
            <input type="text" id="fgkirs_registration" name="fgkirs_registration" maxlength="30" value="<?= $value('fgkirs_registration') ?>" class="<?= $input ?>">
        </div>

        <div>
            <label for="cbki_registration" class="block text-sm font-medium text-slate-700 mb-2">Matrícula CBKI</label>
            <input type="text" id="cbki_registration" name="cbki_registration" maxlength="30" value="<?= $value('cbki_registration') ?>" class="<?= $input ?>">
        </div>
    </div>

    <label class="flex items-center gap-2 mb-5 text-sm text-slate-700">
        <input type="checkbox" name="is_para_karate" value="1" <?= !empty($athlete['is_para_karate']) ? 'checked' : '' ?>
            class="rounded border-slate-300 text-red-700 focus:ring-red-700">
        Atleta de para-karatê
    </label>

    <?php if ($isEdit): ?>
        <div class="mb-6 max-w-xs">
            <label for="status" class="block text-sm font-medium text-slate-700 mb-2">Status</label>
            <select id="status" name="status" class="<?= $input ?> bg-white">
                <?php foreach (Athlete::STATUSES as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($athlete['status'] ?? 'active') === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row gap-3">
        <button type="submit" class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            <?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
        </button>
        <a href="/fgkirs-admin/athletes"
            class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition">
            Cancelar
        </a>
    </div>
</form>

<script>
    // Faixas filtradas pelo estilo escolhido; escolher uma faixa também define o estilo
    const styleSelect = document.getElementById('style_id');
    const gradSelect  = document.getElementById('graduation_id');

    function filterGraduations() {
        const style = styleSelect.value;
        let selectedHidden = false;

        Array.from(gradSelect.options).forEach(opt => {
            if (!opt.value) return;
            const hide = style !== '' && opt.dataset.style !== style;
            opt.hidden = hide;
            opt.disabled = hide;
            if (hide && opt.selected) selectedHidden = true;
        });

        if (selectedHidden) gradSelect.value = '';
    }

    styleSelect.addEventListener('change', filterGraduations);
    gradSelect.addEventListener('change', () => {
        const opt = gradSelect.selectedOptions[0];
        if (opt && opt.dataset.style) { styleSelect.value = opt.dataset.style; filterGraduations(); }
    });
    filterGraduations();

    // Máscara de CPF enquanto digita
    document.getElementById('document').addEventListener('input', e => {
        const d = e.target.value.replace(/\D/g, '').slice(0, 11);
        e.target.value = d
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1-$2');
    });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
