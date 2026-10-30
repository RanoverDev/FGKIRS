<?php

namespace Domain\Championship;

/**
 * Cruza disciplina × sexo × idade × faixa × peso e devolve as categorias do evento.
 * Não acessa banco, sessão nem $_POST.
 */
final class CategoryGenerator
{
    private const GENDER_LABELS = ['M' => 'Masculino', 'F' => 'Feminino'];

    /** @return EventCategoryDraft[] */
    public function generate(Ruleset $ruleset, GenerationRequest $request): array
    {
        $drafts = [];
        $seen   = [];
        $order  = 0;

        foreach ($request->disciplines as $discipline) {
            foreach (['M', 'F', 'X'] as $gender) {
                foreach ($request->ageDivisions as $age) {
                    if (!$discipline->usesAgeDivision($age) || !$this->usesGender($discipline, $age, $gender)) {
                        continue;
                    }

                    $belts = $discipline->splitBelt && !$age->allBelts ? $request->beltDivisions : [null];

                    foreach ($belts as $belt) {
                        foreach ($this->weightOptions($ruleset, $discipline, $age, $gender) as $weight) {
                            $draft = $this->draft($discipline, $gender, $age, $belt, $weight, ++$order);

                            if (isset($seen[$draft->code])) {
                                throw new \DomainException("Código de categoria duplicado: {$draft->code}. Confira as divisões de peso.");
                            }

                            $seen[$draft->code] = true;
                            $drafts[]           = $draft;
                        }
                    }
                }
            }

            if ($discipline->paraCategory) {
                $draft = $this->paraDraft($discipline, ++$order);

                if (isset($seen[$draft->code])) {
                    throw new \DomainException("Código de categoria duplicado: {$draft->code}.");
                }

                $seen[$draft->code] = true;
                $drafts[]           = $draft;
            }
        }

        return $drafts;
    }

    /** Divisão de sexo misto (ou disciplina sem separação por sexo) gera só a categoria X; as demais, M e F. */
    private function usesGender(Discipline $discipline, AgeDivision $age, string $gender): bool
    {
        $mixed = !$discipline->splitGender || $age->mixedGender;

        return $mixed ? $gender === 'X' : $gender !== 'X';
    }

    /** Uma categoria por disciplina, sem limite de idade, sexo, faixa ou peso: só atleta marcado como para-karatê entra. */
    private function paraDraft(Discipline $discipline, int $order): EventCategoryDraft
    {
        return new EventCategoryDraft(
            code: $discipline->code . '-PARA',
            name: $discipline->name . ' — Para-karatê',
            modality: $discipline->modality,
            format: $discipline->format,
            gender: 'X',
            ageMin: null,
            ageMax: null,
            beltLevelMin: null,
            beltLevelMax: null,
            weightMin: null,
            weightMax: null,
            teamSize: $discipline->format === 'team' ? $discipline->teamSize : null,
            disciplineId: $discipline->id,
            ageDivisionId: null,
            beltDivisionId: null,
            weightClassId: null,
            sortOrder: $order,
            disciplineName: $discipline->name,
            ageLabel: 'Para-karatê',
            disciplineCode: $discipline->code,
            shortName: 'Para-karatê',
            teamReserves: $discipline->format === 'team' ? $discipline->teamReserves : null,
            isParaKarate: true,
        );
    }

    /**
     * Sem divisões de peso cadastradas para a idade e o sexo, a categoria sai sem limite de
     * peso em vez de sumir da geração sem aviso.
     *
     * @return array<WeightClass|null>
     */
    private function weightOptions(Ruleset $ruleset, Discipline $discipline, AgeDivision $age, string $gender): array
    {
        if (!$discipline->splitWeight || $gender === 'X') {
            return [null];
        }

        return $ruleset->weightClassesFor($age, $gender) ?: [null];
    }

    private function draft(
        Discipline $discipline,
        string $gender,
        AgeDivision $age,
        ?BeltDivision $belt,
        ?WeightClass $weight,
        int $order
    ): EventCategoryDraft {
        $codeParts = [$discipline->code, $gender, $age->code];
        $nameParts = [$discipline->name];

        if ($gender !== 'X') {
            $nameParts[] = self::GENDER_LABELS[$gender];
        }

        $nameParts[] = $age->label();
        $shortParts  = [$age->name];

        if ($belt !== null) {
            $codeParts[] = $belt->code;
            $nameParts[] = $belt->name;
            $shortParts[] = $belt->name;
        }

        if ($weight !== null) {
            $codeParts[] = $weight->codePart();
            $nameParts[] = $weight->name;
            $shortParts[] = $weight->name;
        }

        return new EventCategoryDraft(
            code: implode('-', $codeParts),
            name: implode(' — ', $nameParts),
            modality: $discipline->modality,
            format: $discipline->format,
            gender: $gender,
            ageMin: $age->ageMin,
            ageMax: $age->ageMax,
            beltLevelMin: $belt?->levelMin,
            beltLevelMax: $belt?->levelMax,
            weightMin: $weight?->weightMin,
            weightMax: $weight?->weightMax,
            teamSize: $discipline->format === 'team' ? $discipline->teamSize : null,
            disciplineId: $discipline->id,
            ageDivisionId: $age->id,
            beltDivisionId: $belt?->id,
            weightClassId: $weight?->id,
            sortOrder: $order,
            disciplineName: $discipline->name,
            ageLabel: $age->label(),
            disciplineCode: $discipline->code,
            shortName: implode(' · ', $shortParts),
            measure: $weight?->measure ?? 'weight',
            teamReserves: $discipline->format === 'team' ? $discipline->teamReserves : null,
        );
    }
}
