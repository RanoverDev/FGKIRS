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
            $genders = $discipline->splitGender ? ['M', 'F'] : ['X'];
            $belts   = $discipline->splitBelt ? $request->beltDivisions : [null];

            foreach ($genders as $gender) {
                foreach ($request->ageDivisions as $age) {
                    if (!$discipline->usesAgeDivision($age)) {
                        continue;
                    }

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
        }

        return $drafts;
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

        if ($belt !== null) {
            $codeParts[] = $belt->code;
            $nameParts[] = $belt->name;
        }

        if ($weight !== null) {
            $codeParts[] = $weight->codePart();
            $nameParts[] = $weight->name;
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
        );
    }
}
