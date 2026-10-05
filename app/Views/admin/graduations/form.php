<?php
$isEdit    = isset($graduation) && $graduation;
$pageTitle = $isEdit ? 'Editar Faixa' : 'Nova Faixa';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900"><?= $pageTitle ?></h1>
</div>

<div class="bg-white rounded-lg shadow p-6 sm:p-8 max-w-2xl">
    <form action="<?= $isEdit ? "/fgkirs-admin/graduations/update/{$graduation['id']}" : '/fgkirs-admin/graduations/store' ?>"
          method="POST">

        <div class="mb-4">
            <label for="style_id" class="block text-sm font-medium text-gray-700 mb-2">Estilo *</label>
            <select id="style_id" name="style_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
                <option value="">Selecione um estilo...</option>
                <?php foreach ($styles as $style): ?>
                    <option value="<?= $style['id'] ?>"
                        <?= ($graduation['style_id'] ?? '') == $style['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($style['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label for="belt_name" class="block text-sm font-medium text-gray-700 mb-2">Nome da Faixa *</label>
            <input type="text" id="belt_name" name="belt_name" required
                   value="<?= htmlspecialchars($graduation['belt_name'] ?? '') ?>"
                   placeholder="Ex: Faixa Branca, Faixa Amarela..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div class="mb-4">
            <label for="belt_color" class="block text-sm font-medium text-gray-700 mb-2">Cor *</label>
            <div class="flex items-center gap-3">
                <input type="color" id="belt_color" name="belt_color"
                       value="<?= htmlspecialchars($graduation['belt_color'] ?? '#ffffff') ?>"
                       class="h-10 w-16 border border-gray-300 rounded-lg cursor-pointer p-0.5">
                <span id="colorLabel" class="text-sm text-gray-600">
                    <?= htmlspecialchars($graduation['belt_color'] ?? '#ffffff') ?>
                </span>
            </div>
        </div>

        <div class="mb-4">
            <label for="level" class="block text-sm font-medium text-gray-700 mb-2">Nível comum (1 a 20)</label>
            <input type="number" id="level" name="level" min="1" max="20"
                   value="<?= htmlspecialchars((string) ($graduation['level'] ?? '')) ?>"
                   class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            <p class="text-xs text-gray-500 mt-1.5">
                Serve para comparar faixas de estilos diferentes nas categorias de disputa. Faixas
                equivalentes em Shotokan, Goju-ryu e Wado-ryu devem ter o mesmo nível
                (ex.: 1 = branca, 10 = marrom 1º kyu, 11 = preta 1º dan).
            </p>
        </div>

        <div class="mb-6">
            <label for="requirements" class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
            <textarea id="requirements" name="requirements" rows="4"
                      placeholder="Requisitos, características ou observações da faixa..."
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent"><?= htmlspecialchars($graduation['requirements'] ?? '') ?></textarea>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit"
                    class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
                <?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
            </button>
            <a href="/fgkirs-admin/graduations"
               class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
    const colorInput = document.getElementById('belt_color');
    const colorLabel = document.getElementById('colorLabel');
    colorInput.addEventListener('input', () => { colorLabel.textContent = colorInput.value; });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
