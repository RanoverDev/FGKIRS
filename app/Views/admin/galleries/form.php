<?php
$isEdit = isset($gallery) && $gallery;
$images = $images ?? [];
$pageTitle = $isEdit ? 'Editar Galeria' : 'Nova Galeria';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6 flex items-center gap-3">
    <a href="/fgkirs-admin/galleries" class="text-slate-500 hover:text-slate-700 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
    </a>
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900"><?= $pageTitle ?></h1>
</div>

<!-- Dados da galeria -->
<div class="bg-white rounded-lg shadow p-6 sm:p-8 max-w-2xl mb-8">
    <form action="<?= $isEdit ? "/fgkirs-admin/galleries/update/{$gallery['id']}" : '/fgkirs-admin/galleries/store' ?>"
        method="POST">

        <div class="mb-4">
            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Título do Evento *</label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($gallery['title'] ?? '') ?>"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div class="mb-4">
            <label for="event_date" class="block text-sm font-medium text-gray-700 mb-2">Data do Evento *</label>
            <input type="date" id="event_date" name="event_date" required
                value="<?= $isEdit ? htmlspecialchars($gallery['event_date']) : date('Y-m-d') ?>"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
            <textarea id="description" name="description" rows="4"
                placeholder="Descreva o evento, competição ou apresentação..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent"><?= htmlspecialchars($gallery['description'] ?? '') ?></textarea>
        </div>

        <div class="mb-6">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status de Publicação</label>
            <select id="status" name="status"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent bg-white">
                <option value="published" <?= ($gallery['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>
                    Publicado</option>
                <option value="draft" <?= ($gallery['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
            </select>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit" id="dataSubmitBtn"
                onclick="this.disabled=true; this.textContent='Salvando...'; this.closest('form').submit();"
                class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition disabled:opacity-60">
                <?= $isEdit ? 'Salvar Alterações' : 'Criar Galeria' ?>
            </button>
            <a href="/fgkirs-admin/galleries"
                class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php if ($isEdit): ?>
    <!-- Upload de imagens em ZIP -->
    <div class="max-w-4xl mb-6">
        <div class="bg-white rounded-lg shadow p-6 sm:p-8">
            <h2 class="text-lg font-bold text-slate-900 mb-1">Adicionar Imagens</h2>
            <p class="text-sm text-gray-500 mb-6">
                Compacte as fotos do evento em um arquivo <strong>.zip</strong> e faça o upload.
                Formatos aceitos dentro do ZIP: JPG, PNG, WebP. As imagens serão redimensionadas e otimizadas
                automaticamente.
            </p>

            <div id="dropZone"
                class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center bg-slate-50 hover:border-red-400 hover:bg-red-50 transition-colors cursor-pointer"
                onclick="document.getElementById('zipInput').click()">
                <svg class="w-12 h-12 text-slate-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="text-sm font-semibold text-slate-600 mb-1" id="dropLabel">Clique ou arraste o arquivo ZIP aqui</p>
                <?php
                    $postMax   = ini_get('post_max_size');
                    $uploadMax = ini_get('upload_max_filesize');
                ?>
                <p class="text-xs text-slate-400">Limite do servidor: <strong><?= htmlspecialchars($postMax) ?></strong> (post) · <strong><?= htmlspecialchars($uploadMax) ?></strong> (upload)</p>
                <input type="file" id="zipInput" accept=".zip" class="hidden">
            </div>

            <!-- Progresso -->
            <div id="progressWrap" class="hidden mt-4 space-y-2">
                <div class="flex justify-between text-xs text-slate-500 font-medium">
                    <span id="progressLabel">Enviando ZIP...</span>
                    <span id="progressPct">0%</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2.5">
                    <div id="progressFill" class="bg-red-600 h-2.5 rounded-full transition-all duration-300" style="width:0%"></div>
                </div>
            </div>
            <div id="uploadResult" class="hidden mt-3 p-3 rounded-lg text-sm font-medium"></div>

            <div class="mt-4 flex justify-end">
                <button id="submitBtn" onclick="startUpload()"
                    class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2 px-6 rounded-lg transition disabled:opacity-50">
                    Enviar e Processar
                </button>
            </div>
        </div>
    </div>

    <!-- Grade de imagens -->
    <div class="max-w-6xl mb-8">
        <div class="bg-white rounded-lg shadow p-6 sm:p-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-slate-900">
                    Imagens da Galeria
                    <span class="ml-2 text-sm font-normal text-slate-500"><?= count($images) ?> foto(s)</span>
                </h2>
            </div>

            <?php if (empty($images)): ?>
                <div class="text-center py-12 bg-slate-50 rounded-lg border border-slate-100">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="text-sm text-slate-400">Nenhuma imagem ainda. Faça upload de um ZIP acima.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    <?php foreach ($images as $img): ?>
                        <div class="relative group rounded-lg overflow-hidden aspect-square
                                <?= $img['is_cover'] ? 'ring-2 ring-red-600' : 'bg-slate-100' ?>">

                            <img src="/uploads/galleries/<?= htmlspecialchars($img['filename']) ?>" alt=""
                                class="w-full h-full object-cover" loading="lazy">

                            <?php if ($img['is_cover']): ?>
                                <span
                                    class="absolute top-1 left-1 bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow">
                                    Capa
                                </span>
                            <?php endif; ?>

                            <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition
                                    flex flex-col items-center justify-center gap-2 p-2">
                                <?php if (!$img['is_cover']): ?>
                                    <a href="/fgkirs-admin/galleries/set-cover/<?= $img['id'] ?>"
                                        class="w-full text-center bg-white hover:bg-gray-100 text-slate-900 text-xs font-bold py-1.5 rounded transition shadow-sm">
                                        Definir como Capa
                                    </a>
                                <?php endif; ?>
                                <a href="/fgkirs-admin/galleries/remove-image/<?= $img['id'] ?>" data-confirm-delete
                                    class="w-full text-center bg-red-600 hover:bg-red-700 text-white text-xs font-bold py-1.5 rounded transition shadow-sm">
                                    Excluir
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const GALLERY_ID = <?= (int) $gallery['id'] ?>;
        const zipInput   = document.getElementById('zipInput');
        const dropZone   = document.getElementById('dropZone');

        zipInput.addEventListener('change', () => updateDropLabel(zipInput));

        function updateDropLabel(input) {
            if (input.files && input.files[0]) {
                const size = (input.files[0].size / 1024 / 1024).toFixed(1);
                document.getElementById('dropLabel').innerHTML =
                    '<span class="text-red-700 font-bold">' + input.files[0].name + ' (' + size + ' MB)</span>';
            }
        }

        function setProgress(pct, label) {
            document.getElementById('progressWrap').classList.remove('hidden');
            document.getElementById('progressFill').style.width = pct + '%';
            document.getElementById('progressPct').textContent  = Math.round(pct) + '%';
            document.getElementById('progressLabel').textContent = label;
        }

        function showResult(ok, msg) {
            const el = document.getElementById('uploadResult');
            el.classList.remove('hidden', 'bg-green-50', 'text-green-800', 'bg-red-50', 'text-red-800');
            el.classList.add(ok ? 'bg-green-50' : 'bg-red-50', ok ? 'text-green-800' : 'text-red-800');
            el.textContent = msg;
        }

        async function startUpload() {
            if (!zipInput.files || !zipInput.files[0]) {
                alert('Selecione um arquivo ZIP antes de enviar.');
                return;
            }

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Enviando...';
            document.getElementById('uploadResult').classList.add('hidden');
            setProgress(0, 'Enviando ZIP para o servidor...');

            // ── Step 1: upload + extract ──────────────────────────────────────
            const formData = new FormData();
            formData.append('zip_file', zipInput.files[0]);

            let total;
            try {
                const res  = await fetch('/fgkirs-admin/galleries/upload-zip-extract/' + GALLERY_ID, {
                    method: 'POST', body: formData
                });
                const data = await res.json();
                if (data.error) { showResult(false, data.error); btn.disabled = false; btn.textContent = 'Enviar e Processar'; return; }
                total = data.total;
            } catch (e) {
                showResult(false, 'Erro ao enviar o arquivo. Verifique a conexão e tente novamente.');
                btn.disabled = false; btn.textContent = 'Enviar e Processar';
                return;
            }

            setProgress(5, 'ZIP recebido. Processando ' + total + ' imagem(ns)...');

            // ── Step 2: process in batches ────────────────────────────────────
            let offset = 0, totalProcessed = 0, totalFailed = 0;

            while (offset < total) {
                try {
                    const res  = await fetch('/fgkirs-admin/galleries/process-batch/' + GALLERY_ID, { method: 'POST' });
                    const data = await res.json();
                    if (data.error) { showResult(false, data.error); btn.disabled = false; btn.textContent = 'Enviar e Processar'; return; }

                    offset         = data.offset;
                    totalProcessed += data.processed;
                    totalFailed    += data.failed;

                    const pct = 5 + Math.round((offset / total) * 95);
                    setProgress(pct, 'Processando... ' + offset + ' / ' + total + ' imagens');

                    if (data.done) break;
                } catch (e) {
                    showResult(false, 'Erro durante o processamento. ' + totalProcessed + ' imagem(ns) já salva(s). Recarregue a página.');
                    btn.disabled = false; btn.textContent = 'Enviar e Processar';
                    return;
                }
            }

            setProgress(100, 'Concluído!');
            let msg = totalProcessed + ' imagem(ns) adicionada(s) com sucesso!';
            if (totalFailed > 0) msg += ' (' + totalFailed + ' falhou)';
            showResult(true, msg);

            btn.textContent = 'Concluído!';
            setTimeout(() => location.reload(), 1500);
        }

        // Drag-and-drop
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('border-red-500', 'bg-red-50'); });
        dropZone.addEventListener('dragleave', () => { dropZone.classList.remove('border-red-500', 'bg-red-50'); });
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('border-red-500', 'bg-red-50');
            const file = e.dataTransfer.files[0];
            if (file && file.name.endsWith('.zip')) {
                const dt = new DataTransfer();
                dt.items.add(file);
                zipInput.files = dt.files;
                updateDropLabel(zipInput);
            }
        });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>