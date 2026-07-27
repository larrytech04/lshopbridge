<?php

namespace App\Exceptions;

use RuntimeException;

/** Thrown when a connector method is called that this source doesn't actually support. */
class ConnectorCapabilityException extends RuntimeException
{
    public static function unsupported(string $connector, string $method): self
    {
        return new self("{$connector} does not support {$method}() — this source has no real integration for that operation yet.");
    }
}
