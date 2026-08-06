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
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\MapDecoration;
use pocketmine\network\mcpe\protocol\types\MapImage;
use pocketmine\network\mcpe\protocol\types\MapTrackedObject;
use pocketmine\utils\Binary;
use function count;

class ClientboundMapItemDataPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET;

	public const BITFLAG_TEXTURE_UPDATE = 0x02;
	public const BITFLAG_DECORATION_UPDATE = 0x04;
	public const BITFLAG_MAP_CREATION = 0x08;

	public int $mapId;
	public int $type;
	public int $dimensionId = DimensionIds::OVERWORLD;
	public bool $isLocked = false;
	public BlockPosition $origin;

	/** @var int[] */
	public array $parentMapIds = [];
	public int $scale;

	/** @var MapTrackedObject[] */
	public array $trackedEntities = [];
	/** @var MapDecoration[] */
	public array $decorations = [];

	public int $xOffset = 0;
	public int $yOffset = 0;
	public ?MapImage $colors = null;

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->mapId = CommonTypes::getActorUniqueId($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->dimensionId = Byte::readUnsigned($in);
			$this->isLocked = CommonTypes::getBool($in);
			$this->origin = CommonTypes::getBlockPosition($in);

			$this->parentMapIds = CommonTypes::readOptional($in, function(ByteBufferReader $in) : array{
				$count = VarInt::readUnsignedInt($in);
				$parentMapIds = [];
				for($i = 0; $i < $count; ++$i){
					$parentMapIds[] = CommonTypes::getActorUniqueId($in);
				}
				return $parentMapIds;
			}) ?? [];
			$this->scale = CommonTypes::readOptional($in, Byte::readUnsigned(...)) ?? 0;
			$this->trackedEntities = CommonTypes::readOptional($in, function(ByteBufferReader $in) : array{
				$count = VarInt::readUnsignedInt($in);
				$entities = [];
				for($i = 0; $i < $count; ++$i){
					$entities[] = MapTrackedObject::read($in);
				}
				return $entities;
			}) ?? [];
			$this->decorations = CommonTypes::readOptional($in, function(ByteBufferReader $in) : array{
				$count = VarInt::readUnsignedInt($in);
				$decorations = [];
				for($i = 0; $i < $count; ++$i){
					$icon = Byte::readUnsigned($in);
					$rotation = Byte::readUnsigned($in);
					$xOffset = Byte::readUnsigned($in);
					$yOffset = Byte::readUnsigned($in);
					$label = CommonTypes::getString($in);
					$color = Color::fromRGBA(Binary::flipIntEndianness(LE::readUnsignedInt($in)));
					$decorations[] = new MapDecoration($icon, $rotation, $xOffset, $yOffset, $label, $color);
				}
				return $decorations;
			}) ?? [];
			$width = CommonTypes::readOptional($in, VarInt::readSignedInt(...));
			$height = CommonTypes::readOptional($in, VarInt::readSignedInt(...));
			$this->xOffset = CommonTypes::readOptional($in, VarInt::readSignedInt(...)) ?? 0;
			$this->yOffset = CommonTypes::readOptional($in, VarInt::readSignedInt(...)) ?? 0;
			$this->colors = CommonTypes::readOptional($in, function(ByteBufferReader $in) use ($width, $height, $protocolId) : MapImage{
				$count = VarInt::readUnsignedInt($in);
				if($width === null || $height === null || $count !== $width * $height){
					throw new PacketDecodeException("Expected colour count of " . (($height ?? 0) * ($width ?? 0)) . ", got $count");
				}
				return MapImage::decode($in, $height, $width, $protocolId);
			});
			$type = 0;
			if(count($this->parentMapIds) > 0){
				$type |= self::BITFLAG_MAP_CREATION;
			}
			if(count($this->decorations) > 0){
				$type |= self::BITFLAG_DECORATION_UPDATE;
			}
			if($this->colors !== null){
				$type |= self::BITFLAG_TEXTURE_UPDATE;
			}
			$this->type = $type;
			return;
		}

		$this->type = VarInt::readUnsignedInt($in);
		$this->dimensionId = Byte::readUnsigned($in);
		$this->isLocked = CommonTypes::getBool($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_19_20){
			$this->origin = CommonTypes::getBlockPosition($in);
		}

		if(($this->type & self::BITFLAG_MAP_CREATION) !== 0){
			$count = VarInt::readUnsignedInt($in);
			for($i = 0; $i < $count; ++$i){
				$this->parentMapIds[] = CommonTypes::getActorUniqueId($in);
			}
		}

		if(($this->type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_DECORATION_UPDATE | self::BITFLAG_TEXTURE_UPDATE)) !== 0){ //Decoration bitflag or colour bitflag
			$this->scale = Byte::readUnsigned($in);
		}

		if(($this->type & self::BITFLAG_DECORATION_UPDATE) !== 0){
			for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
				$object = new MapTrackedObject();
				$object->type = LE::readUnsignedInt($in);
				if($object->type === MapTrackedObject::TYPE_BLOCK){
					$object->blockPosition = CommonTypes::getBlockPosition($in, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);
				}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
					$object->actorUniqueId = CommonTypes::getActorUniqueId($in);
				}else{
					throw new PacketDecodeException("Unknown map object type $object->type");
				}
				$this->trackedEntities[] = $object;
			}

			for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
				$icon = Byte::readUnsigned($in);
				$rotation = Byte::readUnsigned($in);
				$xOffset = Byte::readUnsigned($in);
				$yOffset = Byte::readUnsigned($in);
				$label = CommonTypes::getString($in);
				$color = Color::fromRGBA(Binary::flipIntEndianness(VarInt::readUnsignedInt($in)));
				$this->decorations[] = new MapDecoration($icon, $rotation, $xOffset, $yOffset, $label, $color);
			}
		}

		if(($this->type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
			$width = VarInt::readSignedInt($in);
			$height = VarInt::readSignedInt($in);
			$this->xOffset = VarInt::readSignedInt($in);
			$this->yOffset = VarInt::readSignedInt($in);

			$count = VarInt::readUnsignedInt($in);
			if($count !== $width * $height){
				throw new PacketDecodeException("Expected colour count of " . ($height * $width) . " (height $height * width $width), got $count");
			}

			$this->colors = MapImage::decode($in, $height, $width, $protocolId);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putActorUniqueId($out, $this->mapId);

		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			Byte::writeUnsigned($out, $this->dimensionId);
			CommonTypes::putBool($out, $this->isLocked);
			CommonTypes::putBlockPosition($out, $this->origin);

			CommonTypes::writeOptional($out, count($this->parentMapIds) > 0 ? $this->parentMapIds : null, function(ByteBufferWriter $out, array $parentMapIds) : void{
				VarInt::writeUnsignedInt($out, count($parentMapIds));
				foreach($parentMapIds as $parentMapId){
					CommonTypes::putActorUniqueId($out, $parentMapId);
				}
			});
			$hasScale = count($this->parentMapIds) > 0 || count($this->decorations) > 0 || $this->colors !== null;
			CommonTypes::writeOptional($out, $hasScale ? $this->scale : null, Byte::writeUnsigned(...));
			CommonTypes::writeOptional($out, count($this->trackedEntities) > 0 ? $this->trackedEntities : null, function(ByteBufferWriter $out, array $entities) : void{
				VarInt::writeUnsignedInt($out, count($entities));
				/** @var MapTrackedObject[] $entities */
				foreach($entities as $entity){
					$entity->write($out);
				}
			});
			CommonTypes::writeOptional($out, count($this->decorations) > 0 ? $this->decorations : null, function(ByteBufferWriter $out, array $decorations) : void{
				VarInt::writeUnsignedInt($out, count($decorations));
				/** @var MapDecoration[] $decorations */
				foreach($decorations as $decoration){
					Byte::writeUnsigned($out, $decoration->getIcon());
					Byte::writeUnsigned($out, $decoration->getRotation());
					Byte::writeUnsigned($out, $decoration->getXOffset());
					Byte::writeUnsigned($out, $decoration->getYOffset());
					CommonTypes::putString($out, $decoration->getLabel());
					LE::writeUnsignedInt($out, Binary::flipIntEndianness($decoration->getColor()->toRGBA()));
				}
			});
			CommonTypes::writeOptional($out, $this->colors?->getWidth(), VarInt::writeSignedInt(...));
			CommonTypes::writeOptional($out, $this->colors?->getHeight(), VarInt::writeSignedInt(...));
			CommonTypes::writeOptional($out, $this->colors !== null ? $this->xOffset : null, VarInt::writeSignedInt(...));
			CommonTypes::writeOptional($out, $this->colors !== null ? $this->yOffset : null, VarInt::writeSignedInt(...));
			CommonTypes::writeOptional($out, $this->colors, function(ByteBufferWriter $out, MapImage $colors) use ($protocolId) : void{
				VarInt::writeUnsignedInt($out, $colors->getWidth() * $colors->getHeight());
				$colors->encode($out, $protocolId);
			});
			return;
		}

		$type = 0;
		if(($parentMapIdsCount = count($this->parentMapIds)) > 0){
			$type |= self::BITFLAG_MAP_CREATION;
		}
		if(($decorationCount = count($this->decorations)) > 0){
			$type |= self::BITFLAG_DECORATION_UPDATE;
		}
		if($this->colors !== null){
			$type |= self::BITFLAG_TEXTURE_UPDATE;
		}

		VarInt::writeUnsignedInt($out, $type);
		Byte::writeUnsigned($out, $this->dimensionId);
		CommonTypes::putBool($out, $this->isLocked);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_19_20){
			CommonTypes::putBlockPosition($out, $this->origin);
		}

		if(($type & self::BITFLAG_MAP_CREATION) !== 0){
			VarInt::writeUnsignedInt($out, $parentMapIdsCount);
			foreach($this->parentMapIds as $parentMapId){
				CommonTypes::putActorUniqueId($out, $parentMapId);
			}
		}

		if(($type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_TEXTURE_UPDATE | self::BITFLAG_DECORATION_UPDATE)) !== 0){
			Byte::writeUnsigned($out, $this->scale);
		}

		if(($type & self::BITFLAG_DECORATION_UPDATE) !== 0){
			VarInt::writeUnsignedInt($out, count($this->trackedEntities));
			foreach($this->trackedEntities as $object){
				LE::writeUnsignedInt($out, $object->type);
				if($object->type === MapTrackedObject::TYPE_BLOCK){
					CommonTypes::putBlockPosition($out, $object->blockPosition ?? throw new \InvalidArgumentException("blockPosition must be set for TYPE_BLOCK"), $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);
				}elseif($object->type === MapTrackedObject::TYPE_ENTITY){
					CommonTypes::putActorUniqueId($out, $object->actorUniqueId ?? throw new \InvalidArgumentException("actorUniqueId must be set for TYPE_ENTITY"));
				}else{
					throw new \InvalidArgumentException("Unknown map object type $object->type");
				}
			}

			VarInt::writeUnsignedInt($out, $decorationCount);
			foreach($this->decorations as $decoration){
				Byte::writeUnsigned($out, $decoration->getIcon());
				Byte::writeUnsigned($out, $decoration->getRotation());
				Byte::writeUnsigned($out, $decoration->getXOffset());
				Byte::writeUnsigned($out, $decoration->getYOffset());
				CommonTypes::putString($out, $decoration->getLabel());
				VarInt::writeUnsignedInt($out, Binary::flipIntEndianness($decoration->getColor()->toRGBA()));
			}
		}

		if($this->colors !== null){
			VarInt::writeSignedInt($out, $this->colors->getWidth());
			VarInt::writeSignedInt($out, $this->colors->getHeight());
			VarInt::writeSignedInt($out, $this->xOffset);
			VarInt::writeSignedInt($out, $this->yOffset);

			VarInt::writeUnsignedInt($out, $this->colors->getWidth() * $this->colors->getHeight()); //list count, but we handle it as a 2D array... thanks for the confusion mojang

			$this->colors->encode($out, $protocolId);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundMapItemData($this);
	}
}
