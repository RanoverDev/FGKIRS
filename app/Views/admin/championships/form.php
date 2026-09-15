<?php
use Helpers\Csrf;
use Models\Championship;

$isEdit    = !empty($championship);
$pageTitle = $isEdit ? 'Editar Evento' : 'Novo Evento';
$action    = $isEdit
    ? '/fgkirs-admin/championships/update/' . (int) $championship['id']
    : '/fgkirs-admin/championships/store';

/** datetime-local exige o formato YYYY-MM-DDTHH:MM */
$toLocal = static fn(?string $value): string => $value ? date('Y-m-d\TH:i', strtotime($value)) : '';

require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <a href="/fgkirs-admin/championships" class="text-sm text-slate-500 hover:text-red-700 transition">
        ← Voltar para eventos
    </a>
</div>

<h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-6"><?= $pageTitle ?></h1>

<?php require __DIR__ . '/partials/flash.php'; ?>

<?php if ($isEdit && !empty($totals)): ?>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <?php
        $cards = [
            'Dojos'    => $totals['dojos'],
            'Atletas'  => $totals['athletes'],
            'Equipes'  => $totals['teams'],
            'Árbitros' => $totals['referees'],
        ];
        ?>
        <?php foreach ($cards as $label => $value): ?>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-2xl font-bold text-slate-900"><?= (int) $value ?></p>
                <p class="text-xs uppercase tracking-wider text-slate-500 mt-1"><?= $label ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <?= Csrf::field() ?>

    <div class="mb-5">
        <label for="title" class="block text-sm font-medium text-slate-700 mb-2">Título do evento *</label>
        <input type="text" id="title" name="title" required maxlength="255"
            value="<?= htmlspecialchars($championship['title'] ?? '') ?>"
            placeholder="Ex: 1º Campeonato Gaúcho Interestilos 2026"
            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
    </div>

    <div class="mb-5">
        <label for="description" class="block text-sm font-medium text-slate-700 mb-2">Descrição</label>
        <textarea id="description" name="description" rows="5"
            placeholder="Informações gerais, regulamento, horários…"
            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent"><?= htmlspecialchars($championship['description'] ?? '') ?></textarea>
    </div>

    <div class="grid md:grid-cols-2 gap-5 mb-5">
        <div>
            <label for="event_date" class="block text-sm font-medium text-slate-700 mb-2">Data da competição *</label>
            <input type="date" id="event_date" name="event_date" required
                value="<?= htmlspecialchars($championship['event_date'] ?? '') ?>"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            <p class="text-xs text-slate-500 mt-1.5">
                É esta data que define a idade dos atletas no enquadramento das categorias.
            </p>
        </div>

        <div>
            <label for="location" class="block text-sm font-medium text-slate-700 mb-2">Local</label>
            <input type="text" id="location" name="location" maxlength="255"
                value="<?= htmlspecialchars($championship['location'] ?? '') ?>"
                placeholder="Ex: Ginásio Municipal – Santa Maria/RS"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-5 mb-5">
        <div>
            <label for="registration_start" class="block text-sm font-medium text-slate-700 mb-2">
                Início das inscrições *
            </label>
            <input type="datetime-local" id="registration_start" name="registration_start" required
                value="<?= $toLocal($championship['registration_start'] ?? null) ?>"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div>
            <label for="registration_end" class="block text-sm font-medium text-slate-700 mb-2">
                Fim das inscrições *
            </label>
            <input type="datetime-local" id="registration_end" name="registration_end" required
                value="<?= $toLocal($championship['registration_end'] ?? null) ?>"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            <p class="text-xs text-slate-500 mt-1.5">
                Depois deste prazo os dojos não conseguem mais inscrever nem alterar atletas.
            </p>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-5 mb-6">
        <div>
            <label for="status" class="block text-sm font-medium text-slate-700 mb-2">Situação *</label>
            <select id="status" name="status"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                <?php foreach (Championship::STATUSES as $value => $label): ?>
                    <option value="<?= $value ?>" <?= ($championship['status'] ?? 'draft') === $value ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-slate-500 mt-1.5">
                Em <strong>Rascunho</strong> o evento fica invisível para os senseis.
            </p>
        </div>

        <div>
            <label for="post_id" class="block text-sm font-medium text-slate-700 mb-2">
                Matéria na Agenda de Eventos
            </label>
            <select id="post_id" name="post_id"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                <option value="">— Nenhuma —</option>
                <?php foreach ($agendaPosts as $post): ?>
                    <option value="<?= (int) $post['id'] ?>"
                        <?= (int) ($championship['post_id'] ?? 0) === (int) $post['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($post['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-slate-500 mt-1.5">Opcional — liga o campeonato à notícia publicada no site.</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 pt-5 border-t border-slate-100">
        <button type="submit"
            class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2.5 px-8 rounded-lg transition">
            <?= $isEdit ? 'Salvar alterações' : 'Criar evento' ?>
        </button>
        <a href="/fgkirs-admin/championships"
            class="border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2.5 px-8 rounded-lg transition">
            Cancelar
        </a>
        <?php if ($isEdit): ?>
            <a href="/fgkirs-admin/championships/<?= (int) $championship['id'] ?>/athletes"
                class="ml-auto text-slate-600 hover:text-red-700 font-semibold py-2.5 transition">
                Ir para as inscrições →
            </a>
        <?php endif; ?>
    </div>
</form>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
