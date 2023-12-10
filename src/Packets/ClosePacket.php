<?php

namespace Nicodinus\Loft1AdminBot\NetProto\Packets;

use Amp\Promise;
use Nicodinus\SocketPacketHandler\CanHandlePacket;

class ClosePacket extends AbstractClientRequestPacket implements CanHandlePacket
{
    /**
     * @inheritDoc
     */
    public static function getId(): ?string
    {
        return "close";
    }

    /**
     * @inheritDoc
     */
    public function handle()
    {
        if (!$this->client->getHandler()->isAuthorized()) {
            yield $this->client->getHandler()->createRequestPacket(UnauthorizedErrorPacket::class)->send();
            return;
        }

        Promise\rethrow($this->client->getHandler()->close());
    }

}