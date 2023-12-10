<?php

namespace Nicodinus\Loft1AdminBot\NetProto\Packets;

class InvalidRequestErrorPacket extends AbstractClientRequestPacket
{
    /**
     * @inheritDoc
     */
    public static function getId(): ?string
    {
        return "error.invalid_request";
    }
}