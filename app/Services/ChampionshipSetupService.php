<?php

namespace Services;

use Core\Database;
use Domain\Championship\CategoryGenerator;
use Domain\Championship\EventCategoryDraft;
use Domain\Championship\GenerationRequest;
use Repositories\EventCategoryRepository;
use Repositories\EventTeamRepository;
use Repositories\RegistrationRepository;
use Repositories\RulesetRepository;

/** Montagem do evento: geração de categorias a partir do regulamento, fechar e juntar. Só Presidente. */
class ChampionshipSetupService
{
    public function __construct(
        private RulesetRepository $rulesets = new RulesetRepository(),
        private EventCategoryRepository $categories = new EventCategoryRepository(),
        private RegistrationRepository $registrations = new RegistrationRepository(),
        private EventTeamRepository $teams = new EventTeamRepository(),
        private CategoryGenerator $generator = new CategoryGenerator(),
    ) {
    }

    /**
     * O que o gerador produziria com a escolha do Presidente. Só leitura.
     *
     * @param int[] $disciplineIds
     * @param int[] $ageIds
     * @param int[] $beltIds
     * @return EventCategoryDraft[]
     */
    public function preview(int $rulesetId, array $disciplineIds, array $ageIds, array $beltIds): array
    {
        RegistrationGuard::assertAdmin();

        $ruleset = $this->rulesets->load($rulesetId);
        $pick    = static fn (array $items, array $ids): array => array_values(array_filter(
            $items,
            static fn ($item) => in_array($item->id, array_map('intval', $ids), true)
        ));

        return $this->generator->generate($ruleset, new GenerationRequest(
            $pick($ruleset->disciplines, $disciplineIds),
            $pick($ruleset->ageDivisions, $ageIds),
            $pick($ruleset->beltDivisions, $beltIds),
        ));
    }

    /**
     * Grava as categorias confirmadas na prévia. A geração roda de novo no servidor e só os códigos
     * marcados e ainda inexistentes no evento entram: regerar não duplica nem apaga nada.
     *
     * @param string[] $selectedCodes
     * @return array{inserted: int, skipped: int}
     */
    public function apply(int $championshipId, int $rulesetId, array $disciplineIds, array $ageIds, array $beltIds, array $selectedCodes): array
    {
        $drafts   = $this->preview($rulesetId, $disciplineIds, $ageIds, $beltIds);
        $selected = array_flip(array_map('strval', $selectedCodes));
        $chosen   = array_values(array_filter($drafts, static fn (EventCategoryDraft $d) => isset($selected[$d->code])));

        if ($chosen === []) {
            throw RegistrationException::single('Nenhuma categoria selecionada.');
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $result = $this->categories->insertMissing($championshipId, $chosen);
            $db->query("UPDATE championships SET ruleset_id = :r WHERE id = :id", ['r' => $rulesetId, 'id' => $championshipId]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }

        return $result;
    }

    public function setOpen(int $championshipId, int $categoryId, bool $open): void
    {
        RegistrationGuard::assertAdmin();
        $category = $this->ownCategory($championshipId, $categoryId);

        if ($category['status'] === 'merged' || $category['status'] === 'cancelled') {
            throw RegistrationException::single('Categoria juntada ou cancelada não pode ser reaberta.');
        }

        $this->categories->setStatus($categoryId, $open ? 'open' : 'closed');
    }

    /**
     * Junta uma categoria com poucos inscritos em outra da mesma disciplina. As entradas ativas vão para
     * o destino como exceção (is_override) e as antigas ficam como "moved"; equipes também mudam de categoria.
     *
     * @return int inscrições movidas
     */
    public function merge(int $championshipId, int $fromId, int $toId, int $adminId): int
    {
        RegistrationGuard::assertAdmin();

        if ($fromId === $toId) {
            throw RegistrationException::single('Escolha uma categoria de destino diferente da origem.');
        }

        $from = $this->ownCategory($championshipId, $fromId);
        $to   = $this->ownCategory($championshipId, $toId);

        foreach (['discipline_code', 'format', 'modality'] as $field) {
            if ($from[$field] !== $to[$field]) {
                throw RegistrationException::single('Só é possível juntar categorias da mesma disciplina.');
            }
        }
        if ($to['status'] !== 'open' || !in_array($from['status'], ['open', 'closed'], true)) {
            throw RegistrationException::single('O destino precisa estar aberto e a origem não pode já ter sido juntada.');
        }

        $db    = Database::getInstance();
        $moved = 0;

        $db->beginTransaction();

        try {
            $entries = $db->query(
                "SELECT e.registration_id FROM registration_entries e
                 JOIN championship_registrations r ON r.id = e.registration_id
                 WHERE e.event_category_id = :c AND e.status = 'active' AND r.status <> 'cancelled'",
                ['c' => $fromId]
            )->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($entries as $registrationId) {
                $registrationId = (int) $registrationId;

                $this->registrations->setEntryStatus($registrationId, $fromId, 'moved');
                $this->registrations->activateEntry($registrationId, $toId, true, 'Categoria juntada pelo Presidente', $adminId);
                $this->registrations->log($registrationId, 'entry_moved', $adminId, ['from' => $from['code'], 'to' => $to['code']]);
                $moved++;
            }

            $this->teams->moveCategory($fromId, $toId);
            $this->categories->markMerged($fromId, $toId);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }

        return $moved;
    }

    private function ownCategory(int $championshipId, int $categoryId): array
    {
        $category = $this->categories->find($categoryId);

        if (!$category || (int) $category['championship_id'] !== $championshipId) {
            throw RegistrationException::single('Categoria não encontrada neste evento.', 404);
        }

        return $category;
    }
}
