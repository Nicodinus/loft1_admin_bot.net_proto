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

    /** @var string|null */
    private ?string $requestId;

    //

    /**
     * @param ClientInterface $client
     * @param mixed|null $data
     * @param string|null $requestId
     */
    public function __construct(ClientInterface $client, $data = null, ?string $requestId = null)
    {
        $this->client = $client;
        $this->data = $data;
        $this->requestId = $requestId;
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

    /**
     * @return string|null
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }
}