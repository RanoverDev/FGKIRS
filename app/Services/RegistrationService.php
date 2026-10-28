<?php

namespace Services;

use Core\Database;
use Domain\Championship\AgePolicy;
use Domain\Championship\AthleteSnapshot;
use Domain\Championship\EligibilityService;
use Domain\Championship\EventCategory;
use Domain\Championship\FeeCalculator;
use Domain\Championship\RegistrationStatus;
use Helpers\Auth;
use Helpers\DojoScope;
use Repositories\AthleteRepository;
use Repositories\EventCategoryRepository;
use Repositories\EventTeamRepository;
use Repositories\RegistrationRepository;

/**
 * Casos de uso da inscrição. Escopo de dojo e prazo são aplicados aqui (RegistrationGuard), então
 * nenhum controller consegue esquecê-los. Cada operação é uma transação e grava registration_log.
 */
class RegistrationService
{
    public function __construct(
        private RegistrationRepository $registrations = new RegistrationRepository(),
        private EventCategoryRepository $categories = new EventCategoryRepository(),
        private AthleteRepository $athletes = new AthleteRepository(),
        private EligibilityService $eligibility = new EligibilityService(),
        private EventTeamRepository $teams = new EventTeamRepository(),
    ) {
    }

    // ───────────────────────────── Leitura ─────────────────────────────

    /**
     * Dados da grade: uma linha por atleta ativo do dojo e, por disciplina individual, a categoria
     * sugerida (ou o motivo) e a inscrição atual.
     */
    public function grid(array $championship, int $dojoId): array
    {
        DojoScope::assertDojo($dojoId);

        $categories  = $this->categories->forChampionship((int) $championship['id']);
        $disciplines = $this->individualDisciplines($categories);
        $existing    = $this->registrations->forDojoByAthlete((int) $championship['id'], $dojoId);
        $byId        = $this->byId($categories);
        $rows        = [];

        foreach ($this->athletes->activeForDojo($dojoId) as $athlete) {
            $registration = $existing[(int) $athlete['id']] ?? null;
            $weight       = $registration['declared_weight'] ?? $athlete['weight_kg'];
            $height       = $registration['declared_height'] ?? $athlete['height_cm'] ?? null;
            $snapshot     = $this->snapshot($athlete, $championship, $weight !== null ? (float) $weight : null, $height !== null ? (float) $height : null);

            $rows[] = [
                'id'              => (int) $athlete['id'],
                'name'            => $athlete['full_name'],
                'age'             => $snapshot->age,
                'belt'            => $athlete['belt_name'],
                'beltColor'       => $athlete['belt_color'],
                'weight'          => $weight !== null ? (float) $weight : null,
                'height'          => $height !== null ? (int) $height : null,
                'measure'         => $this->measureFor($snapshot, $categories),
                'isPara'          => (bool) $athlete['is_para_karate'],
                'isGuest'         => !empty($athlete['is_guest']),
                'guestOrigin'     => $athlete['guest_origin'] ?? null,
                'registrationId'  => $registration ? (int) $registration['id'] : null,
                'status'          => $registration['status'] ?? null,
                'fee'             => isset($registration['fee_total']) ? (float) $registration['fee_total'] : null,
                'cells'           => $this->cells($snapshot, $categories, $disciplines, $registration['entries'] ?? [], $byId),
            ];
        }

        return [
            'disciplines' => $disciplines,
            'athletes'    => $rows,
            'fees'        => [
                'first' => $championship['fee_first_entry'] !== null ? (float) $championship['fee_first_entry'] : null,
                'extra' => $championship['fee_extra_entry'] !== null ? (float) $championship['fee_extra_entry'] : null,
            ],
            'maxEntries'  => (int) $championship['max_individual_entries'],
            'guests'      => \Models\Athlete::supportsGuests(),
        ];
    }

    /** Sugestões de um atleta com outro peso ou altura: a grade recalcula a célula sem repetir a regra em JavaScript. */
    public function suggestFor(array $championship, int $dojoId, int $athleteId, ?float $weight, ?float $height = null): array
    {
        DojoScope::assertDojo($dojoId);

        $athlete = $this->athletes->find($athleteId);
        if (!$athlete || (int) $athlete['dojo_id'] !== $dojoId) {
            DojoScope::deny();
        }

        $categories = $this->categories->forChampionship((int) $championship['id']);
        $existing   = $this->registrations->forDojoByAthlete((int) $championship['id'], $dojoId)[$athleteId] ?? null;

        return $this->cells(
            $this->snapshot($athlete, $championship, $weight, $height),
            $categories,
            $this->individualDisciplines($categories),
            $existing['entries'] ?? [],
            $this->byId($categories)
        );
    }

    // ───────────────────────────── Escrita ─────────────────────────────

    /**
     * Grava o rascunho do dojo. Cada seleção é validada pelo mesmo EligibilityService que alimenta a
     * grade; qualquer problema recusa o lote inteiro, sem gravar nada.
     *
     * @param array<int, array{categories?: array, weight?: mixed}> $selections athlete_id => escolha
     * @return array{saved: int, cancelled: int}
     */
    public function saveDraft(array $championship, int $dojoId, array $selections, int $userId): array
    {
        RegistrationGuard::assertWritable($championship, $dojoId);

        $championshipId = (int) $championship['id'];
        $categories     = $this->categories->forChampionship($championshipId);
        $byId           = $this->byId($categories);
        $existing       = $this->registrations->forDojoByAthlete($championshipId, $dojoId);
        $max            = (int) $championship['max_individual_entries'];

        $plans  = [];
        $errors = [];

        foreach ($selections as $athleteId => $choice) {
            $athleteId = (int) $athleteId;
            $athlete   = $this->athletes->find($athleteId);

            // Atleta de outro dojo: nunca confirma que existe
            if (!$athlete || (int) $athlete['dojo_id'] !== $dojoId) {
                DojoScope::deny();
            }

            $registration = $existing[$athleteId] ?? null;
            $wanted       = array_values(array_unique(array_map('intval', (array) ($choice['categories'] ?? []))));

            if ($registration && $registration['status'] === 'confirmed' && !Auth::isAdmin()) {
                // Inscrição confirmada só muda se nada mudou: o sensei não troca categoria depois do pagamento
                $current = array_map(static fn ($e) => (int) $e['event_category_id'], $registration['entries']);
                sort($current);
                $sorted = $wanted;
                sort($sorted);

                if ($current !== $sorted) {
                    $errors[] = "{$athlete['full_name']}: inscrição já confirmada pela federação. Fale com a federação para alterar.";
                }
                continue;
            }

            if ($wanted === [] && !$registration) {
                continue;
            }

            $weight   = $this->decimal($choice['weight'] ?? null) ?? ($registration['declared_weight'] ?? $athlete['weight_kg']);
            $height   = $this->decimal($choice['height'] ?? null) ?? ($registration['declared_height'] ?? $athlete['height_cm'] ?? null);
            $snapshot = $this->snapshot($athlete, $championship, $weight !== null ? (float) $weight : null, $height !== null ? (float) $height : null);
            $current  = [];
            foreach ($registration['entries'] ?? [] as $entry) {
                $current[(int) $entry['event_category_id']] = $entry;
            }

            $perDiscipline = [];
            foreach ($wanted as $categoryId) {
                $category = $byId[$categoryId] ?? null;

                if (!$category || !$category->isIndividual()) {
                    $errors[] = "{$athlete['full_name']}: categoria inválida para este evento.";
                    continue;
                }

                $kept = $current[$categoryId] ?? null;

                // Exceção liberada pelo Presidente (ou categoria juntada) não é reavaliada pelas regras
                if (!($kept && $kept['is_override'])) {
                    // O próprio atleta já ocupa uma vaga desta categoria: não conta contra ele
                    $evaluated = $kept ? $category->withEnrolled(max(0, $category->enrolled - 1))->asOpen() : $category;
                    $result    = $this->eligibility->evaluate($snapshot, [$evaluated]);

                    if (isset($result['rejected'][$categoryId])) {
                        $errors[] = "{$athlete['full_name']} · {$category->shortName}: {$result['rejected'][$categoryId]}.";
                        continue;
                    }
                }

                $perDiscipline[$category->disciplineCode] = ($perDiscipline[$category->disciplineCode] ?? 0) + 1;
            }

            foreach ($perDiscipline as $code => $count) {
                if ($count > 1) {
                    $errors[] = "{$athlete['full_name']}: só é possível uma categoria por disciplina ($code).";
                }
            }
            if (count($wanted) > $max) {
                $errors[] = "{$athlete['full_name']}: o evento permite no máximo $max categorias individuais por atleta.";
            }

            $plans[] = compact('athlete', 'registration', 'wanted', 'snapshot', 'weight', 'height', 'current');
        }

        if ($errors) {
            throw new RegistrationException($errors);
        }

        $saved = $cancelled = 0;

        $this->transaction(function () use ($plans, $championship, $dojoId, $userId, $byId, &$saved, &$cancelled) {
            foreach ($plans as $plan) {
                ['athlete' => $athlete, 'registration' => $registration, 'wanted' => $wanted,
                 'snapshot' => $snapshot, 'weight' => $weight, 'height' => $height, 'current' => $current] = $plan;

                if ($wanted === []) {
                    $this->cancelRegistration($registration, $userId, 'Atleta removido da grade');
                    $cancelled++;
                    continue;
                }

                $id = $this->registrations->save($registration ? (int) $registration['id'] : null, [
                    'championship_id' => (int) $championship['id'], 'athlete_id' => (int) $athlete['id'],
                    'dojo_id' => $dojoId, 'full_name' => $athlete['full_name'], 'gender' => $athlete['gender'],
                    'birth_date' => $athlete['birth_date'], 'age_at_event' => $snapshot->age,
                    'graduation_id' => $athlete['graduation_id'], 'belt_level' => $athlete['belt_level'],
                    'declared_weight' => $weight, 'declared_height' => $height !== null && $this->measureFor($snapshot, array_values($byId)) === 'height' ? (int) $height : null, 'is_para_karate' => (int) $athlete['is_para_karate'],
                    'registered_by' => $userId,
                ]);

                if ($registration === null) {
                    $this->registrations->log($id, 'created', $userId, ['categories' => $wanted]);
                }

                $changed = $registration === null || $registration['status'] === 'cancelled';
                if ($registration && (float) ($registration['declared_weight'] ?? 0) !== (float) ($weight ?? 0)) {
                    $this->registrations->log($id, 'weight_changed', $userId, [
                        'from' => $registration['declared_weight'], 'to' => $weight,
                    ]);
                    $changed = true;
                }
                if ($registration && (int) ($registration['declared_height'] ?? 0) !== (int) ($height ?? 0)) {
                    $this->registrations->log($id, 'height_changed', $userId, [
                        'from' => $registration['declared_height'], 'to' => $height,
                    ]);
                    $changed = true;
                }

                foreach ($wanted as $categoryId) {
                    if (!isset($current[$categoryId])) {
                        $this->registrations->activateEntry($id, $categoryId);
                        $this->registrations->log($id, 'entry_added', $userId, ['category' => $byId[$categoryId]->code]);
                        $changed = true;
                    }
                }

                foreach ($current as $categoryId => $entry) {
                    if (!in_array($categoryId, $wanted, true)) {
                        $this->registrations->setEntryStatus($id, $categoryId, 'cancelled');
                        $this->registrations->log($id, 'entry_removed', $userId, ['category' => $byId[$categoryId]->code ?? $categoryId]);
                        $changed = true;
                    }
                }

                $this->afterChange($championship, $registration, $id, $changed, $userId);
                $saved++;
            }
        });

        return ['saved' => $saved, 'cancelled' => $cancelled];
    }

    /**
     * Envia o que está em rascunho: revalida tudo, calcula a taxa e passa a "enviada".
     *
     * @return int quantidade de inscrições enviadas
     */
    public function submit(array $championship, int $dojoId, int $userId): int
    {
        RegistrationGuard::assertWritable($championship, $dojoId);

        $championshipId = (int) $championship['id'];
        $byId           = $this->byId($this->categories->forChampionship($championshipId));
        $drafts         = array_filter(
            $this->registrations->forDojoByAthlete($championshipId, $dojoId),
            static fn (array $r) => $r['status'] === 'draft' && $r['entries'] !== []
        );
        $draftTeams = array_filter(
            $this->teams->forChampionship($championshipId, $dojoId),
            static fn (array $t) => $t['status'] === 'draft'
        );

        if ($drafts === [] && $draftTeams === []) {
            throw RegistrationException::single('Não há inscrições em rascunho para enviar.');
        }

        $errors = [];
        foreach ($drafts as $registration) {
            $athlete  = $this->athletes->find((int) $registration['athlete_id']);
            $snapshot = new AthleteSnapshot(
                $registration['gender'], (int) $registration['age_at_event'],
                $registration['belt_level'] !== null ? (int) $registration['belt_level'] : null,
                $registration['declared_weight'] !== null ? (float) $registration['declared_weight'] : null,
                (bool) $registration['is_para_karate'],
                (bool) ($athlete['is_black_belt'] ?? false),
                !empty($athlete['fgkirs_registration']),
                $registration['declared_height'] !== null ? (float) $registration['declared_height'] : null,
            );

            foreach ($registration['entries'] as $entry) {
                $category = $byId[(int) $entry['event_category_id']] ?? null;

                if (!$category || $entry['is_override']) {
                    continue;
                }

                $result = $this->eligibility->evaluate($snapshot, [$category->withEnrolled(max(0, $category->enrolled - 1))->asOpen()]);
                if (isset($result['rejected'][$category->id])) {
                    $errors[] = "{$registration['full_name']} · {$category->shortName}: {$result['rejected'][$category->id]}.";
                }
            }
        }

        if ($errors) {
            throw new RegistrationException($errors);
        }

        $first = $championship['fee_first_entry'] !== null ? (float) $championship['fee_first_entry'] : null;
        $extra = $championship['fee_extra_entry'] !== null ? (float) $championship['fee_extra_entry'] : null;

        $this->transaction(function () use ($drafts, $draftTeams, $first, $extra, $userId) {
            foreach ($drafts as $registration) {
                $fee = FeeCalculator::total(count($registration['entries']), $first, $extra);

                $this->registrations->setStatus((int) $registration['id'], 'submitted', [
                    'fee_total' => $fee, 'submitted_at' => date('Y-m-d H:i:s'),
                ]);
                $this->registrations->log((int) $registration['id'], 'submitted', $userId, [
                    'entries' => count($registration['entries']), 'fee_total' => $fee,
                ]);
            }

            foreach ($draftTeams as $team) {
                $this->teams->setStatus((int) $team['id'], 'submitted');
                foreach ($team['members'] as $member) {
                    $this->registrations->log((int) $member['registration_id'], 'team_submitted', $userId, ['team' => (int) $team['id']]);
                }
            }
        });

        return count($drafts);
    }

    /**
     * Cancela a inscrição do atleta: muda o status e as entradas, nada é apagado.
     * O sensei cancela até o fim do prazo e antes da confirmação; o Presidente, sempre.
     */
    public function cancel(array $championship, int $registrationId, int $userId): void
    {
        $registration = $this->registrations->find($registrationId);

        if (!$registration || (int) $registration['championship_id'] !== (int) $championship['id']) {
            DojoScope::deny();
        }

        RegistrationGuard::assertWritable($championship, (int) $registration['dojo_id']);

        if ($registration['status'] === 'confirmed' && !Auth::isAdmin()) {
            throw RegistrationException::single('Inscrição confirmada pela federação: fale com a federação para cancelar.', 403);
        }

        if ($this->teams->hasActiveTeam($registrationId)) {
            throw RegistrationException::single('Este atleta está em uma equipe. Cancele a equipe antes de cancelar a inscrição dele.');
        }

        $this->transaction(fn () => $this->cancelRegistration($registration, $userId, 'Cancelada'));
    }

    /**
     * Confirma o pagamento em lote (só Presidente): enviada → confirmada.
     *
     * @return int quantidade confirmada
     */
    public function confirm(array $championship, array $registrationIds, array $teamIds, int $adminId): int
    {
        RegistrationGuard::assertAdmin();

        $confirmed = 0;

        $this->transaction(function () use ($championship, $registrationIds, $teamIds, $adminId, &$confirmed) {
            foreach (array_unique(array_map('intval', $registrationIds)) as $id) {
                $registration = $this->registrations->find($id);

                if (!$registration || (int) $registration['championship_id'] !== (int) $championship['id']
                    || !RegistrationStatus::from($registration['status'])->canTransitionTo(RegistrationStatus::Confirmed)) {
                    continue;
                }

                $this->registrations->setStatus($id, 'confirmed', ['paid_at' => date('Y-m-d H:i:s')]);
                $this->registrations->log($id, 'confirmed', $adminId, ['fee_total' => $registration['fee_total']]);
                $confirmed++;
            }

            foreach (array_unique(array_map('intval', $teamIds)) as $teamId) {
                $team = $this->teams->find($teamId);

                if ($team && (int) $team['championship_id'] === (int) $championship['id'] && $team['status'] === 'submitted') {
                    $this->teams->setStatus($teamId, 'confirmed');
                }
            }
        });

        return $confirmed;
    }

    /** Inscreve fora da regra (só Presidente), com motivo obrigatório. */
    public function override(array $championship, int $registrationId, int $categoryId, string $reason, int $adminId): void
    {
        RegistrationGuard::assertAdmin();

        $reason       = trim($reason);
        $registration = $this->registrations->find($registrationId);
        $category     = $this->categories->find($categoryId);

        if (mb_strlen($reason) < 3) {
            throw RegistrationException::single('Informe o motivo da exceção.');
        }
        if (!$registration || (int) $registration['championship_id'] !== (int) $championship['id']
            || !$category || (int) $category['championship_id'] !== (int) $championship['id'] || $category['format'] !== 'individual') {
            throw RegistrationException::single('Inscrição ou categoria inválida para este evento.');
        }
        if ($registration['status'] === 'cancelled') {
            throw RegistrationException::single('A inscrição está cancelada.');
        }

        $this->transaction(function () use ($championship, $registration, $categoryId, $category, $reason, $adminId) {
            $id = (int) $registration['id'];

            $this->registrations->activateEntry($id, $categoryId, true, mb_substr($reason, 0, 255), $adminId);
            $this->registrations->log($id, 'override', $adminId, ['category' => $category['code'], 'reason' => $reason]);

            // Inscrição já enviada/confirmada: a taxa acompanha o novo número de categorias
            if ($registration['fee_total'] !== null) {
                $this->registrations->setFee($id, $this->fee($championship, count($this->registrations->activeEntries($id))));
            }
        });
    }

    /**
     * Passa o atleta de uma categoria para outra da mesma disciplina (ex.: ajuste de peso na pesagem),
     * só Presidente, com motivo. A categoria antiga fica como "movida", nada é apagado.
     */
    public function moveEntry(array $championship, int $registrationId, int $fromCategoryId, int $toCategoryId, string $reason, int $adminId): void
    {
        RegistrationGuard::assertAdmin();

        $reason       = trim($reason);
        $registration = $this->registrations->find($registrationId);
        $from         = $this->categories->find($fromCategoryId);
        $to           = $this->categories->find($toCategoryId);

        if (mb_strlen($reason) < 3) {
            throw RegistrationException::single('Informe o motivo da mudança de categoria.');
        }
        if (!$registration || (int) $registration['championship_id'] !== (int) $championship['id']
            || !$from || !$to
            || (int) $from['championship_id'] !== (int) $championship['id'] || (int) $to['championship_id'] !== (int) $championship['id']
            || $from['format'] !== 'individual' || $to['format'] !== 'individual'
            || $from['discipline_code'] !== $to['discipline_code']
            || $fromCategoryId === $toCategoryId) {
            throw RegistrationException::single('Escolha outra categoria da mesma disciplina neste evento.');
        }
        if ($registration['status'] === 'cancelled') {
            throw RegistrationException::single('A inscrição está cancelada.');
        }
        if (!in_array($fromCategoryId, array_map(static fn ($e) => (int) $e['event_category_id'], $this->registrations->activeEntries($registrationId)), true)) {
            throw RegistrationException::single('O atleta não está mais na categoria de origem.');
        }

        $this->transaction(function () use ($registrationId, $fromCategoryId, $toCategoryId, $from, $to, $reason, $adminId) {
            $this->registrations->activateEntry($registrationId, $toCategoryId, true, mb_substr($reason, 0, 255), $adminId);
            $this->registrations->setEntryStatus($registrationId, $fromCategoryId, 'moved');
            $this->registrations->log($registrationId, 'entry_moved', $adminId, [
                'from' => $from['code'], 'to' => $to['code'], 'reason' => $reason,
            ]);
        });
    }

    // ───────────────────────────── Internos ─────────────────────────────

    /** Depois de gravar: edição de sensei em inscrição enviada volta a rascunho; admin mantém o status e recalcula a taxa. */
    private function afterChange(array $championship, ?array $registration, int $id, bool $changed, int $userId): void
    {
        if ($registration === null) {
            return;
        }

        $status = RegistrationStatus::from($registration['status']);

        if ($status === RegistrationStatus::Cancelled) {
            $this->registrations->setStatus($id, 'draft', ['fee_total' => null, 'submitted_at' => null]);
            $this->registrations->log($id, 'reactivated', $userId);
        } elseif ($changed && $status === RegistrationStatus::Submitted && !Auth::isAdmin()) {
            $this->registrations->setStatus($id, 'draft', ['fee_total' => null, 'submitted_at' => null]);
            $this->registrations->log($id, 'reopened', $userId, ['reason' => 'Alteração depois do envio: é preciso enviar de novo']);
        } elseif ($changed && $registration['fee_total'] !== null) {
            $this->registrations->setFee($id, $this->fee($championship, count($this->registrations->activeEntries($id))));
        }
    }

    private function cancelRegistration(array $registration, int $userId, string $reason): void
    {
        if ($registration['status'] === 'cancelled') {
            return;
        }

        $id = (int) $registration['id'];
        $this->registrations->cancelAllEntries($id);
        $this->registrations->setStatus($id, 'cancelled', ['fee_total' => null]);
        $this->registrations->log($id, 'cancelled', $userId, ['reason' => $reason, 'from' => $registration['status']]);
    }

    private function fee(array $championship, int $entries): ?float
    {
        return FeeCalculator::total(
            $entries,
            $championship['fee_first_entry'] !== null ? (float) $championship['fee_first_entry'] : null,
            $championship['fee_extra_entry'] !== null ? (float) $championship['fee_extra_entry'] : null
        );
    }

    /** @param array<string, EventCategory> $byId */
    private function cells(AthleteSnapshot $snapshot, array $categories, array $disciplines, array $entries, array $byId): array
    {
        $cells = [];

        foreach ($disciplines as $discipline) {
            $code       = $discipline['code'];
            $suggestion = $this->eligibility->suggest($snapshot, $categories, $code);

            $entry = null;
            foreach ($entries as $e) {
                if ($e['discipline_code'] === $code) {
                    $entry = $e;
                }
            }

            $cell = [
                'categoryId' => $suggestion->category?->id,
                'label'      => $suggestion->category?->shortName,
                'reason'     => $suggestion->reason,
                'checked'    => false,
                'override'   => false,
            ];

            if ($entry) {
                $sameAsSuggestion = $suggestion->category && $suggestion->category->id === (int) $entry['event_category_id'];

                $entryCategory = $byId[(int) $entry['event_category_id']] ?? null;

                // Categoria fechada ou juntada está congelada: a inscrição que já existe aparece como é,
                // inclusive em evento histórico migrado. Se ela não servir mais, o salvamento diz o motivo.
                if (!$sameAsSuggestion && !$entry['is_override'] && $entryCategory !== null && $entryCategory->status !== 'open') {
                    $sameAsSuggestion = true;
                }

                // Exceção do Presidente, categoria juntada ou fechada depois: mostra a inscrição como ela está
                if ($entry['is_override'] || $sameAsSuggestion) {
                    $cell = [
                        'categoryId' => (int) $entry['event_category_id'],
                        'label'      => $entry['short_name'] ?: $entry['category_name'],
                        'reason'     => null,
                        'checked'    => true,
                        'override'   => (bool) $entry['is_override'],
                    ];
                } elseif ($suggestion->category) {
                    $cell['checked'] = true;
                    $cell['changed'] = true; // categoria recalculada: salvar troca a inscrição antiga pela nova
                }
            }

            $cells[$code] = $cell;
        }

        return $cells;
    }

    /** @param EventCategory[] $categories */
    private function individualDisciplines(array $categories): array
    {
        $out = [];

        foreach ($categories as $category) {
            if ($category->isIndividual() && !isset($out[$category->disciplineCode])) {
                $out[$category->disciplineCode] = ['code' => $category->disciplineCode, 'name' => $category->disciplineName];
            }
        }

        return array_values($out);
    }

    /** @param EventCategory[] $categories @return array<int, EventCategory> */
    private function byId(array $categories): array
    {
        $map = [];
        foreach ($categories as $category) {
            $map[$category->id] = $category;
        }

        return $map;
    }

    private function snapshot(array $athlete, array $championship, ?float $weight, ?float $height = null): AthleteSnapshot
    {
        return AthleteSnapshot::of(
            $athlete['gender'],
            $athlete['birth_date'],
            $championship['event_date'],
            AgePolicy::fromColumn($championship['age_policy'] ?? null),
            $athlete['belt_level'] !== null ? (int) $athlete['belt_level'] : null,
            $weight,
            (bool) $athlete['is_para_karate'],
            (bool) $athlete['is_black_belt'],
            !empty($athlete['fgkirs_registration']),
            $height,
        );
    }

    /** 'height' quando alguma categoria individual da faixa etária do atleta separa por altura (menores de 15), senão 'weight'. */
    private function measureFor(AthleteSnapshot $snapshot, array $categories): string
    {
        foreach ($categories as $category) {
            if ($category->isIndividual() && $category->requiresHeight() && $category->acceptsAge($snapshot->age)) {
                return 'height';
            }
        }

        return 'weight';
    }

    private function decimal(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $number = (float) str_replace(',', '.', (string) $value);

        return $number > 0 && $number < 300 ? $number : null;
    }

    private function transaction(callable $work): void
    {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $work();
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }
}
