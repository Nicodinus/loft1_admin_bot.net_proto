<?php

namespace Nicodinus\Loft1AdminBot\NetProto\Packets;

use Nicodinus\SocketPacketHandler\CanHandlePacket;

class HandshakePacket extends AbstractClientRequestPacket implements CanHandlePacket
{
    /**
     * @inheritDoc
     */
    public static function getId(): ?string
    {
        return "handshake";
    }

    /**
     * @inheritDoc
     */
    public function handle(?string $requestId = null)
    {
        yield $this->client->getHandler()->createRequestPacket(HandshakeStep2Packet::class, $requestId)->send();
    }

}