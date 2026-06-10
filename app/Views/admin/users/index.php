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
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
