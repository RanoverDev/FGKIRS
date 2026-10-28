<?php
use Helpers\Auth;
use Core\Database;

if (!Auth::isSensei() && !Auth::isAdmin()) {
    header('Location: /fgkirs-admin');
    exit;
}

$dojo = ['name' => '—'];
$statusCounts = [];
$gradDistribution = [];
$recentPromotions = [];

try {
    $db = Database::getInstance();
    $dojoId = Auth::dojoId();

    if ($dojoId) {
        $dbDojo = $db->query("SELECT * FROM dojos WHERE id = :id", ['id' => $dojoId])->fetch();
    } else {
        $dbDojo = $db->query("SELECT * FROM dojos WHERE sensei_id = :sensei_id LIMIT 1", ['sensei_id' => Auth::id()])->fetch();
    }

    if ($dbDojo) {
        $dojo = $dbDojo;
        $dojoId = $dojo['id'];
    }

    $statusCounts = $db->query("
        SELECT u.status, COUNT(*) AS total
        FROM users u
        WHERE u.dojo_id = :dojo_id
          AND u.role IN ('aluno', 'aluno-colaborador')
        GROUP BY u.status
    ", ['dojo_id' => $dojoId])->fetchAll(PDO::FETCH_KEY_PAIR);

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

$totalStudents = array_sum($statusCounts);
$activeStudents = (int) ($statusCounts['active'] ?? 0);
$inactiveStudents = (int) ($statusCounts['inactive'] ?? 0);
$absentStudents = (int) ($statusCounts['absent'] ?? 0);

$maxGrad = !empty($gradDistribution) ? max(array_column($gradDistribution, 'count')) : 1;

$pageTitle = 'Dashboard – Sensei';
require_once __DIR__ . '/layout/header.php';
?>

<!-- Page header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Painel do Sensei</p>
        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold text-slate-900 leading-none"><?= htmlspecialchars($dojo['name']) ?></h1>
            <?php if (!empty($dojo['id'])): ?>
                <a href="/fgkirs-admin/dojos/edit/<?= $dojo['id'] ?>" class="text-blue-600 hover:text-blue-700 transition" title="Editar Dojo">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </a>
            <?php endif; ?>
        </div>
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

<?php require_once __DIR__ . '/layout/footer.php'; ?>
