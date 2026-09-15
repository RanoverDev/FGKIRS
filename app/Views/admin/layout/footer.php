</main>
</div>

<script>
    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth < 1024) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.add('-translate-x-full');
            }
        }
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            if (!confirm('Tem certeza que deseja excluir este registro?')) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ── Phone / CEP masks ──────────────────────────────────────────────────
    function applyPhoneMask(input) {
        function fmt(v) {
            const d = v.replace(/\D/g, '').slice(0, 11);
            const n = d.length;
            if (n === 0) return '';
            if (n <= 2)  return `(${d}`;
            if (n <= 6)  return `(${d.slice(0,2)}) ${d.slice(2)}`;
            if (n <= 10) return `(${d.slice(0,2)}) ${d.slice(2,6)}-${d.slice(6)}`;
            return `(${d.slice(0,2)}) ${d.slice(2,7)}-${d.slice(7)}`;
        }
        input.addEventListener('input', function () { this.value = fmt(this.value); });
        if (input.value) input.value = fmt(input.value);
    }

    function applyCepMask(input) {
        function fmt(v) {
            const d = v.replace(/\D/g, '').slice(0, 8);
            return d.length <= 5 ? d : `${d.slice(0,5)}-${d.slice(5)}`;
        }
        input.addEventListener('input', function () { this.value = fmt(this.value); });
        if (input.value) input.value = fmt(input.value);
    }

    function applyCnpjMask(input) {
        function fmt(v) {
            const d = v.replace(/\D/g, '').slice(0, 14);
            if (d.length <= 2)  return d;
            if (d.length <= 5)  return `${d.slice(0,2)}.${d.slice(2)}`;
            if (d.length <= 8)  return `${d.slice(0,2)}.${d.slice(2,5)}.${d.slice(5)}`;
            if (d.length <= 12) return `${d.slice(0,2)}.${d.slice(2,5)}.${d.slice(5,8)}/${d.slice(8)}`;
            return `${d.slice(0,2)}.${d.slice(2,5)}.${d.slice(5,8)}/${d.slice(8,12)}-${d.slice(12)}`;
        }
        input.addEventListener('input', function () { this.value = fmt(this.value); });
        if (input.value) input.value = fmt(input.value);
    }

    document.querySelectorAll('[data-mask="phone"]').forEach(applyPhoneMask);
    document.querySelectorAll('[data-mask="cep"]').forEach(applyCepMask);
    document.querySelectorAll('[data-mask="cnpj"]').forEach(applyCnpjMask);
</script>
</body>

</html>