<?php

namespace Nicodinus\Loft1AdminBot\NetProto;

/**
 * @param \Throwable $exception
 *
 * @return array{class: string, message: string, code: int, file: string, line: int}
 */
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

/**
 * @param array{class: string, message: string, code: int, file: string, line: int} $arr
 *
 * @return \Throwable
 *
 * @throws \ReflectionException
 */
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
        if (!isset($arr[$prop])) {
            continue;
        }
        $_prop = $reflector->getProperty($prop);
        $_prop->setAccessible(true);
        $_prop->setValue($instance, $arr[$prop]);
        $_prop->setAccessible(false);
    }

    return $instance;
}
