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
use pmmp\encoding\VarInt;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\types\BlockPaletteEntry;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\network\mcpe\protocol\types\LegacyBlockPaletteEntry;
use pocketmine\network\mcpe\protocol\types\LevelSettings;
use pocketmine\network\mcpe\protocol\types\NetworkPermissions;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;
use pocketmine\network\mcpe\protocol\types\ServerJoinInformation;
use pocketmine\network\mcpe\protocol\types\ServerAuthMovementMode;
use pocketmine\network\mcpe\protocol\types\ServerTelemetryData;
use Ramsey\Uuid\UuidInterface;
use function count;

class StartGamePacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::START_GAME_PACKET;

	public int $actorUniqueId;
	public int $actorRuntimeId;
	public int $playerGamemode;

	public Vector3 $playerPosition;

	public float $pitch;
	public float $yaw;

	/** @phpstan-var CacheableNbt<CompoundTag>  */
	public CacheableNbt $playerActorProperties; //same as SyncActorPropertyPacket content

	public LevelSettings $levelSettings;

	public string $levelId = ""; //base64 string, usually the same as world folder name in vanilla
	public string $worldName;
	public string $premiumWorldTemplateId = "";
	public bool $isTrial = false;
	public PlayerMovementSettings $playerMovementSettings;
	public int $currentTick = 0; //only used if isTrial is true
	public int $enchantmentSeed = 0;
	public string $multiplayerCorrelationId = ""; //TODO: this should be filled with a UUID of some sort
	public bool $enableNewInventorySystem = false; //TODO
	public string $serverSoftwareVersion;
	public UuidInterface $worldTemplateId; //why is this here twice ??? mojang
	public bool $enableClientSideChunkGeneration;
	public bool $blockNetworkIdsAreHashes = false; //new in 1.19.80, possibly useful for multi version
	public bool $enableTickDeathSystems = false;
	public NetworkPermissions $networkPermissions;
	public bool $isLoggingChat = false;
	public ?ServerJoinInformation $serverJoinInformation;
	public ServerTelemetryData $serverTelemetryData;

	/**
	 * @var BlockPaletteEntry[]
	 * @phpstan-var list<BlockPaletteEntry>
	 */
	public array $blockPalette = [];
	/**
	 * @var LegacyBlockPaletteEntry[]
	 * @phpstan-var list<LegacyBlockPaletteEntry>
	 */
	public array $legacyBlockPalette = [];

	/**
	 * Checksum of the full block palette. This is a hash of some weird stringified version of the NBT.
	 * This is used along with the baseGameVersion to check for inconsistencies in the block palette.
	 * Fill with 0 if you don't want to bother having the client verify the palette (seems pointless anyway).
	 */
	public int $blockPaletteChecksum;

	/**
	 * @var ItemTypeEntry[]
	 * @phpstan-var list<ItemTypeEntry>
	 */
	public array $itemTable;

	/**
	 * @generate-create-func
	 * @param BlockPaletteEntry[]       $blockPalette
	 * @param LegacyBlockPaletteEntry[] $legacyBlockPalette
	 * @param ItemTypeEntry[]           $itemTable
	 * @phpstan-param CacheableNbt<CompoundTag>     $playerActorProperties
	 * @phpstan-param list<BlockPaletteEntry>       $blockPalette
	 * @phpstan-param list<LegacyBlockPaletteEntry> $legacyBlockPalette
	 * @phpstan-param list<ItemTypeEntry>           $itemTable
	 */
	public static function create(
		int $actorUniqueId,
		int $actorRuntimeId,
		int $playerGamemode,
		Vector3 $playerPosition,
		float $pitch,
		float $yaw,
		CacheableNbt $playerActorProperties,
		LevelSettings $levelSettings,
		string $levelId,
		string $worldName,
		string $premiumWorldTemplateId,
		bool $isTrial,
		PlayerMovementSettings $playerMovementSettings,
		int $currentTick,
		int $enchantmentSeed,
		string $multiplayerCorrelationId,
		bool $enableNewInventorySystem,
		string $serverSoftwareVersion,
		UuidInterface $worldTemplateId,
		bool $enableClientSideChunkGeneration,
		bool $blockNetworkIdsAreHashes,
		bool $enableTickDeathSystems,
		NetworkPermissions $networkPermissions,
		bool $isLoggingChat,
		?ServerJoinInformation $serverJoinInformation,
		ServerTelemetryData $serverTelemetryData,
		array $blockPalette,
		array $legacyBlockPalette,
		int $blockPaletteChecksum,
		array $itemTable,
	) : self{
		$result = new self;
		$result->actorUniqueId = $actorUniqueId;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->playerGamemode = $playerGamemode;
		$result->playerPosition = $playerPosition;
		$result->pitch = $pitch;
		$result->yaw = $yaw;
		$result->playerActorProperties = $playerActorProperties;
		$result->levelSettings = $levelSettings;
		$result->levelId = $levelId;
		$result->worldName = $worldName;
		$result->premiumWorldTemplateId = $premiumWorldTemplateId;
		$result->isTrial = $isTrial;
		$result->playerMovementSettings = $playerMovementSettings;
		$result->currentTick = $currentTick;
		$result->enchantmentSeed = $enchantmentSeed;
		$result->multiplayerCorrelationId = $multiplayerCorrelationId;
		$result->enableNewInventorySystem = $enableNewInventorySystem;
		$result->serverSoftwareVersion = $serverSoftwareVersion;
		$result->worldTemplateId = $worldTemplateId;
		$result->enableClientSideChunkGeneration = $enableClientSideChunkGeneration;
		$result->blockNetworkIdsAreHashes = $blockNetworkIdsAreHashes;
		$result->enableTickDeathSystems = $enableTickDeathSystems;
		$result->networkPermissions = $networkPermissions;
		$result->isLoggingChat = $isLoggingChat;
		$result->serverJoinInformation = $serverJoinInformation;
		$result->serverTelemetryData = $serverTelemetryData;
		$result->blockPalette = $blockPalette;
		$result->legacyBlockPalette = $legacyBlockPalette;
		$result->blockPaletteChecksum = $blockPaletteChecksum;
		$result->itemTable = $itemTable;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->playerGamemode = VarInt::readSignedInt($in);

		$this->playerPosition = CommonTypes::getVector3($in);

		$this->pitch = LE::readFloat($in);
		$this->yaw = LE::readFloat($in);

		$this->serverTelemetryData = new ServerTelemetryData("", "", "", "");
		$this->levelSettings = LevelSettings::read($in, $this->serverTelemetryData, $protocolId);

		$this->levelId = CommonTypes::getString($in);
		$this->worldName = CommonTypes::getString($in);
		$this->premiumWorldTemplateId = CommonTypes::getString($in);
		$this->isTrial = CommonTypes::getBool($in);
		$this->playerMovementSettings = $protocolId >= ProtocolInfo::PROTOCOL_1_13_0 ?
			PlayerMovementSettings::read($in, $protocolId) :
			new PlayerMovementSettings(ServerAuthMovementMode::CLIENT_AUTHORITATIVE, 0, false);
		$this->currentTick = LE::readUnsignedLong($in);

		$this->enchantmentSeed = VarInt::readSignedInt($in);

		$this->blockPalette = [];
		$this->legacyBlockPalette = [];
		$this->decodeBlockPalette($in, $protocolId);

		$this->itemTable = [];
		if($protocolId <= ProtocolInfo::PROTOCOL_1_21_50){
			for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
				$stringId = CommonTypes::getString($in);
				$numericId = LE::readSignedShort($in);
				$isComponentBased = $protocolId >= ProtocolInfo::PROTOCOL_1_16_100 && CommonTypes::getBool($in);

				$this->itemTable[] = new ItemTypeEntry($stringId, $numericId, $isComponentBased, -1, new CacheableNbt(new CompoundTag()));
			}
		}

		$this->multiplayerCorrelationId = CommonTypes::getString($in);
		$this->enableNewInventorySystem = $protocolId >= ProtocolInfo::PROTOCOL_1_16_0 && CommonTypes::getBool($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_17_0){
			$this->serverSoftwareVersion = CommonTypes::getString($in);
		}else{
			$this->serverSoftwareVersion = "";
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_19_0){
			$this->playerActorProperties = new CacheableNbt(CommonTypes::getNbtCompoundRoot($in));
			$this->blockPaletteChecksum = LE::readUnsignedLong($in);
			$this->worldTemplateId = CommonTypes::getUUID($in);
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_18_0){
			$this->blockPaletteChecksum = LE::readUnsignedLong($in);
		}else{
			$this->blockPaletteChecksum = 0;
		}
		$this->enableClientSideChunkGeneration = $protocolId >= ProtocolInfo::PROTOCOL_1_19_20 ?
			CommonTypes::getBool($in) :
			false;
		$this->blockNetworkIdsAreHashes = $protocolId >= ProtocolInfo::PROTOCOL_1_19_80 ?
			CommonTypes::getBool($in) :
			false;
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_100 && $protocolId <= ProtocolInfo::PROTOCOL_1_21_124){
			$this->enableTickDeathSystems = CommonTypes::getBool($in);
		}
		$this->networkPermissions = $protocolId >= ProtocolInfo::PROTOCOL_1_20_0 ?
			NetworkPermissions::decode($in) :
			new NetworkPermissions(false);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_0){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
				$this->isLoggingChat = CommonTypes::getBool($in);
			}
			$this->serverJoinInformation = CommonTypes::readOptional($in, fn(ByteBufferReader $in) => ServerJoinInformation::read($in, $protocolId));
			$this->serverTelemetryData = ServerTelemetryData::read($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		VarInt::writeSignedInt($out, $this->playerGamemode);

		CommonTypes::putVector3($out, $this->playerPosition);

		LE::writeFloat($out, $this->pitch);
		LE::writeFloat($out, $this->yaw);

		$this->levelSettings->write($out, $this->serverTelemetryData, $protocolId);

		CommonTypes::putString($out, $this->levelId);
		CommonTypes::putString($out, $this->worldName);
		CommonTypes::putString($out, $this->premiumWorldTemplateId);
		CommonTypes::putBool($out, $this->isTrial);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_13_0){
			$this->playerMovementSettings->write($out, $protocolId);
		}
		LE::writeUnsignedLong($out, $this->currentTick);

		VarInt::writeSignedInt($out, $this->enchantmentSeed);

		$this->encodeBlockPalette($out, $protocolId);

		if($protocolId <= ProtocolInfo::PROTOCOL_1_21_50){
			VarInt::writeUnsignedInt($out, count($this->itemTable));
			foreach($this->itemTable as $entry){
				CommonTypes::putString($out, $entry->getStringId());
				LE::writeSignedShort($out, $entry->getNumericId());
				if($protocolId >= ProtocolInfo::PROTOCOL_1_16_100){
					CommonTypes::putBool($out, $entry->isComponentBased());
				}
			}
		}

		CommonTypes::putString($out, $this->multiplayerCorrelationId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_16_0){
			CommonTypes::putBool($out, $this->enableNewInventorySystem);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_17_0){
			CommonTypes::putString($out, $this->serverSoftwareVersion);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_19_0){
			$out->writeByteArray($this->playerActorProperties->getEncodedNbt());
			LE::writeUnsignedLong($out, $this->blockPaletteChecksum);
			CommonTypes::putUUID($out, $this->worldTemplateId);
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_18_0){
			LE::writeUnsignedLong($out, $this->blockPaletteChecksum);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_19_20){
			CommonTypes::putBool($out, $this->enableClientSideChunkGeneration);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_19_80){
			CommonTypes::putBool($out, $this->blockNetworkIdsAreHashes);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_100 && $protocolId <= ProtocolInfo::PROTOCOL_1_21_124){
			CommonTypes::putBool($out, $this->enableTickDeathSystems);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_0){
			$this->networkPermissions->encode($out);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_0){
				if($protocolId >= ProtocolInfo::PROTOCOL_1_26_30){
					CommonTypes::putBool($out, $this->isLoggingChat);
				}
				CommonTypes::writeOptional($out, $this->serverJoinInformation, fn(ByteBufferWriter $out, ServerJoinInformation $info) => $info->write($out, $protocolId));
				$this->serverTelemetryData->write($out);
			}
		}
	}

	private function decodeBlockPalette(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_13_0){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_16_100){
				for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
					$blockName = CommonTypes::getString($in);
					$state = CommonTypes::getNbtCompoundRoot($in);
					$this->blockPalette[] = new BlockPaletteEntry($blockName, new CacheableNbt($state));
				}
			}else{
				$blockTable = CommonTypes::getNbtRoot($in)->getTag();
				if(!($blockTable instanceof ListTag)){
					throw new PacketDecodeException("Expected TAG_List NBT root");
				}

				foreach($blockTable->getValue() as $state){
					if(!($state instanceof CompoundTag)){
						throw new PacketDecodeException("Expected TAG_Compound NBT state");
					}

					$blockName = $state->getCompoundTag("block");
					if($blockName === null){
						throw new PacketDecodeException("Expected TAG_Compound NBT block");
					}

					$this->blockPalette[] = new BlockPaletteEntry($blockName->getString("name"), new CacheableNbt($state));
				}
			}
		}else{
			for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
				$name = CommonTypes::getString($in);
				$metadata = LE::readSignedShort($in);
				$id = LE::readSignedShort($in);
				$this->legacyBlockPalette[] = new LegacyBlockPaletteEntry($name, $id, $metadata);
			}
		}
	}

	private function encodeBlockPalette(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_16_100){
			VarInt::writeUnsignedInt($out, count($this->blockPalette));
			foreach($this->blockPalette as $entry){
				CommonTypes::putString($out, $entry->getName());
				$out->writeByteArray($entry->getStates()->getEncodedNbt());
			}
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_13_0){
			$root = new ListTag();
			foreach($this->blockPalette as $entry){
				$root->push($entry->getStates()->getRoot());
			}
			$out->writeByteArray((new NetworkNbtSerializer())->write(new TreeRoot($root)));
		}else{
			VarInt::writeUnsignedInt($out, count($this->legacyBlockPalette));
			foreach($this->legacyBlockPalette as $entry){
				CommonTypes::putString($out, $entry->getName());
				LE::writeSignedShort($out, $entry->getMetadata());
				LE::writeSignedShort($out, $entry->getId());
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleStartGame($this);
	}
}
