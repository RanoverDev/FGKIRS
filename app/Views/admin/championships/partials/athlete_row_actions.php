<?php
/**
 * Lapis (editar) e lixeira (remover) de um atleta nas listas de inscritos.
 * O popup de edicao vem de partials/athlete_edit_modal.php.
 *
 * Espera: $athlete, $championshipId, $actionsBack (summary | registrations).
 */

use Helpers\Csrf;

$removeUrl = "/fgkirs-admin/championships/$championshipId/athletes/delete/" . (int) $athlete['id']
    . '?token=' . urlencode(Csrf::token())
    . '&dojo_id=' . (int) $athlete['dojo_id']
    . '&back=' . urlencode($actionsBack);
?>
<div class="flex items-center justify-end gap-1">
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
    <a href="<?= $removeUrl ?>" data-confirm-delete
        title="Remover do evento" aria-label="Remover <?= htmlspecialchars($athlete['name']) ?> do evento"
        class="text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-md p-1.5 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
    </a>
</div>
