<?php
use Helpers\Csrf;
use Models\SitePopup;

$p        = $popup ?? [];
$formats  = SitePopup::FORMATS;
$keyTypes = SitePopup::KEY_TYPES;
$current  = SitePopup::format($p);
$imageUrl = SitePopup::imageUrl($p);
$hasUpload = !empty($p['image']);

require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Popup do Site</h1>
    <p class="text-sm text-slate-500 mt-1">
        Campanha exibida em destaque para quem acessa o site. Controle a imagem, o botão de PIX e o botão de WhatsApp.
    </p>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="mb-6 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">
        <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="mb-6 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
        <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<form action="/fgkirs-admin/popup/update" method="POST" enctype="multipart/form-data"
      class="grid lg:grid-cols-3 gap-6 items-start">

    <?= Csrf::field() ?>

    <!-- ══════════════ COLUNA DE EDIÇÃO ══════════════ -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Status -->
        <div class="bg-white rounded-lg shadow p-6">
            <label class="flex items-start gap-4 cursor-pointer">
                <input type="checkbox" name="is_active" id="isActive" value="1"
                       <?= !empty($p['is_active']) ? 'checked' : '' ?>
                       class="mt-1 w-5 h-5 rounded border-gray-300 text-red-700 focus:ring-red-700">
                <span>
                    <span class="block font-semibold text-slate-900">Exibir popup no site</span>
                    <span class="block text-sm text-slate-500 mt-0.5">
                        Desmarque para desativar a campanha sem perder as configurações.
                    </span>
                </span>
            </label>
        </div>

        <!-- Imagem -->
        <div class="bg-white rounded-lg shadow p-6 sm:p-8">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Imagem da Campanha</p>

            <div class="mb-5">
                <label for="image_format" class="block text-sm font-medium text-gray-700 mb-2">Formato da arte</label>
                <div class="grid sm:grid-cols-3 gap-3">
                    <?php foreach ($formats as $key => $meta): ?>
                        <?php $isCurrent = ($p['image_format'] ?? 'portrait') === $key; ?>
                        <label class="format-option relative flex flex-col gap-1 rounded-xl border-2 px-4 py-3 cursor-pointer transition
                                      <?= $isCurrent ? 'border-red-700 bg-red-50' : 'border-gray-200 hover:border-gray-300' ?>">
                            <input type="radio" name="image_format" value="<?= $key ?>" class="sr-only"
                                   <?= $isCurrent ? 'checked' : '' ?>>
                            <span class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($meta['label']) ?></span>
                            <span class="text-xs text-slate-500"><?= htmlspecialchars($meta['dimensions']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-slate-500 mt-2">
                    Envie a arte exatamente nas dimensões do formato escolhido para não haver corte nem distorção.
                </p>
            </div>

            <div class="mb-4">
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Arquivo da imagem</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"
                       class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                              file:text-sm file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800
                              border border-gray-300 rounded-lg p-2">
                <p class="text-xs text-slate-500 mt-2">JPG, PNG ou WEBP. A imagem é convertida automaticamente para WEBP com 1080px de largura.</p>
            </div>

            <div id="dimensionWarning" class="hidden mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm"></div>

            <div class="mb-4">
                <label for="image_alt" class="block text-sm font-medium text-gray-700 mb-2">Descrição da imagem (acessibilidade)</label>
                <input type="text" id="image_alt" name="image_alt" maxlength="255"
                       value="<?= htmlspecialchars($p['image_alt'] ?? '') ?>"
                       placeholder="Ex.: PIX Solidário FGKIRS – Com sua ajuda, nossos atletas vão mais longe!"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            </div>

            <?php if ($hasUpload): ?>
                <a href="/fgkirs-admin/popup/remove-image" data-confirm-delete
                   class="inline-flex items-center gap-2 text-sm font-medium text-red-700 hover:text-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Remover imagem enviada
                </a>
            <?php endif; ?>
        </div>

        <!-- Botão PIX -->
        <div class="bg-white rounded-lg shadow p-6 sm:p-8">
            <label class="flex items-start gap-4 cursor-pointer mb-5 pb-5 border-b border-slate-100">
                <input type="checkbox" name="pix_enabled" id="pixEnabled" value="1"
                       <?= !empty($p['pix_enabled']) ? 'checked' : '' ?>
                       class="mt-1 w-5 h-5 rounded border-gray-300 text-red-700 focus:ring-red-700">
                <span>
                    <span class="block font-semibold text-slate-900">Botão de PIX</span>
                    <span class="block text-sm text-slate-500 mt-0.5">Ao clicar, a chave é copiada para a área de transferência do visitante.</span>
                </span>
            </label>

            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="pix_label" class="block text-sm font-medium text-gray-700 mb-2">Texto do botão</label>
                    <input type="text" id="pix_label" name="pix_label" maxlength="100"
                           value="<?= htmlspecialchars($p['pix_label'] ?? 'Contribuir via PIX') ?>"
                           placeholder="Contribuir via PIX"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                </div>
                <div>
                    <label for="pix_key_type" class="block text-sm font-medium text-gray-700 mb-2">Tipo da chave</label>
                    <select id="pix_key_type" name="pix_key_type"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                        <?php foreach ($keyTypes as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($p['pix_key_type'] ?? 'cnpj') === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label for="pix_key" class="block text-sm font-medium text-gray-700 mb-2">Chave PIX</label>
                <input type="text" id="pix_key" name="pix_key" maxlength="255"
                       value="<?= htmlspecialchars($p['pix_key'] ?? '') ?>"
                       placeholder="29.834.577/0001-80"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                <p class="text-xs text-slate-500 mt-2">É exatamente este valor que o visitante copia ao clicar no botão.</p>
            </div>
        </div>

        <!-- Botão WhatsApp -->
        <div class="bg-white rounded-lg shadow p-6 sm:p-8">
            <label class="flex items-start gap-4 cursor-pointer mb-5 pb-5 border-b border-slate-100">
                <input type="checkbox" name="whatsapp_enabled" id="whatsappEnabled" value="1"
                       <?= !empty($p['whatsapp_enabled']) ? 'checked' : '' ?>
                       class="mt-1 w-5 h-5 rounded border-gray-300 text-red-700 focus:ring-red-700">
                <span>
                    <span class="block font-semibold text-slate-900">Botão de WhatsApp</span>
                    <span class="block text-sm text-slate-500 mt-0.5">Abre uma conversa no WhatsApp com a mensagem já preenchida.</span>
                </span>
            </label>

            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="whatsapp_label" class="block text-sm font-medium text-gray-700 mb-2">Texto do botão</label>
                    <input type="text" id="whatsapp_label" name="whatsapp_label" maxlength="100"
                           value="<?= htmlspecialchars($p['whatsapp_label'] ?? 'Posso Ajudar?') ?>"
                           placeholder="Posso Ajudar?"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                </div>
                <div>
                    <label for="whatsapp_phone" class="block text-sm font-medium text-gray-700 mb-2">Número do WhatsApp</label>
                    <input type="text" id="whatsapp_phone" name="whatsapp_phone" data-mask="phone"
                           value="<?= htmlspecialchars(SitePopup::formatPhone($p['whatsapp_phone'] ?? '')) ?>"
                           placeholder="(55) 3511-2602"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    <p class="text-xs text-slate-500 mt-2">Informe com DDD. O código do país (+55) é adicionado automaticamente.</p>
                </div>
            </div>

            <div>
                <label for="whatsapp_message" class="block text-sm font-medium text-gray-700 mb-2">Mensagem inicial</label>
                <textarea id="whatsapp_message" name="whatsapp_message" rows="2" maxlength="500"
                          placeholder="Olá! Quero apoiar o Karatê Gaúcho pelo PIX Solidário FGKIRS 🥋"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent"><?= htmlspecialchars($p['whatsapp_message'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Exibição -->
        <div class="bg-white rounded-lg shadow p-6 sm:p-8">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Exibição</p>

            <div class="sm:max-w-xs">
                <label for="frequency_hours" class="block text-sm font-medium text-gray-700 mb-2">Repetir para o mesmo visitante a cada</label>
                <div class="flex items-center gap-3">
                    <input type="number" id="frequency_hours" name="frequency_hours" min="0" max="720"
                           value="<?= (int) ($p['frequency_hours'] ?? 24) ?>"
                           class="w-28 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    <span class="text-sm text-slate-600">horas</span>
                </div>
                <p class="text-xs text-slate-500 mt-2">Use 0 para exibir em todas as visitas.</p>
            </div>
        </div>

        <button type="submit"
                class="bg-red-700 hover:bg-red-800 text-white font-semibold py-3 px-8 rounded-lg transition">
            Salvar Alterações
        </button>
    </div>

    <!-- ══════════════ PRÉ-VISUALIZAÇÃO ══════════════ -->
    <div class="lg:col-span-1 lg:sticky lg:top-6">
        <div class="bg-white rounded-lg shadow p-6 border border-slate-100">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-800">Pré-visualização</h2>
                <a href="/?pix=1" target="_blank" rel="noopener"
                   class="text-xs font-semibold text-red-700 hover:text-red-800">Ver no site ↗</a>
            </div>

            <div class="rounded-2xl overflow-hidden shadow-lg mx-auto" style="max-width:<?= (int) $current['max_width'] ?>px;">
                <div id="previewFrame" class="w-full bg-slate-900 overflow-hidden"
                     style="aspect-ratio:<?= $current['ratio'] ?>;">
                    <img id="previewImage" src="<?= htmlspecialchars($imageUrl) ?>" alt=""
                         class="w-full h-full object-contain">
                </div>

                <div class="flex flex-col gap-3 p-4" style="background:#0f172a;">
                    <div id="previewPix" class="w-full flex flex-col items-center justify-center gap-0.5 rounded-xl py-3 px-4 font-bold"
                         style="background:linear-gradient(135deg,#1a1200 0%,#7c5c00 40%,#c8960c 100%); border:1px solid #c8960c;">
                        <span id="previewPixLabel" class="text-xs font-bold uppercase tracking-widest" style="color:#fde68a;"></span>
                        <span id="previewPixKey" class="text-sm font-black tracking-wide" style="color:#fff;"></span>
                    </div>

                    <div id="previewWhats" class="w-full flex items-center justify-center gap-2 rounded-xl py-3 px-4 text-white font-bold text-sm"
                         style="background:#25D366;">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        <span id="previewWhatsLabel"></span>
                    </div>
                </div>
            </div>

            <p id="previewInactive" class="hidden mt-4 text-center text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                O popup está desativado e não aparece para os visitantes.
            </p>
        </div>
    </div>
</form>

<script>
(function () {
    const FORMATS = <?= json_encode(array_map(
        fn ($meta) => ['ratio' => $meta['ratio'], 'maxWidth' => $meta['max_width'], 'label' => $meta['label'], 'dimensions' => $meta['dimensions']],
        $formats
    ), JSON_UNESCAPED_UNICODE) ?>;
    const KEY_TYPES = <?= json_encode($keyTypes, JSON_UNESCAPED_UNICODE) ?>;

    const $ = (id) => document.getElementById(id);

    const frame      = $('previewFrame');
    const frameWrap  = frame.parentElement;
    const previewImg = $('previewImage');
    const warning    = $('dimensionWarning');

    // ── Formato ───────────────────────────────────────────────────────────
    function selectedFormat() {
        return document.querySelector('input[name="image_format"]:checked')?.value || 'portrait';
    }

    function paintFormatOptions() {
        document.querySelectorAll('.format-option').forEach((label) => {
            const checked = label.querySelector('input').checked;
            label.classList.toggle('border-red-700', checked);
            label.classList.toggle('bg-red-50', checked);
            label.classList.toggle('border-gray-200', !checked);
        });
    }

    function applyFormat() {
        const meta = FORMATS[selectedFormat()];
        frame.style.aspectRatio  = meta.ratio;
        frameWrap.style.maxWidth = meta.maxWidth + 'px';
        paintFormatOptions();
    }

    document.querySelectorAll('input[name="image_format"]').forEach((radio) => {
        radio.addEventListener('change', () => { applyFormat(); checkDimensions(); });
    });

    // ── Imagem ────────────────────────────────────────────────────────────
    let uploadedRatio = null;

    $('image').addEventListener('change', function () {
        const file = this.files?.[0];
        if (!file) return;

        const url = URL.createObjectURL(file);
        const probe = new Image();

        probe.onload = function () {
            previewImg.src = url;
            uploadedRatio  = probe.naturalWidth / probe.naturalHeight;
            autoSelectFormat(uploadedRatio);
            checkDimensions(probe.naturalWidth, probe.naturalHeight);
        };
        probe.src = url;
    });

    function autoSelectFormat(ratio) {
        const targets = { square: 1, portrait: 4 / 5, story: 9 / 16 };
        let best = null, bestDiff = Infinity;

        for (const [key, target] of Object.entries(targets)) {
            const diff = Math.abs(ratio - target);
            if (diff < bestDiff) { bestDiff = diff; best = key; }
        }

        const radio = document.querySelector(`input[name="image_format"][value="${best}"]`);
        if (radio) { radio.checked = true; applyFormat(); }
    }

    function checkDimensions(width, height) {
        if (!uploadedRatio) { warning.classList.add('hidden'); return; }

        const meta    = FORMATS[selectedFormat()];
        const [w, h]  = meta.ratio.split('/').map((n) => parseFloat(n));
        const tolerance = 0.02;

        if (Math.abs(uploadedRatio - (w / h)) > tolerance) {
            const enviada = width && height ? `A imagem enviada tem ${width} × ${height} px. ` : '';
            warning.textContent = `${enviada}O formato "${meta.label}" espera ${meta.dimensions}. A arte será exibida inteira, mas pode sobrar espaço nas laterais.`;
            warning.classList.remove('hidden');
        } else {
            warning.classList.add('hidden');
        }
    }

    // ── Máscara da chave PIX ──────────────────────────────────────────────
    const keyType = $('pix_key_type');
    const keyInput = $('pix_key');

    function maskPixKey(value, type) {
        if (type === 'cnpj') {
            const d = value.replace(/\D/g, '').slice(0, 14);
            return d
                .replace(/^(\d{2})(\d)/, '$1.$2')
                .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2')
                .replace(/(\d{4})(\d)/, '$1-$2');
        }
        if (type === 'cpf') {
            const d = value.replace(/\D/g, '').slice(0, 11);
            return d
                .replace(/^(\d{3})(\d)/, '$1.$2')
                .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1-$2');
        }
        if (type === 'phone') {
            const d = value.replace(/\D/g, '').slice(0, 11);
            if (d.length <= 2) return d ? `(${d}` : '';
            if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`;
            if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`;
            return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`;
        }
        return value;
    }

    function applyKeyMask() {
        keyInput.value = maskPixKey(keyInput.value, keyType.value);
        renderPreview();
    }

    keyType.addEventListener('change', applyKeyMask);
    keyInput.addEventListener('input', applyKeyMask);

    // ── Preview ao vivo ───────────────────────────────────────────────────
    function renderPreview() {
        const pixOn   = $('pixEnabled').checked;
        const whatsOn = $('whatsappEnabled').checked;
        const key     = keyInput.value.trim();
        const type    = keyType.value;

        $('previewPix').style.display   = pixOn ? '' : 'none';
        $('previewWhats').style.display = whatsOn ? '' : 'none';

        $('previewPixLabel').textContent = $('pix_label').value.trim() || 'Contribuir via PIX';
        $('previewPixKey').textContent   = key && (type === 'cnpj' || type === 'cpf')
            ? `${KEY_TYPES[type]} ${key}`
            : key;

        $('previewWhatsLabel').textContent = $('whatsapp_label').value.trim() || 'Posso Ajudar?';
        previewImg.alt = $('image_alt').value.trim();

        $('previewInactive').classList.toggle('hidden', $('isActive').checked);
    }

    ['pixEnabled', 'whatsappEnabled', 'isActive', 'pix_label', 'whatsapp_label', 'image_alt']
        .forEach((id) => $(id).addEventListener('input', renderPreview));

    applyFormat();
    renderPreview();
})();
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
