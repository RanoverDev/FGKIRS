<?php
$p = $profile ?? [];
require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Perfil da Federação</h1>
    <p class="text-sm text-slate-500 mt-1">Dados de contato e redes sociais exibidos no site público.</p>
</div>

<div class="grid lg:grid-cols-3 gap-6 items-start">
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-6 sm:p-8">
        <form action="/fgkirs-admin/federation-profile/update" method="POST">

            <!-- Contato -->
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Contato</p>

            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="whatsapp" class="block text-sm font-medium text-gray-700 mb-2">WhatsApp</label>
                    <input type="text" id="whatsapp" name="whatsapp" data-mask="phone" value="<?= htmlspecialchars($p['whatsapp'] ?? '') ?>"
                        placeholder="(51) 99999-9999"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Fone Fixo</label>
                    <input type="text" id="phone" name="phone" data-mask="phone" value="<?= htmlspecialchars($p['phone'] ?? '') ?>"
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
                    <input type="text" id="zip_code" name="zip_code" data-mask="cep" maxlength="9"
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

    <!-- Webmail Access Card -->
    <div class="lg:col-span-1 bg-white rounded-lg shadow p-6 border border-slate-100">
        <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
            <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <h2 class="text-lg font-bold text-slate-850">Acesso ao Webmail</h2>
        </div>

        <p class="text-xs text-slate-500 mb-6">Contas de e-mail institucionais da federação. Clique para acessar o portal ou copie as credenciais.</p>

        <a href="http://webmail.fgkirs.com.br/" target="_blank" rel="noopener noreferrer" 
           class="flex items-center justify-center gap-2 w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 px-4 rounded-xl transition text-sm mb-6 shadow-sm">
            <span>Acessar Webmail</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
        </a>

        <div class="space-y-4">
            <!-- Account 1 -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Fale Com</p>
                <div class="flex items-center justify-between gap-2 mb-2">
                    <span class="text-xs font-medium text-slate-750 select-all">falecom@fgkirs.com.br</span>
                    <button onclick="copyToClipboard('falecom@fgkirs.com.br')" class="text-slate-400 hover:text-slate-650 transition" title="Copiar e-mail">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                    </button>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-slate-400">Senha:</span>
                        <input type="password" readonly id="pwd-falecom" value="FgkiRS@1447K" class="bg-transparent border-0 p-0 text-xs font-bold text-slate-800 focus:ring-0 focus:outline-none w-24">
                    </div>
                    <div class="flex gap-2">
                        <button onclick="toggleVisibility('pwd-falecom')" class="text-slate-400 hover:text-slate-650 transition" title="Mostrar senha">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <button onclick="copyToClipboard('FgkiRS@1447K')" class="text-slate-400 hover:text-slate-650 transition" title="Copiar senha">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Account 2 -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Presidente</p>
                <div class="flex items-center justify-between gap-2 mb-2">
                    <span class="text-xs font-medium text-slate-750 select-all">presidente@fgkirs.com.br</span>
                    <button onclick="copyToClipboard('presidente@fgkirs.com.br')" class="text-slate-400 hover:text-slate-655 transition" title="Copiar e-mail">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                    </button>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-slate-400">Senha:</span>
                        <input type="password" readonly id="pwd-presidente" value="FgkiRS@3g85h" class="bg-transparent border-0 p-0 text-xs font-bold text-slate-800 focus:ring-0 focus:outline-none w-24">
                    </div>
                    <div class="flex gap-2">
                        <button onclick="toggleVisibility('pwd-presidente')" class="text-slate-400 hover:text-slate-655 transition" title="Mostrar senha">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <button onclick="copyToClipboard('FgkiRS@3g85h')" class="text-slate-400 hover:text-slate-655 transition" title="Copiar senha">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Sem alert intrusivo, usar feedback suave ou console se necessário
    });
}
function toggleVisibility(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>