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
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class MoveActorDeltaPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::MOVE_ACTOR_DELTA_PACKET;

	public const FLAG_HAS_X = 0x01;
	public const FLAG_HAS_Y = 0x02;
	public const FLAG_HAS_Z = 0x04;
	public const FLAG_HAS_PITCH = 0x08;
	public const FLAG_HAS_YAW = 0x10;
	public const FLAG_HAS_HEAD_YAW = 0x20;
	public const FLAG_GROUND = 0x40;
	public const FLAG_TELEPORT = 0x80;
	public const FLAG_FORCE_MOVE_LOCAL_ENTITY = 0x100;
	public const FLAG_FORCE_COMPLETION = 0x200;

	public int $actorRuntimeId;
	public int $flags;
	public float $xPos = 0;
	public float $yPos = 0;
	public float $zPos = 0;
	public float $pitch = 0.0;
	public float $yaw = 0.0;
	public float $headYaw = 0.0;

	/** @throws DataDecodeException */
	private function maybeReadCoord(int $flag, ByteBufferReader $in, int $protocolId) : float{
		if(($this->flags & $flag) !== 0){
			return $protocolId >= ProtocolInfo::PROTOCOL_1_16_100 ?
				LE::readFloat($in) :
				(float) VarInt::readSignedInt($in);
		}
		return 0;
	}

	/** @throws DataDecodeException */
	private function maybeReadRotation(int $flag, ByteBufferReader $in) : float{
		if(($this->flags & $flag) !== 0){
			return CommonTypes::getRotationByte($in);
		}
		return 0.0;
	}

	/** @throws DataDecodeException */
	private function maybeReadCoordOptional(ByteBufferReader $in, int $flag) : float{
		if(CommonTypes::getBool($in)){
			$this->flags |= $flag;
			return LE::readFloat($in);
		}
		return 0;
	}

	/** @throws DataDecodeException */
	private function maybeReadRotationOptional(ByteBufferReader $in, int $flag) : float{
		if(CommonTypes::getBool($in)){
			$this->flags |= $flag;
			return CommonTypes::getRotationByte($in);
		}
		return 0.0;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->flags = 0;
			$this->xPos = $this->maybeReadCoordOptional($in, self::FLAG_HAS_X);
			$this->yPos = $this->maybeReadCoordOptional($in, self::FLAG_HAS_Y);
			$this->zPos = $this->maybeReadCoordOptional($in, self::FLAG_HAS_Z);
			$this->pitch = $this->maybeReadRotationOptional($in, self::FLAG_HAS_PITCH);
			$this->yaw = $this->maybeReadRotationOptional($in, self::FLAG_HAS_YAW);
			$this->headYaw = $this->maybeReadRotationOptional($in, self::FLAG_HAS_HEAD_YAW);
			if(CommonTypes::getBool($in)){
				$this->flags |= self::FLAG_GROUND;
			}
			if(CommonTypes::getBool($in)){
				$this->flags |= self::FLAG_TELEPORT;
			}
			if(CommonTypes::getBool($in)){
				$this->flags |= self::FLAG_FORCE_MOVE_LOCAL_ENTITY;
			}
			if(CommonTypes::getBool($in)){
				$this->flags |= self::FLAG_FORCE_COMPLETION;
			}
			return;
		}

		$this->flags = LE::readUnsignedShort($in);
		$this->xPos = $this->maybeReadCoord(self::FLAG_HAS_X, $in, $protocolId);
		$this->yPos = $this->maybeReadCoord(self::FLAG_HAS_Y, $in, $protocolId);
		$this->zPos = $this->maybeReadCoord(self::FLAG_HAS_Z, $in, $protocolId);
		$this->pitch = $this->maybeReadRotation(self::FLAG_HAS_PITCH, $in);
		$this->yaw = $this->maybeReadRotation(self::FLAG_HAS_YAW, $in);
		$this->headYaw = $this->maybeReadRotation(self::FLAG_HAS_HEAD_YAW, $in);
	}

	private function maybeWriteCoord(int $flag, float $val, ByteBufferWriter $out, int $protocolId) : void{
		if(($this->flags & $flag) !== 0){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_16_100){
				LE::writeFloat($out, $val);
			}else{
				VarInt::writeSignedInt($out, (int) $val);
			}
		}
	}

	private function maybeWriteRotation(int $flag, float $val, ByteBufferWriter $out) : void{
		if(($this->flags & $flag) !== 0){
			CommonTypes::putRotationByte($out, $val);
		}
	}

	private function maybeWriteCoordOptional(int $flag, float $val, ByteBufferWriter $out) : void{
		$has = ($this->flags & $flag) !== 0;
		CommonTypes::putBool($out, $has);
		if($has){
			LE::writeFloat($out, $val);
		}
	}

	private function maybeWriteRotationOptional(int $flag, float $val, ByteBufferWriter $out) : void{
		$has = ($this->flags & $flag) !== 0;
		CommonTypes::putBool($out, $has);
		if($has){
			CommonTypes::putRotationByte($out, $val);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->maybeWriteCoordOptional(self::FLAG_HAS_X, $this->xPos, $out);
			$this->maybeWriteCoordOptional(self::FLAG_HAS_Y, $this->yPos, $out);
			$this->maybeWriteCoordOptional(self::FLAG_HAS_Z, $this->zPos, $out);
			$this->maybeWriteRotationOptional(self::FLAG_HAS_PITCH, $this->pitch, $out);
			$this->maybeWriteRotationOptional(self::FLAG_HAS_YAW, $this->yaw, $out);
			$this->maybeWriteRotationOptional(self::FLAG_HAS_HEAD_YAW, $this->headYaw, $out);
			CommonTypes::putBool($out, ($this->flags & self::FLAG_GROUND) !== 0);
			CommonTypes::putBool($out, ($this->flags & self::FLAG_TELEPORT) !== 0);
			CommonTypes::putBool($out, ($this->flags & self::FLAG_FORCE_MOVE_LOCAL_ENTITY) !== 0);
			CommonTypes::putBool($out, ($this->flags & self::FLAG_FORCE_COMPLETION) !== 0);
			return;
		}

		LE::writeUnsignedShort($out, $this->flags);
		$this->maybeWriteCoord(self::FLAG_HAS_X, $this->xPos, $out, $protocolId);
		$this->maybeWriteCoord(self::FLAG_HAS_Y, $this->yPos, $out, $protocolId);
		$this->maybeWriteCoord(self::FLAG_HAS_Z, $this->zPos, $out, $protocolId);
		$this->maybeWriteRotation(self::FLAG_HAS_PITCH, $this->pitch, $out);
		$this->maybeWriteRotation(self::FLAG_HAS_YAW, $this->yaw, $out);
		$this->maybeWriteRotation(self::FLAG_HAS_HEAD_YAW, $this->headYaw, $out);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleMoveActorDelta($this);
	}
}
