<?php
// ──────────────────────────────────────────────────────────────────────────────
// Popup de campanha — conteúdo gerenciado em /fgkirs-admin/popup
// Exibição controlada por localStorage; ?pix=1 na URL força a reabertura
// ──────────────────────────────────────────────────────────────────────────────

use Helpers\Auth;
use Models\SitePopup;

$popup = SitePopup::get();

// ?pix=1 reabre o popup; para administradores serve também como pré-visualização
$popupPreview = ($_GET['pix'] ?? null) === '1' && Auth::isAdmin();

if (empty($popup['is_active']) && !$popupPreview) {
    return;
}

$popupFormat   = SitePopup::format($popup);
$popupImage    = SitePopup::imageUrl($popup);
$popupPixKey   = trim((string) ($popup['pix_key'] ?? ''));
$popupShowPix  = !empty($popup['pix_enabled']) && $popupPixKey !== '';
$popupWhatsUrl = SitePopup::whatsappUrl($popup);
$popupShowWa   = !empty($popup['whatsapp_enabled']) && $popupWhatsUrl !== null;
$popupPixLabel = trim((string) ($popup['pix_label'] ?? '')) ?: 'Contribuir via PIX';
$popupWaLabel  = trim((string) ($popup['whatsapp_label'] ?? '')) ?: 'Posso Ajudar?';
$popupCooldown = max(0, (int) ($popup['frequency_hours'] ?? 24));
?>

<!-- ══════════════════ POPUP DE CAMPANHA ══════════════════ -->
<div id="pixOverlay"
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
     style="background:rgba(0,0,0,.75); backdrop-filter:blur(4px);
            opacity:0; pointer-events:none; transition:opacity .35s ease;">

    <div id="pixModal"
         class="relative w-full overflow-hidden rounded-2xl shadow-2xl"
         style="max-width:<?= (int) $popupFormat['max_width'] ?>px; transform:translateY(24px); transition:transform .35s ease;">

        <!-- Close button -->
        <button onclick="closePixPopup()"
                class="absolute top-3 right-3 z-10 flex items-center justify-center w-8 h-8 rounded-full
                       bg-black/50 hover:bg-black/80 text-white transition-colors duration-200"
                aria-label="Fechar">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <!-- Poster image -->
        <div class="w-full bg-slate-900 overflow-hidden"
             style="aspect-ratio:<?= $popupFormat['ratio'] ?>; max-height:calc(92vh - 190px);">
            <img src="<?= htmlspecialchars($popupImage) ?>"
                 alt="<?= htmlspecialchars($popup['image_alt'] ?? '') ?>"
                 class="w-full h-full object-contain"
                 style="display:block;">
        </div>

        <?php if ($popupShowPix || $popupShowWa): ?>
            <!-- Buttons -->
            <div class="flex flex-col gap-3 p-4" style="background:#0f172a;">

                <?php if ($popupShowPix): ?>
                    <!-- PIX copy button -->
                    <button id="pixCopyBtn"
                            onclick="copyPix()"
                            class="w-full flex flex-col items-center justify-center gap-0.5 rounded-xl py-4 px-5
                                   font-bold transition-all duration-200 active:scale-95"
                            style="background:linear-gradient(135deg,#1a1200 0%,#7c5c00 40%,#c8960c 100%);
                                   border:1px solid #c8960c; color:#fde68a;">

                        <span class="flex items-center gap-2 text-sm font-bold uppercase tracking-widest" style="color:#fde68a;">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span id="pixBtnLabel"><?= htmlspecialchars($popupPixLabel) ?></span>
                        </span>
                        <span class="text-base font-black tracking-wide mt-0.5" style="color:#fff; letter-spacing:.03em;">
                            <?= htmlspecialchars(SitePopup::pixKeyDisplay($popup)) ?>
                        </span>
                    </button>
                <?php endif; ?>

                <?php if ($popupShowWa): ?>
                    <!-- WhatsApp button -->
                    <a href="<?= htmlspecialchars($popupWhatsUrl) ?>"
                       target="_blank" rel="noopener"
                       class="w-full flex items-center justify-center gap-3 rounded-xl py-4 px-5
                              text-white font-bold text-base transition-all duration-200
                              hover:opacity-90 active:scale-95"
                       style="background:#25D366;">

                        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        <?= htmlspecialchars($popupWaLabel) ?>
                    </a>
                <?php endif; ?>

            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const PIX_KEY     = <?= json_encode($popupPixKey, JSON_UNESCAPED_UNICODE) ?>;
    const PIX_LABEL   = <?= json_encode($popupPixLabel, JSON_UNESCAPED_UNICODE) ?>;
    const STORAGE_KEY = 'fgkirs_pix_popup_last';
    const COOLDOWN_MS = <?= $popupCooldown ?> * 60 * 60 * 1000;

    const overlay = document.getElementById('pixOverlay');
    const modal   = document.getElementById('pixModal');

    function shouldShow() {
        const params = new URLSearchParams(window.location.search);
        if (params.get('pix') === '1') return true;
        if (COOLDOWN_MS === 0) return true;
        const last = localStorage.getItem(STORAGE_KEY);
        if (!last) return true;
        return (Date.now() - parseInt(last, 10)) > COOLDOWN_MS;
    }

    function openPixPopup() {
        overlay.style.pointerEvents = 'auto';
        overlay.style.opacity       = '1';
        modal.style.transform       = 'translateY(0)';
        localStorage.setItem(STORAGE_KEY, Date.now().toString());
    }

    window.closePixPopup = function () {
        overlay.style.opacity       = '0';
        overlay.style.pointerEvents = 'none';
        modal.style.transform       = 'translateY(24px)';
    };

    // Close on overlay click (outside modal)
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) window.closePixPopup();
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') window.closePixPopup();
    });

    // Copy PIX to clipboard
    window.copyPix = function () {
        const btn   = document.getElementById('pixCopyBtn');
        const label = document.getElementById('pixBtnLabel');
        if (!btn || !PIX_KEY) return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(PIX_KEY).then(onCopied).catch(fallbackCopy);
        } else {
            fallbackCopy();
        }

        function fallbackCopy() {
            const ta = document.createElement('textarea');
            ta.value = PIX_KEY;
            ta.style.cssText = 'position:fixed;top:-9999px;';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            onCopied();
        }

        function onCopied() {
            label.textContent = '✓ Chave Copiada!';
            btn.style.background = 'linear-gradient(135deg,#064e3b 0%,#065f46 100%)';
            btn.style.borderColor = '#10b981';
            btn.style.color = '#d1fae5';
            setTimeout(function () {
                label.textContent = PIX_LABEL;
                btn.style.background = 'linear-gradient(135deg,#1a1200 0%,#7c5c00 40%,#c8960c 100%)';
                btn.style.borderColor = '#c8960c';
                btn.style.color = '#fde68a';
            }, 3000);
        }
    };

    // Show after delay
    if (shouldShow()) {
        setTimeout(openPixPopup, 1800);
    }
})();
</script>
