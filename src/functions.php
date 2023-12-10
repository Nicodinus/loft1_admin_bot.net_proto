<?php

namespace Nicodinus\Loft1AdminBot\NetProto;

function serializeException(\Throwable $exception): array
{
    return [
        'class' => \get_class($exception),
        'message' => $exception->getMessage(),
        'code' => $exception->getCode(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        //'trace' => $exception->getTrace(),
        //'previous' => $exception->getPrevious() ? serializeException($exception->getPrevious()) : null,
    ];
}

function unserializeException(array $arr): \Throwable
{
    $props = [
        'message',
        'code',
        'file',
        'line',
    ];

    $reflector = new \ReflectionClass($arr['class']);
    $instance = $reflector->newInstanceWithoutConstructor();

    foreach ($props as $prop) {
        $_prop = $reflector->getProperty($prop);
        $_prop->setAccessible(true);
        $_prop->setValue($instance, $arr[$prop]);
        $_prop->setAccessible(false);
    }

    return $instance;
}
