<?php

namespace App\Services\Ai;

use RuntimeException;

/** Error de IA con un mensaje apto para mostrar al usuario (sin secretos). */
class AiException extends RuntimeException
{
}
