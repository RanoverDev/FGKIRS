<!-- ══════════════════════════ FOOTER ═══════════════════════════════════ -->
<footer id="contato" class="bg-slate-900 text-white pt-14 pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">

            <div>
                <img src="/assets/images/logo-fgkirs-white.png" alt="FGKIRS" class="h-14 mb-4">
                <p class="text-slate-400 text-sm leading-relaxed max-w-xs">
                    A Federação Gaúcha de Karatê Interestilos une dojos e atletas
                    em todo o Rio Grande do Sul, promovendo a excelência técnica e
                    o espírito esportivo do Karatê.
                </p>
                <div class="rs-bar h-1 rounded-full mt-5 w-24 opacity-70"></div>
                <div class="mt-5 flex items-center gap-4">
                    <img src="/assets/images/logo-wukf.png" alt="WUKF – World Union of Karate-Do Federations"
                        class="h-12 w-12 object-contain opacity-80 hover:opacity-100 transition"
                        title="World Union of Karate-Do Federations">
                    <img src="/assets/images/logo-cbki.png" alt="CBKI – Confederação Brasileira de Karatê Interestilos"
                        class="h-10 object-contain opacity-80 hover:opacity-100 transition"
                        title="Confederação Brasileira de Karatê Interestilos">
                </div>
            </div>

            <div>
                <h4 class="text-sm font-bold uppercase tracking-widest text-slate-300 mb-4">Navegação</h4>
                <ul class="space-y-2 text-slate-400 text-sm">
                    <li><a href="/a-fgkirs" class="hover:text-white transition">A FGKIRS</a></li>
                    <li><a href="/noticias" class="hover:text-white transition">Notícias</a></li>
                    <li><a href="/eventos" class="hover:text-white transition">Próximos Eventos</a></li>
                    <li><a href="/dojos" class="hover:text-white transition">Dojos Oficiais</a></li>
                    <li><a href="/contato" class="hover:text-white transition">Contatos</a></li>
                    <li><a href="/login" class="hover:text-white transition">Área Administrativa</a></li>
                </ul>
            </div>

            <?php $fp = $profile ?? []; ?>
            <div>
                <h4 class="text-sm font-bold uppercase tracking-widest text-slate-300 mb-4">Contato</h4>
                <ul class="space-y-2 text-slate-400 text-sm">

                    <?php if (!empty($fp['email'])): ?>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <a href="mailto:<?= htmlspecialchars($fp['email']) ?>" class="hover:text-white transition">
                                <?= htmlspecialchars($fp['email']) ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($fp['whatsapp'])): ?>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <?= htmlspecialchars($fp['whatsapp']) ?>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($fp['phone'])): ?>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498A1 1 0 0121 15.72V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <?= htmlspecialchars($fp['phone']) ?>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($fp['city'])): ?>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>
                                <?= htmlspecialchars($fp['city']) ?>
                                <?= !empty($fp['state']) ? '– ' . htmlspecialchars($fp['state']) : '' ?>
                                <?= !empty($fp['zip_code']) ? '<br><span class="text-xs">' . htmlspecialchars($fp['zip_code']) . '</span>' : '' ?>
                            </span>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($fp['facebook']) || !empty($fp['instagram'])): ?>
                        <li class="flex items-center gap-3 pt-1">
                            <?php if (!empty($fp['facebook'])): ?>
                                <a href="<?= htmlspecialchars($fp['facebook']) ?>" target="_blank" rel="noopener"
                                    class="text-slate-400 hover:text-white transition" title="Facebook">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.235 2.686.235v2.97h-1.513c-1.491 0-1.956.93-1.956 1.884v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($fp['instagram'])): ?>
                                <a href="<?= htmlspecialchars($fp['instagram']) ?>" target="_blank" rel="noopener"
                                    class="text-slate-400 hover:text-white transition" title="Instagram">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endif; ?>

                </ul>
            </div>

        </div>