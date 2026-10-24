<?php
/** @var array $certificate */
/** @var string|null $backgroundUrl */
$title = htmlspecialchars($pageTitle ?? 'Certificado');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <style>
        @page { size: 1440px 1080px; margin: 0; }

        * { box-sizing: border-box; }

        html, body { margin: 0; background: #e2e8f0; }

        .toolbar {
            position: sticky; top: 0; z-index: 10; display: flex; gap: 12px; align-items: center; justify-content: space-between;
            padding: 10px 16px; background: #0f172a; color: #fff; font: 14px/1.4 system-ui, sans-serif;
        }
        .toolbar a { color: #cbd5e1; text-decoration: none; }
        .toolbar a:hover { color: #fff; }
        .toolbar button {
            background: #b91c1c; color: #fff; border: 0; border-radius: 8px; padding: 8px 18px; font: 600 14px system-ui, sans-serif; cursor: pointer;
        }
        .toolbar button:hover { background: #991b1b; }

        .stage { display: flex; justify-content: center; padding: 16px; }
        .scaler { transform-origin: top left; }

        /* Coordenadas em px do layout original (1440 × 1080) */
        .certificate { position: relative; width: 1440px; height: 1080px; background: #fff; overflow: hidden; font-family: 'Open Sans', sans-serif; color: #000; }
        .certificate .art { position: absolute; inset: 0; width: 100%; height: 100%; }
        .certificate .label {
            position: absolute; left: 352px; top: 541px; font-size: 28px; line-height: 38.6px; font-weight: 700; white-space: nowrap;
        }
        .certificate .body { position: absolute; left: 572px; top: 440px; width: 715px; font-size: 21.6px; line-height: 1.5625; text-align: justify; }
        .certificate .body p, .certificate .body div { margin: 0; }

        @media print {
            html, body { background: #fff; }
            .toolbar { display: none; }
            .stage { padding: 0; display: block; }
            .scaler { transform: none !important; width: 1440px !important; height: 1080px !important; }
            .certificate { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <a href="/fgkirs-admin/athletes/edit/<?= (int) $certificate['athlete_id'] ?>">← Voltar ao atleta</a>
        <span>No diálogo de impressão, escolha “Salvar como PDF” e desative cabeçalhos e rodapés.</span>
        <button type="button" id="print">Imprimir / Salvar PDF</button>
    </div>

    <div class="stage">
        <div class="scaler" id="scaler">
            <div class="certificate">
                <?php if ($backgroundUrl): ?>
                    <img class="art" src="<?= htmlspecialchars($backgroundUrl) ?>" alt="">
                <?php endif; ?>
                <div class="label"><?= htmlspecialchars($certificate['graduation_label']) ?></div>
                <div class="body"><?= $certificate['body_html'] /* sanitizado em Helpers\RichText antes de gravar */ ?></div>
            </div>
        </div>
    </div>

    <script>
        const scaler = document.getElementById('scaler');

        function fit() {
            const scale = Math.min(1, (window.innerWidth - 32) / 1440);
            scaler.style.transform = `scale(${scale})`;
            scaler.style.width = '1440px';
            scaler.style.height = '1080px';
            scaler.style.marginBottom = `${-1080 * (1 - scale)}px`;
            scaler.style.marginRight = `${-1440 * (1 - scale)}px`;
        }

        window.addEventListener('resize', fit);
        fit();

        document.getElementById('print').addEventListener('click', () => {
            (document.fonts ? document.fonts.ready : Promise.resolve()).then(() => window.print());
        });
    </script>
</body>

</html>
