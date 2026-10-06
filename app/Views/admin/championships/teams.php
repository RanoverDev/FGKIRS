<?php
use Helpers\Auth;
use Helpers\Csrf;

$pageTitle = $championship['title'];
require_once __DIR__ . '/../layout/header.php';
require __DIR__ . '/partials/header.php';

$championshipId = (int) $championship['id'];
$e              = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''));
$statusLabel    = ['draft' => 'Rascunho', 'submitted' => 'Enviada', 'confirmed' => 'Confirmada'];

$teamsByCategory = [];
foreach ($teams as $team) {
    $teamsByCategory[(int) $team['event_category_id']][] = $team;
}
?>

<?php if (!empty($dojoId)): ?>

    <p class="text-sm text-slate-600 mb-5">
        Só entram atletas <strong>já inscritos no evento</strong> (aba Atletas) que se encaixam na categoria da equipe.
    </p>

    <?php if (empty($categories)): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-500">Este evento não tem categorias de equipe abertas.</div>
    <?php endif; ?>

    <div class="space-y-5">
        <?php foreach ($categories as $category): ?>
            <?php $candidates = $eligible[$category->id] ?? []; ?>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-900 text-white px-5 py-3 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-bold text-sm"><?= $e($category->name) ?></h3>
                    <span class="text-xs text-slate-300">
                        <?= (int) $category->teamSize ?> titulares<?= $category->teamReserves ? ' + até ' . (int) $category->teamReserves . ' reserva(s)' : '' ?>
                    </span>
                </div>

                <?php foreach ($teamsByCategory[$category->id] ?? [] as $team): ?>
                    <div class="px-5 py-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 text-sm">
                        <div>
                            <p class="font-medium text-slate-900">
                                <?= $e($team['name'] ?: 'Equipe') ?>
                                <span class="text-xs font-normal text-slate-500">· <?= $statusLabel[$team['status']] ?? $e($team['status']) ?></span>
                            </p>
                            <p class="text-slate-600">
                                <?php foreach ($team['members'] as $i => $m): ?>
                                    <?= $i ? ', ' : '' ?><?= $e($m['name']) ?><?= $m['role'] === 'reserve' ? ' <em class="text-xs">(reserva)</em>' : '' ?>
                                <?php endforeach; ?>
                            </p>
                        </div>
                        <?php if ($canWrite && ($team['status'] !== 'confirmed' || Auth::isAdmin())): ?>
                            <form method="POST" action="/fgkirs-admin/championships/<?= $championshipId ?>/teams/<?= (int) $team['id'] ?>/cancel"
                                onsubmit="return confirm('Cancelar esta equipe?');">
                                <?= Csrf::field() ?>
                                <?php if (Auth::isAdmin()): ?><input type="hidden" name="dojo_id" value="<?= (int) $dojoId ?>"><?php endif; ?>
                                <button type="submit" class="text-red-600 hover:text-red-700 font-semibold">Cancelar equipe</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if ($canWrite && $category->status === 'open'): ?>
                    <details class="px-5 py-3">
                        <summary class="cursor-pointer text-sm font-semibold text-red-700 hover:text-red-800">+ Montar equipe</summary>

                        <?php if (count($candidates) < (int) $category->teamSize): ?>
                            <p class="text-sm text-slate-500 mt-3">
                                Faltam atletas inscritos que se encaixem nesta categoria
                                (<?= count($candidates) ?> disponíveis, são necessários <?= (int) $category->teamSize ?>).
                            </p>
                        <?php else: ?>
                            <form method="POST" action="/fgkirs-admin/championships/<?= $championshipId ?>/teams/create" class="mt-3">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="category_id" value="<?= (int) $category->id ?>">
                                <?php if (Auth::isAdmin()): ?><input type="hidden" name="dojo_id" value="<?= (int) $dojoId ?>"><?php endif; ?>

                                <input type="text" name="name" maxlength="120" placeholder="Nome da equipe (opcional)"
                                    class="w-full sm:w-80 px-3 py-2 border border-slate-300 rounded-lg text-sm mb-3 focus:ring-2 focus:ring-red-700 focus:border-transparent">

                                <div class="grid sm:grid-cols-2 gap-x-6 gap-y-1">
                                    <?php foreach ($candidates as $c): ?>
                                        <div class="flex items-center justify-between gap-3 text-sm py-1 border-b border-slate-50">
                                            <span class="text-slate-800"><?= $e($c['full_name']) ?> <span class="text-xs text-slate-500">(<?= (int) $c['age_at_event'] ?>)</span></span>
                                            <span class="flex gap-3 text-xs text-slate-600">
                                                <label class="flex items-center gap-1"><input type="checkbox" name="starters[]" value="<?= (int) $c['id'] ?>" class="rounded border-slate-300 text-red-700"> titular</label>
                                                <?php if ($category->teamReserves): ?>
                                                    <label class="flex items-center gap-1"><input type="checkbox" name="reserves[]" value="<?= (int) $c['id'] ?>" class="rounded border-slate-300 text-slate-500"> reserva</label>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button type="submit" class="mt-4 bg-red-700 hover:bg-red-800 text-white text-sm font-semibold py-2 px-5 rounded-lg transition">Criar equipe</button>
                            </form>
                        <?php endif; ?>
                    </details>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
