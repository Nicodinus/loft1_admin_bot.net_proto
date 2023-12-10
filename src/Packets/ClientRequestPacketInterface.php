<?php

namespace Nicodinus\Loft1AdminBot\NetProto\Packets;

use Nicodinus\SocketPacketHandler\CanSendPacket;
use Nicodinus\SocketPacketHandler\RequestPacketInterface;

interface ClientRequestPacketInterface extends RequestPacketInterface, CanSendPacket
{
    //
}