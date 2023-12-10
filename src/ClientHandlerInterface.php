<?php

namespace Nicodinus\Loft1AdminBot\NetProto;

use Amp\ByteStream\ClosedException;
use Amp\ByteStream\StreamException;
use Amp\Promise;
use Amp\Serialization\SerializationException;
use Nicodinus\Loft1AdminBot\NetProto\Packets\ClientRequestPacketInterface;
use Nicodinus\SocketPacketHandler\PacketInterface;
use phpseclib3\Crypt\Common\SymmetricKey;

interface ClientHandlerInterface
{
    /**
     * @return ClientInterface
     */
    public function getClient(): ClientInterface;

    /**
     * @return bool
     */
    public function isAuthorized(): bool;

    /**
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * @return Promise<void>
     */
    public function establishConnection(): Promise;

    /**
     * @return Promise<int|false>
     */
    public function ping(): Promise;

    /**
     * @return Promise<void>
     */
    public function reload(): Promise;

    /**
     * @return Promise<void>
     */
    public function close(): Promise;

    /**
     * @return Promise<void>
     */
    public function shutdown(): Promise;

    /**
     * @param bool $isEnabled
     * @param SymmetricKey|null $cryptor
     *
     * @return self
     */
    public function setEncryption(bool $isEnabled, ?SymmetricKey $cryptor = null): self;

    /**
     * @return bool
     */
    public function isEncrypted(): bool;

    /**
     * @param string $packetClassname
     *
     * @return ClientRequestPacketInterface
     */
    public function createRequestPacket(string $packetClassname): ClientRequestPacketInterface;

    /**
     * @param ClientRequestPacketInterface $request
     *
     * @return Promise<void>
     *
     * @throws ClosedException
     * @throws StreamException
     * @throws SerializationException
     */
    public function sendRequest(ClientRequestPacketInterface $request): Promise;

    /**
     * @param ClientRequestPacketInterface $request
     * @param int $responseTimeoutSeconds
     *
     * @return Promise<PacketInterface>
     */
    public function sendRequestWaitResponse(ClientRequestPacketInterface $request, int $responseTimeoutSeconds = 5): Promise;
}