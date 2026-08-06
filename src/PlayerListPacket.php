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

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use function count;

class PlayerListPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_LIST_PACKET;

	public const TYPE_ADD = 0;
	public const TYPE_REMOVE = 1;

	public int $type;
	/** @var PlayerListEntry[] */
	public array $entries = [];

	/**
	 * @generate-create-func
	 * @param PlayerListEntry[] $entries
	 */
	private static function create(int $type, array $entries) : self{
		$result = new self;
		$result->type = $type;
		$result->entries = $entries;
		return $result;
	}

	/**
	 * @param PlayerListEntry[] $entries
	 */
	public static function add(array $entries) : self{
		foreach($entries as $entry){
			$entry->type = self::TYPE_ADD;
		}
		return self::create(self::TYPE_ADD, $entries);
	}

	/**
	 * @param PlayerListEntry[] $entries
	 */
	public static function remove(array $entries) : self{
		foreach($entries as $entry){
			$entry->type = self::TYPE_REMOVE;
		}
		return self::create(self::TYPE_REMOVE, $entries);
	}

	/**
	 * Maps PHP type constants to 1.26.40 wire values (swapped on wire).
	 * Wire: 0=REMOVE, 1=ADD
	 */
	private static function phpTypeToWire(int $phpType) : int{
		return $phpType === self::TYPE_ADD ? 1 : 0;
	}

	private static function wireTypeToPhp(int $wireType) : int{
		return $wireType === 1 ? self::TYPE_ADD : self::TYPE_REMOVE;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$count = VarInt::readUnsignedInt($in);
			$packetType = null;
			for($i = 0; $i < $count; ++$i){
				$entry = new PlayerListEntry();
				$entry->type = self::wireTypeToPhp(VarInt::readUnsignedInt($in));
				Byte::readUnsigned($in); //legacyId
				$packetType ??= $entry->type;

				if($entry->type === self::TYPE_ADD){
					$entry->uuid = CommonTypes::getUUID($in);
					$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
					$entry->username = CommonTypes::getString($in);
					$entry->xboxUserId = CommonTypes::getString($in);
					$entry->platformChatId = CommonTypes::getString($in);
					$entry->buildPlatform = LE::readSignedInt($in);
					$entry->skinData = CommonTypes::getSkin($in, $protocolId);
					$entry->isTeacher = CommonTypes::getBool($in);
					$entry->isHost = CommonTypes::getBool($in);
					$entry->isSubClient = CommonTypes::getBool($in);
					$entry->color = Color::fromARGB(LE::readUnsignedInt($in));
				}elseif($entry->type === self::TYPE_REMOVE){
					$entry->uuid = CommonTypes::getUUID($in);
				}else{
					throw new PacketDecodeException("Unknown player list entry type " . $entry->type);
				}

				$this->entries[$i] = $entry;
			}
			$this->type = $packetType ?? self::TYPE_ADD;
			return;
		}

		$this->type = Byte::readUnsigned($in);
		$count = VarInt::readUnsignedInt($in);
		for($i = 0; $i < $count; ++$i){
			$entry = new PlayerListEntry();
			$entry->type = $this->type;

			if($this->type === self::TYPE_ADD){
				$entry->uuid = CommonTypes::getUUID($in);
				$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
				$entry->username = CommonTypes::getString($in);
				if($protocolId >= ProtocolInfo::PROTOCOL_1_13_0){
					$entry->xboxUserId = CommonTypes::getString($in);
					$entry->platformChatId = CommonTypes::getString($in);
					$entry->buildPlatform = LE::readSignedInt($in);
					$entry->skinData = CommonTypes::getSkin($in, $protocolId);
					$entry->isTeacher = CommonTypes::getBool($in);
					$entry->isHost = CommonTypes::getBool($in);
					if($protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
						$entry->isSubClient = CommonTypes::getBool($in);
						if($protocolId >= ProtocolInfo::PROTOCOL_1_21_80){
							$entry->color = Color::fromARGB(LE::readUnsignedInt($in));
						}
					}
				}else{
					$skinId = CommonTypes::getString($in);
					$entry->skinData = CommonTypes::getSkin($in, $protocolId);
					$entry->skinData->setSkinId($skinId);
					$entry->xboxUserId = CommonTypes::getString($in);
					$entry->platformChatId = CommonTypes::getString($in);
				}
			}else{
				$entry->uuid = CommonTypes::getUUID($in);
			}

			$this->entries[$i] = $entry;
		}
		if($this->type === self::TYPE_ADD && $protocolId >= ProtocolInfo::PROTOCOL_1_14_60){
			for($i = 0; $i < $count; ++$i){
				$this->entries[$i]->skinData->setVerified(CommonTypes::getBool($in));
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			VarInt::writeUnsignedInt($out, count($this->entries));
			foreach($this->entries as $entry){
				$entryType = $entry->type ?? $this->type;
				VarInt::writeUnsignedInt($out, self::phpTypeToWire($entryType));
				Byte::writeUnsigned($out, $entryType === self::TYPE_ADD ? 0 : 1);

				if($entryType === self::TYPE_ADD){
					CommonTypes::putUUID($out, $entry->uuid);
					CommonTypes::putActorUniqueId($out, $entry->actorUniqueId);
					CommonTypes::putString($out, $entry->username);
					CommonTypes::putString($out, $entry->xboxUserId);
					CommonTypes::putString($out, $entry->platformChatId);
					LE::writeSignedInt($out, $entry->buildPlatform);
					CommonTypes::putSkin($out, $entry->skinData, $protocolId);
					CommonTypes::putBool($out, $entry->isTeacher);
					CommonTypes::putBool($out, $entry->isHost);
					CommonTypes::putBool($out, $entry->isSubClient);
					LE::writeUnsignedInt($out, ($entry->color ?? new Color(255, 255, 255))->toARGB());
				}else{
					CommonTypes::putUUID($out, $entry->uuid);
				}
			}
			return;
		}

		Byte::writeUnsigned($out, $this->type);
		VarInt::writeUnsignedInt($out, count($this->entries));
		foreach($this->entries as $entry){
			if($this->type === self::TYPE_ADD){
				CommonTypes::putUUID($out, $entry->uuid);
				CommonTypes::putActorUniqueId($out, $entry->actorUniqueId);
				CommonTypes::putString($out, $entry->username);
				if($protocolId >= ProtocolInfo::PROTOCOL_1_13_0){
					CommonTypes::putString($out, $entry->xboxUserId);
					CommonTypes::putString($out, $entry->platformChatId);
					LE::writeSignedInt($out, $entry->buildPlatform);
					CommonTypes::putSkin($out, $entry->skinData, $protocolId);
					CommonTypes::putBool($out, $entry->isTeacher);
					CommonTypes::putBool($out, $entry->isHost);
					if($protocolId >= ProtocolInfo::PROTOCOL_1_20_60){
						CommonTypes::putBool($out, $entry->isSubClient);
						if($protocolId >= ProtocolInfo::PROTOCOL_1_21_80){
							LE::writeUnsignedInt($out, ($entry->color ?? new Color(255, 255, 255))->toARGB());
						}
					}
				}else{
					CommonTypes::putString($out, $entry->skinData->getSkinId());
					CommonTypes::putSkin($out, $entry->skinData, $protocolId);
					CommonTypes::putString($out, $entry->xboxUserId);
					CommonTypes::putString($out, $entry->platformChatId);
				}
			}else{
				CommonTypes::putUUID($out, $entry->uuid);
			}
		}
		if($this->type === self::TYPE_ADD && $protocolId >= ProtocolInfo::PROTOCOL_1_14_60){
			foreach($this->entries as $entry){
				CommonTypes::putBool($out, $entry->skinData->isVerified());
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerList($this);
	}
}
