<?php
use Helpers\Auth;

$pageTitle = 'Dojos';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Gerenciar Dojos</h1>
    <?php if (Auth::isAdmin()): ?>
    <a href="/admin/dojos/create" class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
        + Novo Dojo
    </a>
    <?php endif; ?>
</div>

<!-- Dojos Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($dojos)): ?>
    <div class="col-span-full bg-white rounded-lg shadow p-8 text-center text-gray-500">
        Nenhum dojo encontrado.
    </div>
    <?php else: ?>
    <?php foreach ($dojos as $dojo): ?>
    <div class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden">
        <!-- Logo -->
        <div class="h-48 bg-slate-900 flex items-center justify-center p-4">
            <?php if (!empty($dojo['logo'])): ?>
            <img src="/uploads/dojos/<?= htmlspecialchars($dojo['logo']) ?>" 
                 alt="<?= htmlspecialchars($dojo['name']) ?>" 
                 class="max-h-full max-w-full object-contain">
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
                <p>📍 <?= htmlspecialchars($dojo['city']) ?><?= !empty($dojo['state']) ? ' - ' . htmlspecialchars($dojo['state']) : '' ?></p>
                <?php endif; ?>
                
                <?php if (!empty($dojo['sensei_name'])): ?>
                <p>🥋 Sensei: <?= htmlspecialchars($dojo['sensei_name']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-4 border-t">
                <a href="/admin/dojos/edit/<?= $dojo['id'] ?>" 
                   class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                    Editar
                </a>
                <?php if (Auth::isAdmin()): ?>
                <a href="/admin/dojos/delete/<?= $dojo['id'] ?>" 
                   data-confirm-delete 
                   class="flex-1 text-center bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                    Excluir
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
