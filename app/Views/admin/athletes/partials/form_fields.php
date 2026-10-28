<?php
/**
 * Campos do formulário de atleta, compartilhados pela página e pelo modal.
 *
 * @var array|null $athlete
 * @var array      $dojos
 * @var array      $styles
 * @var array      $graduations
 */
use Helpers\Auth;
use Helpers\Format;
use Models\Athlete;

$isEdit = !empty($athlete['id']);

$value = static fn(string $field, string $default = ''): string
    => htmlspecialchars((string) ($athlete[$field] ?? $default));

$input = 'w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent';
?>
    <div class="mb-5 flex items-center gap-4" data-athlete-photo-block>
        <?php $photoUrl = \Helpers\AthletePhoto::url($athlete['photo'] ?? null); ?>
        <div class="w-24 h-24 shrink-0 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400">
            <img data-athlete-photo-preview src="<?= htmlspecialchars((string) $photoUrl) ?>" alt="Foto do atleta"
                class="w-full h-full object-cover object-center <?= $photoUrl ? '' : 'hidden' ?>">
            <svg data-athlete-photo-placeholder xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 <?= $photoUrl ? 'hidden' : '' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.118a7.5 7.5 0 0115 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.5-1.632z"/></svg>
        </div>
        <div class="min-w-0">
            <label for="photo" class="block text-sm font-medium text-slate-700 mb-1">Foto</label>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" data-athlete-photo
                class="block w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
            <p class="text-xs text-slate-500 mt-1">JPG, PNG ou WebP, até 2 MB. Imagens com mais de 400 px de largura são reduzidas.</p>
            <?php if ($photoUrl): ?>
                <label class="inline-flex items-center gap-2 mt-1 text-xs text-slate-600">
                    <input type="checkbox" name="remove_photo" value="1" class="rounded border-slate-300 text-red-700 focus:ring-red-700">
                    Remover a foto atual
                </label>
            <?php endif; ?>
        </div>
    </div>

    <?php if (Athlete::supportsGuests()): ?>
        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-4" data-guest-block>
            <label class="flex items-center gap-2 text-sm font-semibold text-amber-900 cursor-pointer">
                <input type="checkbox" name="is_guest" value="1" data-guest-toggle <?= !empty($athlete['is_guest']) ? 'checked' : '' ?>
                    class="rounded border-amber-400 text-red-700 focus:ring-red-700">
                Atleta convidado (não é aluno deste dojo)
            </label>
            <div data-guest-origin class="mt-3 <?= !empty($athlete['is_guest']) ? '' : 'hidden' ?>">
                <label for="guest_origin" class="block text-sm font-medium text-slate-700 mb-1">Dojo ou clube de origem *</label>
                <input type="text" id="guest_origin" name="guest_origin" maxlength="120" value="<?= $value('guest_origin') ?>"
                    placeholder="Ex.: Dojo Shotokan Pelotas" class="<?= $input ?>" <?= !empty($athlete['is_guest']) ? 'required' : '' ?>>
                <p class="text-xs text-amber-800 mt-1">Fica no cadastro do seu dojo, marcado como convidado, para reaproveitar em outros eventos.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (Auth::isAdmin()): ?>
        <div class="mb-5">
            <label for="dojo_id" class="block text-sm font-medium text-slate-700 mb-2">Dojo *</label>
            <select id="dojo_id" name="dojo_id" required class="<?= $input ?> bg-white">
                <option value="">Selecione o dojo...</option>
                <?php foreach ($dojos as $dojo): ?>
                    <option value="<?= (int) $dojo['id'] ?>" <?= (int) ($athlete['dojo_id'] ?? $defaultDojoId ?? 0) === (int) $dojo['id'] ? 'selected' : '' ?>>
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

