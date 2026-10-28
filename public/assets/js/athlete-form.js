// Comportamento do formulário de atleta (página e modal): faixas filtradas pelo estilo,
// máscara de CPF e prévia da foto com o limite de 2 MB.
window.initAthleteForm = function (root) {
    if (!root) return;

    const styleSelect = root.querySelector('[name="style_id"]');
    const gradSelect  = root.querySelector('[name="graduation_id"]');

    function filterGraduations() {
        const style = styleSelect.value;
        let selectedHidden = false;

        Array.from(gradSelect.options).forEach(opt => {
            if (!opt.value) return;
            const hide = style !== '' && opt.dataset.style !== style;
            opt.hidden = hide;
            opt.disabled = hide;
            if (hide && opt.selected) selectedHidden = true;
        });

        if (selectedHidden) gradSelect.value = '';
    }

    if (styleSelect && gradSelect) {
        styleSelect.addEventListener('change', filterGraduations);
        gradSelect.addEventListener('change', () => {
            const opt = gradSelect.selectedOptions[0];
            if (opt && opt.dataset.style) { styleSelect.value = opt.dataset.style; filterGraduations(); }
        });
        filterGraduations();
    }

    const cpf = root.querySelector('[name="document"]');
    if (cpf) {
        cpf.addEventListener('input', e => {
            const d = e.target.value.replace(/\D/g, '').slice(0, 11);
            e.target.value = d
                .replace(/^(\d{3})(\d)/, '$1.$2')
                .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1-$2');
        });
    }

    // Convidado: a origem só aparece (e só é exigida) quando a caixa está marcada
    const guestToggle = root.querySelector('[data-guest-toggle]');
    const guestOrigin = root.querySelector('[data-guest-origin]');

    if (guestToggle && guestOrigin) {
        const field = guestOrigin.querySelector('input');
        const sync = () => {
            guestOrigin.classList.toggle('hidden', !guestToggle.checked);
            field.required = guestToggle.checked;
            if (guestToggle.checked) field.focus();
        };
        guestToggle.addEventListener('change', sync);
    }

    const photo       = root.querySelector('[data-athlete-photo]');
    const preview     = root.querySelector('[data-athlete-photo-preview]');
    const placeholder = root.querySelector('[data-athlete-photo-placeholder]');

    if (photo) {
        photo.addEventListener('change', () => {
            const file = photo.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                alert('A foto deve ter no máximo 2 MB.');
                photo.value = '';
                return;
            }

            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
            if (placeholder) placeholder.classList.add('hidden');
        });
    }
};
