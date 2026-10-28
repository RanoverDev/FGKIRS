// Envio de imagens no painel: aceita fotos do iPhone (HEIC/HEIF). Se o servidor não decodifica
// HEIC (serverHeic = false), a conversão para JPEG acontece no navegador antes do envio.
window.FgkImages = (function () {
    const HEIC_RE = /\.(heic|heif)$/i;
    const CONVERTER_SRC = 'https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js';
    let converterPromise = null;

    function isHeic(file) {
        return HEIC_RE.test(file.name) || /^image\/hei[cf]/i.test(file.type);
    }

    function loadConverter() {
        if (window.heic2any) return Promise.resolve();
        if (!converterPromise) {
            converterPromise = new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = CONVERTER_SRC;
                s.onload = resolve;
                s.onerror = () => { converterPromise = null; reject(new Error('Falha ao carregar o conversor de HEIC.')); };
                document.head.appendChild(s);
            });
        }
        return converterPromise;
    }

    async function prepare(file, serverHeic) {
        if (serverHeic || !isHeic(file)) return file;
        await loadConverter();
        const out = await window.heic2any({ blob: file, toType: 'image/jpeg', quality: 0.9 });
        const blob = Array.isArray(out) ? out[0] : out;
        return new File([blob], file.name.replace(HEIC_RE, '.jpg'), { type: 'image/jpeg' });
    }

    // Converte a seleção de um <input type="file" multiple> no lugar e segura o envio do
    // formulário enquanto houver conversão em andamento.
    function bindInput(input, { serverHeic, statusEl }) {
        const form = input.form;
        let busy = false;

        if (form) {
            form.addEventListener('submit', e => {
                if (busy) {
                    e.preventDefault();
                    alert('Aguarde a conversão das fotos terminar.');
                }
            });
        }

        input.addEventListener('change', async () => {
            const files = Array.from(input.files || []);
            if (serverHeic || !files.some(isHeic)) return;

            busy = true;
            const submit = form ? form.querySelector('[type="submit"]') : null;
            if (submit) submit.disabled = true;
            const dt = new DataTransfer();
            let failed = 0;

            for (let i = 0; i < files.length; i++) {
                if (statusEl) statusEl.textContent = 'Convertendo fotos do iPhone... ' + (i + 1) + ' / ' + files.length;
                try {
                    dt.items.add(await prepare(files[i], false));
                } catch (e) {
                    failed++;
                }
            }

            input.files = dt.files;
            busy = false;
            if (submit) submit.disabled = false;
            if (statusEl) {
                statusEl.textContent = failed > 0 ? failed + ' foto(s) não puderam ser convertidas e foram ignoradas.' : '';
            }
        });
    }

    // Envia um arquivo por requisição (evita estourar post_max_size e max_file_uploads).
    // Cada resposta é JSON { processed, failed, error? }.
    async function uploadAll(files, url, { serverHeic, onProgress }) {
        let processed = 0, failed = 0;
        const errors = [];

        for (let i = 0; i < files.length; i++) {
            if (onProgress) onProgress(i, files.length, files[i].name);
            try {
                const file = await prepare(files[i], serverHeic);
                const body = new FormData();
                body.append('images[]', file);
                const res = await fetch(url, { method: 'POST', body, headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (data.error) { failed++; errors.push(files[i].name + ': ' + data.error); continue; }
                processed += data.processed;
                failed += data.failed;
            } catch (e) {
                failed++;
                errors.push(files[i].name + ': falha no envio.');
            }
        }

        if (onProgress) onProgress(files.length, files.length, '');
        return { processed, failed, errors };
    }

    return { isHeic, prepare, bindInput, uploadAll };
})();
