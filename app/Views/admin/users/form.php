<?php
use Helpers\Auth;

$pageTitle = isset($user) && $user ? 'Editar Usuário' : 'Novo Usuário';
$isEdit    = isset($user) && $user;
$ap        = $athleteProfile ?? [];

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
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">E-mail de Acesso *</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                Senha <?= $isEdit ? '<span class="font-normal text-gray-400">(deixe em branco para não alterar)</span>' : '*' ?>
            </label>
            <input type="password" id="password" name="password" <?= !$isEdit ? 'required' : '' ?>
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div class="mb-4">
            <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Perfil *</label>
            <select id="role" name="role" required onchange="toggleAthleteSection()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
                <option value="">Selecione...</option>
                <?php if (Auth::isAdmin()): ?>
                <option value="admin"  <?= ($user['role'] ?? '') === 'admin'  ? 'selected' : '' ?>>Administrador</option>
                <option value="sensei" <?= ($user['role'] ?? '') === 'sensei' ? 'selected' : '' ?>>Sensei</option>
                <?php endif; ?>
                <option value="aluno-colaborador" <?= ($user['role'] ?? '') === 'aluno-colaborador' ? 'selected' : '' ?>>Aluno Colaborador</option>
                <option value="aluno"             <?= ($user['role'] ?? '') === 'aluno'             ? 'selected' : '' ?>>Aluno</option>
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
        <?php
        $currentRole    = $user['role'] ?? '';
        $isAthlete      = in_array($currentRole, ['aluno', 'aluno-colaborador']);
        ?>
        <div id="athleteSection" class="<?= $isAthlete ? '' : 'hidden' ?>" style="background: #f4f4f4; margin-bottom8px; padding: 0 8px 6px 8px;">

            <div class="border-t border-gray-100 pt-6 mb-4">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Perfil de Atleta</p>
            </div>

            <!-- Registration numbers -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Registro FGKIRS
                    <span class="font-normal text-gray-400 text-xs">(preenchido automaticamente)</span>
                </label>
                <input type="number" name="fgkirs_registration" min="1"
                       value="<?= htmlspecialchars($ap['fgkirs_registration'] ?? ($nextFgkirs ?? '')) ?>"
                       placeholder="Ex: 1001"
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gênero</label>
                    <select name="athlete_gender"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
                        <option value="">—</option>
                        <option value="M" <?= ($ap['gender'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= ($ap['gender'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option>
                        <option value="O" <?= ($ap['gender'] ?? '') === 'O' ? 'selected' : '' ?>>Outro</option>
                    </select>
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
                    <select id="athleteGraduation" name="athlete_graduation_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
                        <option value="">Selecione o estilo primeiro</option>
                        <?php foreach ($graduations ?? [] as $g): ?>
                        <option value="<?= $g['id'] ?>"
                                data-style="<?= $g['style_id'] ?>"
                                <?= ($ap['graduation_id'] ?? '') == $g['id'] ? 'selected' : '' ?>
                                class="grad-option" style="display:none">
                            <?= htmlspecialchars($g['belt_name']) ?>
                            <?= !empty($g['belt_color']) ? '(' . htmlspecialchars($g['belt_color']) . ')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
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
            <button type="submit"
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
    const ATHLETE_ROLES = ['aluno', 'aluno-colaborador'];
    const currentGradId = <?= (int)($ap['graduation_id'] ?? 0) ?>;
    const currentStyleId = <?= (int)($ap['style_id'] ?? 0) ?>;

    function toggleAthleteSection() {
        const role = document.getElementById('role').value;
        const section = document.getElementById('athleteSection');
        section.classList.toggle('hidden', !ATHLETE_ROLES.includes(role));
        
        const senseiSection = document.getElementById('senseiSection');
        if(senseiSection) senseiSection.classList.toggle('hidden', role !== 'sensei');
    }

    function filterGraduations() {
        const styleId  = document.getElementById('athleteStyle').value;
        const select   = document.getElementById('athleteGraduation');
        const options  = select.querySelectorAll('.grad-option');

        let hasOptions = false;
        options.forEach(opt => {
            const show = opt.dataset.style === styleId;
            opt.style.display = show ? '' : 'none';
            if (show) hasOptions = true;
        });

        if (hasOptions) {
            // set placeholder text
            select.options[0].text = 'Selecione a graduação';
        } else {
            select.options[0].text = styleId ? 'Nenhuma graduação cadastrada' : 'Selecione o estilo primeiro';
        }
    }

    // Init on load
    document.addEventListener('DOMContentLoaded', function () {
        if (currentStyleId) {
            filterGraduations();
        }
        toggleAthleteSection();
    });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
