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

        <!-- Diretoria Principal -->
        <div class="flex flex-col items-center mb-4">

            <!-- Presidente -->
            <div class="bg-white border-t-4 border-rs-red rounded-xl shadow-lg p-6 w-72 text-center z-10 relative hover:shadow-xl transition-shadow">
                <div class="w-20 h-20 rounded-full mx-auto mb-4 overflow-hidden bg-slate-100 border-2 border-rs-red shadow">
                    <img src="/assets/images/cargos/presidente.jpg" alt="Presidente" class="w-full h-full object-cover">
                </div>
                <span class="inline-block text-xs font-semibold uppercase tracking-widest text-rs-red mb-1">Presidente</span>
                <h3 class="font-bold text-slate-900 text-base leading-tight">Gévio Kohler</h3>
                <p class="text-xs text-slate-500 mt-1">5º Dan &bull; Santa Rosa</p>
            </div>

            <div class="w-px h-8 bg-slate-200"></div>

            <!-- Vice-Presidente -->
            <div class="bg-white border-t-4 border-rs-yellow rounded-xl shadow-md p-5 w-72 text-center z-10 relative hover:shadow-lg transition-shadow">
                <div class="w-16 h-16 rounded-full mx-auto mb-3 overflow-hidden bg-slate-100 border-2 border-rs-yellow shadow">
                    <img src="/assets/images/cargos/vice-presidente.jpg" alt="Vice-Presidente" class="w-full h-full object-cover">
                </div>
                <span class="inline-block text-xs font-semibold uppercase tracking-widest text-amber-600 mb-1">Vice-Presidente</span>
                <h3 class="font-bold text-slate-900 text-base leading-tight">Altemar Sabino</h3>
                <p class="text-xs text-slate-500 mt-1">7º Dan &bull; Viamão</p>
            </div>

            <div class="w-px h-8 bg-slate-200"></div>
        </div>

        <!-- Diretores em grid -->
        <div class="max-w-5xl mx-auto">
            <div class="relative mb-2">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[80%] h-px bg-slate-200 hidden sm:block"></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 pt-8">

                <!-- Secretária -->
                <div class="bg-white border-t-4 border-slate-600 rounded-xl shadow p-5 text-center hover:shadow-md transition-shadow">
                    <div class="w-16 h-16 rounded-full mx-auto mb-3 overflow-hidden bg-slate-100 border-2 border-slate-300 shadow">
                        <img src="/assets/images/cargos/secretaria.jpg" alt="Secretária" class="w-full h-full object-cover">
                    </div>
                    <span class="inline-block text-xs font-semibold uppercase tracking-widest text-slate-500 mb-1">Secretária</span>
                    <h3 class="font-bold text-slate-900 text-sm leading-tight">Fernanda Biachi</h3>
                    <p class="text-xs text-slate-400 mt-1">1º Dan &bull; Alegrete</p>
                </div>

                <!-- Diretora Financeira -->
                <div class="bg-white border-t-4 border-slate-600 rounded-xl shadow p-5 text-center hover:shadow-md transition-shadow">
                    <div class="w-16 h-16 rounded-full mx-auto mb-3 overflow-hidden bg-slate-100 border-2 border-slate-300 shadow">
                        <img src="/assets/images/cargos/financeiro.jpg" alt="Diretora Financeira" class="w-full h-full object-cover">
                    </div>
                    <span class="inline-block text-xs font-semibold uppercase tracking-widest text-slate-500 mb-1">Diretora Financeira</span>
                    <h3 class="font-bold text-slate-900 text-sm leading-tight">Rosangela Quatrin</h3>
                    <p class="text-xs text-slate-400 mt-1">3º Dan &bull; Alecrim</p>
                </div>

                <!-- Diretor Técnico -->
                <div class="bg-white border-t-4 border-rs-green rounded-xl shadow p-5 text-center hover:shadow-md transition-shadow">
                    <div class="w-16 h-16 rounded-full mx-auto mb-3 overflow-hidden bg-slate-100 border-2 border-slate-300 shadow">
                        <img src="/assets/images/cargos/diretor-tecnico.jpg" alt="Diretor Técnico" class="w-full h-full object-cover">
                    </div>
                    <span class="inline-block text-xs font-semibold uppercase tracking-widest text-green-700 mb-1">Diretor Técnico</span>
                    <h3 class="font-bold text-slate-900 text-sm leading-tight">Francisco Assunção Garcia</h3>
                    <p class="text-xs text-slate-400 mt-1">7º Dan &bull; Rio Grande</p>
                </div>

                <!-- Diretor de Arbitragem -->
                <div class="bg-white border-t-4 border-slate-400 rounded-xl shadow p-5 text-center hover:shadow-md transition-shadow">
                    <div class="w-16 h-16 rounded-full mx-auto mb-3 overflow-hidden bg-slate-100 border-2 border-slate-300 shadow">
                        <img src="/assets/images/cargos/diretor-arbitragem.jpg" alt="Diretor de Arbitragem" class="w-full h-full object-cover">
                    </div>
                    <span class="inline-block text-xs font-semibold uppercase tracking-widest text-slate-500 mb-1">Diretor de Arbitragem</span>
                    <h3 class="font-bold text-slate-900 text-sm leading-tight">Itamar Ponciano</h3>
                    <p class="text-xs text-slate-400 mt-1">3º Dan &bull; Alecrim</p>
                </div>

                <!-- Diretor Jurídico -->
                <div class="bg-white border-t-4 border-slate-600 rounded-xl shadow p-5 text-center hover:shadow-md transition-shadow">
                    <div class="w-16 h-16 rounded-full mx-auto mb-3 overflow-hidden bg-slate-100 border-2 border-slate-300 shadow">
                        <img src="/assets/images/cargos/diretor-juridico.jpg" alt="Diretor Jurídico" class="w-full h-full object-cover">
                    </div>
                    <span class="inline-block text-xs font-semibold uppercase tracking-widest text-slate-500 mb-1">Diretor Jurídico</span>
                    <h3 class="font-bold text-slate-900 text-sm leading-tight">Altemar Sabino</h3>
                    <p class="text-xs text-slate-400 mt-1">7º Dan &bull; OAB/RS 129.714</p>
                </div>

                <!-- Diretor de Marketing e Eventos -->
                <div class="bg-white border-t-4 border-rs-red rounded-xl shadow p-5 text-center hover:shadow-md transition-shadow">
                    <div class="w-16 h-16 rounded-full mx-auto mb-3 overflow-hidden bg-slate-100 border-2 border-slate-300 shadow">
                        <img src="/assets/images/cargos/diretor-marketing-eventos.jpg" alt="Diretor de Marketing e Eventos" class="w-full h-full object-cover">
                    </div>
                    <span class="inline-block text-xs font-semibold uppercase tracking-widest text-rs-red mb-1">Relações Públicas, Marketing e Eventos</span>
                    <h3 class="font-bold text-slate-900 text-sm leading-tight">Rian Lorenzo Kohler</h3>
                    <p class="text-xs text-slate-400 mt-0.5">2º Dan &bull; Santa Rosa</p>
                    <h3 class="font-bold text-slate-900 text-sm leading-tight mt-2">José Figueroa</h3>
                    <p class="text-xs text-slate-400 mt-0.5">1º Dan &bull; Ubiretama</p>
                </div>

            </div>
        </div>

        <!-- Conselho Fiscal, Suplentes, Comissão de Ética, Kobudo -->
        <div class="max-w-5xl mx-auto mt-12 grid grid-cols-1 md:grid-cols-2 gap-8">

            <!-- Conselho Fiscal -->
            <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                <h4 class="text-sm font-bold uppercase tracking-widest text-slate-600 mb-4 border-b border-slate-200 pb-2">Conselho Fiscal</h4>
                <ul class="space-y-3">
                    <li class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center shrink-0 text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Angelo Tentardini</p>
                            <p class="text-xs text-slate-500">3º Dan &bull; Santana do Livramento</p>
                        </div>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center shrink-0 text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Leanderson Penna</p>
                            <p class="text-xs text-slate-500">3º Dan &bull; Barra do Quaraí</p>
                        </div>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center shrink-0 text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Fabiane Hintz</p>
                            <p class="text-xs text-slate-500">1º Dan &bull; Santa Rosa</p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Suplentes do Conselho -->
            <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                <h4 class="text-sm font-bold uppercase tracking-widest text-slate-600 mb-4 border-b border-slate-200 pb-2">Suplentes do Conselho</h4>
                <ul class="space-y-3">
                    <li class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center shrink-0 text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Arlei Roosevelt Bedatt</p>
                            <p class="text-xs text-slate-500">4º Dan &bull; Campo Bom</p>
                        </div>
                    </li>
                    <li class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center shrink-0 text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Daniel Soares Guimarães</p>
                            <p class="text-xs text-slate-500">4º Dan &bull; Rio Grande</p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Comissão de Ética -->
            <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                <h4 class="text-sm font-bold uppercase tracking-widest text-slate-600 mb-4 border-b border-slate-200 pb-2">Comissão de Ética</h4>
                <ul class="space-y-3">
                    <li class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center shrink-0 text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Fabiano Maciel Ornaghi</p>
                            <p class="text-xs text-slate-500">5º Dan &bull; Capão da Canoa</p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Departamento de Kobudo -->
            <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                <h4 class="text-sm font-bold uppercase tracking-widest text-slate-600 mb-4 border-b border-slate-200 pb-2">Departamento de Kobudo</h4>
                <ul class="space-y-3">
                    <li class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center shrink-0 text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Rogélio Chagas Rodrigues</p>
                            <p class="text-xs text-slate-500">6º Dan &bull; Santana do Livramento</p>
                        </div>
                    </li>
                </ul>
            </div>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/partials/public_footer.php'; ?>