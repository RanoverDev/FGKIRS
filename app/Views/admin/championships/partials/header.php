<?php
/**
 * Cabecalho compartilhado das abas de inscricao: identidade do evento, estado
 * do prazo, seletor de dojo (so para o Presidente) e navegacao das abas.
 */

use Helpers\Auth;
use Models\Championship;

$state      = Championship::registrationState($championship);
$stateLabel = Championship::REGISTRATION_STATES[$state];
$dojoQuery  = (Auth::isAdmin() && !empty($dojoId)) ? '?dojo_id=' . (int) $dojoId : '';

$stateStyle = match ($state) {
    'open'      => 'bg-green-100 text-green-800 border-green-200',
    'scheduled' => 'bg-amber-100 text-amber-800 border-amber-200',
    default     => 'bg-slate-200 text-slate-700 border-slate-300',
};

$tabs = [
    'athletes' => 'Atletas',
    'teams'    => 'Equipes',
    'referees' => 'Árbitros',
    'summary'  => 'Inscritos',
];
?>

<div class="mb-6">
    <a href="/fgkirs-admin/championships" class="text-sm text-slate-500 hover:text-red-700 transition">
        ← Voltar para eventos
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-slate-900 leading-tight">
                <?= htmlspecialchars($championship['title']) ?>
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                <?= date('d/m/Y', strtotime($championship['event_date'])) ?>
                <?php if (!empty($championship['location'])): ?>
                    · <?= htmlspecialchars($championship['location']) ?>
                <?php endif; ?>
            </p>
            <p class="text-xs text-slate-500 mt-2">
                Inscrições de <?= date('d/m/Y H:i', strtotime($championship['registration_start'])) ?>
                até <?= date('d/m/Y H:i', strtotime($championship['registration_end'])) ?>
            </p>
        </div>

        <div class="flex flex-col items-start lg:items-end gap-2 shrink-0">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border <?= $stateStyle ?>">
                <?= htmlspecialchars($stateLabel) ?>
            </span>

            <?php if (!empty($dojo)): ?>
                <span class="text-sm font-semibold text-slate-700">
                    <?= htmlspecialchars($dojo['name']) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (Auth::isAdmin()): ?>
        <form method="GET" class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center gap-3">
            <label for="dojo_id" class="text-sm font-medium text-slate-700">Operando como dojo:</label>
            <select name="dojo_id" id="dojo_id" onchange="this.form.submit()"
                class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-red-700 focus:border-transparent">
                <option value="">— Selecione um dojo —</option>
                <?php foreach ($dojos as $option): ?>
                    <option value="<?= (int) $option['id'] ?>" <?= (int) $option['id'] === (int) $dojoId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($option['name']) ?><?= $option['city'] ? ' – ' . htmlspecialchars($option['city']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a href="/fgkirs-admin/championships/<?= (int) $championship['id'] ?>/registrations"
                class="text-sm text-red-700 hover:text-red-800 font-medium">
                Ver consolidado da federação →
            </a>
        </form>
    <?php endif; ?>

    <?php if (Auth::isAdmin() && $state !== 'open'): ?>
        <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
            <strong>Atenção:</strong> as inscrições não estão abertas. Você está editando como Presidente —
            os senseis não conseguem alterar nada neste evento agora.
        </div>
    <?php endif; ?>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6 overflow-x-auto">
    <nav class="flex min-w-max">
        <?php foreach ($tabs as $slug => $label): ?>
            <a href="/fgkirs-admin/championships/<?= (int) $championship['id'] ?>/<?= $slug ?><?= $dojoQuery ?>"
                class="px-6 py-3.5 text-sm font-semibold border-b-2 transition whitespace-nowrap
                       <?= $activeTab === $slug
                           ? 'border-red-700 text-red-700'
                           : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>

<?php require __DIR__ . '/flash.php'; ?>

<?php if (empty($dojoId)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-10 text-center">
        <p class="text-slate-500">Selecione um dojo acima para ver e gerenciar as inscrições.</p>
    </div>
<?php endif; ?>
