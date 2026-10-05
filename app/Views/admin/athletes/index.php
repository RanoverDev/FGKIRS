<?php
use Helpers\Auth;
use Helpers\Csrf;
use Models\Athlete;

$pageTitle = 'Atletas';
require_once __DIR__ . '/../layout/header.php';

$tokenQuery = '?token=' . urlencode(Csrf::token());
$statusBadge = [
    'active'   => 'bg-green-100 text-green-800',
    'inactive' => 'bg-slate-200 text-slate-600',
    'pending'  => 'bg-amber-100 text-amber-800',
];
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Atletas</h1>
        <p class="text-sm text-slate-500 mt-1">
            Cadastro permanente do dojo. Cadastre uma vez e reaproveite em todos os eventos.
        </p>
    </div>
    <a href="/fgkirs-admin/athletes/create"
        class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition shrink-0">
        + Novo Atleta
    </a>
</div>

<?php require __DIR__ . '/../championships/partials/flash.php'; ?>

<form method="GET" action="/fgkirs-admin/athletes"
    class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
    <input type="search" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Buscar por nome ou matrícula"
        class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent lg:col-span-2">

    <select name="graduation_id"
        class="px-4 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-red-700 focus:border-transparent">
        <option value="">Todas as faixas</option>
        <?php foreach ($graduations as $g): ?>
            <option value="<?= (int) $g['id'] ?>" <?= (int) $filters['graduation_id'] === (int) $g['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['style_name'] . ' — ' . $g['belt_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="status"
        class="px-4 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-red-700 focus:border-transparent">
        <option value="">Todos os status</option>
        <?php foreach (Athlete::STATUSES as $value => $label): ?>
            <option value="<?= $value ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>

    <?php if (Auth::isAdmin()): ?>
        <select name="dojo_id"
            class="px-4 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-red-700 focus:border-transparent">
            <option value="">Todos os dojos</option>
            <?php foreach ($dojos as $dojo): ?>
                <option value="<?= (int) $dojo['id'] ?>" <?= (int) $filters['dojo_id'] === (int) $dojo['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dojo['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <div class="flex gap-2 sm:col-span-2 lg:col-span-5">
        <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2 px-5 rounded-lg transition">
            Filtrar
        </button>
        <a href="/fgkirs-admin/athletes"
            class="border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2 px-5 rounded-lg transition">
            Limpar
        </a>
    </div>
</form>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-900 text-white">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Nome</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Idade</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Sexo</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Faixa</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Peso</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Matrícula FGKIRS</th>
                    <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($athletes)): ?>
                    <tr>
                        <td colspan="8" class="px-5 py-10 text-center text-slate-500">Nenhum atleta encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($athletes as $a): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="text-sm font-medium text-slate-900"><?= htmlspecialchars($a['full_name']) ?></p>
                                <?php if (Auth::isAdmin()): ?>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($a['dojo_name']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-700 whitespace-nowrap"><?= (int) $a['age'] ?> anos</td>
                            <td class="px-5 py-3 text-sm text-slate-700"><?= htmlspecialchars(Athlete::GENDERS[$a['gender']] ?? '—') ?></td>
                            <td class="px-5 py-3 text-sm text-slate-700">
                                <?php if ($a['belt_name']): ?>
                                    <span class="inline-block w-3 h-3 rounded-full border border-slate-300 align-middle mr-1"
                                        style="background-color: <?= htmlspecialchars($a['belt_color']) ?>"></span>
                                    <?= htmlspecialchars($a['belt_name']) ?>
                                    <span class="text-xs text-slate-500 block"><?= htmlspecialchars((string) $a['style_name']) ?></span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-700 whitespace-nowrap">
                                <?= $a['weight_kg'] !== null ? number_format((float) $a['weight_kg'], 1, ',', '') . ' kg' : '—' ?>
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-700"><?= htmlspecialchars((string) ($a['fgkirs_registration'] ?? '—')) ?></td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-bold <?= $statusBadge[$a['status']] ?? '' ?>">
                                    <?= htmlspecialchars(Athlete::STATUSES[$a['status']] ?? $a['status']) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="/fgkirs-admin/athletes/edit/<?= (int) $a['id'] ?>"
                                    class="text-blue-600 hover:text-blue-700 text-sm font-semibold px-2">Editar</a>
                                <?php if ($a['status'] !== 'inactive'): ?>
                                    <a href="/fgkirs-admin/athletes/deactivate/<?= (int) $a['id'] . $tokenQuery ?>"
                                        onclick="return confirm('Desativar este atleta? Ele deixa de aparecer nas inscrições, mas o cadastro é mantido.');"
                                        class="text-red-600 hover:text-red-700 text-sm font-semibold px-2">Desativar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
