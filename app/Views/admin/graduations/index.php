<?php
use Helpers\Auth;

$pageTitle = 'Graduações e Faixas';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-3xl font-bold text-slate-900">Sistema de Graduações</h1>
    <p class="text-gray-600 mt-2">Gerenciamento de faixas por estilo marcial</p>
</div>

<!-- Styles Overview -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <?php foreach ($styles as $style): ?>
        <div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
            <h3 class="text-xl font-bold text-slate-900 mb-2">
                <?= htmlspecialchars($style['name']) ?>
            </h3>
            <p class="text-gray-600 text-sm mb-4">
                <?= htmlspecialchars($style['description'] ?? '') ?>
            </p>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">
                    <?= $style['graduation_count'] ?> graduações
                </span>
                <span class="text-xs bg-slate-900 text-white px-2 py-1 rounded uppercase">
                    <?= htmlspecialchars($style['origin_country'] ?? 'N/A') ?>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Graduations by Style -->
<?php
$graduationsByStyle = [];
foreach ($graduations as $grad) {
    $graduationsByStyle[$grad['style_name']][] = $grad;
}
?>

<?php foreach ($graduationsByStyle as $styleName => $styleGrads): ?>
    <div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6 mb-6">
        <h2 class="text-2xl font-bold text-slate-900 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                </path>
            </svg>
            <?= htmlspecialchars($styleName) ?>
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <?php foreach ($styleGrads as $grad): ?>
                <div class="border-2 border-gray-300 rounded p-3 hover:border-rose-600 transition">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-full border-2 border-slate-900"
                            style="background-color: <?= htmlspecialchars($grad['belt_color']) ?>"></div>
                        <span class="font-bold text-slate-900">
                            <?= $grad['order_rank'] + 1 ?>º
                        </span>
                    </div>
                    <h4 class="font-bold text-sm">
                        <?= htmlspecialchars($grad['belt_name']) ?>
                    </h4>
                    <?php if (!empty($grad['minimum_time_months'])): ?>
                        <p class="text-xs text-gray-500 mt-1">Min:
                            <?= $grad['minimum_time_months'] ?> meses
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>