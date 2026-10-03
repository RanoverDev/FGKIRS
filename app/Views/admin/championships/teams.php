<?php
use Helpers\Auth;
use Helpers\Csrf;

$pageTitle = $championship['title'];
require_once __DIR__ . '/../layout/header.php';
require __DIR__ . '/partials/header.php';

$championshipId = (int) $championship['id'];
$dojoParam      = (Auth::isAdmin() && !empty($dojoId)) ? '?dojo_id=' . (int) $dojoId : '';
$deleteQuery    = '?token=' . urlencode(Csrf::token())
    . (!empty($dojoParam) ? '&dojo_id=' . (int) $dojoId : '');

$byModality = ['kata' => [], 'kumite' => []];
foreach ($categories as $category) {
    $byModality[$category['modality']][] = $category;
}

$modalityTitles = [
    'kata'   => 'Kata Equipe',
    'kumite' => 'Kumite Equipe (Revezamento)',
];
?>

<?php if (!empty($dojoId)): ?>

    <div class="rounded-lg bg-slate-50 border border-slate-200 text-slate-600 px-4 py-3 text-sm mb-6">
        Os integrantes saem da aba <strong>Atletas</strong>: cadastre o atleta lá antes de montar a equipe.
        A lista de cada categoria já traz só quem tem idade e sexo compatíveis.
    </div>

    <?php foreach ($modalityTitles as $modality => $modalityTitle): ?>
        <?php if (empty($byModality[$modality])) {
            continue;
        } ?>

        <h2 class="text-lg font-bold text-slate-900 mb-3"><?= $modalityTitle ?></h2>

        <div class="grid md:grid-cols-2 gap-4 mb-8">
            <?php foreach ($byModality[$modality] as $category): ?>
                <?php
                $categoryId = (int) $category['id'];
                $total      = (int) ($counts[$categoryId] ?? 0);
                $teamsHere  = $byCategory[$categoryId] ?? [];
                ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-slate-900 leading-snug">
                                <?= htmlspecialchars($category['name']) ?>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">
                                <?= $total ?> equipe<?= $total === 1 ? '' : 's' ?> inscrita<?= $total === 1 ? '' : 's' ?>
                                <?php if ($category['team_size'] !== null): ?>
                                    · <?= (int) $category['team_size'] ?> integrantes cada
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center justify-center min-w-[2rem] h-7 px-2 rounded-full bg-slate-900 text-white text-xs font-bold shrink-0">
                            <?= $total ?>
                        </span>
                    </div>

                    <?php if ($teamsHere): ?>
                        <ul class="divide-y divide-slate-100">
                            <?php foreach ($teamsHere as $index => $team): ?>
                                <li class="px-5 py-3 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-800">
                                            <?= htmlspecialchars($team['name'] ?: 'Equipe ' . chr(65 + $index)) ?>
                                        </p>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            <?= htmlspecialchars(implode(', ', array_column($team['members'], 'name'))) ?>
                                        </p>
                                    </div>
                                    <?php if ($canWrite): ?>
                                        <a href="/fgkirs-admin/championships/<?= $championshipId ?>/teams/delete/<?= (int) $team['id'] ?><?= $deleteQuery ?>"
                                            data-confirm-delete
                                            class="text-xs font-semibold text-slate-400 hover:text-red-600 shrink-0 transition">
                                            Remover
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ($canWrite): ?>
                        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200">
                            <button type="button" data-open-team="<?= $categoryId ?>"
                                data-category-name="<?= htmlspecialchars($category['name']) ?>"
                                class="text-sm font-semibold text-red-700 hover:text-red-800 transition">
                                + Inscrever equipe
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <?php if (empty($categories)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-10 text-center">
            <p class="text-slate-500">
                Nenhuma categoria de equipe foi liberada para este evento.
                <?php if (Auth::isAdmin()): ?>
                    <a href="/fgkirs-admin/championships/edit/<?= $championshipId ?>#categorias" class="text-red-700 font-semibold">
                        Configurar categorias do evento →
                    </a>
                <?php else: ?>
                    Avise a federação.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Modal de montagem de equipe -->
    <div id="team-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
        style="background:rgba(15,23,42,.6)">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <div class="min-w-0">
                    <h3 class="font-bold text-slate-900">Inscrever equipe</h3>
                    <p id="team-category-name" class="text-xs text-slate-500 mt-0.5"></p>
                </div>
                <button type="button" id="team-close" class="text-slate-400 hover:text-slate-700 text-xl leading-none">✕</button>
            </div>

            <form method="POST" action="/fgkirs-admin/championships/<?= $championshipId ?>/teams/store"
                class="flex flex-col min-h-0">
                <?= Csrf::field() ?>
                <input type="hidden" name="dojo_id" value="<?= (int) $dojoId ?>">
                <input type="hidden" name="category_id" id="team-category-id">

                <div class="px-5 pt-4">
                    <label for="team-name" class="block text-sm font-medium text-slate-700 mb-2">
                        Nome da equipe
                    </label>
                    <input type="text" id="team-name" name="name" maxlength="120" placeholder="Ex: Equipe A (opcional)"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                </div>

                <p id="team-counter" class="px-5 pt-4 text-sm font-medium text-slate-700"></p>
                <div id="team-body" class="p-5 pt-2 overflow-y-auto space-y-2"></div>

                <div class="px-5 py-4 border-t border-slate-200 flex gap-3">
                    <button type="submit" id="team-submit" disabled
                        class="bg-red-700 hover:bg-red-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-semibold py-2.5 px-6 rounded-lg transition">
                        Inscrever equipe
                    </button>
                    <button type="button" id="team-cancel"
                        class="border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2.5 px-6 rounded-lg transition">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const championshipId = <?= $championshipId ?>;
            const dojoQuery = '<?= $dojoParam ?>';

            const modal = document.getElementById('team-modal');
            const body = document.getElementById('team-body');
            const counter = document.getElementById('team-counter');
            const categoryName = document.getElementById('team-category-name');
            const categoryId = document.getElementById('team-category-id');
            const submit = document.getElementById('team-submit');
            let requiredSize = null;

            function closeModal() {
                modal.classList.add('hidden');
                body.innerHTML = '';
                counter.textContent = '';
                submit.disabled = true;
                document.getElementById('team-name').value = '';
            }

            document.getElementById('team-close')?.addEventListener('click', closeModal);
            document.getElementById('team-cancel')?.addEventListener('click', closeModal);
            modal?.addEventListener('click', event => {
                if (event.target === modal) closeModal();
            });

            document.querySelectorAll('[data-open-team]').forEach(button => {
                button.addEventListener('click', async () => {
                    const id = button.dataset.openTeam;

                    categoryId.value = id;
                    categoryName.textContent = button.dataset.categoryName;
                    body.innerHTML = '<p class="text-sm text-slate-500">Buscando atletas compatíveis…</p>';
                    modal.classList.remove('hidden');

                    try {
                        const response = await fetch(
                            `/fgkirs-admin/championships/${championshipId}/teams/eligible/${id}${dojoQuery}`
                        );
                        renderAthletes(await response.json());
                    } catch (e) {
                        body.innerHTML = '<p class="text-sm text-red-700">Não foi possível carregar os atletas.</p>';
                    }
                });
            });

            function renderAthletes(data) {
                requiredSize = data.team_size;

                if (!data.athletes.length) {
                    body.innerHTML = '<p class="text-sm text-slate-600">'
                        + 'Nenhum atleta do dojo tem idade e sexo compatíveis com esta categoria. '
                        + 'Cadastre os atletas na aba Atletas primeiro.</p>';
                    return;
                }

                body.innerHTML = data.athletes.map(athlete => `
                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer hover:border-red-300 hover:bg-red-50/40 transition">
                        <input type="checkbox" name="members[]" value="${athlete.id}"
                            class="rounded border-slate-300 text-red-700 focus:ring-red-700">
                        <span class="text-sm text-slate-800">
                            ${escapeHtml(athlete.name)}
                            <span class="text-xs text-slate-500">· ${athlete.age} anos</span>
                        </span>
                    </label>
                `).join('');

                body.querySelectorAll('input[name="members[]"]').forEach(box => {
                    box.addEventListener('change', updateCounter);
                });

                updateCounter();
            }

            function updateCounter() {
                const checked = body.querySelectorAll('input[name="members[]"]:checked').length;

                if (requiredSize) {
                    counter.textContent = `${checked} de ${requiredSize} integrantes selecionados`;
                    submit.disabled = checked !== requiredSize;
                } else {
                    counter.textContent = `${checked} integrante(s) selecionado(s)`;
                    submit.disabled = checked < 2;
                }
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text ?? '';
                return div.innerHTML;
            }
        })();
    </script>

<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
