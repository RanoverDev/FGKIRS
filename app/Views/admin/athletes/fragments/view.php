<?php
/**
 * Ficha completa do atleta dentro do modal.
 *
 * @var array $athlete
 * @var array $certificates
 */
use Helpers\AthletePhoto;
use Helpers\Csrf;
use Helpers\Format;
use Models\Athlete;

$e         = static fn ($v): string => htmlspecialchars((string) $v);
$photoUrl  = AthletePhoto::url($athlete['photo'] ?? null);
$isActive  = $athlete['status'] === 'active';
$statusCls = [
    'active'   => 'bg-green-100 text-green-800',
    'inactive' => 'bg-slate-200 text-slate-600',
    'pending'  => 'bg-amber-100 text-amber-800',
][$athlete['status']] ?? 'bg-slate-200 text-slate-600';

$birth = $athlete['birth_date'] ? date('d/m/Y', strtotime($athlete['birth_date'])) : '—';
$rows  = [
    'Dojo'                => $athlete['dojo_name'],
    'Origem (convidado)'  => !empty($athlete['is_guest']) ? ($athlete['guest_origin'] ?: 'Convidado') : null,
    'Nascimento'          => $birth . ' (' . (int) $athlete['age'] . ' anos)',
    'Sexo'                => Athlete::GENDERS[$athlete['gender']] ?? '—',
    'CPF'                 => $athlete['document'] ? Format::cpf($athlete['document']) : '—',
    'Estilo'              => $athlete['style_name'] ?: '—',
    'Graduação'           => $athlete['belt_name'] ?: '—',
    'Peso'                => $athlete['weight_kg'] !== null ? number_format((float) $athlete['weight_kg'], 1, ',', '') . ' kg' : '—',
    'Altura'              => $athlete['height_cm'] ? (int) $athlete['height_cm'] . ' cm' : '—',
    'Para-karatê'         => !empty($athlete['is_para_karate']) ? 'Sim' : 'Não',
    'Matrícula FGKIRS'    => $athlete['fgkirs_registration'] ?: '—',
    'Matrícula CBKI'      => $athlete['cbki_registration'] ?: '—',
    'Cadastrado em'       => $athlete['created_at'] ? date('d/m/Y', strtotime($athlete['created_at'])) : '—',
];
?>
<div data-athlete-id="<?= (int) $athlete['id'] ?>" data-athlete-name="<?= $e($athlete['full_name']) ?>"
    data-csrf="<?= $e(Csrf::token()) ?>">
    <div class="px-6 pt-5 flex items-start justify-between gap-4">
        <div class="flex items-center gap-4 min-w-0">
            <?php if ($photoUrl): ?>
                <img src="<?= $e($photoUrl) ?>" alt="Foto de <?= $e($athlete['full_name']) ?>"
                    class="w-24 h-24 rounded-lg object-cover object-center border border-slate-200 shrink-0">
            <?php else: ?>
                <div class="w-24 h-24 rounded-lg bg-slate-900 text-white text-3xl font-bold flex items-center justify-center shrink-0">
                    <?= $e(mb_strtoupper(mb_substr($athlete['full_name'], 0, 1))) ?>
                </div>
            <?php endif; ?>
            <div class="min-w-0">
                <h2 class="text-xl font-bold text-slate-900 break-words"><?= $e($athlete['full_name']) ?>
                    <?php if (!empty($athlete['is_guest'])): ?>
                        <span class="align-middle ml-1 inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">Convidado</span>
                    <?php endif; ?>
                </h2>
                <button type="button" data-action="toggle-status"
                    data-confirm="<?= $isActive ? 'Desativar este atleta? Ele deixa de aparecer nas inscrições, mas o cadastro é mantido.' : '' ?>"
                    title="<?= $isActive ? 'Clique para desativar' : 'Clique para ativar' ?>"
                    class="mt-1 inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-bold cursor-pointer hover:ring-2 hover:ring-slate-400 transition <?= $statusCls ?>">
                    <?= $e(Athlete::STATUSES[$athlete['status']] ?? $athlete['status']) ?> · <?= $isActive ? 'desativar' : 'ativar' ?>
                </button>
            </div>
        </div>
        <button type="button" data-modal-close class="text-slate-400 hover:text-slate-700 text-2xl leading-none" aria-label="Fechar">&times;</button>
    </div>

    <div class="px-6 py-4">
        <div data-view-error class="hidden rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm mb-4"></div>

        <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <?php foreach (array_filter($rows, static fn ($v) => $v !== null) as $label => $value): ?>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-slate-500"><?= $e($label) ?></dt>
                    <dd class="text-slate-900"><?= $e($value) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>

        <div class="flex flex-wrap gap-2 mt-5">
            <button type="button" data-action="edit"
                class="inline-flex items-center gap-2 border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2 px-4 rounded-lg transition text-sm">
                Editar dados
            </button>
            <?php if ($athlete['status'] !== 'inactive'): ?>
                <button type="button" data-certificate-athlete="<?= (int) $athlete['id'] ?>"
                    data-certificate-name="<?= $e($athlete['full_name']) ?>"
                    class="inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                    Gerar certificado
                </button>
            <?php endif; ?>
        </div>

        <h3 class="text-base font-bold text-slate-900 mt-7 mb-2">Certificados de graduação</h3>

        <?php if (empty($certificates)): ?>
            <p class="text-sm text-slate-500">Nenhum certificado emitido para este atleta.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100 border border-slate-200 rounded-lg">
                <?php foreach ($certificates as $c): ?>
                    <li class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900"><?= $e($c['graduation_label']) ?> · <?= $e($c['style_name']) ?></p>
                            <p class="text-xs text-slate-500">
                                Graduação em <?= date('d/m/Y', strtotime($c['graduation_date'])) ?>
                                · emitido em <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
                                por <?= $e($c['issued_by_name']) ?>
                            </p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <a href="/fgkirs-admin/certificates/<?= (int) $c['id'] ?>" target="_blank" rel="noopener"
                                class="text-blue-600 hover:text-blue-700 text-sm font-semibold px-2">Abrir</a>
                            <button type="button" data-action="delete-certificate" data-certificate-id="<?= (int) $c['id'] ?>"
                                data-confirm="Remover o certificado <?= $e($c['graduation_label']) ?> de <?= $e(date('d/m/Y', strtotime($c['graduation_date']))) ?>?&#10;&#10;Esta ação NÃO tem volta: o certificado e todas as informações dele serão apagados definitivamente."
                                class="text-red-600 hover:text-red-700 text-sm font-semibold px-2">Remover</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
