<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Les messages d'erreur système sous Windows (ex. "Hôte inconnu") sont
     * encodés en Windows-1252 : on les convertit en UTF-8 pour que la
     * réponse JSON d'erreur ne plante pas à son tour.
     */
    protected function convertExceptionToArray(Throwable $e)
    {
        return $this->toUtf8(parent::convertExceptionToArray($e));
    }

    private function toUtf8(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->toUtf8($v), $value);
        }

        if (is_string($value) && ! mb_check_encoding($value, 'UTF-8')) {
            return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        return $value;
    }
}
