<?php
$isEdit    = isset($post) && $post;
$images    = $images ?? [];
$pageTitle = $isEdit ? 'Editar Postagem' : 'Nova Postagem';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900"><?= $pageTitle ?></h1>
</div>

<!-- ── Formulário principal ── -->
<div class="bg-white rounded-lg shadow p-6 sm:p-8 max-w-2xl mb-6">
    <form action="<?= $isEdit ? "/fgkirs-admin/posts/update/{$post['id']}" : '/fgkirs-admin/posts/store' ?>"
          method="POST"
          enctype="multipart/form-data">

        <div class="mb-4">
            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Título *</label>
            <input type="text" id="title" name="title" required
                   value="<?= htmlspecialchars($post['title'] ?? '') ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div class="mb-4">
            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
            <select id="type" name="type" required onchange="toggleEventFields()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
                <option value="news"  <?= ($post['type'] ?? '') === 'news'  ? 'selected' : '' ?>>Notícia</option>
                <option value="event" <?= ($post['type'] ?? '') === 'event' ? 'selected' : '' ?>>Evento</option>
            </select>
        </div>

        <div id="eventFields" class="<?= ($post['type'] ?? 'news') === 'event' ? '' : 'hidden' ?> space-y-4 mb-4">
            <div>
                <label for="event_date" class="block text-sm font-medium text-gray-700 mb-2">Data e Hora do Evento</label>
                <input type="datetime-local" id="event_date" name="event_date"
                       value="<?= $isEdit && !empty($post['event_date']) ? date('Y-m-d\TH:i', strtotime($post['event_date'])) : '' ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            </div>
            <div>
                <label for="event_location" class="block text-sm font-medium text-gray-700 mb-2">Local do Evento</label>
                <input type="text" id="event_location" name="event_location"
                       value="<?= htmlspecialchars($post['event_location'] ?? '') ?>"
                       placeholder="Ex: Ginásio Municipal de Porto Alegre"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            </div>
        </div>

        <div class="mb-4">
            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Conteúdo *</label>
            <textarea id="content" name="content" required rows="10"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
        </div>

        <div class="mb-4">
            <label for="published_at" class="block text-sm font-medium text-gray-700 mb-2">Data de Publicação</label>
            <input type="datetime-local" id="published_at" name="published_at"
                   value="<?= !empty($post['published_at']) ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : date('Y-m-d\TH:i') ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div class="mb-6">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select id="status" name="status"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
                <option value="published" <?= ($post['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Publicado</option>
                <option value="draft"     <?= ($post['status'] ?? '') === 'draft'               ? 'selected' : '' ?>>Rascunho</option>
            </select>
        </div>

        <?php if (!$isEdit): ?>
            <!-- Imagem inicial apenas no cadastro -->
            <div class="mb-6">
                <label for="featured_image" class="block text-sm font-medium text-gray-700 mb-2">Imagem Destacada</label>
                <input type="file" id="featured_image" name="featured_image"
                       accept=".jpg,.jpeg,image/jpeg" onchange="previewImage(this)"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Apenas JPG. Será redimensionada para 1200px, qualidade 55%.</p>
                <div id="imagePreview" class="mt-3 hidden">
                    <img src="" alt="Pré-visualização" class="w-32 h-20 object-cover rounded">
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit"
                    class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
                <?= $isEdit ? 'Salvar Alterações' : 'Publicar' ?>
            </button>
            <a href="/fgkirs-admin/posts"
               class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php if ($isEdit): ?>
<!-- ── Galeria de Imagens ── -->
<div class="bg-white rounded-lg shadow p-6 sm:p-8 max-w-2xl">
    <h2 class="text-lg font-bold text-slate-900 mb-4">Imagens</h2>

    <?php if (empty($images)): ?>
        <p class="text-sm text-gray-500 mb-4">Nenhuma imagem adicionada.</p>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
            <?php foreach ($images as $img): ?>
                <div class="relative group rounded-lg overflow-hidden border-2
                    <?= $img['is_featured'] ? 'border-red-600' : 'border-gray-200' ?>">
                    <img src="/uploads/posts/<?= htmlspecialchars($img['filename']) ?>"
                         alt="" class="w-full h-32 object-cover">

                    <?php if ($img['is_featured']): ?>
                        <span class="absolute top-1 left-1 bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded">
                            Destaque
                        </span>
                    <?php endif; ?>

                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex flex-col items-center justify-center gap-2 p-2">
                        <?php if (!$img['is_featured']): ?>
                            <a href="/fgkirs-admin/posts/set-featured/<?= $img['id'] ?>"
                               class="w-full text-center bg-red-600 hover:bg-red-700 text-white text-xs font-semibold py-1.5 px-2 rounded transition">
                                Definir Destaque
                            </a>
                        <?php endif; ?>
                        <a href="/fgkirs-admin/posts/remove-image/<?= $img['id'] ?>"
                           data-confirm-delete
                           class="w-full text-center bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold py-1.5 px-2 rounded transition">
                            Excluir
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Upload nova imagem -->
    <form action="/fgkirs-admin/posts/add-image/<?= $post['id'] ?>"
          method="POST" enctype="multipart/form-data">
        <label class="block text-sm font-medium text-gray-700 mb-2">Adicionar Imagem</label>
        <div class="flex gap-3 items-start">
            <input type="file" name="image" accept=".jpg,.jpeg,image/jpeg" required
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent text-sm">
            <button type="submit"
                    class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2 px-5 rounded-lg transition text-sm whitespace-nowrap">
                Enviar
            </button>
        </div>
        <p class="text-xs text-gray-500 mt-1">Apenas JPG. A primeira imagem adicionada será automaticamente o destaque.</p>
    </form>
</div>
<?php endif; ?>

<script>
    function toggleEventFields() {
        const type = document.getElementById('type').value;
        document.getElementById('eventFields').classList.toggle('hidden', type !== 'event');
    }

    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.querySelector('img').src = e.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
