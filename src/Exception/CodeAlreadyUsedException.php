<?php

namespace App\Exception;

use Exception;

class CodeAlreadyUsedException extends Exception
{
    /**
     * Le code qui a causé l'exception.
     *
     * @var string
     */
    private string $conflictingCode;

    /**
     * Constructeur de l'exception.
     *
     * @param string $conflictingCode Le code qui est déjà utilisé.
     * @param string $message         Le message d'erreur.
     * @param int    $code            Le code d'erreur.
     * @param Exception|null $previous L'exception précédente pour l'empilement des exceptions.
     */
    public function __construct(
        string $conflictingCode,
        string $message = 'Ce code est déjà pris, veuillez en choisir un autre.',
        int $code = 0,
        Exception $previous = null
    ) {
        $this->conflictingCode = $conflictingCode;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Récupère le code qui a causé l'exception.
     *
     * @return string
     */
    public function getConflictingCode(): string
    {
        return $this->conflictingCode;
    }
}
