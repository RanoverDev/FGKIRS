<?php

namespace Domain\Championship;

final readonly class EventCategory
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public string $shortName,
        public string $disciplineCode,
        public string $disciplineName,
        public string $modality,
        public string $format,
        public string $gender,
        public ?int $ageMin,
        public ?int $ageMax,
        public ?int $beltLevelMin,
        public ?int $beltLevelMax,
        public ?float $weightMin,
        public ?float $weightMax,
        public ?int $teamSize,
        public ?int $teamReserves,
        public bool $isParaKarate,
        public ?int $maxEntries,
        public string $status,
        public int $enrolled = 0,
    ) {
    }

    public static function fromRow(array $r): self
    {
        $int   = static fn (mixed $v): ?int => $v === null ? null : (int) $v;
        $float = static fn (mixed $v): ?float => $v === null ? null : (float) $v;

        return new self(
            (int) $r['id'], $r['code'], $r['name'], $r['short_name'] ?: $r['name'],
            $r['discipline_code'], $r['discipline_name'], $r['modality'], $r['format'], $r['gender'],
            $int($r['age_min']), $int($r['age_max']), $int($r['belt_level_min']), $int($r['belt_level_max']),
            $float($r['weight_min']), $float($r['weight_max']), $int($r['team_size']), $int($r['team_reserves']),
            (bool) $r['is_para_karate'], $int($r['max_entries']), $r['status'], (int) ($r['enrolled'] ?? 0)
        );
    }

    /** Cópia com outra contagem de inscritos, para não contar o próprio atleta ao reavaliar a inscrição dele. */
    public function withEnrolled(int $enrolled): self
    {
        return new self(
            $this->id, $this->code, $this->name, $this->shortName, $this->disciplineCode, $this->disciplineName,
            $this->modality, $this->format, $this->gender, $this->ageMin, $this->ageMax,
            $this->beltLevelMin, $this->beltLevelMax, $this->weightMin, $this->weightMax,
            $this->teamSize, $this->teamReserves, $this->isParaKarate, $this->maxEntries, $this->status, $enrolled
        );
    }

    /** Categoria fechada não aceita inscrição nova, mas quem já está nela continua: reavalia as demais regras como se estivesse aberta. */
    public function asOpen(): self
    {
        return new self(
            $this->id, $this->code, $this->name, $this->shortName, $this->disciplineCode, $this->disciplineName,
            $this->modality, $this->format, $this->gender, $this->ageMin, $this->ageMax,
            $this->beltLevelMin, $this->beltLevelMax, $this->weightMin, $this->weightMax,
            $this->teamSize, $this->teamReserves, $this->isParaKarate, $this->maxEntries,
            $this->status === 'closed' ? 'open' : $this->status, $this->enrolled
        );
    }

    public function isIndividual(): bool
    {
        return $this->format === 'individual';
    }

    public function acceptsAge(int $age): bool
    {
        return ($this->ageMin === null || $age >= $this->ageMin)
            && ($this->ageMax === null || $age <= $this->ageMax);
    }

    /** Categoria sem limite de nível aceita qualquer faixa, inclusive atleta sem nível definido. */
    public function acceptsBelt(?int $level): bool
    {
        if ($this->beltLevelMin === null && $this->beltLevelMax === null) {
            return true;
        }

        return $level !== null
            && ($this->beltLevelMin === null || $level >= $this->beltLevelMin)
            && ($this->beltLevelMax === null || $level <= $this->beltLevelMax);
    }

    public function requiresBelt(): bool
    {
        return $this->beltLevelMin !== null || $this->beltLevelMax !== null;
    }

    public function requiresWeight(): bool
    {
        return $this->weightMin !== null || $this->weightMax !== null;
    }

    /** Mínimo exclusivo e máximo inclusivo: 50,00 cabe em "até 50" e não em "acima de 50". */
    public function acceptsWeight(?float $weight): bool
    {
        if (!$this->requiresWeight()) {
            return true;
        }

        return $weight !== null
            && ($this->weightMin === null || $weight > $this->weightMin)
            && ($this->weightMax === null || $weight <= $this->weightMax);
    }

    public function isFull(): bool
    {
        return $this->maxEntries !== null && $this->enrolled >= $this->maxEntries;
    }
}
