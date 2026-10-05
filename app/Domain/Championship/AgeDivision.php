<?php

namespace Domain\Championship;

final readonly class AgeDivision
{
    public function __construct(
        public ?int $id,
        public string $code,
        public string $name,
        public ?int $ageMin,
        public ?int $ageMax,
        public int $sortOrder = 0,
    ) {
    }

    /**
     * Nome com a faixa entre parênteses ("Infantil (11 a 12)"). Quando o nome já traz a
     * idade ("Até 14 anos"), repetir os números só poluiria o rótulo.
     */
    public function label(): string
    {
        if (preg_match('/\d/', $this->name)) {
            return $this->name;
        }

        return match (true) {
            $this->ageMin !== null && $this->ageMax !== null => "{$this->name} ({$this->ageMin} a {$this->ageMax})",
            $this->ageMin !== null                           => "{$this->name} ({$this->ageMin} acima)",
            $this->ageMax !== null                           => "{$this->name} (até {$this->ageMax})",
            default                                          => $this->name,
        };
    }
}
