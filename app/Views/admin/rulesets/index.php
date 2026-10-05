<?php
use Helpers\Csrf;

$pageTitle = 'Regulamentos';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Regulamentos</h1>
        <p class="text-sm text-slate-500 mt-1">
            Divisões de idade, faixa e peso e as disciplinas. Cada evento gera as próprias categorias a partir de um regulamento.
        </p>
    </div>
    <a href="/fgkirs-admin/rulesets/create"
        class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition shrink-0">
        + Novo Regulamento
    </a>
</div>

<?php require __DIR__ . '/../championships/partials/flash.php'; ?>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-900 text-white">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Regulamento</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Idade calculada</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Divisões</th>
                    <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($rulesets)): ?>
                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Nenhum regulamento cadastrado.</td></tr>
                <?php endif; ?>
                <?php foreach ($rulesets as $r): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-sm font-medium text-slate-900"><?= htmlspecialchars($r['name']) ?></td>
                        <td class="px-5 py-3 text-sm text-slate-700">
                            <?= $r['age_policy'] === 'year_end' ? 'Idade no fim do ano' : 'Idade na data do evento' ?>
                        </td>
                        <td class="px-5 py-3 text-sm text-slate-700">
                            <?= (int) $r['age_count'] ?> idades · <?= (int) $r['belt_count'] ?> faixas · <?= (int) $r['discipline_count'] ?> disciplinas
                        </td>
                        <td class="px-5 py-3 text-center whitespace-nowrap">
                            <?php if ($r['is_default']): ?>
                                <span class="inline-flex px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 text-[11px] font-bold">Padrão</span>
                            <?php endif; ?>
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-bold <?= $r['is_active'] ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-600' ?>">
                                <?= $r['is_active'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            <a href="/fgkirs-admin/rulesets/edit/<?= (int) $r['id'] ?>" class="text-blue-600 hover:text-blue-700 text-sm font-semibold px-2">Editar</a>
                            <a href="/fgkirs-admin/rulesets/<?= (int) $r['id'] ?>/preview" class="text-slate-700 hover:text-slate-900 text-sm font-semibold px-2">Pré-visualizar</a>
                            <form method="POST" action="/fgkirs-admin/rulesets/duplicate/<?= (int) $r['id'] ?>" class="inline"
                                onsubmit="return confirm('Duplicar este regulamento? A cópia nasce sem ser o padrão.');">
                                <?= Csrf::field() ?>
                                <button type="submit" class="text-slate-700 hover:text-slate-900 text-sm font-semibold px-2">Duplicar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
