<?php
$fp = $profile ?? [];
require_once __DIR__ . '/partials/public_header.php';
?>

<main class="bg-white min-h-screen">
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center gap-3 mb-8">
                <span class="h-7 w-1 rounded-full bg-rs-red"></span>
                <h1 class="text-4xl font-bold text-slate-900">Fale Conosco</h1>
            </div>
            <p class="text-slate-500 mb-8">
                Tem alguma dúvida, sugestão ou quer saber mais sobre a Federação? Entre em contato conosco.
            </p>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-8 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r-lg shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <p class="font-semibold"><?= htmlspecialchars($_SESSION['success']) ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-8 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                        <p class="font-semibold"><?= htmlspecialchars($_SESSION['error']) ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">

                <!-- ── FORMULÁRIO ── -->
                <div class="bg-white p-8 rounded-2xl shadow-xl border border-slate-100">
                    <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center gap-2 font-heading">
                        <svg class="w-6 h-6 text-rs-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                        Envie uma Mensagem
                    </h2>

                    <form action="/contato/enviar" method="POST" class="space-y-5">
                        <div>
                            <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">Seu Nome
                                completo</label>
                            <input type="text" id="name" name="name" required
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-rs-red focus:border-transparent transition-all shadow-sm">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700 mb-1">Endereço de
                                E-mail</label>
                            <input type="email" id="email" name="email" required
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-rs-red focus:border-transparent transition-all shadow-sm">
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-semibold text-slate-700 mb-1">Sua
                                Mensagem</label>
                            <textarea id="message" name="message" rows="5" required
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-rs-red focus:border-transparent transition-all shadow-sm resize-y"></textarea>
                        </div>

                        <button type="submit"
                            class="w-full bg-slate-900 hover:bg-rs-red text-white font-bold py-3.5 px-6 rounded-lg transition-colors shadow-md flex items-center justify-center gap-2">
                            <span>Enviar Mensagem</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- ── INFORMAÇÕES E MAPA ── -->
                <div class="space-y-8 flex flex-col">

                    <div class="bg-white p-8 rounded-2xl shadow-xl border border-slate-100">
                        <h3 class="text-xl font-bold text-slate-900 mb-6 font-heading border-b border-slate-100 pb-4">
                            Informações de Contato
                        </h3>

                        <div class="space-y-6">
                            <?php if (!empty($fp['email'])): ?>
                                <div class="flex items-start gap-4">
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">E-mail</p>
                                        <a href="mailto:<?= htmlspecialchars($fp['email']) ?>"
                                            class="text-slate-500 hover:text-rs-red transition-colors"><?= htmlspecialchars($fp['email']) ?></a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($fp['whatsapp'])): ?>
                                <div class="flex items-start gap-4">
                                    <div
                                        class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center text-green-600 shrink-0">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">WhatsApp</p>
                                        <?php
                                        $waNumber = preg_replace('/\D/', '', $fp['whatsapp']);
                                        if ($waNumber && !str_starts_with($waNumber, '55'))
                                            $waNumber = '55' . $waNumber;
                                        ?>
                                        <a href="https://wa.me/<?= $waNumber ?>" target="_blank"
                                            class="text-slate-500 hover:text-green-600 transition-colors"><?= htmlspecialchars($fp['whatsapp']) ?></a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($fp['phone'])): ?>
                                <div class="flex items-start gap-4">
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498A1 1 0 0121 15.72V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Telefone</p>
                                        <p class="text-slate-500"><?= htmlspecialchars($fp['phone']) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($fp['address']) || !empty($fp['city'])): ?>
                                <div class="flex items-start gap-4">
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z">
                                            </path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Endereço</p>
                                        <p class="text-slate-500 leading-snug">
                                            <?= !empty($fp['address']) ? htmlspecialchars($fp['address']) . '<br>' : '' ?>
                                            <?= !empty($fp['city']) ? htmlspecialchars($fp['city']) : '' ?>
                                            <?= !empty($fp['state']) ? ' - ' . htmlspecialchars($fp['state']) : '' ?>
                                            <?= !empty($fp['zip_code']) ? '<br>CEP: ' . htmlspecialchars($fp['zip_code']) : '' ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- MAPA -->
                    <div
                        class="flex-1 bg-white p-2 rounded-2xl shadow-xl border border-slate-100 min-h-[300px] overflow-hidden">
                        <?php
                        $addressParts = [];
                        if (!empty($fp['address']))
                            $addressParts[] = $fp['address'];
                        if (!empty($fp['city']))
                            $addressParts[] = $fp['city'];
                        if (!empty($fp['state']))
                            $addressParts[] = $fp['state'];
                        $addressStr = implode(', ', $addressParts);
                        $mapQuery = urlencode($addressStr ?: 'Rio Grande do Sul, Brasil');
                        ?>
                        <iframe class="w-full h-full rounded-xl border-0"
                            src="https://maps.google.com/maps?q=<?= $mapQuery ?>&t=&z=14&ie=UTF8&iwloc=&output=embed"
                            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>

                </div>

            </div>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/partials/public_footer.php'; ?>