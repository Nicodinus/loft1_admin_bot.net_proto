<?php

namespace Nicodinus\Loft1AdminBot\NetProto\Packets;

use Amp\Promise;

abstract class AbstractClientRequestPacket extends AbstractClientPacket implements ClientRequestPacketInterface
{
    /**
     * @inheritDoc
     */
    public function send(): Promise
    {
        return $this->getClient()->getHandler()->sendRequest($this);
    }

    /**
     * @inheritDoc
     */
    public function sendWaitResponse(int $responseTimeoutSeconds = 5): Promise
    {
        return $this->getClient()->getHandler()->sendRequestWaitResponse($this, $responseTimeoutSeconds);
    }
}