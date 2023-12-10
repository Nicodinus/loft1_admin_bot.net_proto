<?php

namespace Nicodinus\Loft1AdminBot\NetProto\Packets;

class HandshakeStep2Packet extends AbstractClientRequestPacket
{
    /**
     * @inheritDoc
     */
    public static function getId(): ?string
    {
        return "handshake.step2";
    }

}