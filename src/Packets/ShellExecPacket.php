<?php

namespace Nicodinus\Loft1AdminBot\NetProto\Packets;

use Amp\ByteStream;
use Amp\Process\Process;
use Nicodinus\SocketPacketHandler\CanHandlePacket;
use function Amp\Promise\timeout;
use function Amp\Promise\timeoutWithDefault;

class ShellExecPacket extends AbstractClientRequestPacket implements CanHandlePacket
{
    /**
     * @inheritDoc
     */
    public static function getId(): ?string
    {
        return "shell_exec";
    }

    /**
     * @param string $query
     * @return void
     */
    public function setQuery(string $query): void
    {
        if (!$this->data) {
            $this->data = [];
        }

        $this->data['query'] = $query;
    }

    /**
     * @inheritDoc
     */
    public function handle(?string $requestId = null)
    {
        if (!$this->client->getHandler()->isAuthorized()) {
            yield $this->client->getHandler()->createRequestPacket(UnauthorizedErrorPacket::class, $requestId)->send();
            return;
        }

        if (!\is_array($this->data) || !isset($this->data['query'])) {
            yield $this->client->getHandler()->createRequestPacket(InvalidRequestErrorPacket::class, $requestId)->send();
            return;
        }

        try {

            $process = new Process($this->data['query']);
            yield timeout($process->start(), 5000);

            $stdoutResult = ByteStream\buffer($process->getStdout());
            $stderrResult = ByteStream\buffer($process->getStderr());

            yield timeoutWithDefault($process->join(), 5000);
            if ($process->isRunning()) {
                $process->kill();
            }

            /** @var ShellExecResponsePacket $packet */
            $packet = $this->client->getHandler()->createRequestPacket(ShellExecResponsePacket::class, $requestId);
            $packet->setResult([
                'stdout' => yield $stdoutResult,
                'stderr' => yield $stderrResult,
            ]);
            yield $packet->send();

        } catch (\Throwable $exception) {
            /** @var ShellExecResponsePacket $packet */
            $packet = $this->client->getHandler()->createRequestPacket(ShellExecResponsePacket::class, $requestId);
            $packet->setResult([
                'error' => \serialize($exception),
            ]);
            yield $packet->send();
        }

    }

}