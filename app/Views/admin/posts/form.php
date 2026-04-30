<?php
use Helpers\Auth;

$isEdit = isset($post) && $post;
$pageTitle = $isEdit ? 'Editar Post' : 'Nova Postagem';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-3xl font-extrabold text-slate-900 uppercase">
        <?= $pageTitle ?>
    </h1>
</div>

<div class="bg-white rounded border-2 border-slate-900 shadow-lg p-6">
    <form action="<?= $isEdit ? "/fgkirs-admin/posts/update/{$post['id']}" : '/fgkirs-admin/posts/store' ?>"
        method="POST"
        enctype="multipart/form-data">

        <!-- Title -->
        <div class="mb-4">
            <label for="title" class="block text-sm font-bold text-slate-900 mb-2">Título *</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required
                class="w-full px-4 py-2 border-2 border-gray-300 rounded focus:border-rose-600 focus:outline-none">
        </div>

        <!-- Type -->
        <div class="mb-4">
            <label for="type" class="block text-sm font-bold text-slate-900 mb-2">Tipo *</label>
            <select id="type" name="type" required onchange="toggleEventFields()"
                class="w-full px-4 py-2 border-2 border-gray-300 rounded focus:border-rose-600 focus:outline-none">
                <option value="news" <?= ($post['type'] ?? '') === 'news' ? 'selected' : '' ?>>📰 Notícia</option>
                <option value="event" <?= ($post['type'] ?? '') === 'event' ? 'selected' : '' ?>>📅 Evento</option>
            </select>
        </div>

        <!-- Event Fields -->
        <div id="eventFields" class="<?= ($post['type'] ?? 'news') === 'event' ? '' : 'hidden' ?> space-y-4 mb-4">
            <div>
                <label for="event_date" class="block text-sm font-bold text-slate-900 mb-2">Data e Hora do
                    Evento</label>
                <input type="datetime-local" id="event_date" name="event_date"
                    value="<?= $isEdit && $post['event_date'] ? date('Y-m-d\TH:i', strtotime($post['event_date'])) : '' ?>"
                    class="w-full px-4 py-2 border-2 border-gray-300 rounded focus:border-rose-600 focus:outline-none">
            </div>
            <div>
                <label for="event_location" class="block text-sm font-bold text-slate-900 mb-2">Local do Evento</label>
                <input type="text" id="event_location" name="event_location"
                    value="<?= htmlspecialchars($post['event_location'] ?? '') ?>"
                    placeholder="Ex: Ginásio Municipal de Porto Alegre"
                    class="w-full px-4 py-2 border-2 border-gray-300 rounded focus:border-rose-600 focus:outline-none">
            </div>
        </div>

        <!-- Content -->
        <div class="mb-4">
            <label for="content" class="block text-sm font-bold text-slate-900 mb-2">Conteúdo *</label>
            <textarea id="content" name="content" required rows="10"
                class="w-full px-4 py-2 border-2 border-gray-300 rounded focus:border-rose-600 focus:outline-none"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
        </div>

        <!-- Featured Image -->
        <div class="mb-6">
            <label for="featured_image" class="block text-sm font-bold text-slate-900 mb-2">Imagem Destacada</label>
            <?php if ($isEdit && $post['featured_image']): ?>
                <div class="mb-3">
                    <img src="/uploads/posts/<?= htmlspecialchars($post['featured_image']) ?>" alt="Current"
                        class="w-48 h-32 object-cover rounded border-2 border-slate-900">
                </div>
            <?php endif; ?>
            <input type="file" id="featured_image" name="featured_image" accept="image/*" onchange="previewImage(this)"
                class="w-full px-4 py-2 border-2 border-gray-300 rounded focus:border-rose-600 focus:outline-none">
            <p class="text-xs text-gray-500 mt-1">Será automaticamente convertida para JPG, redimensionada e otimizada
            </p>
            <div id="imagePreview" class="mt-3 hidden">
                <img src="" alt="Preview" class="w-48 h-32 object-cover rounded border-2 border-slate-900">
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit"
                class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-3 px-6 rounded uppercase transition border-2 border-slate-900 shadow-lg">
                <?= $isEdit ? '💾 Atualizar' : '✓ Publicar' ?>
            </button>
            <a href="/fgkirs-admin/posts"
                class="text-center bg-gray-200 hover:bg-gray-300 text-slate-900 font-bold py-3 px-6 rounded uppercase transition border-2 border-slate-900">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
    function toggleEventFields() {
        const type = document.getElementById('type').value;
        const eventFields = document.getElementById('eventFields');
        eventFields.classList.toggle('hidden', type !== 'event');
    }

    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        const img = preview.querySelector('img');

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>