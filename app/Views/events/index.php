<?php require_once __DIR__ . '/../partials/public_header.php'; ?>
<section class="py-16 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center gap-3 mb-8">
            <span class="h-7 w-1 rounded-full bg-rs-yellow"></span>
            <h1 class="text-4xl font-bold text-slate-900">Eventos</h1>
        </div>

        <div id="events-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <!-- Events items will be loaded here -->
        </div>

        <div class="mt-12 text-center">
            <button id="load-more" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 px-8 rounded-lg text-sm transition-all duration-200">
                Carregar mais
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../partials/public_footer.php'; ?>

<script>
let currentPage = 1;

function loadEvents() {
    fetch(`/eventos/load?page=${currentPage}`)
        .then(response => response.json())
        .then(data => {
            const grid = document.getElementById('events-grid');
            if(data.data.length === 0) {
                document.getElementById('load-more').style.display = 'none';
                if(currentPage === 1) {
                    grid.innerHTML = '<p class="text-slate-400 text-sm italic col-span-full">Nenhum evento encontrado.</p>';
                }
                return;
            }

            data.data.forEach(evt => {
                const img = evt.featured_image ? `/uploads/posts/${evt.featured_image}` : null;
                const dateObj = new Date(evt.event_date);
                const evtDay = String(dateObj.getUTCDate()).padStart(2, '0');
                const evtMonth = dateObj.toLocaleString('pt-BR', { month: 'short', timeZone: 'UTC' }).toUpperCase().replace('.', '');
                
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = evt.content || '';
                const excerpt = tempDiv.textContent.substring(0, 100) + (tempDiv.textContent.length > 100 ? '...' : '');

                const html = `
                    <article class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-100 flex flex-col hover:shadow-lg transition">
                        <a href="/evento/${evt.slug || evt.id}" class="aspect-video overflow-hidden bg-slate-800 block relative">
                            ${img ? `<img src="${img}" alt="${evt.title}" class="w-full h-full object-cover object-center transition-transform duration-300 hover:scale-105" loading="lazy">` : `<div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-800 to-slate-900 transition-transform duration-300 hover:scale-105"><svg class="w-12 h-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>`}
                            <div class="absolute top-4 left-4 bg-slate-900 text-white rounded-lg w-12 text-center py-1.5 leading-tight shadow-md">
                                <span class="block text-lg font-black">${evtDay}</span>
                                <span class="block text-[9px] font-semibold uppercase tracking-wide text-rs-yellow">${evtMonth}</span>
                            </div>
                        </a>
                        <div class="p-5 flex flex-col flex-1">
                            <h3 class="text-base font-bold text-slate-900 leading-snug mb-2 line-clamp-2">
                                <a href="/evento/${evt.slug || evt.id}" class="hover:text-rs-yellow transition-colors">${evt.title}</a>
                            </h3>
                            ${evt.event_location ? `<p class="flex items-start gap-1 text-xs text-slate-500 mb-3"><svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span>${evt.event_location}</span></p>` : ''}
                            <p class="text-sm text-slate-500 line-clamp-2 flex-1">
                                ${excerpt}
                            </p>
                        </div>
                    </article>
                `;
                grid.insertAdjacentHTML('beforeend', html);
            });

            if(data.data.length < 20) {
                document.getElementById('load-more').style.display = 'none';
            }
        });
}

document.getElementById('load-more').addEventListener('click', () => {
    currentPage++;
    loadEvents();
});

loadEvents();
</script>
</body>
</html>
