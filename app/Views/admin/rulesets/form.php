<?php
use Helpers\Csrf;

$pageTitle = 'Novo Regulamento';
$input = 'w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <a href="/fgkirs-admin/rulesets" class="text-sm text-slate-500 hover:text-red-700 transition">← Voltar para regulamentos</a>
</div>

<h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-6">Novo Regulamento</h1>

<?php require __DIR__ . '/../championships/partials/flash.php'; ?>

<form method="POST" action="/fgkirs-admin/rulesets/store" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-2xl">
    <?= Csrf::field() ?>

    <div class="mb-5">
        <label for="name" class="block text-sm font-medium text-slate-700 mb-2">Nome *</label>
        <input type="text" id="name" name="name" required maxlength="120" placeholder="Ex: Regulamento FGKIRS 2027" class="<?= $input ?>">
        <p class="text-xs text-slate-500 mt-1.5">
            Para partir das regras atuais, use <strong>Duplicar</strong> na lista de regulamentos.
        </p>
    </div>

    <div class="mb-6">
        <label for="age_policy" class="block text-sm font-medium text-slate-700 mb-2">Como a idade é calculada</label>
        <select id="age_policy" name="age_policy" class="<?= $input ?> bg-white">
            <option value="event_date">Idade na data do evento</option>
            <option value="year_end">Idade que completa no ano</option>
        </select>
    </div>

    <button type="submit" class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">Criar</button>
</form>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
