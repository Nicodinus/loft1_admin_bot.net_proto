<?php

namespace Nicodinus\Loft1AdminBot\NetProto;

use Amp\Socket\SocketAddress;

interface ClientInterface extends \Serializable
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return SocketAddress
     */
    public function getRemoteAddress(): SocketAddress;

    /**
     * @return ClientHandlerInterface
     */
    public function getHandler(): ClientHandlerInterface;
}