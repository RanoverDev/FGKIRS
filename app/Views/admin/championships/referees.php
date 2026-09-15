<?php
use Helpers\Auth;
use Helpers\Csrf;
use Models\ChampionshipReferee;

$pageTitle = $championship['title'];
require_once __DIR__ . '/../layout/header.php';
require __DIR__ . '/partials/header.php';

$championshipId = (int) $championship['id'];
$dojoParam      = (Auth::isAdmin() && !empty($dojoId)) ? '?dojo_id=' . (int) $dojoId : '';
$deleteQuery    = '?token=' . urlencode(Csrf::token())
    . (!empty($dojoParam) ? '&dojo_id=' . (int) $dojoId : '');
?>

<?php if (!empty($dojoId)): ?>

    <?php if ($canWrite): ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
            <h2 class="font-bold text-slate-900 mb-4">Indicar árbitro</h2>

            <form method="POST" action="/fgkirs-admin/championships/<?= $championshipId ?>/referees/store">
                <?= Csrf::field() ?>
                <input type="hidden" name="dojo_id" value="<?= (int) $dojoId ?>">
                <input type="hidden" name="user_id" id="referee-user-id">

                <div class="flex flex-wrap gap-4 mb-4">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="referee_source" value="member" checked
                            class="text-red-700 focus:ring-red-700">
                        Buscar no meu dojo
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="referee_source" value="guest" class="text-red-700 focus:ring-red-700">
                        Cadastrar avulso
                    </label>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="md:col-span-2 relative">
                        <label for="referee-name" class="block text-sm font-medium text-slate-700 mb-2">Nome *</label>
                        <input type="text" id="referee-name" name="name" autocomplete="off" required maxlength="255"
                            placeholder="Digite ao menos 2 letras do nome…"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                        <div id="referee-results"
                            class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        </div>
                    </div>

                    <div>
                        <label for="referee-role" class="block text-sm font-medium text-slate-700 mb-2">Função *</label>
                        <select id="referee-role" name="role"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                            <?php foreach (ChampionshipReferee::ROLES as $value => $label): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="referee-qualification" class="block text-sm font-medium text-slate-700 mb-2">
                            Qualificação
                        </label>
                        <select id="referee-qualification" name="qualification"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                            <option value="">— Não informada —</option>
                            <?php foreach (ChampionshipReferee::QUALIFICATIONS as $qualification): ?>
                                <option value="<?= $qualification ?>"><?= $qualification ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="referee-notes" class="block text-sm font-medium text-slate-700 mb-2">Observação</label>
                        <input type="text" id="referee-notes" name="notes" maxlength="255"
                            placeholder="Ex: disponível apenas no turno da manhã"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    </div>
                </div>

                <button type="submit"
                    class="mt-5 bg-red-700 hover:bg-red-800 text-white font-semibold py-2.5 px-8 rounded-lg transition">
                    Indicar árbitro
                </button>
            </form>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h2 class="font-bold text-slate-900">Árbitros indicados</h2>
            <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full bg-slate-900 text-white text-xs font-bold">
                <?= count($referees) ?>
            </span>
        </div>

        <?php if (empty($referees)): ?>
            <p class="px-5 py-10 text-center text-sm text-slate-500">
                Nenhum árbitro indicado por este dojo.
            </p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($referees as $referee): ?>
                    <li class="px-5 py-4 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 text-sm">
                                <?= htmlspecialchars($referee['name']) ?>
                                <?php if (!$referee['user_id']): ?>
                                    <span class="ml-1 inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold uppercase">
                                        Avulso
                                    </span>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                <?= htmlspecialchars(ChampionshipReferee::ROLES[$referee['role']]) ?>
                                <?php if ($referee['qualification']): ?>
                                    · <?= htmlspecialchars($referee['qualification']) ?>
                                <?php endif; ?>
                            </p>
                            <?php if ($referee['notes']): ?>
                                <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars($referee['notes']) ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if ($canWrite): ?>
                            <a href="/fgkirs-admin/championships/<?= $championshipId ?>/referees/delete/<?= (int) $referee['id'] ?><?= $deleteQuery ?>"
                                data-confirm-delete
                                class="text-xs font-semibold text-slate-400 hover:text-red-600 shrink-0 transition">
                                Remover
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <script>
        (function () {
            const championshipId = <?= $championshipId ?>;
            const dojoQuery = '<?= $dojoParam ? '&' . ltrim($dojoParam, '?') : '' ?>';

            const input = document.getElementById('referee-name');
            const results = document.getElementById('referee-results');
            const hiddenId = document.getElementById('referee-user-id');
            let timer = null;

            function currentSource() {
                return document.querySelector('input[name="referee_source"]:checked')?.value ?? 'member';
            }

            document.querySelectorAll('input[name="referee_source"]').forEach(radio => {
                radio.addEventListener('change', () => {
                    hiddenId.value = '';
                    results.classList.add('hidden');
                    input.placeholder = currentSource() === 'member'
                        ? 'Digite ao menos 2 letras do nome…'
                        : 'Nome completo do árbitro';
                });
            });

            input?.addEventListener('input', () => {
                hiddenId.value = '';
                clearTimeout(timer);

                if (currentSource() !== 'member') {
                    results.classList.add('hidden');
                    return;
                }

                const term = input.value.trim();

                if (term.length < 2) {
                    results.classList.add('hidden');
                    return;
                }

                timer = setTimeout(async () => {
                    try {
                        const response = await fetch(
                            `/fgkirs-admin/championships/${championshipId}/referees/search?q=`
                            + encodeURIComponent(term) + dojoQuery
                        );
                        render(await response.json());
                    } catch (e) {
                        results.classList.add('hidden');
                    }
                }, 250);
            });

            function render(members) {
                if (!members.length) {
                    results.innerHTML = '<p class="px-4 py-3 text-sm text-slate-500">'
                        + 'Ninguém encontrado no seu dojo. Use “Cadastrar avulso” se ele não tem conta.</p>';
                    results.classList.remove('hidden');
                    return;
                }

                results.innerHTML = members.map(member => `
                    <button type="button" data-id="${member.id}" data-name="${escapeHtml(member.name)}"
                        class="w-full text-left px-4 py-2.5 hover:bg-slate-50 border-b border-slate-100 last:border-0">
                        <span class="block text-sm font-medium text-slate-800">${escapeHtml(member.name)}</span>
                        <span class="text-xs text-slate-500">${escapeHtml(member.role ?? '')}</span>
                    </button>
                `).join('');

                results.querySelectorAll('button[data-id]').forEach(button => {
                    button.addEventListener('click', () => {
                        hiddenId.value = button.dataset.id;
                        input.value = button.dataset.name;
                        results.classList.add('hidden');
                    });
                });

                results.classList.remove('hidden');
            }

            document.addEventListener('click', event => {
                if (results && !results.contains(event.target) && event.target !== input) {
                    results.classList.add('hidden');
                }
            });

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text ?? '';
                return div.innerHTML;
            }
        })();
    </script>

<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
