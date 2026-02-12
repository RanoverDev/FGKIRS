<?php
use Helpers\Auth;
use Core\Database;

// Redirect if not admin
if (!Auth::isAdmin()) {
    header('Location: /admin/dashboard');
    exit;
}

$db = Database::getInstance();

// KPI: Total Dojos
$totalDojos = $db->query("SELECT COUNT(*) as count FROM dojos")->fetch()['count'];

// KPI: Total Active Athletes
$totalAthletes = $db->query("SELECT COUNT(*) as count FROM student_profiles WHERE status = 'active'")->fetch()['count'];

// KPI: Payment Balance
$paymentStats = $db->query("
    SELECT 
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END) as overdue,
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid
    FROM payments
")->fetch();

// Distribution by Style
$styleDistribution = $db->query("
    SELECT mas.name, COUNT(sp.id) as count
    FROM martial_arts_styles mas
    LEFT JOIN student_profiles sp ON mas.id = sp.style_id AND sp.status = 'active'
    GROUP BY mas.id
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Overdue Senseis
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

// Recent Activities
$recentGraduations = $db->query("
    SELECT u.name, g.belt_name, gh.promotion_date,
           sensei.name as promoted_by
    FROM graduation_history gh
    JOIN student_profiles sp ON gh.student_profile_id = sp.id
    JOIN users u ON sp.user_id = u.id
    JOIN graduations g ON gh.graduation_id = g.id
    JOIN users sensei ON gh.promoted_by_sensei_id = sensei.id
    ORDER BY gh.promotion_date DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Graduation Distribution
$gradDistribution = $db->query("
    SELECT g.belt_name, COUNT(sp.id) as count
    FROM graduations g
    LEFT JOIN student_profiles sp ON g.id = sp.current_graduation_id AND sp.status = 'active'
    WHERE g.style_id = 1
    GROUP BY g.id
    ORDER BY g.order_rank
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Dashboard - Presidente';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-slate-900 uppercase tracking-tight">Painel do Presidente</h1>
    <p class="text-gray-600 mt-1">Análise e métricas em tempo real</p>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Total Dojos -->
    <div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-bold text-gray-600 uppercase tracking-wide">Total de Dojos</p>
                <p class="text-5xl font-black text-slate-900 mt-2">
                    <?= $totalDojos ?>
                </p>
            </div>
            <div class="w-14 h-14 bg-slate-900 rounded flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                    </path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Athletes -->
    <div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm font-bold text-gray-600 uppercase tracking-wide">Atletas Ativos</p>
                <p class="text-5xl font-black text-slate-900 mt-2">
                    <?= $totalAthletes ?>
                </p>
            </div>
            <div class="w-14 h-14 bg-rose-600 rounded flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Payment Balance -->
    <div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
        <div>
            <p class="text-sm font-bold text-gray-600 uppercase tracking-wide">Mensalidades</p>
            <div class="mt-2 space-y-1">
                <div class="flex justify-between">
                    <span class="text-sm font-bold text-yellow-600">Pendente:</span>
                    <span class="text-sm font-black">R$
                        <?= number_format($paymentStats['pending'] ?? 0, 2, ',', '.') ?>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-bold text-red-600">Atrasado:</span>
                    <span class="text-sm font-black">R$
                        <?= number_format($paymentStats['overdue'] ?? 0, 2, ',', '.') ?>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm font-bold text-green-600">Pago:</span>
                    <span class="text-sm font-black">R$
                        <?= number_format($paymentStats['paid'] ?? 0, 2, ',', '.') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Style Distribution Chart -->
    <div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4 uppercase">Distribuição por Estilo</h2>
        <div class="space-y-3">
            <?php
            $maxCount = max(array_column($styleDistribution, 'count'));
            foreach ($styleDistribution as $style):
                $percentage = $maxCount > 0 ? ($style['count'] / $maxCount) * 100 : 0;
                ?>
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-bold">
                            <?= htmlspecialchars($style['name']) ?>
                        </span>
                        <span class="text-sm font-bold">
                            <?= $style['count'] ?>
                        </span>
                    </div>
                    <div class="h-6 bg-gray-200 rounded border-2 border-slate-900">
                        <div class="h-full bg-rose-600 rounded" style="width: <?= $percentage ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Graduation Distribution -->
    <div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-4 uppercase">Faixas Shotokan (Ativas)</h2>
        <div class="space-y-2">
            <?php foreach ($gradDistribution as $grad): ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 flex-1">
                        <div class="w-4 h-4 rounded-full border border-slate-900"
                            style="background-color: <?= htmlspecialchars($grad['belt_color'] ?? '#ccc') ?>"></div>
                        <span class="text-sm font-bold truncate">
                            <?= htmlspecialchars($grad['belt_name']) ?>
                        </span>
                    </div>
                    <span class="text-sm font-black ml-2">
                        <?= $grad['count'] ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Alerts Table -->
<?php if (!empty($overdueSenseis)): ?>
    <div class="bg-white rounded border-2 border-rose-600 shadow-lg p-6 mb-8">
        <h2 class="text-xl font-bold text-rose-600 mb-4 uppercase flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                </path>
            </svg>
            Alertas: Senseis com Pagamentos Atrasados
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="px-4 py-2 text-left font-bold uppercase">Sensei</th>
                        <th class="px-4 py-2 text-left font-bold uppercase">Dojo</th>
                        <th class="px-4 py-2 text-left font-bold uppercase">Vencimento</th>
                        <th class="px-4 py-2 text-right font-bold uppercase">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overdueSenseis as $sensei): ?>
                        <tr class="border-b border-gray-200">
                            <td class="px-4 py-3 font-bold">
                                <?= htmlspecialchars($sensei['name']) ?>
                            </td>
                            <td class="px-4 py-3">
                                <?= htmlspecialchars($sensei['dojo_name']) ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-red-600 font-bold">
                                    <?= date('d/m/Y', strtotime($sensei['oldest_due'])) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-black">
                                R$
                                <?= number_format($sensei['total_overdue'], 2, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Recent Activities -->
<div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4 uppercase">Promoções Recentes</h2>
    <?php if (empty($recentGraduations)): ?>
        <p class="text-gray-500 text-center py-4">Nenhuma promoção registrada.</p>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($recentGraduations as $promo): ?>
                <div class="flex items-center justify-between border-l-4 border-rose-600 pl-3 py-2">
                    <div>
                        <p class="font-bold text-slate-900">
                            <?= htmlspecialchars($promo['name']) ?>
                        </p>
                        <p class="text-sm text-gray-600">
                            Promovido para <span class="font-bold">
                                <?= htmlspecialchars($promo['belt_name']) ?>
                            </span>
                            por
                            <?= htmlspecialchars($promo['promoted_by']) ?>
                        </p>
                    </div>
                    <span class="text-sm text-gray-500">
                        <?= date('d/m/Y', strtotime($promo['promotion_date'])) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>