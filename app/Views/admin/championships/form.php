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

    <?php
    /** Novo evento nasce com tudo marcado; o Presidente desmarca o que nao vale. */
    $enabled = $enabledIds === null
        ? array_flip(array_map('intval', array_column($catalog, 'id')))
        : array_flip($enabledIds);

    $sections = [];
    foreach ($catalog as $category) {
        $key = $category['entry_type'] . ':' . $category['modality'];
        $sections[$key][] = $category;
    }

    $sectionTitles = [
        'individual:kata'   => 'Kata Individual',
        'individual:kumite' => 'Kumite Individual',
        'team:kata'         => 'Kata Equipe',
        'team:kumite'       => 'Kumite Equipe (Revezamento)',
    ];
    ?>

    <div id="categorias" class="pt-5 mb-6 border-t border-slate-100">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
            <h2 class="text-lg font-bold text-slate-900">Categorias do evento</h2>
            <span class="text-sm text-slate-600">
                <strong id="category-count">0</strong> de <?= count($catalog) ?> liberadas
            </span>
        </div>
        <p class="text-xs text-slate-500 mb-4">
            Só as categorias marcadas aparecem para os senseis na inscrição de atletas e de equipes.
            O cruzamento por idade, sexo, graduação e peso continua valendo dentro delas.
            Categoria que já tem inscritos não pode ser retirada.
        </p>

        <?php if (empty($catalog)): ?>
            <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
                O catálogo de categorias está vazio.
                <a href="/fgkirs-admin/categories" class="font-semibold underline">Abrir categorias de disputa</a>
                e use "Restaurar catálogo padrão".
            </div>
        <?php endif; ?>

        <div class="space-y-3">
            <?php foreach ($sectionTitles as $key => $sectionTitle): ?>
                <?php if (empty($sections[$key])) {
                    continue;
                } ?>
                <details class="border border-slate-200 rounded-lg overflow-hidden" data-category-group>
                    <summary class="px-4 py-3 bg-slate-50 cursor-pointer flex flex-wrap items-center justify-between gap-2">
                        <span class="font-semibold text-slate-800 text-sm"><?= $sectionTitle ?></span>
                        <span class="text-xs text-slate-500" data-group-count></span>
                    </summary>
                    <div class="px-4 py-3">
                        <div class="flex gap-3 mb-3 text-xs font-semibold">
                            <button type="button" data-group-all class="text-red-700 hover:text-red-800">Marcar todas</button>
                            <button type="button" data-group-none class="text-slate-500 hover:text-slate-800">Limpar</button>
                        </div>
                        <div class="grid md:grid-cols-2 gap-x-6 gap-y-1.5">
                            <?php foreach ($sections[$key] as $category): ?>
                                <label class="flex items-start gap-2 text-sm text-slate-700 py-0.5 cursor-pointer">
                                    <input type="checkbox" name="category_ids[]" value="<?= (int) $category['id'] ?>"
                                        <?= isset($enabled[(int) $category['id']]) ? 'checked' : '' ?>
                                        class="mt-0.5 rounded border-slate-300 text-red-700 focus:ring-red-700">
                                    <span><?= htmlspecialchars($category['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </details>
            <?php endforeach; ?>
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

<script>
    (function () {
        const boxes = () => document.querySelectorAll('input[name="category_ids[]"]');
        const total = document.getElementById('category-count');

        function refresh() {
            total.textContent = [...boxes()].filter(box => box.checked).length;

            document.querySelectorAll('[data-category-group]').forEach(group => {
                const inputs = group.querySelectorAll('input[type="checkbox"]');
                const checked = [...inputs].filter(box => box.checked).length;
                group.querySelector('[data-group-count]').textContent = `${checked} de ${inputs.length}`;
            });
        }

        document.querySelectorAll('[data-category-group]').forEach(group => {
            const setAll = value => {
                group.querySelectorAll('input[type="checkbox"]').forEach(box => { box.checked = value; });
                refresh();
            };
            group.querySelector('[data-group-all]').addEventListener('click', () => setAll(true));
            group.querySelector('[data-group-none]').addEventListener('click', () => setAll(false));
        });

        boxes().forEach(box => box.addEventListener('change', refresh));
        refresh();

        if (location.hash === '#categorias') {
            document.querySelectorAll('[data-category-group]').forEach(group => { group.open = true; });
        }
    })();
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
