<?php require_once __DIR__ . '/partials/public_header.php'; ?>

<!-- Include the about block extracted from home -->
<?php require_once __DIR__ . '/partials/about_block.php'; ?>

<!-- Administrative Structure -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-slate-900 mb-4">Estrutura Administrativa</h2>
            <p class="text-slate-500 max-w-2xl mx-auto leading-relaxed">
                Nossa equipe é dedicada ao crescimento, excelência técnica e consolidação do Karatê Interestilos em todo
                o Rio Grande do Sul.
            </p>
        </div>

        <div class="relative max-w-5xl mx-auto pb-10">
            <div class="flex flex-col items-center">

                <!-- Presidente -->
                <div
                    class="bg-white border-t-4 border-rs-red rounded-xl shadow-lg p-6 w-64 text-center z-10 relative hover:shadow-xl transition-shadow">
                    <div
                        class="w-16 h-16 bg-slate-50 border border-slate-100 rounded-full mx-auto mb-4 flex items-center justify-center text-rs-red shadow-inner">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-slate-900 text-lg">Presidente</h3>
                    <p class="text-sm font-medium text-slate-500 mt-1">Sérgio Santos</p>
                </div>

                <!-- Vertical Line -->
                <div class="w-px h-10 bg-slate-200"></div>

                <!-- Vice -->
                <div
                    class="bg-white border-t-4 border-rs-yellow rounded-xl shadow-md p-5 w-64 text-center z-10 relative hover:shadow-lg transition-shadow">
                    <h3 class="font-bold text-slate-900">Vice-Presidente</h3>
                    <p class="text-sm font-medium text-slate-500 mt-1">A Definir</p>
                </div>

                <!-- Vertical Line to Horizontal connector -->
                <div class="w-px h-10 bg-slate-200"></div>

                <!-- Horizontal Line container -->
                <div class="w-full max-w-3xl relative">
                    <!-- The main horizontal line -->
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[66%] sm:w-[75%] h-px bg-slate-200"></div>

                    <div
                        class="flex flex-col sm:flex-row justify-between items-center sm:items-start pt-8 relative gap-8 sm:gap-0">

                        <!-- connecting vertical lines (visible only on sm and up) -->
                        <div class="hidden sm:block absolute top-0 left-[16.5%] sm:left-[12.5%] w-px h-8 bg-slate-200">
                        </div>
                        <div class="hidden sm:block absolute top-0 left-1/2 w-px h-8 bg-slate-200 -translate-x-1/2">
                        </div>
                        <div
                            class="hidden sm:block absolute top-0 right-[16.5%] sm:right-[12.5%] w-px h-8 bg-slate-200">
                        </div>

                        <!-- connecting vertical lines for mobile (visible only on mobile) -->
                        <div class="sm:hidden absolute top-0 left-1/2 w-px h-full bg-slate-200 -translate-x-1/2 -z-10">
                        </div>

                        <!-- Director 1 -->
                        <div
                            class="bg-white border-t-4 border-slate-700 rounded-xl shadow p-5 w-56 text-center relative z-10 hover:shadow-md transition-shadow">
                            <h3 class="font-bold text-slate-900">Diretoria Administrativa</h3>
                            <p class="text-sm font-medium text-slate-500 mt-1">Equipe Administrativa</p>
                        </div>

                        <!-- Director 2 -->
                        <div
                            class="bg-white border-t-4 border-rs-green rounded-xl shadow p-5 w-56 text-center relative z-10 hover:shadow-md transition-shadow">
                            <h3 class="font-bold text-slate-900">Diretoria Técnica</h3>
                            <p class="text-sm font-medium text-slate-500 mt-1">Conselho Técnico</p>
                        </div>

                        <!-- Director 3 -->
                        <div
                            class="bg-white border-t-4 border-slate-400 rounded-xl shadow p-5 w-56 text-center relative z-10 hover:shadow-md transition-shadow">
                            <h3 class="font-bold text-slate-900">Diretoria de Arbitragem</h3>
                            <p class="text-sm font-medium text-slate-500 mt-1">Conselho de Árbitros</p>
                        </div>

                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/partials/public_footer.php'; ?>