<?php
use Helpers\Auth;
use Core\Database;

// Redirect if not sensei
if (!Auth::isSensei()) {
    header('Location: /admin/dashboard');
    exit;
}

$db = Database::getInstance();
$dojoId = Auth::dojoId();

// Get dojo info
$dojo = $db->query("SELECT * FROM dojos WHERE id = :id", ['id' => $dojoId])->fetch();

// Get students from this dojo
$students = $db->query("
    SELECT u.*, sp.status as student_status, g.belt_name, g.belt_color, g.order_rank,
           mas.name as style_name
    FROM users u
    LEFT JOIN student_profiles sp ON u.id = sp.user_id
    LEFT JOIN graduations g ON sp.current_graduation_id = g.id
    LEFT JOIN martial_arts_styles mas ON sp.style_id = mas.id
    WHERE u.dojo_id = :dojo_id AND u.role IN ('student', 'aluno')
    ORDER BY sp.status, g.order_rank DESC, u.name
", ['dojo_id' => $dojoId])->fetchAll(PDO::FETCH_ASSOC);

// Student stats
$totalStudents = count($students);
$activeStudents = count(array_filter($students, fn($s) => $s['student_status'] === 'active'));
$inactiveStudents = count(array_filter($students, fn($s) => $s['student_status'] === 'inactive'));

// Graduation distribution for this dojo
$gradDistribution = $db->query("
    SELECT g.belt_name, g.belt_color, COUNT(sp.id) as count
    FROM graduations g
    LEFT JOIN student_profiles sp ON g.id = sp.current_graduation_id 
        AND sp.id IN (SELECT sp2.id FROM student_profiles sp2 
                      JOIN users u2 ON sp2.user_id = u2.id 
                      WHERE u2.dojo_id = :dojo_id)
    WHERE g.style_id = 1
    GROUP BY g.id
    ORDER BY g.order_rank
", ['dojo_id' => $dojoId])->fetchAll(PDO::FETCH_ASSOC);

// Recent promotions in this dojo
$recentPromotions = $db->query("
    SELECT u.name, g.belt_name, gh.promotion_date
    FROM graduation_history gh
    JOIN student_profiles sp ON gh.student_profile_id = sp.id
    JOIN users u ON sp.user_id = u.id
    JOIN graduations g ON gh.graduation_id = g.id
    WHERE u.dojo_id = :dojo_id
    ORDER BY gh.promotion_date DESC
    LIMIT 5
", ['dojo_id' => $dojoId])->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Dashboard - Sensei';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-slate-900 uppercase tracking-tight">Painel do Sensei</h1>
    <p class="text-gray-600 mt-1">Gestão do Dojo: <span class="font-bold">
            <?= htmlspecialchars($dojo['name']) ?>
        </span></p>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
        <p class="text-sm font-bold text-gray-600 uppercase">Total de Alunos</p>
        <p class="text-5xl font-black text-slate-900 mt-2">
            <?= $totalStudents ?>
        </p>
    </div>
    <div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
        <p class="text-sm font-bold text-gray-600 uppercase">Alunos Ativos</p>
        <p class="text-5xl font-black text-green-600 mt-2">
            <?= $activeStudents ?>
        </p>
    </div>
    <div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
        <p class="text-sm font-bold text-gray-600 uppercase">Alunos Inativos</p>
        <p class="text-5xl font-black text-gray-400 mt-2">
            <?= $inactiveStudents ?>
        </p>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Graduation Distribution -->
    <div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4 uppercase">Distribuição de Faixas</h2>
        <div class="space-y-2">
            <?php foreach ($gradDistribution as $grad): ?>
                <?php if ($grad['count'] > 0): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 flex-1">
                            <div class="w-4 h-4 rounded-full border border-slate-900"
                                style="background-color: <?= htmlspecialchars($grad['belt_color']) ?>"></div>
                            <span class="text-sm font-bold">
                                <?= htmlspecialchars($grad['belt_name']) ?>
                            </span>
                        </div>
                        <span class="text-sm font-black">
                            <?= $grad['count'] ?>
                        </span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Promotions -->
    <div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4 uppercase">Promoções Recentes</h2>
        <?php if (empty($recentPromotions)): ?>
            <p class="text-gray-500 text-center py-4">Nenhuma promoção ainda.</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($recentPromotions as $promo): ?>
                    <div class="flex justify-between items-center border-l-4 border-rose-600 pl-3 py-2">
                        <div>
                            <p class="font-bold text-slate-900 text-sm">
                                <?= htmlspecialchars($promo['name']) ?>
                            </p>
                            <p class="text-xs text-gray-600">
                                <?= htmlspecialchars($promo['belt_name']) ?>
                            </p>
                        </div>
                        <span class="text-xs text-gray-500">
                            <?= date('d/m', strtotime($promo['promotion_date'])) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Student List with Filters -->
<div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <h2 class="text-xl font-bold text-slate-900 uppercase">Alunos do Dojo</h2>

        <!-- Filters -->
        <div class="flex flex-wrap gap-2">
            <select id="filterGraduation" class="border-2 border-slate-900 rounded px-3 py-2 text-sm font-bold">
                <option value="">Todas as Faixas</option>
                <?php foreach (array_unique(array_column($students, 'belt_name')) as $belt): ?>
                    <?php if ($belt): ?>
                        <option value="<?= htmlspecialchars($belt) ?>">
                            <?= htmlspecialchars($belt) ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>

            <select id="filterStatus" class="border-2 border-slate-900 rounded px-3 py-2 text-sm font-bold">
                <option value="">Todos Status</option>
                <option value="active">Ativo</option>
                <option value="inactive">Inativo</option>
                <option value="absent">Ausente</option>
            </select>
        </div>
    </div>

    <!-- Student Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="studentTable">
            <thead class="bg-slate-900 text-white">
                <tr>
                    <th class="px-4 py-3 text-left font-bold uppercase">Aluno</th>
                    <th class="px-4 py-3 text-left font-bold uppercase">Faixa</th>
                    <th class="px-4 py-3 text-left font-bold uppercase">Status</th>
                    <th class="px-4 py-3 text-center font-bold uppercase">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-50 student-row"
                        data-belt="<?= htmlspecialchars($student['belt_name'] ?? '') ?>"
                        data-status="<?= htmlspecialchars($student['student_status'] ?? '') ?>">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <?php if ($student['photo']): ?>
                                    <img src="/uploads/users/<?= htmlspecialchars($student['photo']) ?>"
                                        alt="<?= htmlspecialchars($student['name']) ?>"
                                        class="w-10 h-10 rounded-full object-cover border-2 border-slate-900">
                                <?php else: ?>
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold">
                                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <span class="font-bold">
                                    <?= htmlspecialchars($student['name']) ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($student['belt_name']): ?>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded-full border border-slate-900"
                                        style="background-color: <?= htmlspecialchars($student['belt_color']) ?>"></div>
                                    <span class="font-bold text-xs">
                                        <?= htmlspecialchars($student['belt_name']) ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-400">Sem faixa</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="status-badge inline-block px-2 py-1 rounded text-xs font-bold uppercase
                            <?php
                            echo match ($student['student_status']) {
                                'active' => 'bg-green-100 text-green-800',
                                'inactive' => 'bg-gray-100 text-gray-800',
                                'absent' => 'bg-yellow-100 text-yellow-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>">
                                <?= ucfirst($student['student_status'] ?? 'N/A') ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center gap-2">
                                <a href="/admin/graduations/history/<?= $student['id'] ?>"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs font-bold uppercase"
                                    title="Histórico">
                                    📜
                                </a>
                                <?php if ($student['belt_name']): ?>
                                    <a href="/admin/graduations/promote/<?= $student['id'] ?>"
                                        class="bg-rose-600 hover:bg-rose-700 text-white px-2 py-1 rounded text-xs font-bold uppercase"
                                        title="Promover">
                                        ⬆️
                                    </a>
                                <?php endif; ?>
                                <button onclick="toggleStatus(<?= $student['id'] ?>, this)"
                                    class="bg-gray-600 hover:bg-gray-700 text-white px-2 py-1 rounded text-xs font-bold uppercase"
                                    title="Alterar Status">
                                    🔄
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Filter functionality
    const filterGrad = document.getElementById('filterGraduation');
    const filterStatus = document.getElementById('filterStatus');
    const rows = document.querySelectorAll('.student-row');

    function applyFilters() {
        const gradValue = filterGrad.value.toLowerCase();
        const statusValue = filterStatus.value.toLowerCase();

        rows.forEach(row => {
            const belt = row.dataset.belt.toLowerCase();
            const status = row.dataset.status.toLowerCase();

            const matchGrad = !gradValue || belt.includes(gradValue);
            const matchStatus = !statusValue || status === statusValue;

            row.style.display = (matchGrad && matchStatus) ? '' : 'none';
        });
    }

    filterGrad.addEventListener('change', applyFilters);
    filterStatus.addEventListener('change', applyFilters);

    // Status toggle
    function toggleStatus(userId, button) {
        const statusOptions = ['active', 'inactive', 'absent'];
        const currentBadge = button.closest('tr').querySelector('.status-badge');
        const currentStatus = currentBadge.textContent.toLowerCase().trim();
        const currentIndex = statusOptions.indexOf(currentStatus);
        const nextStatus = statusOptions[(currentIndex + 1) % statusOptions.length];

        // Send AJAX request
        fetch(`/admin/users/toggle-status/${userId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: nextStatus })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    currentBadge.textContent = nextStatus.charAt(0).toUpperCase() + nextStatus.slice(1);
                    currentBadge.className = 'status-badge inline-block px-2 py-1 rounded text-xs font-bold uppercase ' +
                        (nextStatus === 'active' ? 'bg-green-100 text-green-800' :
                            nextStatus === 'inactive' ? 'bg-gray-100 text-gray-800' :
                                'bg-yellow-100 text-yellow-800');
                    button.closest('tr').dataset.status = nextStatus;
                }
            });
    }
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>