<?php
use Helpers\Auth;
use Core\Database;

if (!Auth::isSensei() && !Auth::isAdmin()) {
    header('Location: /fgkirs-admin');
    exit;
}

$dojo = ['name' => '—'];
$students = [];
$gradDistribution = [];
$recentPromotions = [];

try {
    $db = Database::getInstance();
    $dojoId = Auth::dojoId();

    $dojo = $db->query("SELECT * FROM dojos WHERE id = :id", ['id' => $dojoId])->fetch() ?: $dojo;

    $students = $db->query("
        SELECT u.id, u.name, u.photo, u.role, u.status,
               ap.gender, ap.birth_date, ap.phone_whatsapp,
               g.belt_name, g.belt_color, g.order_rank,
               mas.name AS style_name
        FROM users u
        LEFT JOIN athlete_profiles ap    ON u.id = ap.user_id
        LEFT JOIN graduations g          ON ap.graduation_id = g.id
        LEFT JOIN martial_arts_styles mas ON ap.style_id = mas.id
        WHERE u.dojo_id = :dojo_id
          AND u.role IN ('aluno', 'aluno-colaborador')
        ORDER BY g.order_rank DESC, u.name ASC
    ", ['dojo_id' => $dojoId])->fetchAll(PDO::FETCH_ASSOC);

    $gradDistribution = $db->query("
        SELECT g.belt_name, g.belt_color, COUNT(ap.id) AS count
        FROM graduations g
        LEFT JOIN athlete_profiles ap ON g.id = ap.graduation_id
            AND ap.user_id IN (
                SELECT id FROM users
                WHERE dojo_id = :dojo_id
                  AND role IN ('aluno', 'aluno-colaborador')
            )
        GROUP BY g.id, g.belt_name, g.belt_color, g.order_rank
        HAVING count > 0
        ORDER BY g.order_rank ASC
    ", ['dojo_id' => $dojoId])->fetchAll(PDO::FETCH_ASSOC);

    try {
        $recentPromotions = $db->query("
            SELECT u.name, g.belt_name, g.belt_color, gh.promotion_date
            FROM graduation_history gh
            JOIN student_profiles sp ON gh.student_profile_id = sp.id
            JOIN users u             ON sp.user_id = u.id
            JOIN graduations g       ON gh.graduation_id = g.id
            WHERE u.dojo_id = :dojo_id
            ORDER BY gh.promotion_date DESC
            LIMIT 5
        ", ['dojo_id' => $dojoId])->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        // graduation_history may not exist yet
    }

} catch (\Exception $e) {
    error_log('dashboard_sensei: ' . $e->getMessage());
}

$totalStudents = count($students);
$activeStudents = count(array_filter($students, fn($s) => $s['status'] === 'active'));
$inactiveStudents = count(array_filter($students, fn($s) => $s['status'] === 'inactive'));
$absentStudents = count(array_filter($students, fn($s) => $s['status'] === 'absent'));

$maxGrad = !empty($gradDistribution) ? max(array_column($gradDistribution, 'count')) : 1;

$statusLabel = ['active' => 'Ativo', 'inactive' => 'Inativo', 'absent' => 'Ausente'];
$statusClass = [
    'active' => 'bg-emerald-50 text-emerald-700',
    'inactive' => 'bg-slate-100 text-slate-500',
    'absent' => 'bg-amber-50 text-amber-700',
];

$pageTitle = 'Dashboard – Sensei';
require_once __DIR__ . '/layout/header.php';
?>

<!-- Page header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Painel do Sensei</p>
        <h1 class="text-3xl font-bold text-slate-900 leading-none"><?= htmlspecialchars($dojo['name']) ?></h1>
    </div>
    <span
        class="hidden sm:inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-500 shadow-sm">
        <svg class="w-4 h-4 text-rs-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <?= date('d/m/Y') ?>
    </span>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Total de Alunos</span>
            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
        </div>
        <p class="text-4xl font-black text-slate-900"><?= $totalStudents ?></p>
        <a href="/fgkirs-admin/users" class="text-xs font-semibold text-slate-500">Ver todos →</a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Ativos</span>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
        <p class="text-4xl font-black text-emerald-600"><?= $activeStudents ?></p>
        <span class="text-xs text-slate-400">alunos regulares</span>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Inativos</span>
            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>
        </div>
        <p class="text-4xl font-black text-slate-400"><?= $inactiveStudents ?></p>
        <span class="text-xs text-slate-400">fora da academia</span>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Ausentes</span>
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>
        <p class="text-4xl font-black text-amber-500"><?= $absentStudents ?></p>
        <span class="text-xs text-slate-400">afastamento temporário</span>
    </div>

</div>

<!-- Charts row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

    <!-- Graduation Distribution -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <h2 class="text-base font-bold text-slate-900 uppercase mb-5">Distribuição de Faixas</h2>
        <?php if (empty($gradDistribution)): ?>
            <p class="text-slate-400 text-sm text-center py-8">Nenhum aluno com faixa cadastrada.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($gradDistribution as $g):
                    $pct = round(($g['count'] / $maxGrad) * 100);
                    $bc = $g['belt_color'] ?: '#94a3b8';
                    ?>
                    <div class="flex items-center gap-3">
                        <div class="w-3.5 h-3.5 rounded-full shrink-0 border border-slate-200 shadow-sm"
                            style="background:<?= htmlspecialchars($bc) ?>"></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between text-xs mb-1">
                                <span
                                    class="font-medium text-slate-600 truncate"><?= htmlspecialchars($g['belt_name']) ?></span>
                                <span class="font-black text-slate-900 ml-2"><?= $g['count'] ?></span>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                    style="width:<?= $pct ?>%;background:<?= htmlspecialchars($bc) ?>"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Promotions -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <div class="flex items-center gap-2 mb-5">
            <svg class="w-5 h-5 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
            </svg>
            <h2 class="text-base font-bold text-slate-900 uppercase">Promoções Recentes</h2>
        </div>
        <?php if (empty($recentPromotions)): ?>
            <p class="text-slate-400 text-sm text-center py-8">Nenhuma promoção registrada ainda.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentPromotions as $p):
                    $bc = $p['belt_color'] ?? '#6b7280';
                    ?>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full border-2 border-slate-200 shrink-0"
                            style="background:<?= htmlspecialchars($bc) ?>"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($p['name']) ?></p>
                            <p class="text-xs text-slate-400"><?= htmlspecialchars($p['belt_name']) ?></p>
                        </div>
                        <span
                            class="text-xs text-slate-400 shrink-0"><?= date('d/m/Y', strtotime($p['promotion_date'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Student List -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h2 class="text-base font-bold text-slate-900 uppercase">Alunos do Dojo</h2>
        <div class="flex flex-wrap gap-2">
            <select id="filterGraduation"
                class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm bg-white text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-red-700">
                <option value="">Todas as Faixas</option>
                <?php foreach (array_unique(array_filter(array_column($students, 'belt_name'))) as $belt): ?>
                    <option value="<?= htmlspecialchars($belt) ?>"><?= htmlspecialchars($belt) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filterStatus"
                class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm bg-white text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-red-700">
                <option value="">Todos os Status</option>
                <option value="active">Ativo</option>
                <option value="inactive">Inativo</option>
                <option value="absent">Ausente</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="text-left px-3 py-2.5 text-xs font-bold uppercase text-slate-400">Aluno</th>
                    <th class="text-left px-3 py-2.5 text-xs font-bold uppercase text-slate-400">Estilo / Faixa</th>
                    <th class="text-left px-3 py-2.5 text-xs font-bold uppercase text-slate-400">Status</th>
                    <th class="text-right px-3 py-2.5 text-xs font-bold uppercase text-slate-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($students as $student):
                    $st = $student['status'] ?? 'active';
                    $cls = $statusClass[$st] ?? 'bg-slate-100 text-slate-500';
                    $lbl = $statusLabel[$st] ?? $st;
                    ?>
                    <tr class="hover:bg-slate-50 transition student-row"
                        data-belt="<?= htmlspecialchars($student['belt_name'] ?? '') ?>"
                        data-status="<?= htmlspecialchars($st) ?>">
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-3">
                                <?php if ($student['photo']): ?>
                                    <img src="/uploads/users/<?= htmlspecialchars($student['photo']) ?>" alt=""
                                        class="w-9 h-9 rounded-full object-cover border border-slate-200 shrink-0">
                                <?php else: ?>
                                    <div
                                        class="w-9 h-9 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-sm shrink-0">
                                        <?= strtoupper(substr($student['name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="font-semibold text-slate-800"><?= htmlspecialchars($student['name']) ?></p>
                                    <?php if ($student['role'] === 'aluno-colaborador'): ?>
                                        <p class="text-xs text-blue-500 font-medium">Colaborador</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3">
                            <?php if ($student['belt_name']): ?>
                                <div class="flex items-center gap-2">
                                    <div class="w-3.5 h-3.5 rounded-full shrink-0 border border-slate-200"
                                        style="background:<?= htmlspecialchars($student['belt_color'] ?: '#94a3b8') ?>"></div>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-700">
                                            <?= htmlspecialchars($student['belt_name']) ?>
                                        </p>
                                        <?php if ($student['style_name']): ?>
                                            <p class="text-xs text-slate-400"><?= htmlspecialchars($student['style_name']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-slate-300 text-xs">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-3">
                            <span
                                class="status-badge inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $cls ?>"
                                data-raw="<?= $st ?>">
                                <?= $lbl ?>
                            </span>
                        </td>
                        <td class="px-3 py-3 text-right">
                            <div class="inline-flex gap-2">
                                <a href="/fgkirs-admin/users/edit/<?= $student['id'] ?>"
                                    class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                    Editar
                                </a>
                                <button onclick="cycleStatus(<?= $student['id'] ?>, this)"
                                    class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                    Status
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-slate-400 py-12 text-sm">
                            Nenhum aluno cadastrado neste dojo ainda.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const STATUS_CYCLE = ['active', 'inactive', 'absent'];
    const STATUS_LABEL = { active: 'Ativo', inactive: 'Inativo', absent: 'Ausente' };
    const STATUS_CLASS = {
        active: 'bg-emerald-50 text-emerald-700',
        inactive: 'bg-slate-100 text-slate-500',
        absent: 'bg-amber-50 text-amber-700',
    };

    const filterGrad = document.getElementById('filterGraduation');
    const filterStatus = document.getElementById('filterStatus');

    function applyFilters() {
        const grad = filterGrad.value.toLowerCase();
        const status = filterStatus.value;
        document.querySelectorAll('.student-row').forEach(row => {
            const ok = (!grad || row.dataset.belt.toLowerCase().includes(grad))
                && (!status || row.dataset.status === status);
            row.style.display = ok ? '' : 'none';
        });
    }

    filterGrad.addEventListener('change', applyFilters);
    filterStatus.addEventListener('change', applyFilters);

    function cycleStatus(userId, btn) {
        const row = btn.closest('tr');
        const badge = row.querySelector('.status-badge');
        const cur = badge.dataset.raw;
        const next = STATUS_CYCLE[(STATUS_CYCLE.indexOf(cur) + 1) % STATUS_CYCLE.length];

        fetch(`/fgkirs-admin/users/toggle-status/${userId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: next })
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                badge.textContent = STATUS_LABEL[next];
                badge.dataset.raw = next;
                badge.className = `status-badge inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${STATUS_CLASS[next]}`;
                row.dataset.status = next;
            });
    }
</script>

<?php require_once __DIR__ . '/layout/footer.php'; ?>