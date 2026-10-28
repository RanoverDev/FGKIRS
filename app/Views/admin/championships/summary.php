<?php
use Helpers\Auth;
use Helpers\Csrf;

$pageTitle = $championship['title'];
require_once __DIR__ . '/../layout/header.php';
require __DIR__ . '/partials/header.php';

$championshipId = (int) $championship['id'];
$dojoParam      = (Auth::isAdmin() && !empty($dojoId)) ? '?dojo_id=' . (int) $dojoId : '';
$e              = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''));

$statusStyle = ['draft' => 'bg-amber-100 text-amber-800', 'submitted' => 'bg-blue-100 text-blue-800', 'confirmed' => 'bg-green-100 text-green-800'];
$statusLabel = ['draft' => 'Rascunho', 'submitted' => 'Enviada', 'confirmed' => 'Confirmada'];

$entryCount = array_sum(array_map(static fn ($r) => count($r['entries']), $registrations));
$feeTotal   = array_sum(array_map(static fn ($r) => (float) ($r['fee_total'] ?? 0), $registrations));
$hasFee     = $championship['fee_first_entry'] !== null;
?>

<?php if (!empty($dojoId)): ?>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 flex-1">
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-2xl font-bold text-slate-900"><?= count($registrations) ?></p>
                <p class="text-xs uppercase tracking-wider text-slate-500 mt-1">Atletas</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-2xl font-bold text-slate-900"><?= (int) $entryCount ?></p>
                <p class="text-xs uppercase tracking-wider text-slate-500 mt-1">Inscrições</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-2xl font-bold text-slate-900"><?= count($teams) ?></p>
                <p class="text-xs uppercase tracking-wider text-slate-500 mt-1">Equipes</p>
            </div>
            <?php if ($hasFee): ?>
                <div class="bg-white rounded-xl border border-slate-200 p-4">
                    <p class="text-2xl font-bold text-slate-900">R$ <?= number_format($feeTotal, 2, ',', '.') ?></p>
                    <p class="text-xs uppercase tracking-wider text-slate-500 mt-1">Enviado</p>
                </div>
            <?php endif; ?>
        </div>

        <a href="/fgkirs-admin/championships/<?= $championshipId ?>/summary/print<?= $dojoParam ?>" target="_blank"
            class="inline-flex items-center justify-center border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2.5 px-5 rounded-lg transition shrink-0">
            Imprimir ficha / PDF
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Atleta</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Categorias</th>
                        <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wider">Situação</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($registrations)): ?>
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">Nenhum atleta inscrito ainda. Use a aba Atletas.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($registrations as $r): ?>
                        <tr class="hover:bg-slate-50 align-top">
                            <td class="px-5 py-3">
                                <p class="text-sm font-medium text-slate-900"><?= $e($r['full_name']) ?></p>
                                <p class="text-xs text-slate-500"><?= (int) $r['age_at_event'] ?> anos · <?= $e(\Helpers\Format::measure($r['declared_weight'], $r['declared_height'] ?? null)) ?></p>
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-700">
                                <?php foreach ($r['entries'] as $entry): ?>
                                    <p><?= $e($entry['category_name']) ?><?= $entry['is_override'] ? ' <em class="text-xs text-amber-700">(exceção)</em>' : '' ?></p>
                                <?php endforeach; ?>
                            </td>
                            <td class="px-5 py-3 text-center whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-bold <?= $statusStyle[$r['status']] ?? '' ?>">
                                    <?= $statusLabel[$r['status']] ?? $e($r['status']) ?>
                                </span>
                                <?php if ($r['fee_total'] !== null): ?>
                                    <p class="text-xs text-slate-500 mt-1">R$ <?= number_format((float) $r['fee_total'], 2, ',', '.') ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <?php if ($canWrite && ($r['status'] !== 'confirmed' || Auth::isAdmin())): ?>
                                    <form method="POST" action="/fgkirs-admin/championships/<?= $championshipId ?>/registrations/<?= (int) $r['id'] ?>/cancel" class="inline"
                                        onsubmit="return confirm('Cancelar a inscrição deste atleta?');">
                                        <?= Csrf::field() ?>
                                        <?php if (Auth::isAdmin()): ?><input type="hidden" name="dojo_id" value="<?= (int) $dojoId ?>"><?php endif; ?>
                                        <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-semibold">Cancelar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($teams)): ?>
        <h2 class="text-lg font-bold text-slate-900 mb-3">Equipes</h2>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 divide-y divide-slate-100">
            <?php foreach ($teams as $team): ?>
                <div class="px-5 py-3 text-sm">
                    <p class="font-medium text-slate-900"><?= $e($team['category_name']) ?> <span class="text-xs text-slate-500">· <?= $statusLabel[$team['status']] ?? $e($team['status']) ?></span></p>
                    <p class="text-slate-600"><?= $e(implode(', ', array_column($team['members'], 'name'))) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
