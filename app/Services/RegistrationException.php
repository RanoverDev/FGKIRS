<?php

namespace Services;

/** Falha de regra de negócio da inscrição, com mensagens prontas para a tela (uma por problema). */
class RegistrationException extends \RuntimeException
{
    /** @param string[] $messages */
    public function __construct(public readonly array $messages, public readonly int $httpStatus = 422)
    {
        parent::__construct(implode(' ', $messages));
    }

    public static function single(string $message, int $httpStatus = 422): self
    {
        return new self([$message], $httpStatus);
    }
}
