<?php
/**
 * Popup de edicao dos dados de um atleta inscrito. Reutilizado nas abas
 * Atletas e Inscritos e no consolidado do Presidente.
 *
 * Espera: $championship, $styles, $graduations e, opcionalmente, $editBack
 * (para onde voltar apos salvar: athletes | summary | registrations).
 * O botao que abre o popup e partials/athlete_row_actions.php.
 */

use Helpers\Csrf;
use Models\ChampionshipAthlete;

$championshipId = (int) $championship['id'];
?>

    <!-- Modal de edicao do atleta -->
    <div id="edit-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
        style="background:rgba(15,23,42,.6)">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-900">Editar dados do atleta</h3>
                <button type="button" id="edit-close" class="text-slate-400 hover:text-slate-700 text-xl leading-none">✕</button>
            </div>

            <form method="POST" id="edit-form" class="flex flex-col min-h-0">
                <?= Csrf::field() ?>
                <input type="hidden" name="dojo_id" id="edit-dojo" value="<?= (int) ($dojoId ?? 0) ?>">
                <input type="hidden" name="back" value="<?= htmlspecialchars($editBack ?? 'athletes') ?>">

                <div class="p-5 overflow-y-auto grid md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="edit-name" class="block text-sm font-medium text-slate-700 mb-2">Nome completo *</label>
                        <input type="text" id="edit-name" name="name" required maxlength="255"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    </div>

                    <div>
                        <label for="edit-gender" class="block text-sm font-medium text-slate-700 mb-2">Sexo *</label>
                        <select id="edit-gender" name="gender" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                            <?php foreach (ChampionshipAthlete::GENDERS as $value => $label): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="edit-birth" class="block text-sm font-medium text-slate-700 mb-2">
                            Data de nascimento *
                        </label>
                        <input type="date" id="edit-birth" name="birth_date" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    </div>

                    <div>
                        <label for="edit-style" class="block text-sm font-medium text-slate-700 mb-2">Estilo</label>
                        <select id="edit-style" name="style_id"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                            <option value="">— Selecione —</option>
                            <?php foreach ($styles as $style): ?>
                                <option value="<?= (int) $style['id'] ?>"><?= htmlspecialchars($style['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="edit-graduation" class="block text-sm font-medium text-slate-700 mb-2">
                            Graduação / Faixa
                        </label>
                        <select id="edit-graduation" name="graduation_id"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                            <option value="">— Selecione —</option>
                            <?php foreach ($graduations as $graduation): ?>
                                <option value="<?= (int) $graduation['id'] ?>" data-black="<?= (int) $graduation['is_black_belt'] ?>">
                                    <?= htmlspecialchars($graduation['style_name'] . ' – ' . $graduation['belt_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="edit-weight" class="block text-sm font-medium text-slate-700 mb-2">Peso (kg)</label>
                        <input type="text" id="edit-weight" name="weight" inputmode="decimal" placeholder="Ex: 49,5"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    </div>

                    <div>
                        <label for="edit-height" class="block text-sm font-medium text-slate-700 mb-2">Altura (cm)</label>
                        <input type="number" id="edit-height" name="height" min="50" max="250" inputmode="numeric" placeholder="Ex: 165"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    </div>

                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm text-slate-700 pb-2">
                            <input type="checkbox" id="edit-para" name="is_para_karate" value="1"
                                class="rounded border-slate-300 text-red-700 focus:ring-red-700">
                            Para-karatê
                        </label>
                    </div>

                    <p class="md:col-span-2 text-xs text-slate-500">
                        Mudar a graduação pode tirar o atleta das categorias em que ele já está inscrito —
                        confira as categorias depois de salvar.
                    </p>
                </div>

                <div class="px-5 py-4 border-t border-slate-200 flex gap-3">
                    <button type="submit"
                        class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2.5 px-6 rounded-lg transition">
                        Salvar
                    </button>
                    <button type="button" id="edit-cancel"
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

            // ── Modal de edicao do atleta ──
            const editModal = document.getElementById('edit-modal');
            const editForm = document.getElementById('edit-form');

            function closeEdit() {
                editModal.classList.add('hidden');
            }

            document.getElementById('edit-close')?.addEventListener('click', closeEdit);
            document.getElementById('edit-cancel')?.addEventListener('click', closeEdit);
            editModal?.addEventListener('click', event => {
                if (event.target === editModal) closeEdit();
            });

            document.querySelectorAll('[data-edit-athlete]').forEach(button => {
                button.addEventListener('click', () => {
                    const id = button.dataset.editAthlete;

                    editForm.action = `/fgkirs-admin/championships/${championshipId}/athletes/update/${id}`;
                    document.getElementById('edit-dojo').value = button.dataset.dojo;
                    document.getElementById('edit-name').value = button.dataset.name;
                    document.getElementById('edit-gender').value = button.dataset.gender;
                    document.getElementById('edit-birth').value = button.dataset.birth;
                    document.getElementById('edit-style').value = button.dataset.style !== '0' ? button.dataset.style : '';
                    document.getElementById('edit-graduation').value = button.dataset.graduation !== '0' ? button.dataset.graduation : '';
                    document.getElementById('edit-weight').value = button.dataset.weight;
                    document.getElementById('edit-height').value = button.dataset.height;
                    document.getElementById('edit-para').checked = button.dataset.para === '1';

                    editModal.classList.remove('hidden');
                });
            });
        })();
    </script>
