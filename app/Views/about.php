<?php require_once __DIR__ . '/partials/public_header.php'; ?>

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

        <?php
        // Color maps
        $borderClass = [
            'red'    => 'border-rs-red',
            'yellow' => 'border-rs-yellow',
            'green'  => 'border-rs-green',
            'slate'  => 'border-slate-600',
        ];
        $textClass = [
            'red'    => 'text-rs-red',
            'yellow' => 'text-amber-600',
            'green'  => 'text-green-700',
            'slate'  => 'text-slate-500',
        ];
        $avatarBorder = [
            'red'    => 'border-rs-red',
            'yellow' => 'border-rs-yellow',
            'green'  => 'border-slate-300',
            'slate'  => 'border-slate-300',
        ];

        $primary   = array_filter($board ?? [], fn($p) => $p['tier'] === 'primary');
        $secondary = array_filter($board ?? [], fn($p) => $p['tier'] === 'secondary');
        $lists     = array_filter($board ?? [], fn($p) => $p['tier'] === 'list');

        // Helper: render member info line
        $memberInfo = function(array $m): string {
            $parts = [];
            if ($m['graduation']) $parts[] = htmlspecialchars($m['graduation']);
            if ($m['city'])       $parts[] = htmlspecialchars($m['city']);
            return implode(' &bull; ', $parts);
        };

        // Helper: avatar img or placeholder
        $avatar = function(array $m, string $size, string $border): string {
            if (!empty($m['photo'])) {
                return '<img src="/uploads/users/' . htmlspecialchars($m['photo']) . '" alt="" class="w-full h-full object-cover">';
            }
            return '<div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>';
        };
        ?>

        <?php if (!empty($primary)): ?>
        <!-- Diretoria Principal -->
        <div class="flex flex-col items-center mb-4">
            <?php $primArr = array_values($primary); ?>
            <?php foreach ($primArr as $i => $pos):
                $bc  = $borderClass[$pos['color']] ?? 'border-slate-600';
                $tc  = $textClass[$pos['color']] ?? 'text-slate-500';
                $ab  = $avatarBorder[$pos['color']] ?? 'border-slate-300';
                $isFirst = $i === 0;
                $cardSize = $isFirst ? 'w-72 p-6' : 'w-72 p-5';
                $imgSize  = $isFirst ? 'w-28 h-28 mb-4 border-2' : 'w-20 h-20 mb-3 border-2';
            ?>
                <div class="bg-white border-t-4 <?= $bc ?> rounded-xl shadow<?= $isFirst ? '-lg' : '-md' ?> <?= $cardSize ?> text-center z-10 relative hover:shadow-xl transition-shadow">
                    <?php foreach ($pos['members'] as $m): ?>
                        <div class="<?= $imgSize ?> rounded-full mx-auto overflow-hidden bg-slate-100 <?= $ab ?> shadow">
                            <?= $avatar($m, $imgSize, $ab) ?>
                        </div>
                        <span class="inline-block text-xs font-semibold uppercase tracking-widest <?= $tc ?> mb-1">
                            <?= htmlspecialchars($pos['title']) ?>
                        </span>
                        <h3 class="font-bold text-slate-900 text-base leading-tight">
                            <?= htmlspecialchars($m['name'] ?? '—') ?>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1"><?= $memberInfo($m) ?></p>
                    <?php endforeach; ?>
                    <?php if (empty($pos['members'])): ?>
                        <div class="<?= $imgSize ?> rounded-full mx-auto overflow-hidden bg-slate-100 border-2 border-slate-200 shadow">
                            <?= $avatar([], $imgSize, '') ?>
                        </div>
                        <span class="inline-block text-xs font-semibold uppercase tracking-widest <?= $tc ?> mb-1">
                            <?= htmlspecialchars($pos['title']) ?>
                        </span>
                        <h3 class="font-bold text-slate-400 text-base leading-tight italic">Não atribuído</h3>
                    <?php endif; ?>
                </div>

                <?php if ($i < count($primArr) - 1): ?>
                    <div class="w-px h-8 bg-slate-200"></div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="w-px h-8 bg-slate-200"></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($secondary)): ?>
        <!-- Diretores em grid -->
        <div class="max-w-5xl mx-auto">
            <div class="relative mb-2">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[80%] h-px bg-slate-200 hidden sm:block"></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 pt-8">
                <?php foreach ($secondary as $pos):
                    $bc = $borderClass[$pos['color']] ?? 'border-slate-600';
                    $tc = $textClass[$pos['color']] ?? 'text-slate-500';
                    $ab = $avatarBorder[$pos['color']] ?? 'border-slate-300';
                ?>
                    <div class="bg-white border-t-4 <?= $bc ?> rounded-xl shadow p-5 text-center hover:shadow-md transition-shadow">
                        <?php if (!empty($pos['members'])): ?>
                            <?php if (count($pos['members']) === 1):
                                $m = $pos['members'][0]; ?>
                                <div class="w-20 h-20 rounded-full mx-auto mb-3 overflow-hidden bg-slate-100 border-2 <?= $ab ?> shadow">
                                    <?= $avatar($m, 'w-20 h-20', $ab) ?>
                                </div>
                                <span class="inline-block text-xs font-semibold uppercase tracking-widest <?= $tc ?> mb-1">
                                    <?= htmlspecialchars($pos['title']) ?>
                                </span>
                                <h3 class="font-bold text-slate-900 text-sm leading-tight">
                                    <?= htmlspecialchars($m['name'] ?? '—') ?>
                                </h3>
                                <p class="text-xs text-slate-400 mt-1"><?= $memberInfo($m) ?></p>
                            <?php else: ?>
                                <span class="inline-block text-xs font-semibold uppercase tracking-widest <?= $tc ?> mb-4">
                                    <?= htmlspecialchars($pos['title']) ?>
                                </span>
                                <div class="flex flex-wrap justify-center gap-5">
                                    <?php foreach ($pos['members'] as $m): ?>
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 rounded-full overflow-hidden bg-slate-100 border-2 <?= $ab ?> shadow mb-2">
                                                <?= $avatar($m, 'w-16 h-16', $ab) ?>
                                            </div>
                                            <p class="font-bold text-slate-900 text-xs leading-tight text-center max-w-[90px]">
                                                <?= htmlspecialchars($m['name'] ?? '—') ?>
                                            </p>
                                            <p class="text-[11px] text-slate-400 mt-0.5 text-center"><?= $memberInfo($m) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="w-20 h-20 rounded-full mx-auto mb-3 overflow-hidden bg-slate-100 border-2 border-slate-200 shadow">
                                <?= $avatar([], 'w-20 h-20', '') ?>
                            </div>
                            <span class="inline-block text-xs font-semibold uppercase tracking-widest <?= $tc ?> mb-1">
                                <?= htmlspecialchars($pos['title']) ?>
                            </span>
                            <h3 class="font-bold text-slate-400 text-sm leading-tight italic">Não atribuído</h3>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($lists)):
            // Group list positions by section
            $sections = [];
            foreach ($lists as $pos) {
                $sec = $pos['section'] ?: $pos['title'];
                $sections[$sec][] = $pos;
            }
        ?>
        <!-- Conselhos e Comissões -->
        <div class="max-w-5xl mx-auto mt-12 grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($sections as $sectionName => $sectionPositions): ?>
                <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                    <h4 class="text-sm font-bold uppercase tracking-widest text-slate-600 mb-4 border-b border-slate-200 pb-2">
                        <?= htmlspecialchars($sectionName) ?>
                    </h4>
                    <ul class="space-y-3">
                        <?php foreach ($sectionPositions as $pos):
                            foreach ($pos['members'] as $m): ?>
                                <li class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full overflow-hidden bg-slate-200 shrink-0">
                                        <?php if (!empty($m['photo'])): ?>
                                            <img src="/uploads/users/<?= htmlspecialchars($m['photo']) ?>" alt="" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-slate-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">
                                            <?= htmlspecialchars($m['name'] ?? '—') ?>
                                        </p>
                                        <p class="text-xs text-slate-500"><?= $memberInfo($m) ?></p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        <?php
                        $anyMember = false;
                        foreach ($sectionPositions as $p) { if (!empty($p['members'])) { $anyMember = true; break; } }
                        if (!$anyMember): ?>
                            <li class="text-sm text-slate-400 italic">Não atribuído</li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/partials/public_footer.php'; ?>
