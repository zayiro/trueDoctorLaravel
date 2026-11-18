<?php

declare(strict_types=1);

namespace App\Payments\Exceptions;

use RuntimeException;

/** El webhook no trae una firma válida: se rechaza sin procesarlo. */
final class InvalidWebhookSignature extends RuntimeException
{
}
