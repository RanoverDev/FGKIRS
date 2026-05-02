<?php
$p = $profile ?? [];
require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Perfil da Federação</h1>
    <p class="text-sm text-slate-500 mt-1">Dados de contato e redes sociais exibidos no site público.</p>
</div>

<div class="bg-white rounded-lg shadow p-6 sm:p-8 max-w-2xl">
    <form action="/fgkirs-admin/federation-profile/update" method="POST">

        <!-- Contato -->
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Contato</p>

        <div class="grid sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-2">WhatsApp</label>
                <input type="text" id="whatsapp" name="whatsapp" value="<?= htmlspecialchars($p['whatsapp'] ?? '') ?>"
                    placeholder="(51) 99999-9999"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Fone Fixo</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($p['phone'] ?? '') ?>"
                    placeholder="(51) 3333-3333"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            </div>
        </div>

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">E-mail</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($p['email'] ?? '') ?>"
                placeholder="contato@fgkirs.com.br"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <!-- Endereço -->
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4 mt-6">Endereço</p>

        <div class="mb-4">
            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Logradouro</label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($p['address'] ?? '') ?>"
                placeholder="Rua Exemplo, 123"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div class="grid sm:grid-cols-3 gap-4 mb-4">
            <div class="sm:col-span-1">
                <label for="city" class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                <input type="text" id="city" name="city" value="<?= htmlspecialchars($p['city'] ?? '') ?>"
                    placeholder="Porto Alegre"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            </div>
            <div>
                <label for="state" class="block text-sm font-medium text-gray-700 mb-2">Estado (UF)</label>
                <input type="text" id="state" name="state" maxlength="2"
                    value="<?= htmlspecialchars($p['state'] ?? '') ?>" placeholder="RS"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent uppercase">
            </div>
            <div>
                <label for="zip_code" class="block text-sm font-medium text-gray-700 mb-2">CEP</label>
                <input type="text" id="zip_code" name="zip_code" maxlength="9"
                    value="<?= htmlspecialchars($p['zip_code'] ?? '') ?>" placeholder="90000-000"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            </div>
        </div>

        <!-- Redes sociais -->
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4 mt-6">Redes Sociais</p>

        <div class="mb-4">
            <label for="facebook" class="block text-sm font-medium text-gray-700 mb-2">Facebook (URL)</label>
            <input type="url" id="facebook" name="facebook" value="<?= htmlspecialchars($p['facebook'] ?? '') ?>"
                placeholder="https://facebook.com/fgkirs"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div class="mb-6">
            <label for="instagram" class="block text-sm font-medium text-gray-700 mb-2">Instagram (URL)</label>
            <input type="url" id="instagram" name="instagram" value="<?= htmlspecialchars($p['instagram'] ?? '') ?>"
                placeholder="https://instagram.com/fgkirs"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <button type="submit"
            class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            Salvar Alterações
        </button>

    </form>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>