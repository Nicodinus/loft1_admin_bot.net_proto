<?php

namespace Nicodinus\Loft1AdminBot\NetProto\Packets;

use Nicodinus\Loft1AdminBot\NetProto\ClientInterface;
use Nicodinus\SocketPacketHandler\PacketInterface;

abstract class AbstractClientPacket implements PacketInterface
{
    /** @var ClientInterface */
    protected ClientInterface $client;

    /** @var mixed|null */
    protected $data;

    //

    /**
     * @param ClientInterface $client
     * @param mixed|null $data
     */
    public function __construct(ClientInterface $client, $data = null)
    {
        $this->client = $client;
        $this->data = $data;
    }

    /**
     * @return ClientInterface
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        return $this->data;
    }

}