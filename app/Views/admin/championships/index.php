<?php
use Helpers\Auth;
use Helpers\Csrf;
use Models\Championship;

$pageTitle = 'Eventos';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Eventos</h1>
        <p class="text-sm text-slate-500 mt-1">Campeonatos de Kata e Kumite da federação</p>
    </div>
    <?php if (Auth::isAdmin()): ?>
        <a href="/fgkirs-admin/championships/create"
            class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            + Novo Evento
        </a>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/flash.php'; ?>

<?php if (empty($championships)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
        <p class="text-slate-500">
            <?= Auth::isAdmin()
                ? 'Nenhum evento criado ainda. Clique em “Novo Evento” para começar.'
                : 'Nenhum evento com inscrições disponível no momento.' ?>
        </p>
    </div>
<?php else: ?>
    <div class="grid gap-4">
        <?php foreach ($championships as $championship): ?>
            <?php
            $state      = Championship::registrationState($championship);
            $stateLabel = Championship::REGISTRATION_STATES[$state];
            $stateStyle = match ($state) {
                'open'      => 'bg-green-100 text-green-800',
                'scheduled' => 'bg-amber-100 text-amber-800',
                'draft'     => 'bg-slate-200 text-slate-600',
                default     => 'bg-slate-200 text-slate-600',
            };
            ?>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h2 class="text-lg font-bold text-slate-900">
                                <?= htmlspecialchars($championship['title']) ?>
                            </h2>
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-semibold <?= $stateStyle ?>">
                                <?= htmlspecialchars($stateLabel) ?>
                            </span>
                        </div>
                        <p class="text-sm text-slate-500">
                            <?= date('d/m/Y', strtotime($championship['event_date'])) ?>
                            <?php if (!empty($championship['location'])): ?>
                                · <?= htmlspecialchars($championship['location']) ?>
                            <?php endif; ?>
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            Inscrições: <?= date('d/m/Y', strtotime($championship['registration_start'])) ?>
                            a <?= date('d/m/Y', strtotime($championship['registration_end'])) ?>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <a href="/fgkirs-admin/championships/<?= (int) $championship['id'] ?>/athletes"
                            class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold py-2 px-4 rounded-lg transition">
                            Gerenciar inscrições
                        </a>

                        <?php if (Auth::isAdmin()): ?>
                            <a href="/fgkirs-admin/championships/<?= (int) $championship['id'] ?>/registrations"
                                class="border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold py-2 px-4 rounded-lg transition">
                                Inscritos
                            </a>
                            <a href="/fgkirs-admin/championships/edit/<?= (int) $championship['id'] ?>"
                                class="text-blue-600 hover:text-blue-700 text-sm font-semibold py-2 px-3 transition">
                                Editar
                            </a>
                            <a href="/fgkirs-admin/championships/delete/<?= (int) $championship['id'] ?>?token=<?= urlencode(Csrf::token()) ?>"
                                data-confirm-delete
                                class="text-red-600 hover:text-red-700 text-sm font-semibold py-2 px-3 transition">
                                Excluir
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
