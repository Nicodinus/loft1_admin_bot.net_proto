<?php

namespace Nicodinus\Loft1AdminBot\NetProto\Packets;

use Nicodinus\Loft1AdminBot\NetProto\ClientInterface;

class ShellExecResponsePacket extends AbstractClientRequestPacket
{
    /**
     * @param ClientInterface $client
     * @param mixed|null $data
     * @param string|null $requestId
     */
    public function __construct(ClientInterface $client, $data = null, ?string $requestId = null)
    {
        parent::__construct($client, $data, $requestId);
    }

    /**
     * @param $result
     * @return void
     */
    public function setResult($result): void
    {
        $this->data = $result;
    }

    /**
     * @inheritDoc
     */
    public static function getId(): ?string
    {
        return "shell_exec.response";
    }
}