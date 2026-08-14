<?php
use Helpers\Auth;

$pageTitle = 'Usuários';
require_once __DIR__ . '/../layout/header.php';

$roleLabel = [
    'admin'             => 'Administrador',
    'sensei'            => 'Sensei',
    'aluno-colaborador' => 'Colaborador',
    'aluno'             => 'Aluno',
];
$roleClass = [
    'admin'             => 'bg-red-100 text-red-800',
    'sensei'            => 'bg-blue-100 text-blue-800',
    'aluno-colaborador' => 'bg-purple-100 text-purple-800',
    'aluno'             => 'bg-slate-100 text-slate-700',
];

// ── Organizar dados ──────────────────────────────────────────────────────────
$senseis    = [];
$dojoGroups = [];   // [ 'dojo_id|name|city' => ['meta' => [...], 'users' => [...]] ]
$noDojo     = [];

foreach ($users as $u) {
    if ($u['role'] === 'admin' || $u['role'] === 'sensei') {
        $senseis[] = $u;
        continue;
    }
    if ($u['dojo_name']) {
        $key = $u['dojo_name'];
        if (!isset($dojoGroups[$key])) {
            $dojoGroups[$key] = [
                'name'  => $u['dojo_name'],
                'city'  => $u['dojo_city'] ?? '',
                'users' => [],
            ];
        }
        $dojoGroups[$key]['users'][] = $u;
    } else {
        $noDojo[] = $u;
    }
}
ksort($dojoGroups);

// Opções de dojo para o filtro
$dojoOptions = [];
foreach ($users as $u) {
    $dn = $u['dojo_name'] ?? '';
    if ($dn && !isset($dojoOptions[$dn])) {
        $dojoOptions[$dn] = $dn . ($u['dojo_city'] ? ' / ' . $u['dojo_city'] : '');
    }
}
ksort($dojoOptions);
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Gerenciar Usuários</h1>
    <?php if (Auth::authorize(['admin', 'sensei'])): ?>
        <a href="/fgkirs-admin/users/create"
            class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            + Novo Usuário
        </a>
    <?php endif; ?>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span><?= htmlspecialchars($_SESSION['success']) ?></span>
    </div>
<?php unset($_SESSION['success']); endif; ?>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-sm border border-slate-100 px-4 py-3 mb-6 flex flex-wrap gap-3 items-center">
    <div class="relative flex-1 min-w-48">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" id="searchInput" placeholder="Buscar por nome ou e-mail..."
            class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-700 focus:border-transparent">
    </div>
    <?php if (!empty($dojoOptions)): ?>
        <select id="filterDojo"
            class="border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-700">
            <option value="">Todos os Dojos</option>
            <?php foreach ($dojoOptions as $key => $label): ?>
                <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <select id="filterRole"
        class="border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-700">
        <option value="">Todos os Perfis</option>
        <?php if (Auth::isAdmin()): ?>
            <option value="admin">Administrador</option>
            <option value="sensei">Sensei</option>
        <?php endif; ?>
        <option value="aluno-colaborador">Aluno Colaborador</option>
        <option value="aluno">Aluno</option>
    </select>
    <span id="countBadge" class="text-xs text-slate-400 whitespace-nowrap"></span>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     VISTA ORGANIZADA (padrão, sem filtros)
════════════════════════════════════════════════════════════════════════════ -->
<div id="organizedView">

    <!-- Senseis -->
    <?php if (!empty($senseis)): ?>
    <div class="mb-6">
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">
            Senseis <span class="font-normal text-slate-400">(<?= count($senseis) ?>)</span>
        </h2>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <?php foreach ($senseis as $i => $u):
                $role = $u['role'];
                $lbl  = $roleLabel[$role] ?? ucfirst($role);
                $cls  = $roleClass[$role] ?? 'bg-slate-100 text-slate-700';
                $dojo = $u['dojo_name'] ? $u['dojo_name'] . ($u['dojo_city'] ? ' / ' . $u['dojo_city'] : '') : '—';
            ?>
                <div class="flex items-center gap-4 px-4 py-3 <?= $i > 0 ? 'border-t border-slate-100' : '' ?> hover:bg-slate-50 transition">
                    <!-- Avatar -->
                    <div class="shrink-0">
                        <?php if (!empty($u['photo'])): ?>
                            <img src="/uploads/users/<?= htmlspecialchars($u['photo']) ?>" alt=""
                                class="w-10 h-10 rounded-full object-cover border border-slate-200">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-blue-700 flex items-center justify-center text-white font-bold text-sm">
                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($u['name']) ?></span>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $cls ?>"><?= $lbl ?></span>
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5 truncate">
                            <?= htmlspecialchars($u['email']) ?>
                            <?php if ($u['dojo_name']): ?>
                                · <span class="text-slate-500"><?= htmlspecialchars($dojo) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Ações -->
                    <div class="flex items-center gap-3 shrink-0">
                        <?php if ($u['role'] === 'sensei'): ?>
                        <button type="button" onclick="openProfile(<?= $u['id'] ?>)" title="Ver ficha do atleta"
                                class="text-slate-400 hover:text-blue-600 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                        <a href="/fgkirs-admin/users/edit/<?= $u['id'] ?>" title="Editar"
                            class="text-slate-500 hover:text-slate-900 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/>
                            </svg>
                        </a>
                        <a href="/fgkirs-admin/users/delete/<?= $u['id'] ?>" data-confirm-delete title="Excluir"
                            class="text-red-400 hover:text-red-700 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-10 0h14"/>
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Accordions por Dojo -->
    <?php if (!empty($dojoGroups)): ?>
    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">
        Dojos <span class="font-normal text-slate-400">(<?= count($dojoGroups) ?>)</span>
    </h2>
    <div class="space-y-2 mb-6">
        <?php foreach ($dojoGroups as $key => $group):
            $totalAlunos = count($group['users']);
            $accordionId = 'dojo-' . preg_replace('/[^a-z0-9]/i', '-', strtolower($key));
        ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <!-- Cabeçalho do accordion -->
            <button type="button"
                onclick="toggleAccordion('<?= $accordionId ?>')"
                class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 transition group">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-red-700 flex items-center justify-center text-white font-bold text-xs shrink-0">
                        <?= strtoupper(substr($group['name'], 0, 2)) ?>
                    </div>
                    <div>
                        <span class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($group['name']) ?></span>
                        <?php if ($group['city']): ?>
                            <span class="text-slate-400 text-sm"> / <?= htmlspecialchars($group['city']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-400"><?= $totalAlunos ?> aluno<?= $totalAlunos !== 1 ? 's' : '' ?></span>
                    <svg id="<?= $accordionId ?>-icon" class="w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </button>
            <!-- Conteúdo do accordion -->
            <div id="<?= $accordionId ?>" class="hidden border-t border-slate-100">
                <?php foreach ($group['users'] as $j => $u):
                    $role = $u['role'];
                    $lbl  = $roleLabel[$role] ?? ucfirst($role);
                    $cls  = $roleClass[$role] ?? 'bg-slate-100 text-slate-700';
                ?>
                    <div class="flex items-center gap-4 px-5 py-3 <?= $j > 0 ? 'border-t border-slate-50' : '' ?> hover:bg-slate-50 transition">
                        <div class="shrink-0">
                            <?php if (!empty($u['photo'])): ?>
                                <img src="/uploads/users/<?= htmlspecialchars($u['photo']) ?>" alt=""
                                    class="w-8 h-8 rounded-full object-cover border border-slate-200">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-slate-300 flex items-center justify-center text-white font-bold text-xs">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-slate-800"><?= htmlspecialchars($u['name']) ?></span>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $cls ?>"><?= $lbl ?></span>
                            </div>
                            <div class="text-xs text-slate-400 truncate"><?= htmlspecialchars($u['email']) ?></div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <button type="button" onclick="openProfile(<?= $u['id'] ?>)" title="Ver ficha do atleta"
                                    class="text-slate-400 hover:text-blue-600 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            <a href="/fgkirs-admin/users/edit/<?= $u['id'] ?>" title="Editar"
                                class="text-slate-400 hover:text-slate-900 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/>
                                </svg>
                            </a>
                            <a href="/fgkirs-admin/users/delete/<?= $u['id'] ?>" data-confirm-delete title="Excluir"
                                class="text-red-400 hover:text-red-700 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-10 0h14"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Sem dojo -->
    <?php if (!empty($noDojo)): ?>
    <div class="mb-6">
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">
            Sem dojo <span class="font-normal text-slate-400">(<?= count($noDojo) ?>)</span>
        </h2>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <?php foreach ($noDojo as $i => $u):
                $role = $u['role'];
                $lbl  = $roleLabel[$role] ?? ucfirst($role);
                $cls  = $roleClass[$role] ?? 'bg-slate-100 text-slate-700';
            ?>
                <div class="flex items-center gap-4 px-4 py-3 <?= $i > 0 ? 'border-t border-slate-100' : '' ?> hover:bg-slate-50 transition">
                    <div class="shrink-0">
                        <?php if (!empty($u['photo'])): ?>
                            <img src="/uploads/users/<?= htmlspecialchars($u['photo']) ?>" alt=""
                                class="w-8 h-8 rounded-full object-cover border border-slate-200">
                        <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-slate-300 flex items-center justify-center text-white font-bold text-xs">
                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-slate-800"><?= htmlspecialchars($u['name']) ?></span>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $cls ?>"><?= $lbl ?></span>
                        </div>
                        <div class="text-xs text-slate-400 truncate"><?= htmlspecialchars($u['email']) ?></div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <button type="button" onclick="openProfile(<?= $u['id'] ?>)" title="Ver ficha do atleta"
                                class="text-slate-400 hover:text-blue-600 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <a href="/fgkirs-admin/users/edit/<?= $u['id'] ?>" title="Editar"
                            class="text-slate-400 hover:text-slate-900 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/>
                            </svg>
                        </a>
                        <a href="/fgkirs-admin/users/delete/<?= $u['id'] ?>" data-confirm-delete title="Excluir"
                            class="text-red-400 hover:text-red-700 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-10 0h14"/>
                            </svg>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /organizedView -->

<!-- ═══════════════════════════════════════════════════════════════════════════
     VISTA FILTRADA (aparece quando há filtro ativo)
════════════════════════════════════════════════════════════════════════════ -->
<div id="filteredView" class="hidden">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div id="filteredList"></div>
        <p id="emptyMsg" class="hidden text-center text-slate-400 py-10 text-sm">Nenhum usuário encontrado.</p>
    </div>
</div>

<script>
// ── Dados dos usuários para o filtro ────────────────────────────────────────
const ALL_USERS = <?= json_encode(array_map(fn($u) => [
    'id'    => $u['id'],
    'name'  => $u['name'],
    'email' => $u['email'],
    'role'  => $u['role'],
    'dojo'  => $u['dojo_name'] ?? '',
    'city'  => $u['dojo_city'] ?? '',
    'photo' => $u['photo'] ?? '',
], $users), JSON_UNESCAPED_UNICODE) ?>;

const ROLE_LABEL = <?= json_encode($roleLabel, JSON_UNESCAPED_UNICODE) ?>;
const ROLE_CLASS = <?= json_encode($roleClass) ?>;

// ── Accordion ───────────────────────────────────────────────────────────────
function toggleAccordion(id) {
    const panel = document.getElementById(id);
    const icon  = document.getElementById(id + '-icon');
    const open  = !panel.classList.contains('hidden');
    panel.classList.toggle('hidden', open);
    icon.style.transform = open ? '' : 'rotate(180deg)';
}

// ── Filtros ─────────────────────────────────────────────────────────────────
const searchInput   = document.getElementById('searchInput');
const filterDojo    = document.getElementById('filterDojo');
const filterRole    = document.getElementById('filterRole');
const countBadge    = document.getElementById('countBadge');
const organizedView = document.getElementById('organizedView');
const filteredView  = document.getElementById('filteredView');
const filteredList  = document.getElementById('filteredList');
const emptyMsg      = document.getElementById('emptyMsg');

function isFiltered() {
    return searchInput.value.trim() !== ''
        || (filterDojo && filterDojo.value !== '')
        || filterRole.value !== '';
}

function avatar(u, size = 10) {
    const s = `w-${size} h-${size}`;
    if (u.photo) return `<img src="/uploads/users/${u.photo}" class="${s} rounded-full object-cover border border-slate-200">`;
    const colors = { admin: 'bg-red-700', sensei: 'bg-blue-700', 'aluno-colaborador': 'bg-purple-600', aluno: 'bg-slate-400' };
    return `<div class="${s} rounded-full ${colors[u.role]||'bg-slate-400'} flex items-center justify-center text-white font-bold text-sm">${u.name[0].toUpperCase()}</div>`;
}

function renderFiltered() {
    const search = searchInput.value.toLowerCase().trim();
    const dojo   = filterDojo ? filterDojo.value : '';
    const role   = filterRole.value;

    const filtered = ALL_USERS.filter(u => {
        const matchSearch = !search || u.name.toLowerCase().includes(search) || u.email.toLowerCase().includes(search);
        const matchDojo   = !dojo   || u.dojo === dojo;
        const matchRole   = !role   || u.role === role;
        return matchSearch && matchDojo && matchRole;
    });

    countBadge.textContent = filtered.length + ' usuário' + (filtered.length !== 1 ? 's' : '');
    emptyMsg.classList.toggle('hidden', filtered.length > 0);

    filteredList.innerHTML = filtered.map((u, i) => {
        const lbl = ROLE_LABEL[u.role] || u.role;
        const cls = ROLE_CLASS[u.role] || 'bg-slate-100 text-slate-700';
        const dojoText = u.dojo ? (u.city ? u.dojo + ' / ' + u.city : u.dojo) : '—';
        return `
        <div class="flex items-center gap-4 px-4 py-3 ${i > 0 ? 'border-t border-slate-100' : ''} hover:bg-slate-50 transition">
            <div class="shrink-0">${avatar(u)}</div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-semibold text-slate-900">${u.name}</span>
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full ${cls}">${lbl}</span>
                </div>
                <div class="text-xs text-slate-400 truncate">${u.email} · ${dojoText}</div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                ${u.role !== 'admin' ? `
                <button type="button" onclick="openProfile(${u.id})" title="Ver ficha do atleta"
                        class="text-slate-400 hover:text-blue-600 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>` : ''}
                <a href="/fgkirs-admin/users/edit/${u.id}" title="Editar" class="text-slate-400 hover:text-slate-900 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
                </a>
                <a href="/fgkirs-admin/users/delete/${u.id}" class="text-red-400 hover:text-red-700 transition" title="Excluir"
                   onclick="return confirm('Excluir este usuário?')">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-10 0h14"/></svg>
                </a>
            </div>
        </div>`;
    }).join('');
}

function applyFilters() {
    const filtered = isFiltered();
    organizedView.classList.toggle('hidden', filtered);
    filteredView.classList.toggle('hidden', !filtered);
    if (filtered) {
        renderFiltered();
    } else {
        countBadge.textContent = ALL_USERS.length + ' usuário' + (ALL_USERS.length !== 1 ? 's' : '');
    }
}

searchInput.addEventListener('input', applyFilters);
if (filterDojo) filterDojo.addEventListener('change', applyFilters);
filterRole.addEventListener('change', applyFilters);

applyFilters();

// ── Modal Ficha do Atleta ────────────────────────────────────────────────────
// Elementos buscados dentro das funções para garantir que o DOM está pronto
async function openProfile(userId) {
    const profileModal   = document.getElementById('profileModal');
    const profileLoading = document.getElementById('profileLoading');
    const profileContent = document.getElementById('profileContent');

    profileModal.classList.remove('hidden');
    profileModal.classList.add('flex');
    profileLoading.classList.remove('hidden');
    profileContent.classList.add('hidden');
    profileContent.innerHTML = '';

    try {
        const res = await fetch(`/fgkirs-admin/users/profile/${userId}`);
        if (!res.ok) throw new Error('Erro ao carregar dados');
        const d = await res.json();
        if (d.error) throw new Error(d.error);
        profileContent.innerHTML = buildProfileHTML(d);
        profileLoading.classList.add('hidden');
        profileContent.classList.remove('hidden');
    } catch (e) {
        profileLoading.innerHTML = `<p class="text-red-500 text-sm text-center py-4">Erro ao carregar dados do atleta.</p>`;
    }
}

function closeProfile() {
    const profileModal = document.getElementById('profileModal');
    if (profileModal) {
        profileModal.classList.add('hidden');
        profileModal.classList.remove('flex');
    }
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeProfile(); });

function fmtDate(d) {
    if (!d) return '—';
    const [y, m, day] = d.split('-');
    return `${day}/${m}/${y}`;
}

function calcAge(d) {
    if (!d) return null;
    const today = new Date(), birth = new Date(d);
    let age = today.getFullYear() - birth.getFullYear();
    const mo = today.getMonth() - birth.getMonth();
    if (mo < 0 || (mo === 0 && today.getDate() < birth.getDate())) age--;
    return age;
}

function fmtPhone(p) {
    if (!p) return '—';
    const d = p.replace(/\D/g, '');
    if (d.length === 11) return `(${d.slice(0,2)}) ${d.slice(2,7)}-${d.slice(7)}`;
    if (d.length === 10) return `(${d.slice(0,2)}) ${d.slice(2,6)}-${d.slice(6)}`;
    return p;
}

const GENDER_LABEL = { M: 'Masculino', F: 'Feminino' };
const STATUS_CONFIG = {
    active:   { label: 'Ativo',    cls: 'bg-green-100 text-green-800' },
    inactive: { label: 'Inativo',  cls: 'bg-slate-100 text-slate-600' },
    absent:   { label: 'Ausente',  cls: 'bg-yellow-100 text-yellow-800' },
};
const ROLE_LABEL_MODAL = { admin: 'Administrador', sensei: 'Sensei', 'aluno-colaborador': 'Colaborador', aluno: 'Aluno' };

function buildProfileHTML(d) {
    const age      = calcAge(d.birth_date);
    const ageStr   = age !== null ? ` · ${age} anos` : '';
    const status   = STATUS_CONFIG[d.status] || STATUS_CONFIG.active;
    const roleLabel = ROLE_LABEL_MODAL[d.role] || d.role;
    const dojoStr  = d.dojo_name ? (d.dojo_city ? `${d.dojo_name} / ${d.dojo_city}` : d.dojo_name) : null;
    const beltColor = d.belt_color || '';
    const beltBorder = beltColor.toLowerCase() === '#ffffff' || beltColor.toLowerCase() === '#fff'
        ? 'border: 1px solid #d1d5db;' : '';
    const hasMartial = d.style_name || d.belt_name;
    const hasContact = d.athlete_email || d.phone_whatsapp;
    const hasRegs    = d.fgkirs_registration || d.cbki_registration;

    const photo = d.photo
        ? `<img src="/uploads/users/${d.photo}" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow" alt="">`
        : `<div class="w-16 h-16 rounded-full bg-slate-700 flex items-center justify-center text-white text-2xl font-bold shadow">${d.name[0].toUpperCase()}</div>`;

    const row = (label, value) => value
        ? `<div class="flex justify-between gap-4 py-1.5 border-b border-slate-50 last:border-0">
               <span class="text-xs text-slate-400 whitespace-nowrap">${label}</span>
               <span class="text-sm text-slate-800 font-medium text-right">${value}</span>
           </div>`
        : '';

    return `
    <!-- Cabeçalho: status acima do nome, registros logo abaixo -->
    <div class="flex items-start gap-4 mb-4">
        ${photo}
        <div class="flex-1 min-w-0">
            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${status.cls} mb-1">${status.label}</span>
            <h3 class="text-base font-bold text-slate-900 leading-tight">${d.name}</h3>
            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-slate-100 text-slate-600 mt-1 inline-block">${roleLabel}</span>
            ${dojoStr ? `<p class="text-xs text-slate-400 mt-1 truncate">${dojoStr}</p>` : ''}
        </div>
    </div>

    ${hasRegs ? `
    <div class="mb-4">
        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Registros</p>
        <div class="bg-slate-50 rounded-lg px-4 py-1">
            ${row('FGKIRS', d.fgkirs_registration ? '#' + d.fgkirs_registration : null)}
            ${row('CBKI', d.cbki_registration || null)}
        </div>
    </div>` : ''}

    ${d.birth_date || d.gender || d.weight || d.height ? `
    <div class="mb-4">
        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Dados Pessoais</p>
        <div class="bg-slate-50 rounded-lg px-4 py-1">
            ${row('Nascimento', d.birth_date ? fmtDate(d.birth_date) + ageStr : null)}
            ${row('Sexo', GENDER_LABEL[d.gender] || null)}
            ${row('Para-karatê', Number(d.is_para_karate) === 1 ? 'Sim' : null)}
            ${row('Peso', d.weight ? d.weight + ' kg' : null)}
            ${row('Altura', d.height ? d.height + ' cm' : null)}
        </div>
    </div>` : ''}

    ${hasContact ? `
    <div class="mb-4">
        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Contato</p>
        <div class="bg-slate-50 rounded-lg px-4 py-1">
            ${row('E-mail pessoal', d.athlete_email || null)}
            ${row('WhatsApp', d.phone_whatsapp ? fmtPhone(d.phone_whatsapp) : null)}
        </div>
    </div>` : ''}

    ${hasMartial ? `
    <div class="mb-4">
        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Arte Marcial</p>
        <div class="bg-slate-50 rounded-lg px-4 py-1">
            ${row('Estilo', d.style_name || null)}
            ${d.belt_name ? `
            <div class="flex justify-between gap-4 py-1.5 border-b border-slate-50 last:border-0">
                <span class="text-xs text-slate-400 whitespace-nowrap">Graduação</span>
                <span class="text-sm text-slate-800 font-medium text-right flex items-center gap-1.5">
                    ${beltColor ? `<span class="w-3 h-3 rounded-full inline-block shrink-0" style="background:${beltColor};${beltBorder}"></span>` : ''}
                    ${d.belt_name}
                </span>
            </div>` : ''}
        </div>
    </div>` : ''}

    ${d.notes ? `
    <div class="mb-1">
        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Observações</p>
        <div class="bg-slate-50 rounded-lg px-4 py-3 text-sm text-slate-700 whitespace-pre-line">${d.notes}</div>
    </div>` : ''}

    <div class="mt-5 pt-4 border-t border-slate-100">
        <a href="/fgkirs-admin/users/edit/${d.id}"
           class="block text-center bg-red-700 hover:bg-red-800 text-white text-sm font-semibold py-2 px-4 rounded-lg transition">
            Editar cadastro
        </a>
    </div>`;
}
</script>

<!-- ── Modal Ficha do Atleta ──────────────────────────────────────────────── -->
<div id="profileModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeProfile()"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 shrink-0">
            <h2 class="text-base font-bold text-slate-900">Ficha do Atleta</h2>
            <button onclick="closeProfile()"
                    class="text-slate-400 hover:text-slate-700 transition p-1 rounded-lg hover:bg-slate-100"
                    aria-label="Fechar">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <!-- Body -->
        <div class="overflow-y-auto px-6 py-5 flex-1">
            <div id="profileLoading" class="flex justify-center py-10">
                <div class="w-8 h-8 border-2 border-red-700 border-t-transparent rounded-full animate-spin"></div>
            </div>
            <div id="profileContent" class="hidden"></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
