<?php
use Helpers\Auth;

$pageTitle = isset($user) && $user ? 'Editar Usuário' : 'Novo Usuário';
$isEdit    = isset($user) && $user;
$ap        = $athleteProfile ?? [];

$currentRole = is_array($user) ? ($user['role'] ?? '') : '';
// If role is empty/invalid but athlete_profile exists, recover as 'aluno'
if ($currentRole === '' && !empty($athleteProfile)) {
    $currentRole = 'aluno';
}
$isAthlete = in_array($currentRole, ['aluno', 'aluno-colaborador']);

require_once __DIR__ . '/../layout/header.php';

// Build graduations lookup for JS: {styleId: [{id, belt_name, belt_color}, ...]}
$gradsByStyle = [];
foreach ($graduations ?? [] as $g) {
    $gradsByStyle[$g['style_id']][] = $g;
}
?>

<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900"><?= $pageTitle ?></h1>
</div>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span><?= htmlspecialchars($_SESSION['error']) ?></span>
    </div>
<?php unset($_SESSION['error']); endif; ?>

<!-- ── Dados de acesso ────────────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow p-6 sm:p-8 max-w-2xl mb-6">
    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Dados de Acesso</p>

    <form id="userForm"
          action="<?= $isEdit ? "/fgkirs-admin/users/update/{$user['id']}" : '/fgkirs-admin/users/store' ?>"
          method="POST"
          enctype="multipart/form-data">

        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
            <input type="text" id="name" name="name" required
                   value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                E-mail de Acesso *
                <span id="emailAutoBadge" class="hidden ml-1 text-xs font-normal text-blue-600 bg-blue-50 border border-blue-200 rounded px-1.5 py-0.5">auto-gerado</span>
            </label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            <p id="emailAutoNote" class="hidden mt-1 text-xs text-slate-400">Gerado para acesso futuro. Pode ser alterado.</p>
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                Senha <?= $isEdit ? '<span class="font-normal text-gray-400">(deixe em branco para não alterar)</span>' : '*' ?>
            </label>
            <div class="relative">
                <input type="password" id="password" name="password"
                       <?= !$isEdit ? 'required' : '' ?>
                       autocomplete="new-password"
                       oninput="checkPwdStrength()"
                       class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                <button type="button" onclick="togglePwdVisibility()"
                        tabindex="-1"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition"
                        aria-label="Mostrar/ocultar senha">
                    <svg id="pwdEyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>

            <!-- Botão regenerar (visível só para aluno novo) -->
            <div id="pwdRegenWrap" class="hidden mt-2">
                <button type="button" onclick="autoFillAluno(true)"
                        class="inline-flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 font-medium transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Regenerar senha
                </button>
            </div>

            <!-- Barra de força (oculta enquanto vazio) -->
            <div id="pwdStrengthWrap" class="hidden mt-2">
                <div class="flex gap-1 h-1 mb-2">
                    <div class="flex-1 rounded-full bg-gray-200" id="pbar1"></div>
                    <div class="flex-1 rounded-full bg-gray-200" id="pbar2"></div>
                    <div class="flex-1 rounded-full bg-gray-200" id="pbar3"></div>
                    <div class="flex-1 rounded-full bg-gray-200" id="pbar4"></div>
                </div>
                <ul class="space-y-0.5 text-[11px] leading-normal" id="pwdRequirements">
                    <li class="flex items-center gap-1.5 text-gray-400" id="preq-len">    <span class="preq-icon">○</span> Mínimo de 8 caracteres</li>
                    <li class="flex items-center gap-1.5 text-gray-400" id="preq-upper">  <span class="preq-icon">○</span> Pelo menos uma maiúscula</li>
                    <li class="flex items-center gap-1.5 text-gray-400" id="preq-lower">  <span class="preq-icon">○</span> Pelo menos uma minúscula</li>
                    <li class="flex items-center gap-1.5 text-gray-400" id="preq-num">    <span class="preq-icon">○</span> Pelo menos um número</li>
                    <li class="flex items-center gap-1.5 text-gray-400" id="preq-special"><span class="preq-icon">○</span> Pelo menos um caractere especial (@, #, $, !...)</li>
                </ul>
            </div>
        </div>

        <div class="mb-4">
            <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Perfil *</label>
            <select id="role" name="role" required onchange="toggleAthleteSection()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
                <option value="">Selecione...</option>
                <?php if (Auth::isAdmin()): ?>
                <option value="admin"  <?= $currentRole === 'admin'  ? 'selected' : '' ?>>Administrador</option>
                <option value="sensei" <?= $currentRole === 'sensei' ? 'selected' : '' ?>>Sensei</option>
                <?php endif; ?>
                <option value="aluno-colaborador" <?= $currentRole === 'aluno-colaborador' ? 'selected' : '' ?>>Aluno Colaborador</option>
                <option value="aluno"             <?= $currentRole === 'aluno'             ? 'selected' : '' ?>>Aluno</option>
            </select>
        </div>

        <!-- ── Perfil Sensei ──────────────────────────────────────────── -->
        <div id="senseiSection" class="hidden mb-4 p-4 bg-slate-50 border border-slate-100 rounded-lg">
            <label class="block text-sm font-medium text-slate-700 mb-2">Registro CBKI (Exclusivo Sensei)</label>
            <input type="text" id="cbki_registration" name="cbki_registration"
                   value="<?= htmlspecialchars($ap['cbki_registration'] ?? '') ?>"
                   placeholder="Ex: CBKI-00123"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
        </div>

        <!-- ── Perfil de Atleta ──────────────────────────────────────────── -->
        <div id="athleteSection" class="<?= $isAthlete ? '' : 'hidden' ?>">

            <div class="border-t border-gray-100 pt-6 mb-4 flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Perfil de Atleta</p>
                <?php if ($isEdit && $athleteProfile === null && $isAthlete): ?>
                <span class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded px-2 py-0.5">Perfil ainda não preenchido</span>
                <?php endif; ?>
            </div>

            <!-- Registration numbers -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Registro FGKIRS
                    <span class="font-normal text-gray-400 text-xs">(preenchido automaticamente)</span>
                </label>
                <input type="number" id="fgkirs_reg" name="fgkirs_registration" min="1"
                       value="<?= htmlspecialchars($ap['fgkirs_registration'] ?? ($nextFgkirs ?? '')) ?>"
                       placeholder="Ex: 1001"
                       oninput="onRegNumChange()"
                       class="w-full sm:w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            </div>

            <!-- Birth date + Gender -->
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento</label>
                    <input type="date" name="athlete_birth_date"
                           value="<?= htmlspecialchars($ap['birth_date'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sexo</label>
                    <select name="athlete_gender"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
                        <option value="">—</option>
                        <option value="M" <?= ($ap['gender'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= ($ap['gender'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option>
                    </select>
                </div>
            </div>

            <!-- Para-karatê -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Para-karatê</label>
                <div class="flex items-center gap-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="athlete_para_karate" value="1"
                               class="text-red-700 focus:ring-red-700"
                               <?= !empty($ap['is_para_karate']) ? 'checked' : '' ?>>
                        <span class="text-sm text-gray-700">Sim</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="athlete_para_karate" value="0"
                               class="text-red-700 focus:ring-red-700"
                               <?= empty($ap['is_para_karate']) ? 'checked' : '' ?>>
                        <span class="text-sm text-gray-700">Não</span>
                    </label>
                </div>
            </div>

            <!-- Email + WhatsApp -->
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">E-mail Pessoal</label>
                    <input type="email" name="athlete_email"
                           value="<?= htmlspecialchars($ap['email'] ?? '') ?>"
                           placeholder="atleta@email.com"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp</label>
                    <input type="text" name="athlete_phone_whatsapp" data-mask="phone"
                           value="<?= htmlspecialchars($ap['phone_whatsapp'] ?? '') ?>"
                           placeholder="(51) 99999-9999"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                </div>
            </div>

            <!-- Weight + Height -->
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Peso (kg)</label>
                    <input type="number" name="athlete_weight" step="0.1" min="20" max="300"
                           value="<?= htmlspecialchars($ap['weight'] ?? '') ?>"
                           placeholder="70.5"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Altura (cm)</label>
                    <input type="number" name="athlete_height" min="100" max="250"
                           value="<?= htmlspecialchars($ap['height'] ?? '') ?>"
                           placeholder="175"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                </div>
            </div>

            <!-- Style + Graduation -->
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estilo</label>
                    <select id="athleteStyle" name="athlete_style_id"
                            onchange="filterGraduations()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
                        <option value="">Selecione...</option>
                        <?php foreach ($styles ?? [] as $s): ?>
                        <option value="<?= $s['id'] ?>"
                                <?= ($ap['style_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Graduação Atual</label>

                    <!-- Hidden native select — submits the value -->
                    <select id="athleteGraduation" name="athlete_graduation_id" class="hidden">
                        <option value="">Selecione o estilo primeiro</option>
                        <?php foreach ($graduations ?? [] as $g): ?>
                        <option value="<?= $g['id'] ?>"
                                data-style="<?= $g['style_id'] ?>"
                                data-color="<?= htmlspecialchars($g['belt_color'] ?? '') ?>"
                                <?= ($ap['graduation_id'] ?? '') == $g['id'] ? 'selected' : '' ?>
                                class="grad-option">
                            <?= htmlspecialchars($g['belt_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Custom dropdown -->
                    <div id="gradDropdownWrap" class="relative">
                        <button type="button" id="gradTrigger"
                                onclick="toggleGradDropdown(event)"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white text-left flex items-center gap-2
                                       focus:outline-none focus:ring-2 focus:ring-red-700 transition text-sm text-gray-400"
                                disabled>
                            <span id="gradDot" class="w-3.5 h-3.5 rounded-full border border-gray-300 shrink-0 hidden"></span>
                            <span id="gradLabel">Selecione o estilo primeiro</span>
                            <svg class="w-4 h-4 ml-auto text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <ul id="gradList"
                            class="hidden absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg
                                   max-h-52 overflow-y-auto text-sm">
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Histórico / Observações</label>
                <textarea name="athlete_notes" rows="4"
                          placeholder="Competições, histórico, observações médicas, etc."
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent"><?= htmlspecialchars($ap['notes'] ?? '') ?></textarea>
            </div>

        </div>

        <?php if (Auth::isAdmin() && !empty($dojos)): ?>
        <div class="mb-4 mt-4">
            <label for="dojo_id" class="block text-sm font-medium text-gray-700 mb-2">Dojo</label>
            <select id="dojo_id" name="dojo_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
                <option value="">Nenhum</option>
                <?php foreach ($dojos as $dojo): ?>
                <option value="<?= $dojo['id'] ?>" <?= ($user['dojo_id'] ?? '') == $dojo['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dojo['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="mb-6">
            <label for="photo" class="block text-sm font-medium text-gray-700 mb-2">Foto</label>
            <?php if ($isEdit && !empty($user['photo'])): ?>
            <div class="mb-2">
                <img src="/uploads/users/<?= htmlspecialchars($user['photo']) ?>"
                     alt="Foto atual" class="w-20 h-20 rounded-full object-cover border border-gray-200">
            </div>
            <?php endif; ?>
            <input type="file" id="photo" name="photo" accept=".jpg,.jpeg,image/jpeg"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            <p class="text-xs text-gray-400 mt-1">Apenas JPG — 1200px, qualidade 55%.</p>
        </div>

        
        <!-- /athleteSection -->

        <div class="flex flex-col sm:flex-row gap-3 pt-2">
            <button type="submit" id="userSubmitBtn"
                    class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
                <?= $isEdit ? 'Salvar Alterações' : 'Cadastrar' ?>
            </button>
            <a href="/fgkirs-admin/users"
               class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition">
                Cancelar
            </a>
        </div>

    </form>
</div>

<script>
    const ATHLETE_ROLES  = ['aluno', 'aluno-colaborador'];
    const currentGradId  = <?= (int)($ap['graduation_id'] ?? 0) ?>;
    const currentStyleId = <?= (int)($ap['style_id'] ?? 0) ?>;
    const IS_EDIT        = <?= $isEdit ? 'true' : 'false' ?>;

    /* ── Athlete / Sensei section toggle ──────────────────────── */
    function toggleAthleteSection() {
        const role = document.getElementById('role').value;
        document.getElementById('athleteSection').classList.toggle('hidden', !ATHLETE_ROLES.includes(role));
        const ss = document.getElementById('senseiSection');
        if (ss) ss.classList.toggle('hidden', role !== 'sensei');

        const isAluno = role === 'aluno';
        document.getElementById('pwdRegenWrap').classList.toggle('hidden', !isAluno || IS_EDIT);
        document.getElementById('emailAutoBadge').classList.toggle('hidden', !isAluno || IS_EDIT);
        document.getElementById('emailAutoNote').classList.toggle('hidden', !isAluno || IS_EDIT);

        if (isAluno && !IS_EDIT) autoFillAluno(false);
    }

    /* ── Auto-fill para aluno ──────────────────────────────────── */
    function generateStrongPassword() {
        const upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const lower   = 'abcdefghjkmnpqrstuvwxyz';
        const numbers = '23456789';
        const special = '@#$!%*?&';
        const pick    = arr => arr[Math.floor(Math.random() * arr.length)];

        let parts = [pick(upper), pick(upper), pick(lower), pick(lower),
                     pick(numbers), pick(numbers), pick(special), pick(special)];
        // Pad to 12 chars with mixed chars
        const all = upper + lower + numbers + special;
        while (parts.length < 12) parts.push(pick(all));
        // Shuffle
        for (let i = parts.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [parts[i], parts[j]] = [parts[j], parts[i]];
        }
        return parts.join('');
    }

    function autoFillAluno(regenerateOnly) {
        const regNum = document.getElementById('fgkirs_reg')?.value || '0';
        const emailField = document.getElementById('email');
        const pwdField   = document.getElementById('password');

        // Fill email only on first call (not regenerate)
        if (!regenerateOnly) {
            emailField.value = 'aluno' + regNum + '@fgkirs.com.br';
        }

        // Generate strong password and show it
        const pwd = generateStrongPassword();
        pwdField.value  = pwd;
        pwdField.type   = 'text'; // visible so admin pode copiar

        // Sync eye icon to "visible" state
        const icon = document.getElementById('pwdEyeIcon');
        if (icon) icon.innerHTML = eyeClosedPaths;

        checkPwdStrength();
    }

    function onRegNumChange() {
        if (IS_EDIT) return;
        const role = document.getElementById('role').value;
        if (role !== 'aluno') return;
        // Update email to reflect new reg number, only if it still matches the auto pattern
        const emailField = document.getElementById('email');
        if (/^aluno\d+@fgkirs\.com\.br$/.test(emailField.value)) {
            const regNum = document.getElementById('fgkirs_reg')?.value || '0';
            emailField.value = 'aluno' + regNum + '@fgkirs.com.br';
        }
    }

    /* ── Graduation custom dropdown ───────────────────────────── */
    const gradSelect  = document.getElementById('athleteGraduation');
    const gradTrigger = document.getElementById('gradTrigger');
    const gradList    = document.getElementById('gradList');
    const gradLabel   = document.getElementById('gradLabel');
    const gradDot     = document.getElementById('gradDot');

    function filterGraduations() {
        const styleId = document.getElementById('athleteStyle').value;
        const options = gradSelect.querySelectorAll('.grad-option');

        // Collect matching options
        const matches = [];
        options.forEach(opt => {
            if (opt.dataset.style === styleId) matches.push(opt);
        });

        // Update native select visibility (still used for value)
        gradSelect.options[0].text = matches.length
            ? 'Selecione a graduação'
            : (styleId ? 'Nenhuma graduação cadastrada' : 'Selecione o estilo primeiro');

        // Reset trigger
        gradSelect.value = '';
        gradLabel.textContent = gradSelect.options[0].text;
        gradLabel.classList.add('text-gray-400');
        gradDot.classList.add('hidden');
        gradDot.style.backgroundColor = '';

        // Enable/disable trigger
        gradTrigger.disabled = matches.length === 0;
        gradTrigger.classList.toggle('opacity-50', matches.length === 0);
        gradTrigger.classList.toggle('cursor-not-allowed', matches.length === 0);

        // Build list
        gradList.innerHTML = '';
        matches.forEach(opt => {
            const color = opt.dataset.color || '';
            const li    = document.createElement('li');
            li.className = 'flex items-center gap-2.5 px-4 py-2 hover:bg-gray-50 cursor-pointer select-none';
            li.dataset.value = opt.value;
            li.dataset.color = color;
            li.dataset.label = opt.textContent.trim();

            // Color swatch
            const dot = document.createElement('span');
            dot.className = 'w-3.5 h-3.5 rounded-full shrink-0';
            dot.style.backgroundColor = color || '#e5e7eb';
            dot.style.border = color.toLowerCase() === '#ffffff' || color.toLowerCase() === '#fff'
                ? '1px solid #d1d5db' : '1px solid transparent';

            const text = document.createElement('span');
            text.textContent = opt.textContent.trim();
            text.className = 'text-gray-700';

            li.appendChild(dot);
            li.appendChild(text);
            li.addEventListener('click', () => selectGraduation(li));
            gradList.appendChild(li);
        });
    }

    function selectGraduation(li) {
        gradSelect.value  = li.dataset.value;
        gradLabel.textContent = li.dataset.label;
        gradLabel.classList.remove('text-gray-400');

        const color = li.dataset.color || '';
        gradDot.style.backgroundColor = color || '#e5e7eb';
        gradDot.style.border = color.toLowerCase() === '#ffffff' || color.toLowerCase() === '#fff'
            ? '1px solid #d1d5db' : '1px solid transparent';
        gradDot.classList.remove('hidden');

        gradList.classList.add('hidden');
    }

    function toggleGradDropdown(e) {
        e.stopPropagation();
        gradList.classList.toggle('hidden');
    }

    // Close on outside click
    document.addEventListener('click', () => gradList.classList.add('hidden'));
    gradList.addEventListener('click', e => e.stopPropagation());

    // Restore selected value on edit mode
    function restoreGradSelection() {
        if (!currentGradId) return;
        const selected = gradSelect.querySelector(`option[value="${currentGradId}"]`);
        if (!selected) return;
        gradSelect.value = String(currentGradId);
        const color = selected.dataset.color || '';
        gradLabel.textContent = selected.textContent.trim();
        gradLabel.classList.remove('text-gray-400');
        gradDot.style.backgroundColor = color || '#e5e7eb';
        gradDot.style.border = (color.toLowerCase() === '#ffffff' || color.toLowerCase() === '#fff')
            ? '1px solid #d1d5db' : '1px solid transparent';
        gradDot.classList.remove('hidden');
    }

    /* ── Password strength ────────────────────────────────────── */
    const eyeOpenPaths   = `<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
    const eyeClosedPaths = `<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`;

    const pwdRules = [
        { id: 'preq-len',     re: /.{8,}/         },
        { id: 'preq-upper',   re: /[A-Z]/          },
        { id: 'preq-lower',   re: /[a-z]/          },
        { id: 'preq-num',     re: /[0-9]/          },
        { id: 'preq-special', re: /[^A-Za-z0-9]/  },
    ];
    const barColors = ['#ef4444','#f97316','#eab308','#22c55e'];
    const pbars     = [1,2,3,4].map(i => document.getElementById('pbar' + i));

    function togglePwdVisibility() {
        const inp  = document.getElementById('password');
        const icon = document.getElementById('pwdEyeIcon');
        const show = inp.type === 'password';
        inp.type       = show ? 'text' : 'password';
        icon.innerHTML = show ? eyeClosedPaths : eyeOpenPaths;
    }

    function checkPwdStrength() {
        const val  = document.getElementById('password').value;
        const wrap = document.getElementById('pwdStrengthWrap');

        wrap.classList.toggle('hidden', val.length === 0);

        let score = 0;
        pwdRules.forEach(r => {
            const el   = document.getElementById(r.id);
            const icon = el.querySelector('.preq-icon');
            const ok   = r.re.test(val);
            el.style.color = ok ? '#16a34a' : (val.length > 0 ? '#dc2626' : '#9ca3af');
            icon.textContent = ok ? '✓' : (val.length > 0 ? '✗' : '○');
            if (ok) score++;
        });

        pbars.forEach((b, i) => {
            b.style.backgroundColor = i < score ? barColors[Math.min(score - 1, 3)] : '#e5e7eb';
        });

        updateSubmitState();
    }

    function isPasswordStrong() {
        const val = document.getElementById('password').value;
        if (!val) return IS_EDIT; // edit mode: blank = ok; create mode: blank = not ok
        return pwdRules.every(r => r.re.test(val));
    }

    function updateSubmitState() {
        const btn = document.getElementById('userSubmitBtn');
        const ok  = isPasswordStrong();
        btn.disabled = !ok;
        btn.style.opacity  = ok ? '1'          : '0.5';
        btn.style.cursor   = ok ? 'pointer'    : 'not-allowed';
    }

    /* ── Init ─────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        if (currentStyleId) {
            filterGraduations();
            restoreGradSelection();
        }
        toggleAthleteSection();
        updateSubmitState();
    });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
