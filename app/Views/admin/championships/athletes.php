<?php
use Helpers\Auth;
use Helpers\Csrf;
use Models\ChampionshipAthlete;

$pageTitle = $championship['title'];
require_once __DIR__ . '/../layout/header.php';
require __DIR__ . '/partials/header.php';

$championshipId = (int) $championship['id'];
$dojoParam      = (Auth::isAdmin() && !empty($dojoId)) ? '&dojo_id=' . (int) $dojoId : '';
$deleteQuery    = '?token=' . urlencode(Csrf::token()) . $dojoParam;

$boxes = [
    'black'   => ['title' => 'Faixa Preta',    'hint' => 'Somente atletas já cadastrados no dojo.'],
    'colored' => ['title' => 'Faixa Colorida', 'hint' => 'Cadastrados no dojo ou atletas avulsos.'],
];
?>

<?php if (!empty($dojoId)): ?>

    <?php if ($canWrite): ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
            <div class="flex border-b border-slate-200 mb-5">
                <button type="button" data-add-tab="student"
                    class="add-tab px-4 py-2.5 text-sm font-semibold border-b-2 border-red-700 text-red-700 transition">
                    Buscar aluno do dojo
                </button>
                <button type="button" data-add-tab="guest"
                    class="add-tab px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition">
                    Cadastrar atleta avulso
                </button>
            </div>

            <!-- Busca no cadastro do dojo -->
            <form method="POST" action="/fgkirs-admin/championships/<?= $championshipId ?>/athletes/store"
                id="student-panel" data-add-panel="student">
                <?= Csrf::field() ?>
                <input type="hidden" name="source" value="student">
                <input type="hidden" name="dojo_id" value="<?= (int) $dojoId ?>">
                <input type="hidden" name="user_id" id="student-user-id">

                <label for="student-search" class="block text-sm font-medium text-slate-700 mb-2">
                    Nome do atleta *
                </label>
                <div class="relative">
                    <input type="text" id="student-search" autocomplete="off"
                        placeholder="Digite ao menos 2 letras do nome…"
                        class="w-full pl-4 pr-11 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    <svg id="student-spinner" class="hidden absolute right-3.5 top-1/2 -translate-y-1/2 h-5 w-5 animate-spin text-red-700"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" role="status" aria-label="Buscando atletas">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <div id="student-results"
                        class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                    </div>
                </div>

                <p class="text-xs text-slate-500 mt-2">
                    A faixa (preta ou colorida) vem da graduação cadastrada na ficha do atleta.
                </p>

                <div id="student-selected" class="hidden mt-4">
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        <span class="inline-flex items-center gap-2 bg-slate-100 rounded-lg px-3 py-2 text-sm font-semibold text-slate-800">
                            <span id="student-selected-name"></span>
                            <button type="button" id="student-clear" class="text-slate-400 hover:text-red-600"
                                aria-label="Trocar atleta">✕</button>
                        </span>
                        <span class="text-xs text-slate-500">Confira os dados abaixo antes de inscrever.</span>
                    </div>

                    <div id="student-incomplete"
                        class="hidden mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
                        A ficha deste aluno está incompleta. Informe sexo e data de nascimento para continuar.
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label for="student-gender" class="block text-sm font-medium text-slate-700 mb-2">Sexo *</label>
                            <select id="student-gender" name="gender" required
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                                <option value="">— Selecione —</option>
                                <?php foreach (ChampionshipAthlete::GENDERS as $value => $label): ?>
                                    <option value="<?= $value ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="student-birth" class="block text-sm font-medium text-slate-700 mb-2">Nascimento *</label>
                            <input type="date" id="student-birth" name="birth_date" required
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                        </div>
                        <div>
                            <label for="student-weight" class="block text-sm font-medium text-slate-700 mb-2">Peso (kg)</label>
                            <input type="text" id="student-weight" name="weight" inputmode="decimal" placeholder="Ex: 49,5"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                        </div>
                        <div>
                            <label for="student-height" class="block text-sm font-medium text-slate-700 mb-2">Altura (cm)</label>
                            <input type="number" id="student-height" name="height" min="50" max="250" inputmode="numeric" placeholder="Ex: 165"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                        </div>
                    </div>

                    <button type="submit"
                        class="mt-5 bg-red-700 hover:bg-red-800 text-white font-semibold py-2.5 px-8 rounded-lg transition">
                        Inscrever no evento
                    </button>
                </div>
            </form>

            <!-- Atleta avulso / convidado -->
            <form method="POST" action="/fgkirs-admin/championships/<?= $championshipId ?>/athletes/store"
                data-add-panel="guest" class="hidden">
                <?= Csrf::field() ?>
                <input type="hidden" name="source" value="guest">
                <input type="hidden" name="dojo_id" value="<?= (int) $dojoId ?>">

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="guest-name" class="block text-sm font-medium text-slate-700 mb-2">Nome completo *</label>
                        <input type="text" id="guest-name" name="name" required maxlength="255"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    </div>

                    <div>
                        <label for="guest-gender" class="block text-sm font-medium text-slate-700 mb-2">Sexo *</label>
                        <select id="guest-gender" name="gender" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                            <option value="">— Selecione —</option>
                            <?php foreach (ChampionshipAthlete::GENDERS as $value => $label): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="guest-birth" class="block text-sm font-medium text-slate-700 mb-2">
                            Data de nascimento *
                        </label>
                        <input type="date" id="guest-birth" name="birth_date" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    </div>

                    <div>
                        <label for="guest-style" class="block text-sm font-medium text-slate-700 mb-2">Estilo</label>
                        <select id="guest-style" name="style_id"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                            <option value="">— Selecione —</option>
                            <?php foreach ($styles as $style): ?>
                                <option value="<?= (int) $style['id'] ?>"><?= htmlspecialchars($style['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="guest-graduation" class="block text-sm font-medium text-slate-700 mb-2">
                            Graduação / Faixa
                        </label>
                        <select id="guest-graduation" name="graduation_id"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                            <option value="">— Selecione —</option>
                            <?php foreach ($graduations as $graduation): ?>
                                <option value="<?= (int) $graduation['id'] ?>"
                                    data-style="<?= (int) $graduation['style_id'] ?>"
                                    data-black="<?= (int) $graduation['is_black_belt'] ?>">
                                    <?= htmlspecialchars($graduation['style_name'] . ' – ' . $graduation['belt_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="guest-weight" class="block text-sm font-medium text-slate-700 mb-2">
                            Peso (kg)
                        </label>
                        <input type="text" id="guest-weight" name="weight" inputmode="decimal"
                            placeholder="Ex: 49,5"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                        <p class="text-xs text-slate-500 mt-1.5">Necessário apenas para categorias de Kumite.</p>
                    </div>

                    <div>
                        <label for="guest-height" class="block text-sm font-medium text-slate-700 mb-2">
                            Altura (cm)
                        </label>
                        <input type="number" id="guest-height" name="height" min="50" max="250" inputmode="numeric"
                            placeholder="Ex: 165"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    </div>

                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm text-slate-700 pb-2">
                            <input type="checkbox" name="is_para_karate" value="1"
                                class="rounded border-slate-300 text-red-700 focus:ring-red-700">
                            Para-karatê
                        </label>
                    </div>
                </div>

                <div id="guest-black-warning"
                    class="hidden mt-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
                    Atletas de faixa preta precisam estar cadastrados no dojo. Cadastre o atleta em
                    <strong>Usuários</strong> e depois inscreva-o pela busca.
                </div>

                <button type="submit"
                    class="mt-5 bg-red-700 hover:bg-red-800 text-white font-semibold py-2.5 px-8 rounded-lg transition">
                    Cadastrar e inscrever
                </button>
            </form>
        </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-2 gap-6">
        <?php foreach ($boxes as $group => $box): ?>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-bold text-slate-900"><?= $box['title'] ?></h2>
                        <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full bg-slate-900 text-white text-xs font-bold">
                            <?= count($grouped[$group]) ?>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1"><?= $box['hint'] ?></p>
                </div>

                <?php if (empty($grouped[$group])): ?>
                    <p class="px-5 py-10 text-center text-sm text-slate-500">
                        Nenhum atleta de <?= mb_strtolower($box['title']) ?> inscrito ainda.
                    </p>
                <?php else: ?>
                    <ul class="divide-y divide-slate-100">
                        <?php foreach ($grouped[$group] as $athlete): ?>
                            <li class="px-5 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-900 text-sm">
                                            <?= htmlspecialchars($athlete['name']) ?>
                                            <?php if ($athlete['is_guest']): ?>
                                                <span class="ml-1 inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold uppercase">
                                                    Avulso
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($athlete['is_para_karate']): ?>
                                                <span class="ml-1 inline-flex px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-[10px] font-bold uppercase">
                                                    Para-karatê
                                                </span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            <?= (int) $athlete['age'] ?> anos ·
                                            <?= htmlspecialchars(ChampionshipAthlete::GENDERS[$athlete['gender']]) ?>
                                            <?php if (!empty($athlete['belt_name'])): ?>
                                                · <?= htmlspecialchars($athlete['belt_name']) ?>
                                            <?php endif; ?>
                                            <?php if ($athlete['weight'] !== null): ?>
                                                · <?= number_format((float) $athlete['weight'], 1, ',', '') ?> kg
                                            <?php endif; ?>
                                            <?php if (!empty($athlete['height'])): ?>
                                                · <?= (int) $athlete['height'] ?> cm
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <?php if ($canWrite): ?>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <button type="button"
                                                data-open-categories="<?= (int) $athlete['id'] ?>"
                                                data-athlete-name="<?= htmlspecialchars($athlete['name']) ?>"
                                                class="text-xs font-semibold text-red-700 hover:text-red-800 px-2 py-1 transition">
                                                + Categoria
                                            </button>
                                            <button type="button"
                                                data-edit-athlete="<?= (int) $athlete['id'] ?>"
                                                data-dojo="<?= (int) $athlete['dojo_id'] ?>"
                                                data-name="<?= htmlspecialchars($athlete['name']) ?>"
                                                data-gender="<?= htmlspecialchars($athlete['gender']) ?>"
                                                data-birth="<?= htmlspecialchars($athlete['birth_date']) ?>"
                                                data-style="<?= (int) ($athlete['style_id'] ?? 0) ?>"
                                                data-graduation="<?= (int) ($athlete['graduation_id'] ?? 0) ?>"
                                                data-weight="<?= $athlete['weight'] !== null ? htmlspecialchars(number_format((float) $athlete['weight'], 1, ',', '')) : '' ?>"
                                                data-height="<?= !empty($athlete['height']) ? (int) $athlete['height'] : '' ?>"
                                                data-para="<?= (int) $athlete['is_para_karate'] ?>"
                                                title="Editar dados do atleta" aria-label="Editar dados de <?= htmlspecialchars($athlete['name']) ?>"
                                                class="text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-md p-1.5 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2 2 0 112.828 2.828L11.828 15.828a4 4 0 01-1.414.943L6 18l1.229-4.414A4 4 0 018.172 12.17L9 13z"/>
                                                </svg>
                                            </button>
                                            <a href="/fgkirs-admin/championships/<?= $championshipId ?>/athletes/delete/<?= (int) $athlete['id'] ?><?= $deleteQuery ?>"
                                                data-confirm-delete
                                                class="text-xs font-semibold text-slate-400 hover:text-red-600 px-2 py-1 transition">
                                                Remover
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($athlete['entries'])): ?>
                                    <div class="flex flex-wrap gap-1.5 mt-3">
                                        <?php foreach ($athlete['entries'] as $entry): ?>
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold
                                                <?= $entry['modality'] === 'kata'
                                                    ? 'bg-indigo-50 text-indigo-800'
                                                    : 'bg-rose-50 text-rose-800' ?>">
                                                <?= htmlspecialchars($entry['category_name']) ?>
                                                <?php if ($entry['weight'] !== null): ?>
                                                    (<?= number_format((float) $entry['weight'], 1, ',', '') ?> kg)
                                                <?php endif; ?>
                                                <?php if ($canWrite): ?>
                                                    <a href="/fgkirs-admin/championships/<?= $championshipId ?>/entries/delete/<?= (int) $entry['id'] ?><?= $deleteQuery ?>"
                                                        class="text-current opacity-50 hover:opacity-100" title="Remover desta categoria">✕</a>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-[11px] text-amber-700 mt-3">
                                        Ainda sem categoria — este atleta não aparece na ficha de inscrição.
                                    </p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php $editBack = 'athletes'; require __DIR__ . '/partials/athlete_edit_modal.php'; ?>

    <!-- Modal de categorias -->
    <div id="category-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
        style="background:rgba(15,23,42,.6)">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-900">Inscrever em categoria</h3>
                    <p id="modal-athlete" class="text-xs text-slate-500 mt-0.5"></p>
                </div>
                <button type="button" id="modal-close" class="text-slate-400 hover:text-slate-700 text-xl leading-none">✕</button>
            </div>

            <form method="POST" action="/fgkirs-admin/championships/<?= $championshipId ?>/entries/store"
                class="flex flex-col min-h-0">
                <?= Csrf::field() ?>
                <input type="hidden" name="dojo_id" value="<?= (int) $dojoId ?>">
                <input type="hidden" name="championship_athlete_id" id="modal-athlete-id">

                <div id="modal-body" class="p-5 overflow-y-auto space-y-2"></div>

                <div id="modal-weight-wrap" class="hidden px-5 pb-4">
                    <label for="modal-weight" class="block text-sm font-medium text-slate-700 mb-2">
                        Peso do atleta (kg) *
                    </label>
                    <input type="text" id="modal-weight" name="weight" inputmode="decimal" placeholder="Ex: 49,5"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    <p id="modal-weight-hint" class="text-xs text-slate-500 mt-1.5"></p>
                </div>

                <div class="px-5 py-4 border-t border-slate-200 flex gap-3">
                    <button type="submit" id="modal-submit" disabled
                        class="bg-red-700 hover:bg-red-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-semibold py-2.5 px-6 rounded-lg transition">
                        Inscrever
                    </button>
                    <button type="button" id="modal-cancel"
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
            const dojoParam = '<?= $dojoParam ?>';

            // ── Abas do painel de cadastro ──
            document.querySelectorAll('.add-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    const target = tab.dataset.addTab;

                    document.querySelectorAll('.add-tab').forEach(t => {
                        const active = t === tab;
                        t.classList.toggle('border-red-700', active);
                        t.classList.toggle('text-red-700', active);
                        t.classList.toggle('border-transparent', !active);
                        t.classList.toggle('text-slate-500', !active);
                    });

                    document.querySelectorAll('[data-add-panel]').forEach(panel => {
                        panel.classList.toggle('hidden', panel.dataset.addPanel !== target);
                    });
                });
            });

            // ── Autocomplete de alunos do dojo ──
            const search = document.getElementById('student-search');
            const results = document.getElementById('student-results');
            const spinner = document.getElementById('student-spinner');
            const hiddenId = document.getElementById('student-user-id');
            const selected = document.getElementById('student-selected');
            const selectedName = document.getElementById('student-selected-name');
            const incompleteWarning = document.getElementById('student-incomplete');
            const studentFields = {
                gender: document.getElementById('student-gender'),
                birth: document.getElementById('student-birth'),
                weight: document.getElementById('student-weight'),
                height: document.getElementById('student-height'),
            };
            const studentsById = new Map();
            let timer = null;
            let controller = null;

            function setSearching(active) {
                spinner.classList.toggle('hidden', !active);
                search.setAttribute('aria-busy', active ? 'true' : 'false');
            }

            function clearSelection() {
                hiddenId.value = '';
                selected.classList.add('hidden');
                Object.values(studentFields).forEach(field => { field.value = ''; });
            }

            document.getElementById('student-clear')?.addEventListener('click', () => {
                clearSelection();
                search.value = '';
                search.focus();
            });

            search?.addEventListener('input', () => {
                clearTimeout(timer);
                controller?.abort();
                clearSelection();
                const term = search.value.trim();

                if (term.length < 2) {
                    setSearching(false);
                    results.classList.add('hidden');
                    return;
                }

                setSearching(true);
                results.innerHTML = '<p class="px-4 py-3 text-sm text-slate-500">Buscando atletas…</p>';
                results.classList.remove('hidden');

                timer = setTimeout(async () => {
                    controller = new AbortController();

                    try {
                        const url = `/fgkirs-admin/championships/${championshipId}/athletes/search?q=`
                            + encodeURIComponent(term) + dojoParam;
                        const response = await fetch(url, { signal: controller.signal });
                        if (!response.ok) throw new Error('http ' + response.status);
                        renderResults(await response.json());
                        setSearching(false);
                    } catch (e) {
                        if (e.name === 'AbortError') return;
                        setSearching(false);
                        results.innerHTML = '<p class="px-4 py-3 text-sm text-red-700">'
                            + 'Não foi possível buscar agora. Tente novamente.</p>';
                    }
                }, 250);
            });

            function renderResults(students) {
                if (!students.length) {
                    results.innerHTML = '<p class="px-4 py-3 text-sm text-slate-500">'
                        + 'Nenhum aluno encontrado. Ele pode já estar inscrito ou não estar cadastrado no dojo.</p>';
                    results.classList.remove('hidden');
                    return;
                }

                studentsById.clear();
                students.forEach(student => studentsById.set(String(student.id), student));

                results.innerHTML = students.map(student => {
                    const belt = student.belt_name
                        ? `<span class="text-xs text-slate-500">${escapeHtml(student.belt_name)}</span>`
                        : '<span class="text-xs text-amber-700">sem graduação na ficha</span>';
                    const missing = (!student.birth_date || !student.gender)
                        ? '<span class="block text-[11px] text-amber-700">ficha incompleta — você poderá completar ao inscrever</span>'
                        : '';

                    return `<button type="button" data-id="${student.id}" data-name="${escapeHtml(student.name)}"
                                class="w-full text-left px-4 py-2.5 hover:bg-slate-50 border-b border-slate-100 last:border-0">
                                <span class="block text-sm font-medium text-slate-800">${escapeHtml(student.name)}</span>
                                ${belt}${missing}
                            </button>`;
                }).join('');

                results.querySelectorAll('button[data-id]').forEach(button => {
                    button.addEventListener('click', () => {
                        const student = studentsById.get(button.dataset.id);

                        hiddenId.value = button.dataset.id;
                        selectedName.textContent = button.dataset.name;
                        search.value = button.dataset.name;

                        studentFields.gender.value = ['M', 'F'].includes(student.gender) ? student.gender : '';
                        studentFields.birth.value = student.birth_date ?? '';
                        studentFields.weight.value = student.weight ? formatKg(student.weight) : '';
                        studentFields.height.value = student.height ?? '';
                        incompleteWarning.classList.toggle('hidden', !!(studentFields.gender.value && studentFields.birth.value));

                        selected.classList.remove('hidden');
                        results.classList.add('hidden');
                    });
                });

                results.classList.remove('hidden');
            }

            document.addEventListener('click', event => {
                if (results && !results.contains(event.target) && event.target !== search) {
                    results.classList.add('hidden');
                }
            });

            document.getElementById('student-panel')?.addEventListener('submit', event => {
                if (!hiddenId.value) {
                    event.preventDefault();
                    alert('Selecione um atleta na lista de busca.');
                }
            });

            // ── Aviso de faixa preta no cadastro avulso ──
            const guestGraduation = document.getElementById('guest-graduation');
            const blackWarning = document.getElementById('guest-black-warning');

            guestGraduation?.addEventListener('change', () => {
                const option = guestGraduation.selectedOptions[0];
                blackWarning.classList.toggle('hidden', option?.dataset.black !== '1');
            });

            // ── Modal de categorias ──
            const modal = document.getElementById('category-modal');
            const modalBody = document.getElementById('modal-body');
            const modalAthlete = document.getElementById('modal-athlete');
            const modalAthleteId = document.getElementById('modal-athlete-id');
            const modalSubmit = document.getElementById('modal-submit');
            const weightWrap = document.getElementById('modal-weight-wrap');
            const weightInput = document.getElementById('modal-weight');
            const weightHint = document.getElementById('modal-weight-hint');

            function closeModal() {
                modal.classList.add('hidden');
                modalBody.innerHTML = '';
                weightWrap.classList.add('hidden');
                weightInput.value = '';
                modalSubmit.disabled = true;
            }

            document.getElementById('modal-close')?.addEventListener('click', closeModal);
            document.getElementById('modal-cancel')?.addEventListener('click', closeModal);
            modal?.addEventListener('click', event => {
                if (event.target === modal) closeModal();
            });

            document.querySelectorAll('[data-open-categories]').forEach(button => {
                button.addEventListener('click', async () => {
                    const athleteId = button.dataset.openCategories;

                    modalAthleteId.value = athleteId;
                    modalBody.innerHTML = '<p class="text-sm text-slate-500">Buscando categorias compatíveis…</p>';
                    modal.classList.remove('hidden');

                    try {
                        const url = `/fgkirs-admin/championships/${championshipId}/athletes/${athleteId}/categories`
                            + (dojoParam ? '?' + dojoParam.replace(/^&/, '') : '');
                        const response = await fetch(url);
                        const data = await response.json();
                        renderCategories(data);
                    } catch (e) {
                        modalBody.innerHTML = '<p class="text-sm text-red-700">Não foi possível carregar as categorias.</p>';
                    }
                });
            });

            function renderCategories(data) {
                modalAthlete.textContent = `${data.athlete.name} · ${data.athlete.age} anos`;

                if (data.event_empty) {
                    modalBody.innerHTML = '<p class="text-sm text-red-700">'
                        + 'O Presidente ainda não liberou categorias para este evento. '
                        + 'Avise a federação para configurar as categorias do evento.</p>';
                    return;
                }

                if (!data.categories.length) {
                    modalBody.innerHTML = '<p class="text-sm text-slate-600">'
                        + 'Nenhuma categoria liberada neste evento é compatível com a idade, o sexo e a graduação deste atleta.</p>';
                    return;
                }

                modalBody.innerHTML = data.categories.map(category => {
                    const disabled = category.already_entered;
                    return `<label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer transition
                                    ${disabled ? 'border-slate-200 bg-slate-50 opacity-60 cursor-not-allowed' : 'border-slate-200 hover:border-red-300 hover:bg-red-50/40'}">
                                <input type="radio" name="category_id" value="${category.id}" class="mt-0.5"
                                    ${disabled ? 'disabled' : ''}
                                    data-requires-weight="${category.requires_weight ? '1' : '0'}"
                                    data-weight-min="${category.weight_min ?? ''}"
                                    data-weight-max="${category.weight_max ?? ''}">
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-slate-800">${escapeHtml(category.name)}</span>
                                    ${disabled ? '<span class="block text-[11px] text-slate-500">já inscrito</span>' : ''}
                                </span>
                            </label>`;
                }).join('');

                modalBody.querySelectorAll('input[name="category_id"]').forEach(radio => {
                    radio.addEventListener('change', () => {
                        modalSubmit.disabled = false;
                        const needsWeight = radio.dataset.requiresWeight === '1';

                        weightWrap.classList.toggle('hidden', !needsWeight);
                        weightInput.required = needsWeight;

                        if (needsWeight) {
                            const min = radio.dataset.weightMin;
                            const max = radio.dataset.weightMax;
                            weightHint.textContent = max
                                ? `Esta categoria aceita até ${formatKg(max)} kg.`
                                : `Esta categoria aceita acima de ${formatKg(min)} kg.`;

                            if (!weightInput.value && data.athlete.weight) {
                                weightInput.value = String(data.athlete.weight).replace('.', ',');
                            }
                        }
                    });
                });
            }

            function formatKg(value) {
                return String(parseFloat(value)).replace('.', ',');
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
