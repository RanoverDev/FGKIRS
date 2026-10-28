<?php
use Helpers\Auth;
use Helpers\Csrf;
use Models\Athlete;

$pageTitle = 'Atletas';
require_once __DIR__ . '/../layout/header.php';

$statusBadge = [
    'active'   => 'bg-green-100 text-green-800 ring-green-300',
    'inactive' => 'bg-slate-200 text-slate-600 ring-slate-400',
    'pending'  => 'bg-amber-100 text-amber-800 ring-amber-300',
];

// Filtros preservados nos links de ordenação e ao voltar de uma ação
$baseQuery = array_filter(
    array_intersect_key($_GET, array_flip(['q', 'graduation_id', 'status', 'dojo_id'])),
    fn ($v) => $v !== ''
);
$currentQuery = http_build_query($baseQuery + ['sort' => $filters['sort'], 'dir' => $filters['dir']]);

$sortHeader = static function (string $key, string $label, string $align = 'left') use ($baseQuery, $filters): string {
    $active = $filters['sort'] === $key;
    $next   = $active && $filters['dir'] === 'asc' ? 'desc' : 'asc';
    $url    = '/fgkirs-admin/athletes?' . http_build_query($baseQuery + ['sort' => $key, 'dir' => $next]);
    $arrow  = $active ? ($filters['dir'] === 'asc' ? '▲' : '▼') : '↕';
    $arrowClass = $active ? 'text-white' : 'text-slate-500';
    $title  = $key === 'age' ? 'Ordenar por idade' : 'Ordenar por ' . mb_strtolower($label);

    return '<a href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($title) . '" class="inline-flex items-center gap-1 hover:text-red-300 transition">'
        . htmlspecialchars($label) . ' <span class="text-[10px] ' . $arrowClass . '">' . $arrow . '</span></a>';
};

$th = 'px-5 py-3 text-%s text-xs font-medium uppercase tracking-wider';
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Atletas</h1>
        <p class="text-sm text-slate-500 mt-1">
            Cadastro permanente do dojo. Cadastre uma vez e reaproveite em todos os eventos.
        </p>
    </div>
    <button type="button" data-athlete-new
        class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition shrink-0">
        + Novo Atleta
    </button>
</div>

<?php require __DIR__ . '/../championships/partials/flash.php'; ?>

<form method="GET" action="/fgkirs-admin/athletes"
    class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
    <input type="hidden" name="sort" value="<?= htmlspecialchars($filters['sort']) ?>">
    <input type="hidden" name="dir" value="<?= htmlspecialchars($filters['dir']) ?>">
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
                    <th class="<?= sprintf($th, 'left') ?>"><?= $sortHeader('name', 'Nome') ?></th>
                    <th class="<?= sprintf($th, 'left') ?>"><?= $sortHeader('age', 'Idade') ?></th>
                    <th class="<?= sprintf($th, 'left') ?>"><?= $sortHeader('gender', 'Sexo') ?></th>
                    <th class="<?= sprintf($th, 'left') ?>"><?= $sortHeader('belt', 'Faixa') ?></th>
                    <th class="<?= sprintf($th, 'left') ?>"><?= $sortHeader('weight', 'Peso') ?></th>
                    <th class="<?= sprintf($th, 'left') ?>">Matrícula FGKIRS</th>
                    <th class="<?= sprintf($th, 'center') ?>"><?= $sortHeader('status', 'Status') ?></th>
                    <th class="<?= sprintf($th, 'right') ?>">Ações</th>
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
                                <div class="flex items-center gap-3">
                                    <?php $photoUrl = \Helpers\AthletePhoto::url($a['photo'] ?? null); ?>
                                    <?php if ($photoUrl): ?>
                                        <img src="<?= htmlspecialchars($photoUrl) ?>" alt="" loading="lazy"
                                            class="w-10 h-10 rounded-full object-cover object-center border border-slate-200 shrink-0">
                                    <?php else: ?>
                                        <span class="w-10 h-10 rounded-full bg-slate-900 text-white text-sm font-bold flex items-center justify-center shrink-0">
                                            <?= htmlspecialchars(mb_strtoupper(mb_substr($a['full_name'], 0, 1))) ?>
                                        </span>
                                    <?php endif; ?>
                                    <div>
                                        <button type="button" data-athlete-view="<?= (int) $a['id'] ?>"
                                            class="text-sm font-medium text-slate-900 hover:text-red-700 text-left transition"><?= htmlspecialchars($a['full_name']) ?></button>
                                        <?php if (!empty($a['is_guest'])): ?>
                                            <span class="ml-1 inline-flex px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800"
                                                title="<?= htmlspecialchars((string) ($a['guest_origin'] ?? '')) ?>">Convidado</span>
                                        <?php endif; ?>
                                        <?php if (Auth::isAdmin()): ?>
                                            <p class="text-xs text-slate-500"><?= htmlspecialchars($a['dojo_name']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
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
                                <?php $isActive = $a['status'] === 'active'; ?>
                                <form method="POST" action="/fgkirs-admin/athletes/toggle-status/<?= (int) $a['id'] ?>" class="inline"
                                    <?php if ($isActive): ?>onsubmit="return confirm('Desativar este atleta? Ele deixa de aparecer nas inscrições, mas o cadastro é mantido.');"<?php endif; ?>>
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="query" value="<?= htmlspecialchars($currentQuery) ?>">
                                    <button type="submit"
                                        title="<?= $isActive ? 'Clique para desativar' : 'Clique para ativar' ?>"
                                        class="inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-bold cursor-pointer hover:ring-2 transition <?= $statusBadge[$a['status']] ?? '' ?>">
                                        <?= htmlspecialchars(Athlete::STATUSES[$a['status']] ?? $a['status']) ?>
                                    </button>
                                </form>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <button type="button" data-athlete-view="<?= (int) $a['id'] ?>"
                                    title="Ver dados do atleta" aria-label="Ver dados do atleta"
                                    class="inline-flex p-1.5 rounded-md text-blue-600 hover:bg-blue-50 hover:text-blue-700 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                <button type="button" data-athlete-edit="<?= (int) $a['id'] ?>"
                                    title="Editar atleta" aria-label="Editar atleta"
                                    class="inline-flex p-1.5 rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
                                </button>
                                <?php if ($a['status'] !== 'inactive'): ?>
                                    <button type="button" data-certificate-athlete="<?= (int) $a['id'] ?>"
                                        data-certificate-name="<?= htmlspecialchars($a['full_name']) ?>"
                                        title="Gerar certificado de graduação" aria-label="Gerar certificado de graduação"
                                        class="inline-flex p-1.5 rounded-md text-emerald-700 hover:bg-emerald-50 hover:text-emerald-800 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9h20v4H2z"/><rect x="9.5" y="8" width="5" height="6" rx="1"/><path d="M11 14l-2.5 7M13 14l2.5 7"/></svg>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/partials/athlete_modals.php'; ?>
<?php require __DIR__ . '/partials/certificate_modal.php'; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
