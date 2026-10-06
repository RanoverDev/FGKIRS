<?php

namespace Services;

use Core\Database;
use Domain\Championship\AgePolicy;
use Domain\Championship\AthleteSnapshot;
use Domain\Championship\EligibilityService;
use Domain\Championship\EventCategory;
use Helpers\Auth;
use Helpers\DojoScope;
use Repositories\EventCategoryRepository;
use Repositories\EventTeamRepository;
use Repositories\RegistrationRepository;

/**
 * Equipes (kata e kumite por equipe). Só entram atletas já inscritos no evento que se encaixam
 * na categoria de equipe; respeita o tamanho da equipe e o número de reservas da disciplina.
 */
class TeamService
{
    public function __construct(
        private EventTeamRepository $teams = new EventTeamRepository(),
        private RegistrationRepository $registrations = new RegistrationRepository(),
        private EventCategoryRepository $categories = new EventCategoryRepository(),
        private EligibilityService $eligibility = new EligibilityService(),
    ) {
    }

    /** @return EventCategory[] categorias de equipe do evento (só as abertas, salvo $includeClosed) */
    public function teamCategories(int $championshipId, bool $includeClosed = false): array
    {
        return array_values(array_filter(
            $this->categories->forChampionship($championshipId),
            static fn (EventCategory $c) => !$c->isIndividual() && ($includeClosed || $c->status === 'open')
        ));
    }

    /** Inscrições do dojo que podem entrar numa equipe da categoria (e ainda não estão em outra equipe dela). */
    public function eligibleMembers(array $championship, int $dojoId, EventCategory $category, ?int $exceptTeamId = null): array
    {
        DojoScope::assertDojo($dojoId);

        $taken    = array_flip($this->teams->registrationIdsInCategory($category->id, $exceptTeamId));
        $eligible = [];

        foreach ($this->registrations->activeForDojo((int) $championship['id'], $dojoId) as $registration) {
            if (isset($taken[(int) $registration['id']])) {
                continue;
            }

            $snapshot = new AthleteSnapshot(
                $registration['gender'],
                AgePolicy::fromColumn($championship['age_policy'] ?? null)->ageOn(
                    new \DateTimeImmutable($registration['birth_date']),
                    new \DateTimeImmutable($championship['event_date'])
                ),
                null,
                null,
                (bool) $registration['is_para_karate'],
            );

            if ($this->eligibility->evaluate($snapshot, [$category])['eligible'] !== []) {
                $eligible[] = $registration;
            }
        }

        return $eligible;
    }

    /**
     * @param int[] $starterIds inscrições titulares (exatamente team_size)
     * @param int[] $reserveIds inscrições reservas (até team_reserves)
     */
    public function create(array $championship, int $dojoId, int $categoryId, ?string $name, array $starterIds, array $reserveIds, int $userId): int
    {
        RegistrationGuard::assertWritable($championship, $dojoId);

        $row = $this->categories->find($categoryId);
        if (!$row || (int) $row['championship_id'] !== (int) $championship['id'] || $row['format'] !== 'team') {
            throw RegistrationException::single('Categoria de equipe inválida para este evento.');
        }

        $category = EventCategory::fromRow($row);
        if ($category->status !== 'open') {
            throw RegistrationException::single('Categoria fechada.');
        }

        $starters = array_values(array_unique(array_map('intval', $starterIds)));
        $reserves = array_values(array_diff(array_unique(array_map('intval', $reserveIds)), $starters));
        $size     = (int) $category->teamSize;
        $maxRes   = (int) $category->teamReserves;

        if (count($starters) !== $size) {
            throw RegistrationException::single("A equipe precisa de exatamente $size titulares.");
        }
        if (count($reserves) > $maxRes) {
            throw RegistrationException::single($maxRes === 0
                ? 'Esta categoria não permite reservas.'
                : "A equipe aceita no máximo $maxRes reserva(s).");
        }

        $allowed = [];
        foreach ($this->eligibleMembers($championship, $dojoId, $category) as $registration) {
            $allowed[(int) $registration['id']] = $registration;
        }

        $errors = [];
        foreach (array_merge($starters, $reserves) as $registrationId) {
            if (!isset($allowed[$registrationId])) {
                $registration = $this->registrations->find($registrationId);

                // Inscrição de outro dojo: 403 sem confirmar que existe
                if (!$registration || (int) $registration['dojo_id'] !== $dojoId) {
                    DojoScope::deny();
                }

                $errors[] = "{$registration['full_name']} não pode entrar nesta equipe (fora da categoria, cancelado ou já escalado nela).";
            }
        }

        if ($errors) {
            throw new RegistrationException($errors);
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $teamId = $this->teams->create((int) $championship['id'], $dojoId, $categoryId, $name !== null && trim($name) !== '' ? mb_substr(trim($name), 0, 120) : null);

            foreach ($starters as $i => $registrationId) {
                $this->teams->addMember($teamId, $registrationId, 'starter', $i + 1);
                $this->registrations->log($registrationId, 'team_added', $userId, ['team' => $teamId, 'category' => $category->code, 'role' => 'starter']);
            }
            foreach ($reserves as $i => $registrationId) {
                $this->teams->addMember($teamId, $registrationId, 'reserve', $i + 1);
                $this->registrations->log($registrationId, 'team_added', $userId, ['team' => $teamId, 'category' => $category->code, 'role' => 'reserve']);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }

        return $teamId;
    }

    /** Cancela a equipe (nada é apagado). O sensei cancela dentro do prazo e antes da confirmação. */
    public function cancel(array $championship, int $teamId, int $userId): void
    {
        $team = $this->teams->find($teamId);

        if (!$team || (int) $team['championship_id'] !== (int) $championship['id']) {
            DojoScope::deny();
        }

        RegistrationGuard::assertWritable($championship, (int) $team['dojo_id']);

        if ($team['status'] === 'confirmed' && !Auth::isAdmin()) {
            throw RegistrationException::single('Equipe confirmada pela federação: fale com a federação para cancelar.', 403);
        }

        $this->teams->setStatus($teamId, 'cancelled');

        foreach ($this->teams->forChampionship((int) $championship['id'], (int) $team['dojo_id'], true) as $candidate) {
            if ((int) $candidate['id'] === $teamId) {
                foreach ($candidate['members'] as $member) {
                    $this->registrations->log((int) $member['registration_id'], 'team_cancelled', $userId, ['team' => $teamId]);
                }
            }
        }
    }
}
