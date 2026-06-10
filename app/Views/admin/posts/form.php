<?php
$isEdit = isset($post) && $post;
$images ??= [];
$pageTitle = $isEdit ? 'Editar Postagem' : 'Nova Postagem';
require_once __DIR__ . '/../layout/header.php';

$currentType = $post['type'] ?? 'news';
?>

<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900"><?= $pageTitle ?></h1>
</div>

<!-- ── Formulário principal ── -->
<div class="bg-white rounded-lg shadow p-6 sm:p-8 max-w-2xl mb-6">
    <form action="<?= $isEdit ? "/fgkirs-admin/posts/update/{$post['id']}" : '/fgkirs-admin/posts/store' ?>"
        method="POST" enctype="multipart/form-data">

        <div class="mb-4">
            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Título *</label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($post['title'] ?? '') ?>"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div class="mb-4">
            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
            <select id="type" name="type" required onchange="toggleTypeFields()"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
                <option value="news" <?= $currentType === 'news' ? 'selected' : '' ?>>Notícia</option>
                <option value="event" <?= $currentType === 'event' ? 'selected' : '' ?>>Evento</option>
                <option value="video" <?= $currentType === 'video' ? 'selected' : '' ?>>Vídeo</option>
                <option value="live" <?= $currentType === 'live' ? 'selected' : '' ?>>Ao Vivo</option>
            </select>
        </div>

        <!-- Campos para evento -->
        <div id="eventFields" class="<?= $currentType === 'event' ? '' : 'hidden' ?> space-y-4 mb-4">
            <div>
                <label for="event_date" class="block text-sm font-medium text-gray-700 mb-2">Data e Hora do
                    Evento</label>
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

        <!-- Campo URL do YouTube (todos os tipos, obrigatório apenas para video/live) -->
        <div id="videoFields" class="mb-4">
            <label for="video_url" class="block text-sm font-medium text-gray-700 mb-2">
                URL do YouTube <span class="text-gray-400 font-normal">(opcional)</span>
                <span id="liveBadge"
                    class="<?= $currentType === 'live' ? '' : 'hidden' ?> ml-2 bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded animate-pulse">
                    AO VIVO
                </span>
            </label>
            <input type="url" id="video_url" name="video_url" value="<?= htmlspecialchars($post['video_url'] ?? '') ?>"
                placeholder="https://www.youtube.com/watch?v=..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            <p class="text-xs text-gray-500 mt-1">Cole a URL completa do YouTube (youtube.com/watch?v=... ou
                youtu.be/...).</p>
            <!-- Pré-visualização do vídeo -->
            <div id="videoPreview" class="mt-3 hidden">
                <iframe id="videoIframe" class="w-full aspect-video rounded-lg border border-gray-200" frameborder="0"
                    allowfullscreen></iframe>
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
                <option value="published" <?= ($post['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>
                    Publicado</option>
                <option value="draft" <?= ($post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
            </select>
        </div>

        <?php if (!$isEdit): ?>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Imagens</label>
                <input type="file" name="images[]"
                    accept=".jpg,.jpeg,.png,.webp,.heic,image/jpeg,image/png,image/webp"
                    multiple
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP ou HEIC. Selecione várias de uma vez. A primeira será a imagem de destaque. Convertidas para WebP automaticamente.</p>
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
    <!-- ── Imagens ── -->
    <div class="bg-white rounded-lg shadow p-6 sm:p-8 max-w-2xl">
        <h2 class="text-lg font-bold text-slate-900 mb-4">Imagens</h2>

        <?php if (empty($images)): ?>
            <p class="text-sm text-gray-500 mb-4">Nenhuma imagem adicionada.</p>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
                <?php foreach ($images as $img): ?>
                    <div class="relative group rounded-lg overflow-hidden border-2
                    <?= $img['is_featured'] ? 'border-red-600' : 'border-gray-200' ?>">
                        <img src="/uploads/posts/<?= htmlspecialchars($img['filename']) ?>" alt="" class="w-full h-32 object-cover">

                        <?php if ($img['is_featured']): ?>
                            <span class="absolute top-1 left-1 bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded">
                                Destaque
                            </span>
                        <?php endif; ?>

                        <div
                            class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex flex-col items-center justify-center gap-2 p-2">
                            <?php if (!$img['is_featured']): ?>
                                <a href="/fgkirs-admin/posts/set-featured/<?= $img['id'] ?>"
                                    class="w-full text-center bg-red-600 hover:bg-red-700 text-white text-xs font-semibold py-1.5 px-2 rounded transition">
                                    Definir Destaque
                                </a>
                            <?php endif; ?>
                            <a href="/fgkirs-admin/posts/remove-image/<?= $img['id'] ?>" data-confirm-delete
                                class="w-full text-center bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold py-1.5 px-2 rounded transition">
                                Excluir
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="/fgkirs-admin/posts/add-image/<?= $post['id'] ?>" method="POST" enctype="multipart/form-data">
            <label class="block text-sm font-medium text-gray-700 mb-2">Adicionar Imagens</label>
            <div class="flex gap-3 items-start">
                <input type="file" name="images[]"
                    accept=".jpg,.jpeg,.png,.webp,.heic,image/jpeg,image/png,image/webp"
                    multiple required
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent text-sm">
                <button type="submit"
                    class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2 px-5 rounded-lg transition text-sm whitespace-nowrap">
                    Enviar
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP ou HEIC. Selecione várias de uma vez. Convertidas para WebP automaticamente.</p>
        </form>
    </div>
<?php endif; ?>

<script>
    function extractYouTubeId(url) {
        const m = url.match(/(?:youtube\.com\/(?:watch\?.*v=|live\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
        return m ? m[1] : null;
    }

    function toggleTypeFields() {
        const type = document.getElementById('type').value;
        document.getElementById('eventFields').classList.toggle('hidden', type !== 'event');
        document.getElementById('liveBadge').classList.toggle('hidden', type !== 'live');
        updateVideoPreview();
    }

    function updateVideoPreview() {
        const urlInput = document.getElementById('video_url');
        const preview = document.getElementById('videoPreview');
        const iframe = document.getElementById('videoIframe');
        const id = extractYouTubeId(urlInput.value || '');
        if (id) {
            iframe.src = 'https://www.youtube.com/embed/' + id;
            preview.classList.remove('hidden');
        } else {
            iframe.src = '';
            preview.classList.add('hidden');
        }
    }

    document.getElementById('video_url')?.addEventListener('input', updateVideoPreview);

    // Initialize preview if editing and already has a video URL
    updateVideoPreview();
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>