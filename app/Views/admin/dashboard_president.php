<?php
use Helpers\Auth;
use Core\Database;

if (!Auth::isAdmin()) {
    header('Location: /fgkirs-admin');
    exit;
}

$totalDojos      = 0;
$totalAthletes   = 0;
$paymentStats    = ['pending' => 0, 'overdue' => 0, 'paid' => 0];
$styleDistribution  = [];
$overdueSenseis     = [];
$recentGraduations  = [];
$gradDistribution   = [];

try {
    $db = Database::getInstance();

    $totalDojos = $db->query("SELECT COUNT(*) as count FROM dojos")->fetch()['count'] ?? 0;

    try {
        $totalAthletes = $db->query(
            "SELECT COUNT(*) as count FROM student_profiles WHERE status = 'active'"
        )->fetch()['count'] ?? 0;
    } catch (\Exception $e) { error_log('dashboard KPI athletes: ' . $e->getMessage()); }

    try {
        $paymentStats = $db->query("
            SELECT
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END) as overdue,
                SUM(CASE WHEN status = 'paid'    THEN amount ELSE 0 END) as paid
            FROM payments
        ")->fetch() ?: $paymentStats;
    } catch (\Exception $e) { error_log('dashboard KPI payments: ' . $e->getMessage()); }

    try {
        $styleDistribution = $db->query("
            SELECT mas.name, COUNT(sp.id) as count
            FROM martial_arts_styles mas
            LEFT JOIN student_profiles sp ON mas.id = sp.style_id AND sp.status = 'active'
            GROUP BY mas.id
            ORDER BY count DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Exception $e) { error_log('dashboard styles: ' . $e->getMessage()); }

    try {
        $overdueSenseis = $db->query("
            SELECT u.name, d.name as dojo_name,
                   SUM(p.amount) as total_overdue,
                   MIN(p.due_date) as oldest_due
            FROM users u
            JOIN dojos d ON d.sensei_id = u.id
            JOIN payments p ON p.user_id = u.id
            WHERE p.status = 'overdue' AND u.role = 'sensei'
            GROUP BY u.id
            ORDER BY oldest_due ASC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Exception $e) { error_log('dashboard overdue: ' . $e->getMessage()); }

    try {
        $recentGraduations = $db->query("
            SELECT u.name, g.belt_name, g.belt_color, gh.promotion_date,
                   sensei.name as promoted_by
            FROM graduation_history gh
            JOIN student_profiles sp ON gh.student_profile_id = sp.id
            JOIN users u ON sp.user_id = u.id
            JOIN graduations g ON gh.graduation_id = g.id
            JOIN users sensei ON gh.promoted_by_sensei_id = sensei.id
            ORDER BY gh.promotion_date DESC
            LIMIT 8
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Exception $e) { error_log('dashboard graduations: ' . $e->getMessage()); }

    try {
        $gradDistribution = $db->query("
            SELECT g.belt_name, g.belt_color, COUNT(sp.id) as count
            FROM graduations g
            LEFT JOIN student_profiles sp ON g.id = sp.current_graduation_id AND sp.status = 'active'
            WHERE g.style_id = 1
            GROUP BY g.id
            ORDER BY g.order_rank
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Exception $e) { error_log('dashboard grad dist: ' . $e->getMessage()); }

} catch (\Exception $e) {
    error_log('dashboard_president DB error: ' . $e->getMessage());
}

$pendingAmt = (float)($paymentStats['pending'] ?? 0);
$overdueAmt = (float)($paymentStats['overdue'] ?? 0);
$paidAmt    = (float)($paymentStats['paid']    ?? 0);
$totalAmt   = $pendingAmt + $overdueAmt + $paidAmt;

$pageTitle = 'Dashboard – Presidente';
require_once __DIR__ . '/layout/header.php';
?>

<!-- ── Page header ─────────────────────────────────────────────────── -->
<div class="flex items-center justify-between mb-8">
    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Painel Administrativo</p>
        <h1 class="text-3xl font-bold text-slate-900 leading-none">Painel do Presidente</h1>
    </div>
    <span class="hidden sm:inline-flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-500 shadow-sm">
        <svg class="w-4 h-4 text-rs-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <?= date('d/m/Y') ?>
    </span>
</div>

<!-- ── KPI cards ───────────────────────────────────────────────────── -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

    <!-- Dojos -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Dojos</span>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#00AB4E22">
                <svg class="w-5 h-5" style="color:#00AB4E" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
        </div>
        <p class="text-4xl font-black text-slate-900"><?= $totalDojos ?></p>
        <a href="/fgkirs-admin/dojos" class="text-xs font-semibold" style="color:#00AB4E">Ver todos →</a>
    </div>

    <!-- Atletas -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Atletas ativos</span>
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-4xl font-black text-slate-900"><?= $totalAthletes ?></p>
        <a href="/fgkirs-admin/users" class="text-xs font-semibold text-blue-600">Ver usuários →</a>
    </div>

    <!-- Mensalidades pagas -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Mensalidades pagas</span>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-4xl font-black text-emerald-600">R$&nbsp;<?= number_format($paidAmt, 0, ',', '.') ?></p>
        <span class="text-xs text-slate-400">total arrecadado</span>
    </div>

    <!-- Em atraso -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col gap-3 <?= $overdueAmt > 0 ? 'ring-2 ring-rs-red/40' : '' ?>">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Em atraso</span>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#EE302F22">
                <svg class="w-5 h-5" style="color:#EE302F" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
        <p class="text-4xl font-black" style="color:#EE302F">R$&nbsp;<?= number_format($overdueAmt, 0, ',', '.') ?></p>
        <span class="text-xs text-slate-400">R$ <?= number_format($pendingAmt, 0, ',', '.') ?> pendentes</span>
    </div>

</div>

<!-- ── Charts row ──────────────────────────────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

    <!-- Estilos -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <h2 class="text-base font-bold text-slate-900 uppercase mb-5">Atletas por Estilo</h2>
        <?php if (!empty($styleDistribution)): ?>
        <div class="space-y-3">
            <?php
            $maxStyle = max(array_column($styleDistribution, 'count')) ?: 1;
            $styleColors = ['#00AB4E','#EE302F','#FFCB04','#3b82f6','#8b5cf6'];
            foreach ($styleDistribution as $i => $s):
                $pct = round(($s['count'] / $maxStyle) * 100);
                $color = $styleColors[$i % count($styleColors)];
            ?>
            <div>
                <div class="flex justify-between text-sm mb-1.5">
                    <span class="font-medium text-slate-700"><?= htmlspecialchars($s['name']) ?></span>
                    <span class="font-black text-slate-900"><?= $s['count'] ?></span>
                </div>
                <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500"
                         style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-slate-400 text-sm text-center py-8">Nenhum estilo cadastrado.</p>
        <?php endif; ?>
    </div>

    <!-- Faixas Shotokan -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <h2 class="text-base font-bold text-slate-900 uppercase mb-5">Faixas Shotokan Ativas</h2>
        <?php
        $gradActive = array_filter($gradDistribution, fn($g) => $g['count'] > 0);
        $maxGrad    = !empty($gradActive) ? max(array_column(array_values($gradActive), 'count')) : 1;
        ?>
        <?php if (!empty($gradActive)): ?>
        <div class="space-y-2.5">
            <?php foreach ($gradActive as $g):
                $pct = round(($g['count'] / $maxGrad) * 100);
                $bc  = $g['belt_color'] ?? '#ccc';
            ?>
            <div class="flex items-center gap-3">
                <div class="w-3.5 h-3.5 rounded-full shrink-0 border border-slate-300 shadow-sm"
                     style="background:<?= htmlspecialchars($bc) ?>"></div>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="font-medium text-slate-600 truncate"><?= htmlspecialchars($g['belt_name']) ?></span>
                        <span class="font-black text-slate-900 ml-2"><?= $g['count'] ?></span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full" style="width:<?= $pct ?>%;background:<?= htmlspecialchars($bc) ?>"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-slate-400 text-sm text-center py-8">Nenhum atleta graduado ainda.</p>
        <?php endif; ?>
    </div>

</div>

<!-- ── Bottom row ──────────────────────────────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Alertas de atraso -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <div class="flex items-center gap-2 mb-5">
            <svg class="w-5 h-5 shrink-0" style="color:#EE302F" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <h2 class="text-base font-bold text-slate-900 uppercase">Pagamentos em Atraso</h2>
        </div>

        <?php if (empty($overdueSenseis)): ?>
        <div class="flex flex-col items-center justify-center py-10 text-center">
            <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-slate-600">Tudo em dia!</p>
            <p class="text-xs text-slate-400 mt-1">Nenhum pagamento em atraso.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto -mx-1">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="text-left px-2 py-2 text-xs font-bold uppercase text-slate-400">Sensei</th>
                        <th class="text-left px-2 py-2 text-xs font-bold uppercase text-slate-400 hidden sm:table-cell">Dojo</th>
                        <th class="text-left px-2 py-2 text-xs font-bold uppercase text-slate-400">Vencimento</th>
                        <th class="text-right px-2 py-2 text-xs font-bold uppercase text-slate-400">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($overdueSenseis as $s): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-2 py-3 font-semibold text-slate-800"><?= htmlspecialchars($s['name']) ?></td>
                        <td class="px-2 py-3 text-slate-500 hidden sm:table-cell"><?= htmlspecialchars($s['dojo_name']) ?></td>
                        <td class="px-2 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold" style="background:#EE302F22;color:#EE302F">
                                <?= date('d/m/Y', strtotime($s['oldest_due'])) ?>
                            </span>
                        </td>
                        <td class="px-2 py-3 text-right font-black text-slate-900">
                            R$ <?= number_format($s['total_overdue'], 2, ',', '.') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Promoções recentes -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <div class="flex items-center gap-2 mb-5">
            <svg class="w-5 h-5 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
            <h2 class="text-base font-bold text-slate-900 uppercase">Promoções Recentes</h2>
        </div>

        <?php if (empty($recentGraduations)): ?>
        <p class="text-slate-400 text-sm text-center py-10">Nenhuma promoção registrada.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($recentGraduations as $p):
                $bc = $p['belt_color'] ?? '#6b7280';
            ?>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full border-2 border-slate-200 shrink-0"
                     style="background:<?= htmlspecialchars($bc) ?>"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($p['name']) ?></p>
                    <p class="text-xs text-slate-400 truncate">
                        <?= htmlspecialchars($p['belt_name']) ?> · por <?= htmlspecialchars($p['promoted_by']) ?>
                    </p>
                </div>
                <span class="text-xs text-slate-400 shrink-0"><?= date('d/m/Y', strtotime($p['promotion_date'])) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
