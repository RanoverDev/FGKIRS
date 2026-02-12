<?php
use Helpers\Auth;

$pageTitle = 'Histórico de Graduações - ' . $student['name'];
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Student Header -->
<div
    class="bg-gradient-to-r from-rose-600 to-slate-900 text-white rounded border-2 border-slate-900 shadow-lg p-6 mb-6">
    <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
        <div class="flex-1">
            <h1 class="text-3xl font-bold">
                <?= htmlspecialchars($student['name']) ?>
            </h1>
            <p class="text-rose-100 mt-1">
                <?= htmlspecialchars($student['registration_number'] ?? 'Sem registro') ?> |
                <?= htmlspecialchars($student['style_name'] ?? 'Sem estilo') ?>
            </p>
        </div>
        <div class="text-center">
            <p class="text-sm text-rose-100 uppercase tracking-wide">Graduação Atual</p>
            <div class="flex items-center gap-3 mt-2">
                <div class="w-16 h-16 rounded-full border-4 border-white"
                    style="background-color: <?= htmlspecialchars($student['belt_color'] ?? '#cccccc') ?>"></div>
                <div class="text-left">
                    <p class="font-bold text-xl">
                        <?= htmlspecialchars($student['belt_name'] ?? 'N/A') ?>
                    </p>
                    <span class="inline-block px-2 py-1 bg-white text-slate-900 text-xs font-bold rounded uppercase">
                        <?= ucfirst($student['student_status'] ?? 'N/A') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actions -->
<?php if (Auth::isAdmin() || (Auth::isSensei() && $student['dojo_id'] === Auth::dojoId())): ?>
    <div class="mb-6">
        <a href="/admin/graduations/promote/<?= $student['id'] ?>"
            class="inline-block bg-rose-600 hover:bg-rose-700 text-white font-bold py-3 px-6 rounded uppercase transition border-2 border-slate-900 shadow-lg">
            🥋 Promover Aluno
        </a>
    </div>
<?php endif; ?>

<!-- Graduation Timeline -->
<div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
    <h2 class="text-2xl font-bold text-slate-900 mb-6">Histórico de Promoções</h2>

    <?php if (empty($history)): ?>
        <p class="text-gray-500 text-center py-8">Nenhuma promoção registrada ainda.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($history as $record): ?>
                <div class="border-l-4 border-rose-600 pl-4 py-2">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full border-2 border-slate-900"
                                style="background-color: <?= htmlspecialchars($record['belt_color']) ?>"></div>
                            <div>
                                <h3 class="font-bold text-lg text-slate-900">
                                    <?= htmlspecialchars($record['belt_name']) ?>
                                </h3>
                                <p class="text-sm text-gray-600">
                                    Promovido por:
                                    <?= htmlspecialchars($record['promoted_by_name']) ?>
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-slate-900">
                                <?= date('d/m/Y', strtotime($record['promotion_date'])) ?>
                            </p>
                            <?php if (!empty($record['exam_score'])): ?>
                                <p class="text-sm text-gray-600">Nota:
                                    <?= number_format($record['exam_score'], 1) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($record['notes'])): ?>
                        <p class="text-sm text-gray-600 mt-2 italic">
                            <?= htmlspecialchars($record['notes']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>