<?php
declare(strict_types=1);

namespace App\CryptoGatewayEngine\Exception;

final class ValueObjectValidationException extends \LogicException
{
    private const MESSAGE = 'Value object "%s" validation error with value "%s"';

    public function __construct(object $object, string $value)
    {
        parent::__construct(sprintf(static::MESSAGE, get_class($object), $value));
    }
}
