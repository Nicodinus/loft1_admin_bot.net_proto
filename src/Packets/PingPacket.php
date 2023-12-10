<?php

namespace Nicodinus\Loft1AdminBot\NetProto\Packets;

use Nicodinus\Loft1AdminBot\NetProto\ClientInterface;
use Nicodinus\SocketPacketHandler\CanHandlePacket;

class PingPacket extends AbstractClientRequestPacket implements CanHandlePacket
{
    /**
     * @param ClientInterface $client
     * @param mixed|null $data
     * @param string|null $requestId
     */
    public function __construct(ClientInterface $client, $data = null, ?string $requestId = null)
    {
        parent::__construct($client, $data, $requestId);

        if (!$this->data) {
            $this->data = \hrtime(true);
        }
    }

    /**
     * @inheritDoc
     */
    public static function getId(): ?string
    {
        return "ping";
    }

    /**
     * @inheritDoc
     */
    public function handle(?string $requestId = null)
    {
        /** @var PingResponsePacket $packet */
        $packet = $this->client->getHandler()->createRequestPacket(PingResponsePacket::class, $requestId);
        $packet->appendTime($this->data);

        //yield Amp\delay(\mt_rand(1, 20) * 100);

        $packet->appendTime(\hrtime(true));

        yield $packet->send();
    }

}