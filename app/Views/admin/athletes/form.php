<?php
use Helpers\Auth;
use Helpers\Csrf;
use Helpers\Format;
use Models\Athlete;

$isEdit    = !empty($athlete['id']);
$pageTitle = $isEdit ? 'Editar Atleta' : 'Novo Atleta';
$action    = $isEdit ? '/fgkirs-admin/athletes/update/' . (int) $athlete['id'] : '/fgkirs-admin/athletes/store';

$value = static fn(string $field, string $default = ''): string
    => htmlspecialchars((string) ($athlete[$field] ?? $default));

$input = 'w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent';

require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <a href="/fgkirs-admin/athletes" class="text-sm text-slate-500 hover:text-red-700 transition">← Voltar para atletas</a>
</div>

<h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-6"><?= $pageTitle ?></h1>

<?php require __DIR__ . '/../championships/partials/flash.php'; ?>

<form method="POST" action="<?= $action ?>" enctype="multipart/form-data" data-athlete-form class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-3xl">
    <?= Csrf::field() ?>
    <?php require __DIR__ . '/partials/form_fields.php'; ?>

    <div class="flex flex-col sm:flex-row gap-3">
        <button type="submit" class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            <?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
        </button>
        <a href="/fgkirs-admin/athletes"
            class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition">
            Cancelar
        </a>
    </div>
</form>

<?php if ($isEdit): ?>
    <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-3xl mt-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <h2 class="text-lg font-bold text-slate-900">Certificados de graduação</h2>
            <?php if (($athlete['status'] ?? '') !== 'inactive'): ?>
                <button type="button" data-certificate-athlete="<?= (int) $athlete['id'] ?>" data-certificate-reload="1"
                    data-certificate-name="<?= $value('full_name') ?>"
                    class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-2 px-5 rounded-lg transition">
                    Gerar certificado
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($certificates)): ?>
            <p class="text-sm text-slate-500">Nenhum certificado emitido para este atleta.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($certificates as $c): ?>
                    <li class="flex items-center justify-between gap-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                <?= htmlspecialchars($c['graduation_label']) ?> · <?= htmlspecialchars($c['style_name']) ?>
                            </p>
                            <p class="text-xs text-slate-500">
                                Graduação em <?= date('d/m/Y', strtotime($c['graduation_date'])) ?>
                                · emitido em <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?>
                                por <?= htmlspecialchars($c['issued_by_name']) ?>
                            </p>
                        </div>
                        <a href="/fgkirs-admin/certificates/<?= (int) $c['id'] ?>" target="_blank" rel="noopener"
                            class="text-blue-600 hover:text-blue-700 text-sm font-semibold shrink-0">Abrir</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php require __DIR__ . '/partials/certificate_modal.php'; ?>
<?php endif; ?>

<script src="/assets/js/athlete-form.js"></script>
<script>initAthleteForm(document.querySelector('[data-athlete-form]'));</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
