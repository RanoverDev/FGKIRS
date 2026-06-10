<?php
use Helpers\Auth;

$pageTitle = 'Usuários';
require_once __DIR__ . '/../layout/header.php';

$roleLabel = [
    'admin' => 'Administrador',
    'sensei' => 'Sensei',
    'aluno-colaborador' => 'Colaborador',
    'aluno' => 'Aluno',
];
$roleClass = [
    'admin' => 'bg-red-100 text-red-800',
    'sensei' => 'bg-blue-100 text-blue-800',
    'aluno-colaborador' => 'bg-purple-100 text-purple-800',
    'aluno' => 'bg-slate-100 text-slate-700',
];
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span><?= htmlspecialchars($_SESSION['success']) ?></span>
    </div>
<?php unset($_SESSION['success']); endif; ?>

<?php
// Build unique dojos list from users for the filter (admin only — sensei sees one dojo)
$dojoOptions = [];
if (Auth::isAdmin()) {
    foreach ($users as $u) {
        $did = $u['dojo_name'] ?? '';
        if ($did && !isset($dojoOptions[$did])) {
            $dojoOptions[$did] = $did . ($u['dojo_city'] ? ' / ' . $u['dojo_city'] : '');
        }
    }
    ksort($dojoOptions);
}
?>
<!-- Filters -->
<div class="bg-white rounded-lg shadow-sm border border-slate-100 px-4 py-3 mb-4 flex flex-wrap gap-3 items-center">
    <div class="relative flex-1 min-w-48">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input type="text" id="searchInput" placeholder="Buscar por nome ou cidade..."
            class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-700 focus:border-transparent">
    </div>
    <?php if (!empty($dojoOptions)): ?>
        <select id="filterDojo"
            class="border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-700">
            <option value="">Todos os Dojos</option>
            <?php foreach ($dojoOptions as $key => $label): ?>
                <option value="<?= htmlspecialchars(mb_strtolower($key)) ?>"><?= htmlspecialchars($label) ?></option>
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

<!-- Users Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-900 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Foto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden sm:table-cell">
                        E-mail</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Perfil</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden md:table-cell">
                        Dojo / Cidade</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="userTableBody">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            Nenhum usuário encontrado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user):
                        $role = $user['role'] ?? 'aluno';
                        $lbl = $roleLabel[$role] ?? ucfirst($role);
                        $cls = $roleClass[$role] ?? 'bg-slate-100 text-slate-700';
                        $city = $user['dojo_city'] ?? '';
                        $dojo = $user['dojo_name'] ?? '';
                        $dojoDisplay = $dojo ? ($city ? "$dojo / $city" : $dojo) : '—';
                        ?>
                        <tr class="hover:bg-gray-50 user-row" data-name="<?= htmlspecialchars(mb_strtolower($user['name'])) ?>"
                            data-city="<?= htmlspecialchars(mb_strtolower($city)) ?>"
                            data-dojo="<?= htmlspecialchars(mb_strtolower($dojo)) ?>"
                            data-role="<?= htmlspecialchars($role) ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($user['photo'])): ?>
                                    <img src="/uploads/users/<?= htmlspecialchars($user['photo']) ?>" alt=""
                                        class="w-10 h-10 rounded-full object-cover border border-slate-200">
                                <?php else: ?>
                                    <div
                                        class="w-10 h-10 rounded-full bg-red-700 flex items-center justify-center text-white font-semibold text-sm">
                                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></div>
                                <div class="text-xs text-gray-400 sm:hidden"><?= htmlspecialchars($user['email']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                                <div class="text-sm text-gray-600"><?= htmlspecialchars($user['email']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $cls ?>">
                                    <?= $lbl ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">
                                <?= htmlspecialchars($dojoDisplay) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                <a href="/fgkirs-admin/users/edit/<?= $user['id'] ?>" title="Editar"
                                    class="inline-flex text-slate-600 hover:text-slate-900 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
                                </a>
                                <a href="/fgkirs-admin/users/delete/<?= $user['id'] ?>" data-confirm-delete title="Excluir"
                                    class="inline-flex text-red-500 hover:text-red-700 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-10 0h14"/></svg>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <p id="emptyMsg" class="hidden text-center text-gray-400 py-10 text-sm">Nenhum usuário encontrado para os
            filtros selecionados.</p>
    </div>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const filterDojo = document.getElementById('filterDojo');
    const filterRole = document.getElementById('filterRole');
    const countBadge = document.getElementById('countBadge');
    const emptyMsg = document.getElementById('emptyMsg');
    const rows = document.querySelectorAll('.user-row');
    const total = rows.length;

    function applyFilters() {
        const search = searchInput.value.toLowerCase().trim();
        const dojo = filterDojo ? filterDojo.value : '';
        const role = filterRole.value;
        let visible = 0;

        rows.forEach(row => {
            const matchSearch = !search || row.dataset.name.includes(search) || row.dataset.city.includes(search);
            const matchDojo = !dojo || row.dataset.dojo === dojo;
            const matchRole = !role || row.dataset.role === role;
            const show = matchSearch && matchDojo && matchRole;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        countBadge.textContent = visible === total
            ? `${total} usuário${total !== 1 ? 's' : ''}`
            : `${visible} de ${total}`;
        emptyMsg.classList.toggle('hidden', visible > 0);
    }

    searchInput.addEventListener('input', applyFilters);
    if (filterDojo) filterDojo.addEventListener('change', applyFilters);
    filterRole.addEventListener('change', applyFilters);

    applyFilters();
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>