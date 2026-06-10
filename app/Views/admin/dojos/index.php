<?php
use Helpers\Auth;

$pageTitle = 'Dojos';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Gerenciar Dojos</h1>
    <?php if (Auth::isAdmin()): ?>
        <a href="/fgkirs-admin/dojos/create"
            class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            + Novo Dojo
        </a>
    <?php endif; ?>
</div>

<!-- Dojos Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <?php if (empty($dojos)): ?>
        <div class="col-span-full bg-white rounded-lg shadow p-8 text-center text-gray-500">
            Nenhum dojo encontrado.
        </div>
    <?php else: ?>
        <?php foreach ($dojos as $dojo): ?>
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden">
                <!-- Logo -->
                <div class="h-48 flex items-center justify-center p-4">
                    <?php if (!empty($dojo['logo'])): ?>
                        <img src="/uploads/dojos/<?= htmlspecialchars($dojo['logo']) ?>"
                            alt="<?= htmlspecialchars($dojo['name']) ?>" class="max-h-full max-w-full object-contain">
                    <?php else: ?>
                        <div class="text-white text-6xl font-bold">
                            <?= strtoupper(substr($dojo['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="p-6">
                    <h3 class="text-xl font-bold text-slate-900 mb-2"><?= htmlspecialchars($dojo['name']) ?></h3>

                    <div class="space-y-1 text-sm text-gray-600 mb-4">
                        <?php if (!empty($dojo['city'])): ?>
                            <p>📍
                                <?= htmlspecialchars($dojo['city']) ?>            <?= !empty($dojo['state']) ? ' - ' . htmlspecialchars($dojo['state']) : '' ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($dojo['sensei_name'])): ?>
                            <p>🥋 Sensei: <?= htmlspecialchars($dojo['sensei_name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4 border-t justify-end">
                        <a href="/fgkirs-admin/dojos/edit/<?= $dojo['id'] ?>" title="Editar"
                            class="text-slate-600 hover:text-slate-900 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
                        </a>
                        <?php if (Auth::isAdmin()): ?>
                            <a href="/fgkirs-admin/dojos/delete/<?= $dojo['id'] ?>" data-confirm-delete title="Excluir"
                                class="text-red-500 hover:text-red-700 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-10 0h14"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>