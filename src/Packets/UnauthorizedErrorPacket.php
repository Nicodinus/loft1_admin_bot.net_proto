<?php

namespace Nicodinus\Loft1AdminBot\NetProto\Packets;

class UnauthorizedErrorPacket extends AbstractClientRequestPacket
{
    /**
     * @inheritDoc
     */
    public static function getId(): ?string
    {
        return "error.unauthorized";
    }
}