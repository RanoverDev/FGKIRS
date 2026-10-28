<?php

namespace Domain\Championship;

/**
 * Única fonte da regra de elegibilidade: a tela da grade e a validação do POST chamam os mesmos
 * métodos. Não acessa banco, sessão nem $_POST.
 */
final class EligibilityService
{
    /** Ordem das checagens; o índice mede o quanto a categoria chegou perto de servir. */
    private const CHECKS = ['gender', 'age', 'belt', 'weight', 'para', 'status', 'full'];

    /**
     * @param EventCategory[] $categories
     * @return array{eligible: EventCategory[], rejected: array<int,string>}
     */
    public function evaluate(AthleteSnapshot $athlete, array $categories): array
    {
        $eligible = $rejected = [];
        $blocker  = $this->athleteBlocker($athlete);

        foreach ($categories as $category) {
            $reason = $blocker ?? $this->reject($athlete, $category)[1];

            if ($reason === null) {
                $eligible[] = $category;
            } else {
                $rejected[$category->id] = $reason;
            }
        }

        return ['eligible' => $eligible, 'rejected' => $rejected];
    }

    /**
     * A única categoria individual da disciplina em que o atleta cai. Mais de uma é erro de
     * configuração do regulamento (as divisões se sobrepõem): loga e não sugere nenhuma.
     *
     * @param EventCategory[] $categories categorias do evento (qualquer disciplina)
     */
    public function suggest(AthleteSnapshot $athlete, array $categories, string $disciplineCode): Suggestion
    {
        if ($blocker = $this->athleteBlocker($athlete)) {
            return new Suggestion(null, $blocker);
        }

        $pool = array_values(array_filter(
            $categories,
            static fn (EventCategory $c) => $c->isIndividual() && $c->disciplineCode === $disciplineCode
        ));

        if ($pool === []) {
            return new Suggestion(null, 'Disciplina sem categorias neste evento');
        }

        $eligible  = [];
        $bestDepth = -1;
        $bestReason = null;

        foreach ($pool as $category) {
            [$depth, $reason] = $this->reject($athlete, $category);

            if ($reason === null) {
                $eligible[] = $category;
            } elseif ($depth > $bestDepth) {
                [$bestDepth, $bestReason] = [$depth, $reason];
            }
        }

        if (count($eligible) === 1) {
            return new Suggestion($eligible[0], null);
        }

        if (count($eligible) > 1) {
            error_log(sprintf(
                'Regulamento ambíguo: atleta (%s, %d anos) cabe em %d categorias de %s: %s',
                $athlete->gender, $athlete->age, count($eligible), $disciplineCode,
                implode(', ', array_map(static fn (EventCategory $c) => $c->code, $eligible))
            ));

            return new Suggestion(null, 'Mais de uma categoria serve: confira o regulamento com a federação');
        }

        // Nenhuma categoria da disciplina cobre a idade: a mensagem fala do evento, não de uma categoria
        if ($bestDepth === array_search('age', self::CHECKS, true)) {
            $bestReason = "Idade {$athlete->age} fora das faixas do evento";
        }

        return new Suggestion(null, $bestReason);
    }

    /** Impedimento do próprio atleta, que vale para qualquer categoria. */
    private function athleteBlocker(AthleteSnapshot $athlete): ?string
    {
        return $athlete->isBlackBelt && !$athlete->hasFgkirsRegistration
            ? 'Faixa preta exige matrícula FGKIRS'
            : null;
    }

    /** @return array{0: int, 1: ?string} índice da checagem que falhou e o motivo; motivo null = serve */
    private function reject(AthleteSnapshot $a, EventCategory $c): array
    {
        // O status vem depois dos critérios do atleta: categoria fechada que serviria é o "quase" mais útil de mostrar
        $reason = match (true) {
            $c->gender !== 'X' && $c->gender !== $a->gender                => ['gender', 'Sexo diferente'],
            !$c->acceptsAge($a->age)                                       => ['age', "Idade {$a->age} fora da faixa"],
            $c->requiresBelt() && $a->beltLevel === null                   => ['belt', 'Graduação sem nível definido'],
            !$c->acceptsBelt($a->beltLevel)                                => ['belt', 'Graduação fora da divisão'],
            $c->requiresWeight() && $a->weight === null                    => ['weight', 'Informe o peso'],
            !$c->acceptsWeight($a->weight)                                 => ['weight', 'Peso fora da divisão'],
            $c->requiresHeight() && $a->height === null                    => ['weight', 'Informe a altura'],
            !$c->acceptsHeight($a->height)                                 => ['weight', 'Altura fora da divisão'],
            $c->isParaKarate && !$a->isParaKarate                          => ['para', 'Exclusiva para-karatê'],
            $c->status !== 'open'                                          => ['status', 'Categoria fechada'],
            $c->isFull()                                                   => ['full', 'Vagas esgotadas'],
            default                                                        => null,
        };

        return $reason === null ? [count(self::CHECKS), null] : [array_search($reason[0], self::CHECKS, true), $reason[1]];
    }
}
