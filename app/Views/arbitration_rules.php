<?php require_once __DIR__ . '/partials/public_header.php'; ?>

<div class="bg-slate-900 border-b border-slate-700/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-rs-red mb-1">Documentos Oficiais</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-white">Regras de Arbitragem</h1>
                <p class="text-slate-400 text-sm mt-1">Regulamento oficial da Federação Gaúcha de Karatê Interestilos</p>
            </div>
            <a href="/15-regras-de-arbitragem.pdf" download
               class="inline-flex items-center gap-2 bg-rs-red hover:bg-red-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Baixar PDF
            </a>
        </div>
    </div>
</div>

<!-- Desktop: iframe viewer -->
<div class="hidden sm:block bg-slate-100" style="height: calc(100vh - 180px); min-height: 600px;">
    <iframe
        src="/15-regras-de-arbitragem.pdf"
        class="w-full h-full border-0"
        title="Regras de Arbitragem FGKIRS">
    </iframe>
</div>

<!-- Mobile: card with open/download options -->
<div class="sm:hidden py-12 px-4">
    <div class="max-w-sm mx-auto bg-white rounded-2xl shadow-lg p-8 text-center border border-slate-100">
        <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-rs-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h2 class="text-lg font-bold text-slate-900 mb-2">Regras de Arbitragem</h2>
        <p class="text-sm text-slate-500 mb-7 leading-relaxed">
            Regulamento oficial de arbitragem da FGKIRS em formato PDF.
        </p>
        <div class="flex flex-col gap-3">
            <a href="/15-regras-de-arbitragem.pdf" target="_blank"
               class="flex items-center justify-center gap-2 bg-rs-red hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Visualizar
            </a>
            <a href="/15-regras-de-arbitragem.pdf" download
               class="flex items-center justify-center gap-2 border border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold py-3 px-6 rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Baixar PDF
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/public_footer.php'; ?>
