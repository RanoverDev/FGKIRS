<?php require_once __DIR__ . '/../partials/public_header.php'; ?>
<section class="py-16 bg-white min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center gap-3 mb-8">
            <span class="h-7 w-1 rounded-full bg-rs-red"></span>
            <h1 class="text-4xl font-bold text-slate-900">Notícias</h1>
        </div>

        <div id="news-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <!-- News items will be loaded here -->
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

function loadNews() {
    fetch(`/noticias/load?page=${currentPage}`)
        .then(response => response.json())
        .then(data => {
            const grid = document.getElementById('news-grid');
            if(data.data.length === 0) {
                document.getElementById('load-more').style.display = 'none';
                if(currentPage === 1) {
                    grid.innerHTML = '<p class="text-slate-400 text-sm italic col-span-full">Nenhuma notícia encontrada.</p>';
                }
                return;
            }

            data.data.forEach(post => {
                const img = post.featured_image ? `/uploads/posts/${post.featured_image}` : null;
                const dateObj = new Date(post.published_at || post.created_at);
                const dateStr = dateObj.toLocaleDateString('pt-BR');
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = post.content;
                const excerpt = tempDiv.textContent.substring(0, 100) + '...';

                const html = `
                    <article class="news-card bg-white rounded-xl shadow-md overflow-hidden border border-slate-100 flex flex-col">
                        <a href="/noticia/${post.slug || post.id}" class="aspect-video overflow-hidden bg-slate-800 block">
                            ${img ? `<img src="${img}" alt="${post.title}" class="w-full h-full object-cover object-center transition-transform duration-300 hover:scale-105" loading="lazy">` : `<div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-700 to-slate-900 transition-transform duration-300 hover:scale-105"><svg class="w-12 h-12 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg></div>`}
                        </a>
                        <div class="p-5 flex flex-col flex-1">
                            <time class="text-xs text-slate-400 mb-2">${dateStr}</time>
                            <h3 class="text-base font-bold text-slate-900 leading-snug mb-2 line-clamp-2">
                                <a href="/noticia/${post.slug || post.id}" class="hover:text-rs-red transition-colors">${post.title}</a>
                            </h3>
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
    loadNews();
});

loadNews();
</script>
</body>
</html>
