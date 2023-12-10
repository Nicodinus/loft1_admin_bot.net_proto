<?php

namespace Nicodinus\Loft1AdminBot\NetProto;

use Amp\Promise;
use Amp\Serialization\JsonSerializer;
use Amp\Socket;
use Nicodinus\Loft1AdminBot\NetProto\Packets\AbstractClientPacket;
use Nicodinus\SocketPacketHandler\AbstractPacketHandler;
use Nicodinus\SocketPacketHandler\PacketInterface;
use phpseclib3\Crypt\Common\SymmetricKey;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ClientPacketHandler extends AbstractPacketHandler
{
    /** @var Client */
    private Client $client;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var SymmetricKey|null */
    private ?SymmetricKey $cryptor;

    /** @var JsonSerializer */
    private JsonSerializer $jsonSerializer;

    /** @var bool */
    private bool $isEncrypted;

    //

    /**
     * @param Socket\Socket $socket
     * @param Client $client
     * @param SymmetricKey|null $cryptor
     * @param LoggerInterface|null $logger
     */
    public function __construct(Socket\Socket $socket, Client $client, ?SymmetricKey $cryptor = null, ?LoggerInterface $logger = null)
    {
        parent::__construct($socket);

        $this->isEncrypted = false;
        $this->client = $client;
        $this->logger = $logger ?? new NullLogger();
        $this->cryptor = $cryptor;
        $this->jsonSerializer = JsonSerializer::withAssociativeArrays();
    }

    /**
     * @param bool $isEnabled
     * @param SymmetricKey|null $cryptor
     *
     * @return self
     */
    public function setEncryption(bool $isEnabled, ?SymmetricKey $cryptor = null): self
    {
        if ($cryptor) {
            $this->cryptor = $cryptor;
        }

        if ($isEnabled && !$this->cryptor) {
            throw new \InvalidArgumentException("Can't enable encryption because cryptor is not presented!");
        }

        $this->isEncrypted = $isEnabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return $this->isEncrypted;
    }

    /**
     * @inheritDoc
     */
    protected function _handlePacket(PacketInterface $packet)
    {
        $this->logger->debug("HANDLE packet {$packet::getId()}");
    }

    /**
     * @inheritDoc
     */
    protected function _unserializePacket(string $data): ?array
    {
        if ($this->isEncrypted) {
            $data = $this->cryptor->decrypt($data);
        }

        $data = $this->jsonSerializer->unserialize($data);
        if (!$data['id']) {
            return null;
        }

        return [
            'id' => $data['id'],
            'request_id' => $data['request_id'] ?? null,
            'data' => $data['data'] ?? null,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function _serializePacket(string $id, ?string $requestId = null, $data = null): string
    {
        $data = $this->jsonSerializer->serialize([
            'id' => $id,
            'request_id' => $requestId,
            'data' => $data,
        ]);

        if ($this->isEncrypted) {
            $data = $this->cryptor->encrypt($data);
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function _handleException(\Throwable $throwable): void
    {
        $this->logger->error("An unhandled exception", ['exception' => $throwable]);

        Promise\rethrow($this->client->getHandler()->close());
    }

    /**
     * @inheritDoc
     */
    protected function _createPacket(string $packetClassname, ?string $requestId = null, $data = null): ?PacketInterface
    {
        if (\is_a($packetClassname, AbstractClientPacket::class, true)) {
            return new $packetClassname($this->client, $data);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function _onClosed(): void
    {
        $this->logger->debug("Connection with {$this->client->getRemoteAddress()} closed");

        Promise\rethrow($this->client->getHandler()->close());
    }

}