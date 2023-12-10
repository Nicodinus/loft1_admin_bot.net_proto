<?php

namespace Nicodinus\Loft1AdminBot\NetProto;

use Amp\Socket\SocketAddress;

class Client implements ClientInterface
{
    /** @var string */
    protected string $id;

    /** @var SocketAddress */
    protected SocketAddress $remoteAddress;

    /** @var ClientHandler */
    protected ClientHandler $handler;

    //

    /**
     * @param SocketAddress $remoteAddress
     * @param string|null $id
     */
    public function __construct(SocketAddress $remoteAddress, ?string $id = null)
    {
        $this->id = $id ?? $remoteAddress->toString();
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getRemoteAddress(): SocketAddress
    {
        return $this->remoteAddress;
    }

    /**
     * @param ClientHandlerInterface $clientHandler
     *
     * @return self
     */
    public function setHandler(ClientHandlerInterface $clientHandler): self
    {
        $this->handler = $clientHandler;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getHandler(): ClientHandlerInterface
    {
        return $this->handler;
    }

    /**
     * Returns array containing all the necessary state of the object.
     * @since 7.4
     * @link https://wiki.php.net/rfc/custom_object_serialization
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'remote_address' => $this->remoteAddress->toString(),
        ];
    }

    /**
     * Restores the object state from the given data array.
     * @param array $data
     * @since 7.4
     * @link https://wiki.php.net/rfc/custom_object_serialization
     */
    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->remoteAddress = SocketAddress::fromSocketName($data['remote_address']);
    }

    /**
     * @inheritDoc
     */
    public function serialize(): ?string
    {
        return \serialize($this->__serialize());
    }

    /**
     * @inheritDoc
     *
     * @throws \ReflectionException
     */
    public function unserialize($data)
    {
        $data = \unserialize($data);

        $reflector = new \ReflectionClass($this);
        $instance = $reflector->newInstanceWithoutConstructor();
        $instance->__unserialize($data);
        return $instance;
    }

}