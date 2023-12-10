<?php

namespace Nicodinus\Loft1AdminBot\NetProto\Packets;

use Nicodinus\Loft1AdminBot\NetProto\ClientInterface;

class PingResponsePacket extends AbstractClientRequestPacket
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
            $this->data = [];
        }
    }

    /**
     * @param $hrtime
     * @return void
     */
    public function appendTime($hrtime): void
    {
        $this->data[] = $hrtime;
    }

    /**
     * @inheritDoc
     */
    public static function getId(): ?string
    {
        return "ping.response";
    }
}