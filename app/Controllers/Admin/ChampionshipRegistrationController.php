<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Helpers\Auth;
use Helpers\Csrf;
use Helpers\DojoScope;
use Models\Championship;
use Models\ChampionshipAthlete;
use Models\ChampionshipReferee;
use Models\ChampionshipTeam;
use Models\CompetitionCategory;
use Models\FederationProfile;
use PDO;

/**
 * Inscricoes de um campeonato na visao do dojo (abas Atletas, Equipes,
 * Arbitros e Inscritos).
 *
 * Regra de ouro deste controller: o dojo NUNCA vem da URL. Ele e resolvido da
 * sessao e todo id recebido e reconferido contra ele antes de qualquer leitura
 * ou escrita.
 */
class ChampionshipRegistrationController extends Controller
{
    // ─────────────────────────── Abas ───────────────────────────

    public function athletes(int $championshipId): void
    {
        [$championship, $dojoId] = $this->context($championshipId);

        $athletes = $dojoId ? ChampionshipAthlete::forDojo($championshipId, $dojoId) : [];

        $grouped = ['black' => [], 'colored' => []];
        foreach ($athletes as $athlete) {
            $athlete['age'] = ChampionshipAthlete::ageOn($athlete['birth_date'], $championship['event_date']);
            $grouped[$athlete['belt_group']][] = $athlete;
        }

        $this->render('athletes', $championship, $dojoId, [
            'grouped'     => $grouped,
            'styles'      => $this->styles(),
            'graduations' => $this->graduations(),
        ]);
    }

    public function teams(int $championshipId): void
    {
        [$championship, $dojoId] = $this->context($championshipId);

        $categories = CompetitionCategory::teams();
        $counts     = $dojoId ? ChampionshipTeam::countsByCategory($championshipId, $dojoId) : [];
        $teams      = $dojoId ? ChampionshipTeam::forDojo($championshipId, $dojoId) : [];

        $byCategory = [];
        foreach ($teams as $team) {
            $byCategory[(int) $team['category_id']][] = $team;
        }

        $this->render('teams', $championship, $dojoId, [
            'categories' => $categories,
            'counts'     => $counts,
            'byCategory' => $byCategory,
        ]);
    }

    public function referees(int $championshipId): void
    {
        [$championship, $dojoId] = $this->context($championshipId);

        $this->render('referees', $championship, $dojoId, [
            'referees' => $dojoId ? ChampionshipReferee::forDojo($championshipId, $dojoId) : [],
        ]);
    }

    public function summary(int $championshipId): void
    {
        [$championship, $dojoId] = $this->context($championshipId);

        $this->render('summary', $championship, $dojoId, $this->summaryData($championshipId, $dojoId, $championship));
    }

    /** Versao para impressao do dojo — o navegador salva como PDF. */
    public function summaryPrint(int $championshipId): void
    {
        [$championship, $dojoId] = $this->context($championshipId);

        if (!$dojoId) {
            DojoScope::deny('Selecione um dojo para gerar a versão para impressão.');
        }

        $data = $this->summaryData($championshipId, $dojoId, $championship);

        $this->view('admin/championships/print', array_merge($data, [
            'championship' => $championship,
            'profile'      => FederationProfile::get(),
            'scope'        => 'dojo',
            'dojo'         => $this->dojo($dojoId),
            'referees'     => ChampionshipReferee::forDojo($championshipId, $dojoId),
        ]));
    }

    // ───────────────────── Atletas (escrita) ─────────────────────

    /** Autocomplete dos alunos do proprio dojo. */
    public function searchStudents(int $championshipId): void
    {
        [, $dojoId] = $this->context($championshipId);

        if (!$dojoId) {
            $this->json([]);
        }

        $term = trim($_GET['q'] ?? '');

        if (mb_strlen($term) < 2) {
            $this->json([]);
        }

        $this->json(ChampionshipAthlete::searchDojoStudents($championshipId, $dojoId, $term));
    }

    public function storeAthlete(int $championshipId): void
    {
        [$championship, $dojoId] = $this->writeContext($championshipId);

        $back = $this->tabUrl($championshipId, 'athletes', $dojoId);

        $source = ($_POST['source'] ?? 'student') === 'guest' ? 'guest' : 'student';
        $data   = $source === 'student'
            ? $this->athleteFromStudent($championshipId, $dojoId, $back)
            : $this->athleteFromGuest($championshipId, $dojoId, $back);

        try {
            ChampionshipAthlete::create($data);
            $_SESSION['success'] = 'Atleta inscrito no evento!';
        } catch (\Exception $e) {
            error_log('storeAthlete error: ' . $e->getMessage());
            $_SESSION['error'] = 'Não foi possível inscrever este atleta. Ele já pode estar na lista.';
        }

        $this->redirect($back);
    }

    public function updateAthlete(int $championshipId, int $athleteId): void
    {
        [$championship, $dojoId] = $this->writeContext($championshipId);

        $athlete = $this->athleteOf($championshipId, $athleteId);
        $back    = $this->tabUrl($championshipId, 'athletes', $dojoId);

        $graduationId = ($_POST['graduation_id'] ?? '') !== '' ? (int) $_POST['graduation_id'] : null;
        $beltGroup    = ChampionshipAthlete::beltGroupFor($graduationId);

        if ($athlete['is_guest'] && $beltGroup === 'black') {
            $_SESSION['error'] = 'Atletas de faixa preta precisam estar cadastrados no dojo.';
            $this->redirect($back);
        }

        ChampionshipAthlete::update($athleteId, [
            'name'           => trim($_POST['name'] ?? $athlete['name']) ?: $athlete['name'],
            'gender'         => isset(ChampionshipAthlete::GENDERS[$_POST['gender'] ?? '']) ? $_POST['gender'] : $athlete['gender'],
            'birth_date'     => $_POST['birth_date'] ?: $athlete['birth_date'],
            'style_id'       => ($_POST['style_id'] ?? '') !== '' ? (int) $_POST['style_id'] : null,
            'graduation_id'  => $graduationId,
            'belt_group'     => $beltGroup,
            'weight'         => ($_POST['weight'] ?? '') !== '' ? $this->decimal($_POST['weight']) : null,
            'is_para_karate' => !empty($_POST['is_para_karate']) ? 1 : 0,
        ]);

        $_SESSION['success'] = 'Dados do atleta atualizados!';
        $this->redirect($back);
    }

    public function deleteAthlete(int $championshipId, int $athleteId): void
    {
        $this->assertDeleteToken();

        [, $dojoId] = $this->writeContext($championshipId);

        $this->athleteOf($championshipId, $athleteId);
        ChampionshipAthlete::delete($athleteId);

        $_SESSION['success'] = 'Atleta removido do evento.';
        $this->redirect($this->tabUrl($championshipId, 'athletes', $dojoId));
    }

    /** Categorias compativeis com o atleta, cruzando sexo, idade e graduacao. */
    public function athleteCategories(int $championshipId, int $athleteId): void
    {
        [$championship] = $this->context($championshipId);

        $athlete = $this->athleteOf($championshipId, $athleteId);
        $age     = ChampionshipAthlete::ageOn($athlete['birth_date'], $championship['event_date']);

        $categories = CompetitionCategory::forIndividual($athlete['gender'], $athlete['belt_group'], $age);
        $taken      = array_column(ChampionshipAthlete::entries($athleteId), 'category_id');

        $payload = [];
        foreach ($categories as $category) {
            $payload[] = [
                'id'              => (int) $category['id'],
                'name'            => $category['name'],
                'modality'        => $category['modality'],
                'requires_weight' => CompetitionCategory::requiresWeight($category),
                'weight_min'      => $category['weight_min'] !== null ? (float) $category['weight_min'] : null,
                'weight_max'      => $category['weight_max'] !== null ? (float) $category['weight_max'] : null,
                'already_entered' => in_array((int) $category['id'], array_map('intval', $taken), true),
            ];
        }

        $this->json([
            'athlete' => [
                'id'     => (int) $athlete['id'],
                'name'   => $athlete['name'],
                'age'    => $age,
                'weight' => $athlete['weight'] !== null ? (float) $athlete['weight'] : null,
            ],
            'categories' => $payload,
        ]);
    }

    public function storeEntry(int $championshipId): void
    {
        [$championship, $dojoId] = $this->writeContext($championshipId);

        $athleteId  = (int) ($_POST['championship_athlete_id'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $back       = $this->tabUrl($championshipId, 'athletes', $dojoId);

        $athlete  = $this->athleteOf($championshipId, $athleteId);
        $category = CompetitionCategory::find($categoryId);

        if (!$category || $category['entry_type'] !== 'individual' || !$category['is_active']) {
            $_SESSION['error'] = 'Categoria inválida.';
            $this->redirect($back);
        }

        if ($error = $this->validateEntry($athlete, $category, $championship)) {
            $_SESSION['error'] = $error;
            $this->redirect($back);
        }

        $weight = ($_POST['weight'] ?? '') !== '' ? $this->decimal($_POST['weight']) : null;

        if (CompetitionCategory::requiresWeight($category)) {
            if ($weight === null) {
                $_SESSION['error'] = 'Informe o peso do atleta para inscrevê-lo em uma categoria de Kumite.';
                $this->redirect($back);
            }

            if (!CompetitionCategory::weightFits($category, $weight)) {
                $_SESSION['error'] = 'O peso informado não corresponde à faixa de peso da categoria escolhida.';
                $this->redirect($back);
            }

            // O peso mais recente vale para as proximas categorias do mesmo atleta
            ChampionshipAthlete::update($athleteId, [
                'name'           => $athlete['name'],
                'gender'         => $athlete['gender'],
                'birth_date'     => $athlete['birth_date'],
                'style_id'       => $athlete['style_id'],
                'graduation_id'  => $athlete['graduation_id'],
                'belt_group'     => $athlete['belt_group'],
                'weight'         => $weight,
                'is_para_karate' => $athlete['is_para_karate'],
            ]);
        }

        ChampionshipAthlete::addEntry($athleteId, $categoryId, $weight);

        $_SESSION['success'] = 'Atleta inscrito na categoria!';
        $this->redirect($back);
    }

    public function deleteEntry(int $championshipId, int $entryId): void
    {
        $this->assertDeleteToken();

        [, $dojoId] = $this->writeContext($championshipId);

        $entry = ChampionshipAthlete::findEntry($entryId);

        if (!$entry || (int) $entry['championship_id'] !== $championshipId) {
            DojoScope::deny();
        }

        DojoScope::assertDojo((int) $entry['dojo_id']);
        ChampionshipAthlete::removeEntry($entryId);

        $_SESSION['success'] = 'Inscrição removida da categoria.';
        $this->redirect($this->tabUrl($championshipId, 'athletes', $dojoId));
    }

    // ───────────────────── Equipes (escrita) ─────────────────────

    /** Atletas do dojo que cabem na categoria de equipe escolhida. */
    public function teamEligible(int $championshipId, int $categoryId): void
    {
        [$championship, $dojoId] = $this->context($championshipId);

        $category = CompetitionCategory::find($categoryId);

        if (!$dojoId || !$category || $category['entry_type'] !== 'team') {
            $this->json(['athletes' => [], 'team_size' => null]);
        }

        $eligible = ChampionshipAthlete::eligibleForTeam(
            $championshipId,
            $dojoId,
            $category,
            $championship['event_date']
        );

        $this->json([
            'team_size' => $category['team_size'] !== null ? (int) $category['team_size'] : null,
            'athletes'  => array_map(fn(array $a) => [
                'id'   => (int) $a['id'],
                'name' => $a['name'],
                'age'  => $a['age'],
            ], $eligible),
        ]);
    }

    public function storeTeam(int $championshipId): void
    {
        [$championship, $dojoId] = $this->writeContext($championshipId);

        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $members    = array_map('intval', (array) ($_POST['members'] ?? []));
        $back       = $this->tabUrl($championshipId, 'teams', $dojoId);

        $category = CompetitionCategory::find($categoryId);

        if (!$category || $category['entry_type'] !== 'team' || !$category['is_active']) {
            $_SESSION['error'] = 'Categoria de equipe inválida.';
            $this->redirect($back);
        }

        $members = array_values(array_unique(array_filter($members)));
        $size    = $category['team_size'] !== null ? (int) $category['team_size'] : null;

        if ($size !== null && count($members) !== $size) {
            $_SESSION['error'] = "Esta categoria exige exatamente $size integrantes.";
            $this->redirect($back);
        }

        if ($size === null && count($members) < 2) {
            $_SESSION['error'] = 'Selecione pelo menos 2 integrantes para a equipe.';
            $this->redirect($back);
        }

        foreach ($members as $memberId) {
            $athlete = $this->athleteOf($championshipId, $memberId);
            $age     = ChampionshipAthlete::ageOn($athlete['birth_date'], $championship['event_date']);

            if (!CompetitionCategory::matchesAge($category, $age)) {
                $_SESSION['error'] = "{$athlete['name']} não tem idade compatível com esta categoria.";
                $this->redirect($back);
            }

            if (!CompetitionCategory::matchesGender($category, $athlete['gender'])) {
                $_SESSION['error'] = "{$athlete['name']} não pode competir nesta categoria por sexo.";
                $this->redirect($back);
            }
        }

        $teamId = ChampionshipTeam::create([
            'championship_id' => $championshipId,
            'dojo_id'         => $dojoId,
            'category_id'     => $categoryId,
            'name'            => trim($_POST['name'] ?? '') ?: null,
        ]);

        ChampionshipTeam::syncMembers($teamId, $members);

        $_SESSION['success'] = 'Equipe inscrita com sucesso!';
        $this->redirect($back);
    }

    public function deleteTeam(int $championshipId, int $teamId): void
    {
        $this->assertDeleteToken();

        [, $dojoId] = $this->writeContext($championshipId);

        $team = ChampionshipTeam::find($teamId);

        if (!$team || (int) $team['championship_id'] !== $championshipId) {
            DojoScope::deny();
        }

        DojoScope::assertDojo((int) $team['dojo_id']);
        ChampionshipTeam::delete($teamId);

        $_SESSION['success'] = 'Equipe removida.';
        $this->redirect($this->tabUrl($championshipId, 'teams', $dojoId));
    }

    // ───────────────────── Arbitros (escrita) ─────────────────────

    public function searchReferees(int $championshipId): void
    {
        [, $dojoId] = $this->context($championshipId);

        if (!$dojoId) {
            $this->json([]);
        }

        $term = trim($_GET['q'] ?? '');

        if (mb_strlen($term) < 2) {
            $this->json([]);
        }

        $this->json(ChampionshipReferee::searchDojoMembers($championshipId, $dojoId, $term));
    }

    public function storeReferee(int $championshipId): void
    {
        [, $dojoId] = $this->writeContext($championshipId);

        $back = $this->tabUrl($championshipId, 'referees', $dojoId);

        $userId = ($_POST['user_id'] ?? '') !== '' ? (int) $_POST['user_id'] : null;
        $name   = trim($_POST['name'] ?? '');

        if ($userId) {
            $member = Database::getInstance()->query(
                "SELECT id, name FROM users WHERE id = :id AND dojo_id = :dojo_id LIMIT 1",
                ['id' => $userId, 'dojo_id' => $dojoId]
            )->fetch(PDO::FETCH_ASSOC);

            if (!$member) {
                DojoScope::deny('Este usuário não pertence ao seu dojo.');
            }

            $name = $member['name'];
        }

        if ($name === '') {
            $_SESSION['error'] = 'Informe o nome do árbitro.';
            $this->redirect($back);
        }

        ChampionshipReferee::create([
            'championship_id' => $championshipId,
            'dojo_id'         => $dojoId,
            'user_id'         => $userId,
            'name'            => $name,
            'role'            => isset(ChampionshipReferee::ROLES[$_POST['role'] ?? '']) ? $_POST['role'] : 'referee',
            'qualification'   => trim($_POST['qualification'] ?? '') ?: null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
            'registered_by'   => (int) Auth::id(),
        ]);

        $_SESSION['success'] = 'Árbitro indicado para o evento!';
        $this->redirect($back);
    }

    public function deleteReferee(int $championshipId, int $refereeId): void
    {
        $this->assertDeleteToken();

        [, $dojoId] = $this->writeContext($championshipId);

        $referee = ChampionshipReferee::find($refereeId);

        if (!$referee || (int) $referee['championship_id'] !== $championshipId) {
            DojoScope::deny();
        }

        DojoScope::assertDojo((int) $referee['dojo_id']);
        ChampionshipReferee::delete($refereeId);

        $_SESSION['success'] = 'Árbitro removido.';
        $this->redirect($this->tabUrl($championshipId, 'referees', $dojoId));
    }

    // ───────────────────────── Internos ─────────────────────────

    /**
     * Contexto de leitura: o evento e o dojo em que estamos operando. O sensei
     * sempre cai no proprio dojo; o admin escolhe um.
     */
    private function context(int $championshipId): array
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->redirect('/login');
        }

        $championship = Championship::findForCurrentUser($championshipId);

        if (!$championship) {
            $_SESSION['error'] = 'Evento não encontrado.';
            $this->redirect('/fgkirs-admin/championships');
        }

        $requested = isset($_REQUEST['dojo_id']) && $_REQUEST['dojo_id'] !== ''
            ? (int) $_REQUEST['dojo_id']
            : null;

        $dojoId = DojoScope::resolveDojoId($requested);

        if ($dojoId === null && !Auth::isAdmin()) {
            DojoScope::deny('Seu usuário não está vinculado a nenhum dojo. Fale com a federação.');
        }

        return [$championship, $dojoId];
    }

    /** Contexto de escrita: exige CSRF, prazo aberto e um dojo definido. */
    private function writeContext(int $championshipId): array
    {
        [$championship, $dojoId] = $this->context($championshipId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::validate($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = 'Sessão expirada. Tente novamente.';
            $this->redirect($this->tabUrl($championshipId, 'athletes', $dojoId));
        }

        Championship::assertRegistrationOpen($championship);

        if ($dojoId === null) {
            DojoScope::deny('Selecione um dojo antes de inscrever.');
        }

        return [$championship, $dojoId];
    }

    /**
     * Exclusao trafega por GET (padrao do painel), entao o token vai na URL:
     * sem ele um <img> em outro site apagaria inscricoes do sensei logado.
     */
    private function assertDeleteToken(): void
    {
        if (!Csrf::validate($_GET['token'] ?? null)) {
            DojoScope::deny('Link de exclusão inválido ou expirado. Volte e tente novamente.');
        }
    }

    /** Atleta do evento, reconferido contra o dojo do usuario. */
    private function athleteOf(int $championshipId, int $athleteId): array
    {
        $athlete = ChampionshipAthlete::find($athleteId);

        if (!$athlete || (int) $athlete['championship_id'] !== $championshipId) {
            DojoScope::deny();
        }

        DojoScope::assertDojo((int) $athlete['dojo_id']);

        return $athlete;
    }

    private function athleteFromStudent(int $championshipId, int $dojoId, string $back): array
    {
        $userId = (int) ($_POST['user_id'] ?? 0);

        // A consulta filtra por dojo: um id de outro dojo simplesmente nao existe aqui
        $student = ChampionshipAthlete::dojoStudent($dojoId, $userId);

        if (!$student) {
            DojoScope::deny('Este aluno não pertence ao seu dojo.');
        }

        if (empty($student['birth_date']) || empty($student['gender'])) {
            $_SESSION['error'] = "Complete a ficha de {$student['name']} (data de nascimento e sexo) antes de inscrevê-lo.";
            $this->redirect($back);
        }

        return [
            'championship_id' => $championshipId,
            'dojo_id'         => $dojoId,
            'user_id'         => $userId,
            'name'            => $student['name'],
            'gender'          => $student['gender'],
            'birth_date'      => $student['birth_date'],
            'style_id'        => $student['style_id'] ? (int) $student['style_id'] : null,
            'graduation_id'   => $student['graduation_id'] ? (int) $student['graduation_id'] : null,
            'belt_group'      => ChampionshipAthlete::beltGroupFor(
                $student['graduation_id'] ? (int) $student['graduation_id'] : null
            ),
            'weight'          => $student['weight'] !== null ? (float) $student['weight'] : null,
            'is_guest'        => 0,
            'is_para_karate'  => (int) ($student['is_para_karate'] ?? 0),
            'registered_by'   => (int) Auth::id(),
        ];
    }

    private function athleteFromGuest(int $championshipId, int $dojoId, string $back): array
    {
        $name         = trim($_POST['name'] ?? '');
        $gender       = $_POST['gender'] ?? '';
        $birthDate    = $_POST['birth_date'] ?? '';
        $graduationId = ($_POST['graduation_id'] ?? '') !== '' ? (int) $_POST['graduation_id'] : null;

        if ($name === '' || !isset(ChampionshipAthlete::GENDERS[$gender]) || $birthDate === '') {
            $_SESSION['error'] = 'Preencha nome, sexo e data de nascimento do atleta.';
            $this->redirect($back);
        }

        $beltGroup = ChampionshipAthlete::beltGroupFor($graduationId);

        // Faixa preta exige registro previo no dojo — o avulso e so para faixa colorida
        if ($beltGroup === 'black') {
            $_SESSION['error'] = 'Atletas de faixa preta precisam estar cadastrados no dojo. '
                . 'Cadastre o atleta em Usuários e depois inscreva-o pela busca.';
            $this->redirect($back);
        }

        return [
            'championship_id' => $championshipId,
            'dojo_id'         => $dojoId,
            'user_id'         => null,
            'name'            => $name,
            'gender'          => $gender,
            'birth_date'      => $birthDate,
            'style_id'        => ($_POST['style_id'] ?? '') !== '' ? (int) $_POST['style_id'] : null,
            'graduation_id'   => $graduationId,
            'belt_group'      => 'colored',
            'weight'          => ($_POST['weight'] ?? '') !== '' ? $this->decimal($_POST['weight']) : null,
            'is_guest'        => 1,
            'is_para_karate'  => !empty($_POST['is_para_karate']) ? 1 : 0,
            'registered_by'   => (int) Auth::id(),
        ];
    }

    private function validateEntry(array $athlete, array $category, array $championship): ?string
    {
        $age = ChampionshipAthlete::ageOn($athlete['birth_date'], $championship['event_date']);

        if (!CompetitionCategory::matchesAge($category, $age)) {
            return "{$athlete['name']} tem $age anos e não se enquadra na faixa etária desta categoria.";
        }

        if (!CompetitionCategory::matchesGender($category, $athlete['gender'])) {
            return "Esta categoria não aceita o sexo informado para {$athlete['name']}.";
        }

        if ($category['belt_group'] !== 'any' && $category['belt_group'] !== $athlete['belt_group']) {
            return "Esta categoria é de " . CompetitionCategory::BELT_GROUPS[$category['belt_group']] . '.';
        }

        return null;
    }

    private function summaryData(int $championshipId, ?int $dojoId, array $championship): array
    {
        $athletes = $dojoId ? ChampionshipAthlete::forDojo($championshipId, $dojoId) : [];
        $teams    = $dojoId ? ChampionshipTeam::forDojo($championshipId, $dojoId) : [];

        $entryCount = 0;
        foreach ($athletes as &$athlete) {
            $athlete['age'] = ChampionshipAthlete::ageOn($athlete['birth_date'], $championship['event_date']);
            $entryCount += count($athlete['entries']);
        }

        return [
            'athletes'   => $athletes,
            'teams'      => $teams,
            'entryCount' => $entryCount,
        ];
    }

    private function render(string $tab, array $championship, ?int $dojoId, array $data): void
    {
        $this->view("admin/championships/$tab", array_merge($data, [
            'championship'  => $championship,
            'dojoId'        => $dojoId,
            'dojo'          => $dojoId ? $this->dojo($dojoId) : null,
            'dojos'         => Auth::isAdmin() ? $this->dojos() : [],
            'activeTab'     => $tab,
            'canWrite'      => Auth::isAdmin() || Championship::isRegistrationOpen($championship),
            'pageTitle'     => $championship['title'],
        ]));
    }

    private function tabUrl(int $championshipId, string $tab, ?int $dojoId): string
    {
        $url = "/fgkirs-admin/championships/$championshipId/$tab";

        if ($dojoId && Auth::isAdmin()) {
            $url .= "?dojo_id=$dojoId";
        }

        return $url;
    }

    private function decimal(string $value): ?float
    {
        $normalized = (float) str_replace(',', '.', trim($value));

        return $normalized > 0 ? $normalized : null;
    }

    private function dojo(int $dojoId): ?array
    {
        $row = Database::getInstance()->query(
            "SELECT * FROM dojos WHERE id = :id LIMIT 1",
            ['id' => $dojoId]
        )->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function dojos(): array
    {
        return Database::getInstance()->query(
            "SELECT id, name, city FROM dojos ORDER BY name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    private function styles(): array
    {
        return Database::getInstance()->query(
            "SELECT id, name FROM martial_arts_styles ORDER BY name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    private function graduations(): array
    {
        return Database::getInstance()->query(
            "SELECT g.id, g.belt_name, g.belt_color, g.is_black_belt, g.style_id, s.name AS style_name
             FROM graduations g
             JOIN martial_arts_styles s ON s.id = g.style_id
             ORDER BY s.name ASC, g.order_rank ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
