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
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class ActorFallPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::ACTOR_FALL_PACKET;

	private int $actorRuntimeId;
	private float $fallDistance;
	private bool $inVoid;

	/**
	 * @generate-create-func
	 */
	public static function create(int $actorRuntimeId, float $fallDistance, bool $inVoid) : self{
		$result = new self;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->fallDistance = $fallDistance;
		$result->inVoid = $inVoid;
		return $result;
	}

	public function getActorRuntimeId() : int{ return $this->actorRuntimeId; }

	public function getFallDistance() : float{ return $this->fallDistance; }

	public function isInVoid() : bool{ return $this->inVoid; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->fallDistance = LE::readFloat($in);
		$this->inVoid = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		LE::writeFloat($out, $this->fallDistance);
		CommonTypes::putBool($out, $this->inVoid);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleActorFall($this);
	}
}
