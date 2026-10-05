<?php

namespace Domain\Championship;

/** Grupo de graduações por nível comum (graduations.level), válido para qualquer estilo. */
final readonly class BeltDivision
{
    public function __construct(
        public ?int $id,
        public string $code,
        public string $name,
        public int $levelMin,
        public int $levelMax,
        public int $sortOrder = 0,
    ) {
    }
}
