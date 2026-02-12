<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FGKIRS - Federação Gaúcha de Karatê Interestilos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Oswald:wght@500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .gradient-rs {
            background: linear-gradient(135deg, #2D7A2D 0%, #E11D48 50%, #FCD34D 100%);
        }

        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-slate-900 text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <img src="/assets/images/logo-fgkirs.png" alt="FGKIRS" class="h-12">
                    <span class="hidden md:block text-sm font-medium">Federação Gaúcha de Karatê Interestilos</span>
                </div>
                <a href="/login"
                    class="bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                    Área Administrativa
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="gradient-rs text-white py-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-20"></div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <h1 class="text-6xl md:text-7xl font-bold mb-6 drop-shadow-lg">
                FGKIRS
            </h1>
            <p class="text-2xl md:text-3xl mb-8 font-light">
                Federação Gaúcha de Karatê Interestilos
            </p>
            <p class="text-lg md:text-xl mb-10 max-w-2xl mx-auto">
                Unindo tradição, técnica e espírito esportivo em todo o Rio Grande do Sul
            </p>
            <a href="#dojos"
                class="inline-block bg-white text-rose-600 hover:bg-gray-100 font-bold py-4 px-10 rounded-lg text-lg transition shadow-xl">
                Seja um Federado
            </a>
        </div>
    </section>

    <!-- Federation Stats -->
    <section class="py-12 bg-white border-b-2 border-gray-200">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- Dojos Counter -->
                <div
                    class="text-center p-8 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border-2 border-green-600">
                    <div class="text-6xl font-black text-green-700 mb-2">
                        <?= $stats['dojos'] ?? 0 ?>
                    </div>
                    <p class="text-xl font-semibold text-green-800">Dojos Oficiais</p>
                </div>

                <!--Athletes Counter -->
                <div
                    class="text-center p-8 bg-gradient-to-br from-red-50 to-red-100 rounded-xl border-2 border-red-600">
                    <div class="text-6xl font-black text-red-700 mb-2">
                        <?= $stats['athletes'] ?? 0 ?>
                    </div>
                    <p class="text-xl font-semibold text-red-800">Atletas Ativos</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest News -->
    <?php if (!empty($news)): ?>
        <section class="py-16 bg-gray-50">
            <div class="container mx-auto px-4">
                <h2 class="text-4xl font-bold text-slate-900 text-center mb-12">Últimas Notícias</h2>

                <div class="grid md:grid-cols-3 gap-8">
                    <?php foreach ($news as $post): ?>
                        <article class="bg-white rounded-lg shadow-lg overflow-hidden card-hover">
                            <?php if ($post['featured_image']): ?>
                                <img src="<?= htmlspecialchars($post['featured_image']) ?>"
                                    alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-48 object-cover" loading="lazy">
                            <?php else: ?>
                                <div
                                    class="w-full h-48 bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center">
                                    <span class="text-white text-6xl">📰</span>
                                </div>
                            <?php endif; ?>

                            <div class="p-6">
                                <time class="text-sm text-gray-500">
                                    <?= date('d/m/Y', strtotime($post['published_at'] ?? $post['created_at'])) ?>
                                </time>
                                <h3 class="text-xl font-bold text-slate-900 mt-2 mb-3">
                                    <?= htmlspecialchars($post['title']) ?>
                                </h3>
                                <p class="text-gray-600 line-clamp-3">
                                    <?= htmlspecialchars(substr(strip_tags($post['content']), 0, 120)) ?>...
                                </p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Upcoming Events -->
    <?php if (!empty($events)): ?>
        <section class="py-16 bg-white">
            <div class="container mx-auto px-4">
                <h2 class="text-4xl font-bold text-slate-900 text-center mb-12">Próximos Eventos</h2>

                <div class="max-w-3xl mx-auto space-y-6">
                    <?php foreach ($events as $event): ?>
                        <div
                            class="flex items-start gap-6 p-6 bg-gradient-to-r from-yellow-50 to-yellow-100 rounded-lg border-l-4 border-yellow-600 shadow-md">
                            <?php if ($event['featured_image']): ?>
                                <img src="<?= htmlspecialchars($event['featured_image']) ?>"
                                    alt="<?= htmlspecialchars($event['title']) ?>" class="w-24 h-24 object-cover rounded-lg"
                                    loading="lazy">
                            <?php else: ?>
                                <div
                                    class="w-24 h-24 bg-yellow-600 text-white flex items-center justify-center rounded-lg text-4xl">
                                    📅
                                </div>
                            <?php endif; ?>

                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-slate-900 mb-2">
                                    <?= htmlspecialchars($event['title']) ?>
                                </h3>
                                <p class="text-gray-700 mb-1">
                                    <strong>Data:</strong> <?= date('d/m/Y', strtotime($event['event_date'])) ?>
                                </p>
                                <?php if ($event['event_location']): ?>
                                    <p class="text-gray-700">
                                        <strong>Local:</strong> <?= htmlspecialchars($event['event_location']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Dojo Locator -->
    <section id="dojos" class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-slate-900 text-center mb-12">Localizador de Dojos</h2>

            <?php if (!empty($dojos)): ?>
                <!-- Search Box -->
                <div class="max-w-2xl mx-auto mb-8">
                    <input type="text" id="dojoSearch" placeholder="Buscar por cidade ou nome do dojo..."
                        class="w-full px-6 py-4 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent text-lg">
                </div>

                <!-- Dojo List -->
                <div id="dojoList" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
                    <?php foreach ($dojos as $dojo): ?>
                        <div class="dojo-card bg-white rounded-lg shadow-lg p-6 border-l-4 border-rose-600 card-hover"
                            data-city="<?= strtolower($dojo['city'] ?? '') ?>"
                            data-name="<?= strtolower($dojo['name'] ?? '') ?>">
                            <h3 class="text-xl font-bold text-slate-900 mb-3">
                                <?= htmlspecialchars($dojo['name']) ?>
                            </h3>
                            <div class="space-y-2 text-gray-700">
                                <?php if ($dojo['sensei_name']): ?>
                                    <p><strong>Sensei:</strong> <?= htmlspecialchars($dojo['sensei_name']) ?></p>
                                <?php endif; ?>
                                <p><strong>Cidade:</strong> <?= htmlspecialchars($dojo['city']) ?> -
                                    <?= htmlspecialchars($dojo['state'] ?? 'RS') ?></p>
                                <?php if ($dojo['phone']): ?>
                                    <p><strong>Contato:</strong> <?= htmlspecialchars($dojo['phone']) ?></p>
                                <?php endif; ?>
                                <?php if ($dojo['website']): ?>
                                    <a href="<?= htmlspecialchars($dojo['website']) ?>" target="_blank"
                                        class="inline-block mt-2 text-rose-600 hover:text-rose-700 font-semibold">
                                        Visitar Site →
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <script>
                    // Dojo search functionality
                    const searchInput = document.getElementById('dojoSearch');
                    const dojoCards = document.querySelectorAll('.dojo-card');

                    searchInput.addEventListener('input', function () {
                        const searchTerm = this.value.toLowerCase();

                        dojoCards.forEach(card => {
                            const city = card.dataset.city;
                            const name = card.dataset.name;

                            if (city.includes(searchTerm) || name.includes(searchTerm)) {
                                card.style.display = 'block';
                            } else {
                                card.style.display = 'none';
                            }
                        });
                    });
                </script>
            <?php else: ?>
                <p class="text-center text-gray-600 text-lg">Nenhum dojo cadastrado no momento.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8 mb-8">
                <!-- Logo & About -->
                <div>
                    <img src="/assets/images/logo-fgkirs.png" alt="FGKIRS" class="h-16 mb-4">
                    <p class="text-gray-300 text-sm leading-relaxed">
                        A Federação Gaúcha de Karatê Interestilos une dojos e atletas em todo o Rio Grande do Sul,
                        promovendo o desenvolvimento técnico e o espírito esportivo do Karatê.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-bold mb-4">Links Rápidos</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="#dojos" class="hover:text-rose-400 transition">Dojos Oficiais</a></li>
                        <li><a href="/login" class="hover:text-rose-400 transition">Área Administrativa</a></li>
                        <li><a href="/fgkirs-admin" class="hover:text-rose-400 transition">Sistema de Gestão</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-lg font-bold mb-4">Contato</h4>
                    <p class="text-gray-300 text-sm mb-2">
                        Email: contato@fgkirs.com.br
                    </p>
                    <p class="text-gray-300 text-sm">
                        Rio Grande do Sul - Brasil
                    </p>
                </div>
            </div>

            <div class="border-t border-gray-700 pt-6 text-center text-gray-400 text-sm">
                <p>&copy; <?= date('Y') ?> FGKIRS - Federação Gaúcha de Karatê Interestilos. Todos os direitos
                    reservados.</p>
                <p class="mt-2">Sistema desenvolvido com PHP 8.3+ | MVC Architecture</p>
            </div>
        </div>
    </footer>
</body>

</html>