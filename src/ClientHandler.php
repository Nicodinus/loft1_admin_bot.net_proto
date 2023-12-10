<?php

namespace Nicodinus\Loft1AdminBot\NetProto;

use Amp\ByteStream\ClosedException;
use Amp\Promise;
use Amp\Socket;
use Amp\Sync\LocalMutex;
use Amp\Sync\Mutex;
use HaydenPierce\ClassFinder\ClassFinder;
use Nicodinus\SocketPacketHandler\PacketInterface;
use phpseclib3\Crypt\Common\SymmetricKey;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function Amp\call;

class ClientHandler implements ClientHandlerInterface
{
    /** @var class-string<PacketInterface>[] */
    protected static array $packetsRegistry;

    /** @var ClientInterface */
    protected ClientInterface $client;

    /** @var LoggerInterface */
    protected LoggerInterface $logger;

    /** @var Mutex */
    protected Mutex $mutex;

    /** @var SymmetricKey|null */
    protected ?SymmetricKey $cryptor;

    /** @var ClientPacketHandler|null */
    protected ?ClientPacketHandler $packetHandler;

    /** @var bool */
    protected bool $isShutdown;

    /** @var bool */
    protected bool $isAuthorized;

    //

    /**
     * @param Client $client
     * @param SymmetricKey|null $cryptor
     * @param LoggerInterface|null $logger
     */
    public function __construct(Client $client, ?SymmetricKey $cryptor = null, ?LoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->client->setHandler($this);

        $this->cryptor = $cryptor;
        $this->logger = $logger ?? new NullLogger();

        $this->mutex = new LocalMutex();
        $this->packetHandler = null;
        $this->isShutdown = false;
        $this->isAuthorized = false;

        if (empty(self::$packetsRegistry)) {

            self::$packetsRegistry = [];

            $classes = [];
            try {
                $classes = ClassFinder::getClassesInNamespace("Nicodinus\\Loft1AdminBot\\NetProto\\Packets", ClassFinder::RECURSIVE_MODE);
            } catch (\Throwable $exception) {
                $this->logger->error("Packets finder exception", ['exception' => $exception]);
            }

            foreach ($classes as $class) {

                try {

                    $reflector = new \ReflectionClass($class);
                    if ($reflector->isAbstract() || $reflector->isInterface() || !\is_a($class, PacketInterface::class, true)) {
                        continue;
                    }

                } catch (\Throwable $exception) {
                    $this->logger->error("Packets finder exception", ['exception' => $exception]);
                    continue;
                }

                self::$packetsRegistry[] = $class;

            }


        }
    }

    /**
     * @inheritDoc
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * @inheritDoc
     */
    public function isAuthorized(): bool
    {
        return $this->isAuthorized;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        return $this->packetHandler && !$this->packetHandler->isClosed();
    }

    /**
     * @inheritDoc
     */
    public function establishConnection(): Promise
    {
        return call(function () {

            if ($this->isAvailable()) {
                return;
            }

            if ($this->isShutdown) {
                throw new ClosedException("Shutdown pending");
            }

            $lock = yield $this->mutex->acquire();
            if ($this->isAvailable()) {
                $lock->release();
                return;
            }

            try {

                $this->logger->debug("Trying connect with {$this->client->getRemoteAddress()}");
                $socket = yield Socket\connect($this->client->getRemoteAddress());

                $this->packetHandler = (new ClientPacketHandler(
                    $socket,
                    $this->client,
                    $this->cryptor,
                    $this->logger
                ));

                foreach (self::$packetsRegistry as $packetClassname) {
                    $this->packetHandler->registerPacket($packetClassname);
                }

                //TODO: handshake

                $this->logger->debug("Connection successfully established with {$this->client->getRemoteAddress()}");

            } catch (\Throwable $exception) {
                $this->packetHandler = null;
                $this->logger->debug("Can't establish connection with {$this->client->getRemoteAddress()}");
            } finally {
                $lock->release();
            }

        });
    }

    /**
     * @return Promise<void>
     */
    protected function _checkConnection(): Promise
    {
        return call(function () {

            if (!$this->isAvailable()) {
                yield $this->establishConnection();
            }

            if (!$this->isAvailable()) {
                throw new ClosedException("Can't establish connection with {$this->client->getRemoteAddress()}");
            }

        });
    }

    /**
     * @inheritDoc
     */
    public function ping(): Promise
    {
        return call(function () {

            yield $this->_checkConnection();

            // TODO: Implement ping() method.

        });
    }

    /**
     * @inheritDoc
     */
    public function reload(): Promise
    {
        return call(function () {

            yield $this->_checkConnection();

            // TODO: Implement reload() method.

        });
    }

    /**
     * @return Promise<void>
     */
    public function getClosedPromise(): Promise
    {
        return $this->packetHandler->getClosedPromise();
    }

    /**
     * @inheritDoc
     */
    public function close(): Promise
    {
        return call(function () {

            if (!$this->isAvailable()) {
                return;
            }

            $lock = yield $this->mutex->acquire();
            if (!$this->isAvailable()) {
                $lock->release();
                return;
            }

            $this->logger->debug("Pending close {$this->client->getRemoteAddress()}");

            try {

                yield $this->createRequestPacket(Packets\ClosePacket::class)->send();

            } finally {
                $this->packetHandler->close();
                $lock->release();
            }

        });
    }

    /**
     * @inheritDoc
     */
    public function shutdown(): Promise
    {
        $this->isShutdown = true;
        return $this->close();
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        Promise\rethrow($this->shutdown());
    }

    /**
     * @inheritDoc
     */
    public function createRequestPacket(string $packetClassname): Packets\ClientRequestPacketInterface
    {
        if (\is_a($packetClassname, Packets\AbstractClientRequestPacket::class, true)) {
            return new $packetClassname($this->client);
        }

        throw new \InvalidArgumentException("Unsupported class {$packetClassname}!");
    }

    /**
     * @inheritDoc
     */
    public function sendRequest(Packets\ClientRequestPacketInterface $request): Promise
    {
        $data = [];
        if ($request->getData()) {
            if (\is_array($request->getData())) {
                $data = $request->getData();
            } else {
                $data['data'] = $request->getData();
            }
        }

        $this->logger->debug("SEND {$request::getId()}", $data);

        return $this->packetHandler->sendPacket($request);
    }

    /**
     * @inheritDoc
     */
    public function sendRequestWaitResponse(Packets\ClientRequestPacketInterface $request, int $responseTimeoutSeconds = 5): Promise
    {
        $data = [];
        if ($request->getData()) {
            if (\is_array($request->getData())) {
                $data = $request->getData();
            } else {
                $data['data'] = $request->getData();
            }
        }

        $this->logger->debug("SEND & WAIT {$responseTimeoutSeconds} {$request::getId()}", $data);

        return $this->packetHandler->sendPacketWithResponse($request, $responseTimeoutSeconds);
    }

    /**
     * @inheritDoc
     */
    public function setEncryption(bool $isEnabled, ?SymmetricKey $cryptor = null): self
    {
        if ($cryptor) {
            $this->cryptor = $cryptor;
        }

        $this->packetHandler->setEncryption($isEnabled, $this->cryptor);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isEncrypted(): bool
    {
        return $this->packetHandler->isEncrypted();
    }
}