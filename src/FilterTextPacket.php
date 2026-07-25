<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * Legacy text filtering packet used before protocol 671.
 */
class FilterTextPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::FILTER_TEXT_PACKET;

	private string $text;
	private bool $fromServer;

	public static function create(string $text, bool $fromServer) : self{
		$result = new self;
		$result->text = $text;
		$result->fromServer = $fromServer;
		return $result;
	}

	public function getText() : string{ return $this->text; }

	public function isFromServer() : bool{ return $this->fromServer; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->text = CommonTypes::getString($in);
		$this->fromServer = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->text);
		CommonTypes::putBool($out, $this->fromServer);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleFilterText($this);
	}
}
