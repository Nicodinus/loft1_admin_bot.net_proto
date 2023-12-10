<?php

namespace Nicodinus\Loft1AdminBot\NetProto;

use Amp\Deferred;
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
    protected Client $client;

    /** @var LoggerInterface */
    protected LoggerInterface $logger;

    /** @var SymmetricKey|null */
    protected ?SymmetricKey $cryptor;

    /** @var JsonSerializer */
    protected JsonSerializer $jsonSerializer;

    /** @var bool */
    protected bool $isEncrypted;

    /** @var Deferred */
    protected Deferred $closedDefer;

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
        $this->closedDefer = new Deferred();
    }

    /**
     * @return Promise<void>
     */
    public function getClosedPromise(): Promise
    {
        return $this->closedDefer->promise();
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

        $this->logger->debug("Toggle encryption", ['enabled' => $isEnabled]);

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
    protected function _handlePacket(PacketInterface $packet, ?string $requestId = null)
    {
        $data = [];
        if ($requestId) {
            $data['request_id'] = $requestId;
        }

        $this->logger->debug("HANDLE packet {$packet::getId()}", $data);
    }

    /**
     * @inheritDoc
     */
    protected function _unserializePacket(string $data): ?array
    {
        if ($this->isEncrypted) {
            $data = $this->cryptor->decrypt($data);
        }

        //dump($data);

        $data = $this->jsonSerializer->unserialize($data);
        if (!$data['id']) {
            return null;
        }

        $this->logger->debug("UNSERIALIZE packet {$data['id']}", [
            'request_id' => $data['request_id'] ?? null,
            'data' => $data['data'] ?? null,
        ]);

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
        $_data = $this->jsonSerializer->serialize([
            'id' => $id,
            'request_id' => $requestId,
            'data' => $data,
        ]);

        //dump($_data);

        if ($this->isEncrypted) {
            $_data = $this->cryptor->encrypt($_data);
        }

        $this->logger->debug("SERIALIZE packet {$id}", [
            'request_id' => $requestId,
            'data' => $data,
        ]);

        return $_data;
    }

    /**
     * @inheritDoc
     */
    protected function _handleException(\Throwable $throwable): void
    {
        $this->logger->error("An unhandled exception", ['exception' => $throwable]);
        if (!$this->closedDefer->isResolved()) {
            $this->closedDefer->fail($throwable);
        }

        $this->close();
    }

    /**
     * @inheritDoc
     */
    protected function _createPacket(string $packetClassname, ?string $requestId = null, $data = null): ?PacketInterface
    {
        if (\is_a($packetClassname, AbstractClientPacket::class, true)) {
            return new $packetClassname($this->client, $data, $requestId);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function _onClosed(): void
    {
        $this->logger->debug("Connection with {$this->client->getRemoteAddress()} closed");

        if (!$this->closedDefer->isResolved()) {
            $this->closedDefer->resolve();
        }

        Promise\rethrow($this->client->getHandler()->close());
    }

}