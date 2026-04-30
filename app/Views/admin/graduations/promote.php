<?php
use Helpers\Auth;

$pageTitle = 'Promover Aluno';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-3xl font-bold text-slate-900">Promover Aluno</h1>
    <p class="text-gray-600 mt-2">
        <?= htmlspecialchars($student['name']) ?>
    </p>
</div>

<!-- Current Status Card -->
<div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6 mb-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Graduação Atual</h2>
    <div class="flex items-center gap-4">
        <div class="w-20 h-20 rounded-full border-4 border-slate-900"
            style="background-color: <?= htmlspecialchars($student['belt_color'] ?? '#cccccc') ?>"></div>
        <div>
            <h3 class="text-2xl font-bold text-slate-900">
                <?= htmlspecialchars($student['current_belt']) ?>
            </h3>
            <p class="text-gray-600">
                <?= htmlspecialchars($student['style_name']) ?>
            </p>
        </div>
    </div>
</div>

<!-- Promotion Form -->
<div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
    <h2 class="text-xl font-bold text-slate-900 mb-6">Nova Graduação</h2>

    <?php if (empty($availableGraduations)): ?>
        <div class="bg-yellow-50 border-2 border-yellow-600 text-yellow-800 rounded p-4">
            <p class="font-bold">⚠️ Atenção</p>
            <p>Este aluno já atingiu a graduação máxima disponível neste estilo.</p>
        </div>
    <?php else: ?>
        <form action="/fgkirs-admin/graduations/promote" method="POST">
            <input type="hidden" name="user_id" value="<?= $student['id'] ?>">

            <!-- Graduation Selection -->
            <div class="mb-4">
                <label class="block text-sm font-bold text-slate-900 mb-2">Selecione a Nova Faixa *</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <?php foreach ($availableGraduations as $grad): ?>
                        <label class="border-2 border-gray-300 rounded p-4 cursor-pointer hover:border-rose-600 transition">
                            <input type="radio" name="graduation_id" value="<?= $grad['id'] ?>" required class="mr-2">
                            <div class="inline-flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full border-2 border-slate-900"
                                    style="background-color: <?= htmlspecialchars($grad['belt_color']) ?>"></div>
                                <div>
                                    <span class="font-bold">
                                        <?= htmlspecialchars($grad['belt_name']) ?>
                                    </span>
                                    <?php if (!empty($grad['minimum_time_months'])): ?>
                                        <span class="text-xs text-gray-500 block">Min:
                                            <?= $grad['minimum_time_months'] ?> meses
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Exam Score -->
            <div class="mb-4">
                <label for="exam_score" class="block text-sm font-bold text-slate-900 mb-2">Nota do Exame (opcional)</label>
                <input type="number" id="exam_score" name="exam_score" step="0.1" min="0" max="10"
                    class="w-full px-4 py-2 border-2 border-gray-300 rounded focus:border-rose-600 focus:outline-none">
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <label for="notes" class="block text-sm font-bold text-slate-900 mb-2">Observações (opcional)</label>
                <textarea id="notes" name="notes" rows="3"
                    class="w-full px-4 py-2 border-2 border-gray-300 rounded focus:border-rose-600 focus:outline-none"
                    placeholder="Ex: Excelente desempenho técnico..."></textarea>
            </div>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row gap-3">
                <button type="submit"
                    class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-3 px-6 rounded uppercase transition border-2 border-slate-900 shadow-lg">
                    ✓ Confirmar Promoção
                </button>
                <a href="/fgkirs-admin/graduations/history/<?= $student['id'] ?>"
                    class="text-center bg-gray-200 hover:bg-gray-300 text-slate-900 font-bold py-3 px-6 rounded uppercase transition border-2 border-slate-900">
                    Cancelar
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>