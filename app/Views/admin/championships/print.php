<?php
/**
 * Ficha oficial de inscricao — documento para conferencia e assinatura.
 *
 * Pagina propria, fora do layout do admin. O cabecalho e o rodape usam
 * position:fixed para repetirem em todas as folhas impressas; a margem do
 * @page reserva o espaco deles para o conteudo nunca passar por cima.
 */

use Helpers\Format;
use Models\ChampionshipAthlete;
use Models\ChampionshipReferee;

$isDojoScope = ($scope ?? 'dojo') === 'dojo';
$fp          = $profile ?? [];

$federationName = trim($fp['legal_name'] ?? '') ?: 'Federação Gaúcha de Karatê Interestilos';

$contactLine = array_filter([
    !empty($fp['address']) ? $fp['address'] : null,
    !empty($fp['city']) ? $fp['city'] . (!empty($fp['state']) ? '/' . $fp['state'] : '') : null,
    !empty($fp['zip_code']) ? 'CEP ' . Format::cep($fp['zip_code']) : null,
]);

$channels = array_filter([
    !empty($fp['phone']) ? Format::phone($fp['phone']) : null,
    !empty($fp['whatsapp']) ? 'WhatsApp ' . Format::phone($fp['whatsapp']) : null,
    !empty($fp['email']) ? $fp['email'] : null,
    !empty($fp['website']) ? $fp['website'] : 'fgkirs.com.br',
]);

$athleteCount = count($athletes);
$teamCount    = count($teams);
$entryCount   = 0;
foreach ($athletes as $athlete) {
    $entryCount += count($athlete['entries'] ?? []);
}

// Codigo de conferencia: identifica a folha sem depender de numeracao sequencial
$documentRef = sprintf(
    'FGKIRS-%d-%s-%s',
    (int) $championship['id'],
    $isDojoScope ? 'D' . (int) ($dojo['id'] ?? 0) : 'GERAL',
    date('ymd.Hi')
);

/** No consolidado da federacao as listas saem agrupadas por dojo. */
$athletesByDojo = [];
$teamsByDojo    = [];

if ($isDojoScope) {
    $key = $dojo['name'] ?? 'Dojo';
    $athletesByDojo[$key] = $athletes;
    $teamsByDojo[$key]    = $teams;
} else {
    foreach ($athletes as $athlete) {
        $athletesByDojo[$athlete['dojo_name']][] = $athlete;
    }
    foreach ($teams as $team) {
        $teamsByDojo[$team['dojo_name']][] = $team;
    }
}

$teamMemberNames = static function (array $team): string {
    $names = isset($team['members'])
        ? array_column($team['members'], 'name')
        : ($team['member_names'] ?? []);

    return implode(', ', $names);
};
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Inscrição – <?= htmlspecialchars($championship['title']) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Oswald:wght@500;600;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --rs-green: #00AB4E;
            --rs-red: #EE302F;
            --rs-yellow: #FFCB04;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #cbd5e1;
            --soft: #f1f5f9;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif;
            color: var(--ink);
            margin: 0;
            background: #e2e8f0;
            font-size: 12.5px;
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        h1, h2, h3 { font-family: 'Oswald', 'Arial Narrow', Arial, sans-serif; letter-spacing: .03em; }

        /* ── Barra de acoes (nunca impressa) ───────────────────────────── */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: var(--ink);
            padding: 12px 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .2);
        }

        .btn {
            background: var(--rs-red);
            color: #fff;
            border: 0;
            border-radius: 6px;
            padding: 9px 22px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-family: inherit;
        }

        .btn-ghost { background: transparent; color: #cbd5e1; border: 1px solid #475569; }

        /* ── Folha ─────────────────────────────────────────────────────── */
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 24px auto;
            background: #fff;
            padding: 14mm 14mm 0;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .12);
        }

        /* Moldura do documento: thead/tfoot sao a unica forma de repetir
           cabecalho e rodape em toda folha reservando o espaco correto —
           position:fixed nao reserva e acaba cobrindo o conteudo. */
        table.doc { width: 100%; border-collapse: collapse; }
        table.doc > thead > tr > td,
        table.doc > tfoot > tr > td,
        table.doc > tbody > tr > td { border: 0; padding: 0; }
        table.doc > thead { display: table-header-group; }
        table.doc > tfoot { display: table-footer-group; }
        table.doc > thead > tr > td { padding-bottom: 2px; }

        /* ── Papel timbrado ────────────────────────────────────────────── */
        .letterhead {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 8px;
        }

        .letterhead .brand { height: 52px; width: auto; flex-shrink: 0; }
        .letterhead .identity { flex: 1; min-width: 0; }
        .letterhead .affiliations { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .letterhead .affiliations img { height: 40px; width: auto; object-fit: contain; }

        /* O logo ja traz o nome por extenso: aqui a linha e de identificacao
           legal, nao de marca — por isso discreta e em corpo menor. */
        .org-name {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .01em;
            line-height: 1.3;
            margin: 0;
            color: var(--ink);
        }

        .org-meta { font-size: 9px; color: var(--muted); line-height: 1.5; margin: 1px 0 0; }

        .rs-rule {
            height: 3px;
            background: linear-gradient(to right,
                var(--rs-green) 0 33.33%,
                var(--rs-red) 33.33% 66.66%,
                var(--rs-yellow) 66.66% 100%);
            margin-bottom: 12px;
        }

        /* ── Titulo do documento ───────────────────────────────────────── */
        .doc-title {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 2px solid var(--ink);
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .doc-title h1 { font-size: 18px; margin: 0; text-transform: uppercase; }
        .doc-title .event { font-size: 13px; color: var(--ink); font-weight: 600; margin: 3px 0 0; }
        .doc-ref { font-size: 9px; color: var(--muted); text-align: right; white-space: nowrap; }

        /* ── Bloco de dados do evento ──────────────────────────────────── */
        .facts {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border: 1px solid var(--line);
            margin-bottom: 16px;
        }

        .facts div { padding: 7px 10px; border-right: 1px solid var(--line); }
        .facts div:last-child { border-right: 0; }
        .facts dt { font-size: 8.5px; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); margin: 0; }
        .facts dd { font-size: 11.5px; font-weight: 600; margin: 2px 0 0; }

        /* ── Secoes e tabelas ──────────────────────────────────────────── */
        h2.section {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .09em;
            margin: 18px 0 7px;
            padding: 5px 10px;
            background: var(--ink);
            color: #fff;
            display: flex;
            justify-content: space-between;
        }

        h2.section span { font-weight: 400; font-size: 10.5px; letter-spacing: .04em; }

        h3.dojo {
            font-size: 11.5px;
            margin: 12px 0 5px;
            padding-left: 8px;
            border-left: 3px solid var(--rs-red);
            text-transform: uppercase;
        }

        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }

        th, td { border: 1px solid var(--line); padding: 4px 7px; text-align: left; vertical-align: top; }

        th {
            background: var(--soft);
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 600;
            color: #334155;
        }

        td { font-size: 11px; }
        tbody tr:nth-child(even) td { background: #fafbfc; }

        td.num, th.num { width: 26px; text-align: center; color: var(--muted); }
        td.center, th.center { text-align: center; white-space: nowrap; }
        td.name { font-weight: 600; }

        ul.categories { margin: 0; padding-left: 13px; }
        ul.categories li { margin-bottom: 1px; }

        .tag {
            display: inline-block;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: 1px 4px;
            border-radius: 3px;
            background: var(--soft);
            color: #475569;
            border: 1px solid var(--line);
        }

        .empty { font-size: 11px; color: var(--muted); font-style: italic; padding: 6px 0; }

        /* ── Fechamento ────────────────────────────────────────────────── */
        .closing { padding-top: 16px; }

        .totals {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border: 2px solid var(--ink);
            margin-bottom: 14px;
        }

        .totals div { padding: 8px 10px; border-right: 1px solid var(--line); text-align: center; }
        .totals div:last-child { border-right: 0; }
        .totals strong { display: block; font-family: 'Oswald', Arial, sans-serif; font-size: 20px; line-height: 1; }
        .totals span { font-size: 8.5px; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); }

        .statement {
            font-size: 10.5px;
            line-height: 1.6;
            color: #334155;
            text-align: justify;
            border-left: 3px solid var(--rs-yellow);
            padding: 2px 0 2px 10px;
            margin-bottom: 26px;
        }

        .signatures { display: flex; gap: 28px; justify-content: space-between; margin-bottom: 14px; }

        .signature { flex: 1; text-align: center; }
        .signature .line { border-top: 1px solid var(--ink); margin-top: 34px; padding-top: 5px; }
        .signature .role { font-size: 10px; font-weight: 600; }
        .signature .hint { font-size: 8.5px; color: var(--muted); }

        /* ── Rodape ────────────────────────────────────────────────────── */
        .colophon {
            border-top: 1px solid var(--line);
            padding: 7px 0 10px;
            font-size: 8.5px;
            color: var(--muted);
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        /* ══════════════════ IMPRESSAO ══════════════════ */
        @media print {
            body { background: #fff; font-size: 10.5px; }
            .toolbar, .screen-hint { display: none !important; }

            .page {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            @page { size: A4 portrait; margin: 11mm 13mm; }

            .content table { page-break-inside: auto; }
            .content tr { page-break-inside: avoid; page-break-after: auto; }
            .content thead { display: table-header-group; }
            h2.section, h3.dojo { page-break-after: avoid; }
            .totals, .signatures, .statement { page-break-inside: avoid; }
            .closing { page-break-inside: avoid; }
        }

        @media screen and (max-width: 860px) {
            .page { width: auto; margin: 12px; padding: 16px; }
            .facts, .totals { grid-template-columns: repeat(2, 1fr); }
            .signatures { flex-direction: column; gap: 8px; }
        }
    </style>
</head>

<body>

    <div class="toolbar">
        <button type="button" class="btn" onclick="window.print()">Imprimir / Salvar em PDF</button>
        <a href="#" class="btn btn-ghost" onclick="window.close(); return false;">Fechar</a>
    </div>

    <div class="page">
        <table class="doc">

        <thead>
            <tr><td>
                <div class="letterhead">
                <img src="/assets/images/FGKIRS-LOGO.png" alt="FGKIRS" class="brand"
                    onerror="this.style.display='none'">
                <div class="identity">
                    <p class="org-name"><?= htmlspecialchars($federationName) ?></p>
                    <p class="org-meta">
                        <?php if (!empty($fp['cnpj'])): ?>
                            CNPJ <?= htmlspecialchars(Format::cnpj($fp['cnpj'])) ?><br>
                        <?php endif; ?>
                        <?php if ($contactLine): ?>
                            <?= htmlspecialchars(implode(' · ', $contactLine)) ?><br>
                        <?php endif; ?>
                        <?= htmlspecialchars(implode(' · ', $channels)) ?>
                    </p>
                </div>
                <div class="affiliations">
                    <img src="/assets/images/logo-wukf.png" alt="WUKF" onerror="this.style.display='none'">
                    <img src="/assets/images/logo-cbki.png" alt="CBKI" onerror="this.style.display='none'">
                </div>
            </div>
                <div class="rs-rule"></div>
            </td></tr>
        </thead>

        <tfoot>
            <tr><td>
                <div class="colophon">
                    <span><?= htmlspecialchars($federationName) ?><?= !empty($fp['cnpj']) ? ' · CNPJ ' . htmlspecialchars(Format::cnpj($fp['cnpj'])) : '' ?></span>
                    <span><?= htmlspecialchars(implode(' · ', array_slice($channels, -2))) ?></span>
                    <span><?= htmlspecialchars($documentRef) ?></span>
                </div>
            </td></tr>
        </tfoot>

        <tbody>
            <tr><td class="content">

        <div class="doc-title">
            <div>
                <h1>Ficha de Inscrição</h1>
                <p class="event"><?= htmlspecialchars($championship['title']) ?></p>
            </div>
            <div class="doc-ref">
                Documento <?= htmlspecialchars($documentRef) ?><br>
                Emitido em <?= date('d/m/Y \à\s H:i') ?>
            </div>
        </div>

        <dl class="facts">
            <div>
                <dt>Data da competição</dt>
                <dd><?= date('d/m/Y', strtotime($championship['event_date'])) ?></dd>
            </div>
            <div>
                <dt>Local</dt>
                <dd><?= htmlspecialchars($championship['location'] ?: 'A definir') ?></dd>
            </div>
            <div>
                <dt><?= $isDojoScope ? 'Dojo / Academia' : 'Abrangência' ?></dt>
                <dd>
                    <?php if ($isDojoScope): ?>
                        <?= htmlspecialchars($dojo['name'] ?? '—') ?>
                    <?php else: ?>
                        Todos os dojos filiados
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt><?= $isDojoScope ? 'Cidade do dojo' : 'Dojos participantes' ?></dt>
                <dd>
                    <?php if ($isDojoScope): ?>
                        <?= htmlspecialchars(trim(($dojo['city'] ?? '') . (!empty($dojo['state']) ? '/' . $dojo['state'] : ''))) ?: '—' ?>
                    <?php else: ?>
                        <?= count($athletesByDojo) ?>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>

        <h2 class="section">
            Atletas — Individual
            <span><?= Format::plural($athleteCount, 'atleta', 'atletas') ?> · <?= Format::plural($entryCount, 'inscrição', 'inscrições') ?></span>
        </h2>

        <?php if (!$athleteCount): ?>
            <p class="empty">Nenhum atleta inscrito.</p>
        <?php else: ?>
            <?php foreach ($athletesByDojo as $dojoName => $dojoAthletes): ?>
                <?php if (!$isDojoScope): ?>
                    <h3 class="dojo"><?= htmlspecialchars($dojoName) ?> — <?= Format::plural(count($dojoAthletes), 'atleta', 'atletas') ?></h3>
                <?php endif; ?>

                <table>
                    <thead>
                        <tr>
                            <th class="num">#</th>
                            <th>Atleta</th>
                            <th class="center">Nascimento</th>
                            <th class="center">Idade</th>
                            <th class="center">Sexo</th>
                            <th>Graduação</th>
                            <th class="center">Peso</th>
                            <th>Categorias inscritas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dojoAthletes as $index => $athlete): ?>
                            <?php $age = ChampionshipAthlete::ageOn($athlete['birth_date'], $championship['event_date']); ?>
                            <tr>
                                <td class="num"><?= $index + 1 ?></td>
                                <td class="name">
                                    <?= htmlspecialchars($athlete['name']) ?>
                                    <?php if (!empty($athlete['is_para_karate'])): ?>
                                        <span class="tag">Para-karatê</span>
                                    <?php endif; ?>
                                    <?php if (!empty($athlete['is_guest'])): ?>
                                        <span class="tag">Avulso</span>
                                    <?php endif; ?>
                                </td>
                                <td class="center"><?= date('d/m/Y', strtotime($athlete['birth_date'])) ?></td>
                                <td class="center"><?= $age ?></td>
                                <td class="center"><?= $athlete['gender'] === 'M' ? 'M' : 'F' ?></td>
                                <td><?= htmlspecialchars($athlete['belt_name'] ?? '—') ?></td>
                                <td class="center">
                                    <?= htmlspecialchars(Format::weight($athlete['weight'] !== null ? (float) $athlete['weight'] : null)) ?>
                                </td>
                                <td>
                                    <?php if (empty($athlete['entries'])): ?>
                                        —
                                    <?php else: ?>
                                        <ul class="categories">
                                            <?php foreach ($athlete['entries'] as $entry): ?>
                                                <li><?= htmlspecialchars($entry['category_name']) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>

        <h2 class="section">
            Equipes
            <span><?= Format::plural($teamCount, 'equipe', 'equipes') ?></span>
        </h2>

        <?php if (!$teamCount): ?>
            <p class="empty">Nenhuma equipe inscrita.</p>
        <?php else: ?>
            <?php foreach ($teamsByDojo as $dojoName => $dojoTeams): ?>
                <?php if (!$isDojoScope): ?>
                    <h3 class="dojo"><?= htmlspecialchars($dojoName) ?> — <?= Format::plural(count($dojoTeams), 'equipe', 'equipes') ?></h3>
                <?php endif; ?>

                <table>
                    <thead>
                        <tr>
                            <th class="num">#</th>
                            <th>Equipe</th>
                            <th>Categoria</th>
                            <th>Integrantes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dojoTeams as $index => $team): ?>
                            <tr>
                                <td class="num"><?= $index + 1 ?></td>
                                <td class="name"><?= htmlspecialchars($team['name'] ?: 'Equipe ' . chr(65 + $index)) ?></td>
                                <td><?= htmlspecialchars($team['category_name']) ?></td>
                                <td><?= htmlspecialchars($teamMemberNames($team)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($referees)): ?>
            <h2 class="section">
                Árbitros e equipe de mesa
                <span><?= Format::plural(count($referees), 'indicado', 'indicados') ?></span>
            </h2>

            <table>
                <thead>
                    <tr>
                        <th class="num">#</th>
                        <th>Nome</th>
                        <th>Função</th>
                        <th>Qualificação</th>
                        <?php if (!$isDojoScope): ?>
                            <th>Dojo</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referees as $index => $referee): ?>
                        <tr>
                            <td class="num"><?= $index + 1 ?></td>
                            <td class="name"><?= htmlspecialchars($referee['name']) ?></td>
                            <td><?= htmlspecialchars(ChampionshipReferee::ROLES[$referee['role']] ?? '') ?></td>
                            <td><?= htmlspecialchars($referee['qualification'] ?: '—') ?></td>
                            <?php if (!$isDojoScope): ?>
                                <td><?= htmlspecialchars($referee['dojo_name'] ?? '') ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="closing">
            <div class="totals">
                <div>
                    <strong><?= $athleteCount ?></strong>
                    <span>Atletas</span>
                </div>
                <div>
                    <strong><?= $entryCount ?></strong>
                    <span>Inscrições em categorias</span>
                </div>
                <div>
                    <strong><?= $teamCount ?></strong>
                    <span>Equipes</span>
                </div>
                <div>
                    <strong><?= count($referees ?? []) ?></strong>
                    <span>Árbitros</span>
                </div>
            </div>

            <p class="statement">
                Declaro, para os devidos fins, que o responsável pelo Dojo/equipe está ciente de que todas as
                informações prestadas nesta ficha de inscrição — nomes, datas de nascimento, graduações, pesos e
                categorias — estão corretas e são de sua inteira responsabilidade, respondendo por eventuais
                divergências constatadas no dia da competição.
            </p>

            <div class="signatures">
                <div class="signature">
                    <div class="line">
                        <span class="role">Responsável pelo Dojo</span><br>
                        <span class="hint">Nome legível e assinatura</span>
                    </div>
                </div>
                <div class="signature">
                    <div class="line">
                        <span class="role">Local e data</span><br>
                        <span class="hint">_______________________, ____ / ____ / ________</span>
                    </div>
                </div>
                <?php if (!$isDojoScope): ?>
                    <div class="signature">
                        <div class="line">
                            <span class="role">Conferência da Federação</span><br>
                            <span class="hint">Assinatura e carimbo</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

            </td></tr>
        </tbody>

        </table>
    </div>

    <p class="screen-hint" style="text-align:center;font-size:11px;color:#64748b;margin:0 0 28px">
        Para gerar o PDF, clique em <strong>Imprimir</strong> e escolha “Salvar como PDF” no destino.
    </p>

</body>

</html>
