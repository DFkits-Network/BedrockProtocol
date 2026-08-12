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

namespace pocketmine\network\mcpe\protocol\serializer;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\command\CommandOriginData;
use pocketmine\network\mcpe\protocol\types\entity\BlockPosMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\CompoundTagMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ShortMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\Vec3MetadataProperty;
use pocketmine\network\mcpe\protocol\types\FloatGameRule;
use pocketmine\network\mcpe\protocol\types\GameRule;
use pocketmine\network\mcpe\protocol\types\IntGameRule;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraDataShield;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\recipe\ComplexAliasItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\IntIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\ItemDescriptorType;
use pocketmine\network\mcpe\protocol\types\recipe\MolangItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use pocketmine\network\mcpe\protocol\types\recipe\StringIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\TagItemDescriptor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use pocketmine\network\mcpe\protocol\types\StructureEditorData;
use pocketmine\network\mcpe\protocol\types\StructureSettings;
use pocketmine\utils\Binary;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function array_flip;
use function array_values;
use function count;
use function is_numeric;
use function str_starts_with;
use function strlen;
use function strrev;
use function strtolower;
use function substr;

final class CommonTypes{

	private const LEGACY_SHIELD_RUNTIME_ID = 513;
	private const MODERN_SHIELD_RUNTIME_ID = 355;

	private function __construct(){
		//NOOP
	}

	private static function isShieldRuntimeId(int $id, int $protocolId) : bool{
		return $id === ($protocolId >= ProtocolInfo::PROTOCOL_1_16_100 ?
			self::MODERN_SHIELD_RUNTIME_ID :
			self::LEGACY_SHIELD_RUNTIME_ID);
	}

	/** @throws DataDecodeException */
	public static function getString(ByteBufferReader $in) : string{
		return $in->readByteArray(VarInt::readUnsignedInt($in));
	}

	public static function putString(ByteBufferWriter $out, string $v) : void{
		VarInt::writeUnsignedInt($out, strlen($v));
		$out->writeByteArray($v);
	}

	/** @throws DataDecodeException */
	public static function getBool(ByteBufferReader $in) : bool{
		return Byte::readUnsigned($in) !== 0;
	}

	public static function putBool(ByteBufferWriter $out, bool $v) : void{
		Byte::writeUnsigned($out, $v ? 1 : 0);
	}

	/** @throws DataDecodeException */
	public static function getUUID(ByteBufferReader $in) : UuidInterface{
		//This is two little-endian longs: bytes 7-0 followed by bytes 15-8
		$p1 = strrev($in->readByteArray(8));
		$p2 = strrev($in->readByteArray(8));
		return Uuid::fromBytes($p1 . $p2);
	}

	public static function putUUID(ByteBufferWriter $out, UuidInterface $uuid) : void{
		$bytes = $uuid->getBytes();
		$out->writeByteArray(strrev(substr($bytes, 0, 8)));
		$out->writeByteArray(strrev(substr($bytes, 8, 8)));
	}

	/**
	 * @return array<int, string>
	 */
	private static function getPersonaPieceTypeNames() : array{
		return [
			0 => "persona_unknown",
			1 => PersonaSkinPiece::PIECE_TYPE_PERSONA_SKELETON,
			2 => PersonaSkinPiece::PIECE_TYPE_PERSONA_BODY,
			3 => PersonaSkinPiece::PIECE_TYPE_PERSONA_SKIN,
			4 => PersonaSkinPiece::PIECE_TYPE_PERSONA_BOTTOM,
			5 => PersonaSkinPiece::PIECE_TYPE_PERSONA_FEET,
			6 => "persona_dress",
			7 => PersonaSkinPiece::PIECE_TYPE_PERSONA_TOP,
			8 => "persona_high_pants",
			9 => "persona_hand",
			10 => "persona_outerwear",
			11 => PersonaSkinPiece::PIECE_TYPE_PERSONA_FACIAL_HAIR,
			12 => PersonaSkinPiece::PIECE_TYPE_PERSONA_MOUTH,
			13 => PersonaSkinPiece::PIECE_TYPE_PERSONA_EYES,
			14 => PersonaSkinPiece::PIECE_TYPE_PERSONA_HAIR,
			15 => "persona_hood",
			16 => "persona_back",
			17 => "persona_face_accessory",
			18 => "persona_head",
			19 => "persona_legs",
			20 => "persona_left_leg",
			21 => "persona_right_leg",
			22 => "persona_arms",
			23 => "persona_left_arm",
			24 => "persona_right_arm",
			25 => "persona_capes",
			26 => "persona_classic_skin",
			27 => "persona_emote",
			28 => "unsupported",
		];
	}

	private static function personaPieceTypeToString(int $pieceType) : string{
		return self::getPersonaPieceTypeNames()[$pieceType] ?? (string) $pieceType;
	}

	private static function personaPieceTypeToInt(string $pieceType) : int{
		$normalized = strtolower($pieceType);
		if($normalized === "hands" || $normalized === "persona_hands"){
			$normalized = "persona_hand";
		}
		if(!str_starts_with($normalized, "persona_") && $normalized !== "" && $normalized !== "unsupported"){
			$normalized = "persona_" . $normalized;
		}
		$flipped = array_flip(self::getPersonaPieceTypeNames());
		return $flipped[$normalized] ?? (is_numeric($pieceType) ? (int) $pieceType : 0);
	}

	private static function personaPieceTypeToBareString(string $pieceType) : string{
		$normalized = strtolower($pieceType);
		$bare = str_starts_with($normalized, "persona_") ? substr($normalized, strlen("persona_")) : $normalized;
		return $bare === "hand" ? "hands" : $bare;
	}

	private static function personaPieceTypeFromBareString(string $bare) : string{
		$normalized = strtolower($bare);
		if($normalized === ""){
			return "";
		}
		if($normalized === "hands"){
			return "persona_hand";
		}
		if($normalized === "unsupported"){
			return "unsupported";
		}
		if(str_starts_with($normalized, "persona_")){
			return $normalized;
		}
		return "persona_" . $normalized;
	}

	public static function getSkin(ByteBufferReader $in, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : SkinData{
		$hasModernSkinFormat = $protocolId >= ProtocolInfo::PROTOCOL_1_13_0;
		$skinId = $hasModernSkinFormat ? self::getString($in) : "";
		$skinPlayFabId = $protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ? self::getString($in) : "";
		$skinResourcePatch = $hasModernSkinFormat ? self::getString($in) : null;
		$skinData = self::getSkinImage($in, $protocolId);
		$animations = [];
		$capeData = new SkinImage(0, 0, "");
		if($hasModernSkinFormat){
			$animationCount = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40 ? VarInt::readUnsignedInt($in) : LE::readUnsignedInt($in);
			for($i = 0; $i < $animationCount; ++$i){
				$skinImage = self::getSkinImage($in, $protocolId);
				$animationType = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40 ? VarInt::readUnsignedInt($in) : LE::readUnsignedInt($in);
				$animationFrames = LE::readFloat($in);
				$expressionType = match(true){
					$protocolId >= ProtocolInfo::PROTOCOL_1_26_40 => VarInt::readUnsignedInt($in),
					$protocolId >= ProtocolInfo::PROTOCOL_1_16_100 => LE::readUnsignedInt($in),
					default => 0,
				};
				$animations[] = new SkinAnimation($skinImage, $animationType, $animationFrames, $expressionType);
			}
			$capeData = self::getSkinImage($in, $protocolId);
		}else{
			$capeRawData = self::getString($in);
			if($capeRawData !== ""){
				try{
				$capeData = SkinImage::fromLegacy($capeRawData);
				}catch(\InvalidArgumentException $e){
					throw new PacketDecodeException($e->getMessage(), 0, $e);
				}
			}
			$geometryName = self::getString($in);
		}
		$geometryData = self::getString($in);
		if(!$hasModernSkinFormat){
			return new SkinData(
				$skinId,
				"",
				null,
				$skinData,
				capeImage: $capeData,
				geometryData: $geometryData,
				premium: self::getBool($in),
				geometryName: $geometryName,
			);
		}
		$hasModernSkinBooleans = $protocolId >= ProtocolInfo::PROTOCOL_1_17_30;
		$geometryDataVersion = $hasModernSkinBooleans ? self::getString($in) : ProtocolInfo::MINECRAFT_VERSION_NETWORK;
		$animationData = self::getString($in);
		if(!$hasModernSkinBooleans){
			$premium = self::getBool($in);
			$persona = self::getBool($in);
			$capeOnClassic = self::getBool($in);
		}
		$capeId = self::getString($in);
		$fullSkinId = self::getString($in);
		$personaPieces = [];
		$pieceTintColors = [];
		if($protocolId >= ProtocolInfo::PROTOCOL_1_14_60){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				$armSize = SkinData::armSizeToString(Byte::readUnsigned($in));
				$skinColor = SkinData::colorToString(LE::readSignedInt($in));
				$personaPieceCount = VarInt::readUnsignedInt($in);
				for($i = 0; $i < $personaPieceCount; ++$i){
					$pieceId = self::getString($in);
					$pieceType = self::personaPieceTypeToString(LE::readSignedInt($in));
					$packId = self::getUUID($in)->toString();
					$isDefaultPiece = self::getBool($in);
					$productId = self::getString($in);
					$personaPieces[] = new PersonaSkinPiece($pieceId, $pieceType, $packId, $isDefaultPiece, $productId);
				}
				$pieceTintColorCount = VarInt::readUnsignedInt($in);
				for($i = 0; $i < $pieceTintColorCount; ++$i){
					// 1.26.40 tint keys are bare enum names ("hair"), not "persona_hair"
					$rawPieceType = self::getString($in);
					$pieceType = self::personaPieceTypeFromBareString($rawPieceType);
					$colors = [];
					for($j = 0; $j < 4; ++$j){
						$colors[] = SkinData::colorToString(LE::readSignedInt($in));
					}
					if($pieceType !== ""){
						$pieceTintColors[] = new PersonaPieceTintColor($pieceType, $colors);
					}
				}
			}else{
				$armSize = self::getString($in);
				$skinColor = self::getString($in);
				$personaPieceCount = LE::readUnsignedInt($in);
				for($i = 0; $i < $personaPieceCount; ++$i){
					$pieceId = self::getString($in);
					$pieceType = self::getString($in);
					$packId = self::getString($in);
					$isDefaultPiece = self::getBool($in);
					$productId = self::getString($in);
					$personaPieces[] = new PersonaSkinPiece($pieceId, $pieceType, $packId, $isDefaultPiece, $productId);
				}
				$pieceTintColorCount = LE::readUnsignedInt($in);
				for($i = 0; $i < $pieceTintColorCount; ++$i){
					$pieceType = self::getString($in);
					$colorCount = LE::readUnsignedInt($in);
					$colors = [];
					for($j = 0; $j < $colorCount; ++$j){
						$colors[] = self::getString($in);
					}
					$pieceTintColors[] = new PersonaPieceTintColor($pieceType, $colors);
				}
			}
		}

		if($hasModernSkinBooleans){
			$premium = self::getBool($in);
			$persona = self::getBool($in);
			$capeOnClassic = self::getBool($in);
			$isPrimaryUser = self::getBool($in);
		}else{
			$isPrimaryUser = true;
		}
		$override = $protocolId >= ProtocolInfo::PROTOCOL_1_19_63 ? self::getBool($in) : true;
		$trustedSkinFlag = SkinData::TRUSTED_SKIN_FLAG_TRUE;
		$profileHash = "";
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			// Treat every non-true value as false, matching client/Cloudburst behaviour
			$trustedSkinFlag = strtolower(self::getString($in)) === "true" ?
				SkinData::TRUSTED_SKIN_FLAG_TRUE :
				SkinData::TRUSTED_SKIN_FLAG_FALSE;
			$profileHash = self::getString($in);
		}

		return new SkinData(
			$skinId,
			$skinPlayFabId,
			$skinResourcePatch,
			$skinData,
			$animations,
			$capeData,
			$geometryData,
			$geometryDataVersion,
			$animationData,
			$capeId,
			$fullSkinId,
			$armSize ?? "",
			$skinColor ?? "",
			$personaPieces,
			$pieceTintColors,
			true,
			$premium,
			$persona,
			$capeOnClassic,
			$isPrimaryUser,
			$override,
			null,
			$trustedSkinFlag,
			$profileHash,
		);
	}

	public static function putSkin(ByteBufferWriter $out, SkinData $skin, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : void{
		$hasModernSkinFormat = $protocolId >= ProtocolInfo::PROTOCOL_1_13_0;
		if($hasModernSkinFormat){
			self::putString($out, $skin->getSkinId());
			if($protocolId >= ProtocolInfo::PROTOCOL_1_16_210){
				self::putString($out, $skin->getPlayFabId());
			}
			self::putString($out, $skin->getResourcePatch());
		}
		self::putSkinImage($out, $skin->getSkinImage(), $protocolId);
		if($hasModernSkinFormat){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				VarInt::writeUnsignedInt($out, count($skin->getAnimations()));
			}else{
				LE::writeUnsignedInt($out, count($skin->getAnimations()));
			}
			foreach($skin->getAnimations() as $animation){
				self::putSkinImage($out, $animation->getImage(), $protocolId);
				if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
					VarInt::writeUnsignedInt($out, $animation->getType());
				}else{
					LE::writeUnsignedInt($out, $animation->getType());
				}
				LE::writeFloat($out, $animation->getFrames());
				if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
					VarInt::writeUnsignedInt($out, $animation->getExpressionType());
				}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_16_100){
					LE::writeUnsignedInt($out, $animation->getExpressionType());
				}
			}
		}
		self::putSkinImage($out, $skin->getCapeImage(), $protocolId);
		if(!$hasModernSkinFormat){
			self::putString($out, $skin->getGeometryName());
		}
		self::putString($out, $skin->getGeometryData());
		if(!$hasModernSkinFormat){
			self::putBool($out, $skin->isPremium());
			return;
		}
		$hasModernSkinBooleans = $protocolId >= ProtocolInfo::PROTOCOL_1_17_30;
		if($hasModernSkinBooleans){
			self::putString($out, $skin->getGeometryDataEngineVersion());
		}
		self::putString($out, $skin->getAnimationData());
		if(!$hasModernSkinBooleans){
			self::putBool($out, $skin->isPremium());
			self::putBool($out, $skin->isPersona());
			self::putBool($out, $skin->isPersonaCapeOnClassic());
		}
		self::putString($out, $skin->getCapeId());
		self::putString($out, $skin->getFullSkinId());
		if($protocolId >= ProtocolInfo::PROTOCOL_1_14_60){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				Byte::writeUnsigned($out, SkinData::convertArmSize($skin->getArmSize()));
				LE::writeSignedInt($out, SkinData::convertColor($skin->getSkinColor()));
				VarInt::writeUnsignedInt($out, count($skin->getPersonaPieces()));
				foreach($skin->getPersonaPieces() as $piece){
					self::putString($out, $piece->getPieceId());
					LE::writeSignedInt($out, self::personaPieceTypeToInt($piece->getPieceType()));
					$packId = $piece->getPackId();
					self::putUUID($out, Uuid::isValid($packId) ? Uuid::fromString($packId) : Uuid::fromInteger("0"));
					self::putBool($out, $piece->isDefaultPiece());
					self::putString($out, $piece->getProductId());
				}
				VarInt::writeUnsignedInt($out, count($skin->getPieceTintColors()));
				foreach($skin->getPieceTintColors() as $tint){
					self::putString($out, self::personaPieceTypeToBareString($tint->getPieceType()));
					$colors = array_values($tint->getColors());
					for($j = 0; $j < 4; ++$j){
						LE::writeSignedInt($out, SkinData::convertColor($colors[$j] ?? ""));
					}
				}
			}else{
				self::putString($out, $skin->getArmSize());
				self::putString($out, $skin->getSkinColor());
				LE::writeUnsignedInt($out, count($skin->getPersonaPieces()));
				foreach($skin->getPersonaPieces() as $piece){
					self::putString($out, $piece->getPieceId());
					self::putString($out, $piece->getPieceType());
					self::putString($out, $piece->getPackId());
					self::putBool($out, $piece->isDefaultPiece());
					self::putString($out, $piece->getProductId());
				}
				LE::writeUnsignedInt($out, count($skin->getPieceTintColors()));
				foreach($skin->getPieceTintColors() as $tint){
					self::putString($out, $tint->getPieceType());
					LE::writeUnsignedInt($out, count($tint->getColors()));
					foreach($tint->getColors() as $color){
						self::putString($out, $color);
					}
				}
			}
		}
		if($hasModernSkinBooleans){
			self::putBool($out, $skin->isPremium());
			self::putBool($out, $skin->isPersona());
			self::putBool($out, $skin->isPersonaCapeOnClassic());
			self::putBool($out, $skin->isPrimaryUser());
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_19_63){
			self::putBool($out, $skin->isOverride());
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			// v2168 serializes this enum as lowercase boolean text
			self::putString($out, strtolower($skin->getTrustedSkinFlag()) === "true" ? "true" : "false");
			self::putString($out, $skin->getProfileHash());
		}
	}

	/** @throws DataDecodeException */
	private static function getSkinImage(ByteBufferReader $in, int $protocolId) : SkinImage{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_13_0){
			$width = LE::readUnsignedInt($in);
			$height = LE::readUnsignedInt($in);
		}
		$data = self::getString($in);
		try{
			return $protocolId >= ProtocolInfo::PROTOCOL_1_13_0 ?
				new SkinImage($height, $width, $data) :
				SkinImage::fromLegacy($data);
		}catch(\InvalidArgumentException $e){
			throw new PacketDecodeException($e->getMessage(), 0, $e);
		}
	}

	private static function putSkinImage(ByteBufferWriter $out, SkinImage $image, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_13_0){
			LE::writeUnsignedInt($out, $image->getWidth());
			LE::writeUnsignedInt($out, $image->getHeight());
		}
		self::putString($out, $image->getData());
	}

	/**
	 * @return int[]
	 * @phpstan-return array{0: int, 1: int, 2: int}
	 * @throws DataDecodeException
	 */
	private static function getItemStackHeader(ByteBufferReader $in, int $protocolId) : array{
		$id = VarInt::readSignedInt($in);
		if($id === 0 && $protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			return [0, 0, 0];
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_16_220){
			$count = LE::readUnsignedShort($in);
			$meta = VarInt::readUnsignedInt($in);
		}else{
			$auxValue = VarInt::readSignedInt($in);
			$count = $auxValue & 0xff;
			$meta = $auxValue >> 8;
		}

		return [$id, $count, $meta];
	}

	private static function putItemStackHeader(ByteBufferWriter $out, ItemStack $itemStack, int $protocolId) : bool{
		if($itemStack->getId() === 0){
			VarInt::writeSignedInt($out, 0);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				LE::writeUnsignedShort($out, 0);
				VarInt::writeUnsignedInt($out, 0);
			}
			return false;
		}

		VarInt::writeSignedInt($out, $itemStack->getId());
		if($protocolId >= ProtocolInfo::PROTOCOL_1_16_220){
			LE::writeUnsignedShort($out, $itemStack->getCount());
			VarInt::writeUnsignedInt($out, $itemStack->getMeta());
		}else{
			VarInt::writeSignedInt($out, (($itemStack->getMeta() & 0x7fff) << 8) | $itemStack->getCount());
		}

		return true;
	}

	/** @throws DataDecodeException */
	private static function getItemStackFooter(ByteBufferReader $in, int $id, int $meta, int $count, int $protocolId) : ItemStack{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_16_220){
			$blockRuntimeId = VarInt::readSignedInt($in);
			$rawExtraData = self::getString($in);
		}else{
			$blockRuntimeId = 0;
			$nbtLen = LE::readSignedShort($in);
			$nbt = null;
			if($nbtLen === -1){
				$nbtDataVersion = Byte::readUnsigned($in);
				if($nbtDataVersion !== 1){
					throw new PacketDecodeException("Unexpected NBT data version $nbtDataVersion");
				}
				$nbt = self::getNbtCompoundRoot($in);
			}elseif($nbtLen !== 0){
				throw new PacketDecodeException("Unexpected fake NBT length $nbtLen");
			}

			$canPlaceOn = [];
			for($i = 0, $countEntries = VarInt::readSignedInt($in); $i < $countEntries; ++$i){
				$canPlaceOn[] = self::getString($in);
			}
			$canDestroy = [];
			for($i = 0, $countEntries = VarInt::readSignedInt($in); $i < $countEntries; ++$i){
				$canDestroy[] = self::getString($in);
			}

			$extraData = self::isShieldRuntimeId($id, $protocolId) ?
				new ItemStackExtraDataShield($nbt, $canPlaceOn, $canDestroy, VarInt::readSignedLong($in)) :
				new ItemStackExtraData($nbt, $canPlaceOn, $canDestroy);
			$rawExtraDataWriter = new ByteBufferWriter();
			$extraData->write($rawExtraDataWriter);
			$rawExtraData = $rawExtraDataWriter->getData();
		}

		return new ItemStack($id, $meta, $count, $blockRuntimeId, $rawExtraData);
	}

	private static function putItemStackFooter(ByteBufferWriter $out, ItemStack $itemStack, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_16_220){
			VarInt::writeSignedInt($out, $itemStack->getBlockRuntimeId());
			self::putString($out, $itemStack->getRawExtraData());
		}else{
			$extraDataReader = new ByteBufferReader($itemStack->getRawExtraData());
			$extraData = self::isShieldRuntimeId($itemStack->getId(), $protocolId) ?
				ItemStackExtraDataShield::read($extraDataReader) :
				ItemStackExtraData::read($extraDataReader);
			$nbt = $extraData->getNbt();
			if($nbt !== null){
				LE::writeSignedShort($out, -1);
				Byte::writeUnsigned($out, 1);
				$out->writeByteArray((new NetworkNbtSerializer())->write(new TreeRoot($nbt)));
			}else{
				LE::writeSignedShort($out, 0);
			}
			VarInt::writeSignedInt($out, count($extraData->getCanPlaceOn()));
			foreach($extraData->getCanPlaceOn() as $entry){
				self::putString($out, $entry);
			}
			VarInt::writeSignedInt($out, count($extraData->getCanDestroy()));
			foreach($extraData->getCanDestroy() as $entry){
				self::putString($out, $entry);
			}
			if($extraData instanceof ItemStackExtraDataShield){
				VarInt::writeSignedLong($out, $extraData->getBlockingTick());
			}
		}
	}

	/**
	 * @throws PacketDecodeException
	 * @throws DataDecodeException
	 */
	public static function getItemStackWithoutStackId(ByteBufferReader $in, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : ItemStack{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$id = VarInt::readSignedInt($in);
			$count = LE::readUnsignedShort($in);
			$meta = VarInt::readUnsignedInt($in);
			$itemStack = self::getItemStackFooter($in, $id, $meta, $count, $protocolId);
			return $id !== 0 ? $itemStack : ItemStack::null();
		}

		[$id, $count, $meta] = self::getItemStackHeader($in, $protocolId);
		return $id !== 0 ? self::getItemStackFooter($in, $id, $meta, $count, $protocolId) : ItemStack::null();
	}

	public static function putItemStackWithoutStackId(ByteBufferWriter $out, ItemStack $itemStack, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			// 1.26.40 always writes id/count/meta + footer (including empty stacks)
			VarInt::writeSignedInt($out, $itemStack->getId());
			LE::writeUnsignedShort($out, $itemStack->getCount());
			VarInt::writeUnsignedInt($out, $itemStack->getMeta());
			self::putItemStackFooter($out, $itemStack, $protocolId);
			return;
		}
		if(self::putItemStackHeader($out, $itemStack, $protocolId)){
			self::putItemStackFooter($out, $itemStack, $protocolId);
		}
	}

	/**
	 * Reads a v2168 (1.26.40+) "network item instance descriptor" as used in ItemStackRequest
	 * CRAFT_RESULTS_DEPRECATED action payloads.
	 *
	 * Wire format: VarUInt descriptor type + duplicate type byte + (if non-air: namespaced string ID
	 * + VarInt aux value) + signed shortLE count + VarUInt blockRuntimeId + VarUInt length-prefixed user data.
	 *
	 * The item is identified by a namespaced string ID, which cannot be resolved to a numeric item ID
	 * in the protocol layer. Since the deprecated results action is not consumed server-side, the decoded
	 * item is discarded.
	 *
	 * @throws DataDecodeException
	 */
	public static function getItemStackRequestNetworkItemInstanceDescriptor(ByteBufferReader $in, int $protocolId) : ItemStack{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$descriptorType = VarInt::readUnsignedInt($in);
			Byte::readUnsigned($in); //duplicate type byte, discarded
			if($descriptorType !== 0){
				self::getString($in); //namespaced string ID, discarded
				VarInt::readSignedInt($in); //aux value, discarded
			}
			LE::readSignedShort($in); //count, discarded
			VarInt::readUnsignedInt($in); //blockRuntimeId, discarded
			self::getString($in); //user data, discarded
			return ItemStack::null();
		}
		return self::getItemStackWithoutStackId($in, $protocolId);
	}

	public static function putItemStackRequestNetworkItemInstanceDescriptor(ByteBufferWriter $out, ItemStack $itemStack, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$air = $itemStack->isNull();
			VarInt::writeUnsignedInt($out, $air ? 0 : 1); //descriptor type
			Byte::writeUnsigned($out, $air ? 0 : 1); //duplicate type byte
			if(!$air){
				throw new \LogicException("Non-air items cannot be encoded as v2168 item stack request descriptors in the protocol layer (namespaced string IDs are not resolvable)");
			}
			LE::writeSignedShort($out, 0); //count
			VarInt::writeUnsignedInt($out, 0); //blockRuntimeId
			self::putString($out, ""); //user data
			return;
		}
		self::putItemStackWithoutStackId($out, $itemStack, $protocolId);
	}

	/** @throws DataDecodeException */
	public static function getItemStackWrapper(ByteBufferReader $in, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL, bool $hasLegacyNetId = false) : ItemStackWrapper{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			return self::getNetworkItemStackDescriptor($in, $protocolId);
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_16_0 && $protocolId < ProtocolInfo::PROTOCOL_1_16_220 && $hasLegacyNetId){
			$stackId = self::readServerItemStackId($in);
			return new ItemStackWrapper($stackId, self::getItemStackWithoutStackId($in, $protocolId));
		}

		[$id, $count, $meta] = self::getItemStackHeader($in, $protocolId);
		if($id === 0){
			return new ItemStackWrapper(0, ItemStack::null());
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_16_220){
			$hasNetId = self::getBool($in);
			$stackId = $hasNetId ? self::readServerItemStackId($in) : 0;
		}else{
			$stackId = 0;
		}

		$itemStack = self::getItemStackFooter($in, $id, $meta, $count, $protocolId);

		return new ItemStackWrapper($stackId, $itemStack);
	}

	public static function putItemStackWrapper(ByteBufferWriter $out, ItemStackWrapper $itemStackWrapper, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL, bool $hasLegacyNetId = false) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			self::putNetworkItemStackDescriptor($out, $itemStackWrapper, $protocolId);
			return;
		}

		$itemStack = $itemStackWrapper->getItemStack();
		if($protocolId >= ProtocolInfo::PROTOCOL_1_16_0 && $protocolId < ProtocolInfo::PROTOCOL_1_16_220 && $hasLegacyNetId){
			self::writeServerItemStackId($out, $itemStackWrapper->getStackId());
			self::putItemStackWithoutStackId($out, $itemStack, $protocolId);
			return;
		}

		if(self::putItemStackHeader($out, $itemStack, $protocolId)){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_16_220){
				$hasNetId = $itemStackWrapper->getStackId() !== 0;
				self::putBool($out, $hasNetId);
				if($hasNetId){
					self::writeServerItemStackId($out, $itemStackWrapper->getStackId());
				}
			}

			self::putItemStackFooter($out, $itemStack, $protocolId);
		}
	}

	public static function getNetworkItemStackDescriptor(ByteBufferReader $in, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : ItemStackWrapper{
		$id = LE::readSignedShort($in);
		$count = LE::readUnsignedShort($in);
		$meta = VarInt::readUnsignedInt($in);

		$hasNetId = self::getBool($in);
		if($hasNetId){
			$variant = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40 ? 0 : VarInt::readUnsignedInt($in);
			$stackId = VarInt::readSignedInt($in);
		}else{
			$variant = 0;
			$stackId = 0;
		}

		$blockRuntimeId = VarInt::readUnsignedInt($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$blockRuntimeId = Binary::signInt($blockRuntimeId);
		}
		$rawExtraData = self::getString($in);

		return new ItemStackWrapper($stackId, new ItemStack($id, $meta, $count, $blockRuntimeId, $rawExtraData), $variant);
	}

	public static function putNetworkItemStackDescriptor(ByteBufferWriter $out, ItemStackWrapper $itemStackWrapper, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : void{
		LE::writeSignedShort($out, $itemStackWrapper->getItemStack()->getId());
		LE::writeUnsignedShort($out, $itemStackWrapper->getItemStack()->getCount());
		VarInt::writeUnsignedInt($out, $itemStackWrapper->getItemStack()->getMeta());

		self::putBool($out, $hasNetId = $itemStackWrapper->getStackId() !== 0);
		if($hasNetId){
			if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
				VarInt::writeUnsignedInt($out, $itemStackWrapper->getStackIdVariant());
			}
			VarInt::writeSignedInt($out, $itemStackWrapper->getStackId());
		}

		$blockRuntimeId = $itemStackWrapper->getItemStack()->getBlockRuntimeId();
		// 1.26.40+ network item descriptor uses unsigned varint for (possibly hashed) block runtime IDs
		VarInt::writeUnsignedInt(
			$out,
			$protocolId >= ProtocolInfo::PROTOCOL_1_26_40 ? Binary::unsignInt($blockRuntimeId) : $blockRuntimeId
		);
		self::putString($out, $itemStackWrapper->getItemStack()->getRawExtraData());
	}

	/** @throws DataDecodeException */
	public static function getRecipeIngredient(ByteBufferReader $in, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : RecipeIngredient{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			return RecipeIngredient::read($in, $protocolId);
		}

		if($protocolId < ProtocolInfo::PROTOCOL_1_19_30){
			$id = VarInt::readSignedInt($in);
			if($id === 0){
				return new RecipeIngredient(null, 0);
			}
			$meta = VarInt::readSignedInt($in);
			$count = VarInt::readSignedInt($in);
			return new RecipeIngredient(new IntIdMetaItemDescriptor($id, $meta), $count);
		}

		$descriptorType = Byte::readUnsigned($in);
		$descriptor = match($descriptorType){
			ItemDescriptorType::INT_ID_META => IntIdMetaItemDescriptor::read($in, $protocolId),
			ItemDescriptorType::STRING_ID_META => StringIdMetaItemDescriptor::read($in),
			ItemDescriptorType::TAG => TagItemDescriptor::read($in),
			ItemDescriptorType::MOLANG => MolangItemDescriptor::read($in, $protocolId),
			ItemDescriptorType::COMPLEX_ALIAS => ComplexAliasItemDescriptor::read($in),
			default => null
		};
		$count = VarInt::readSignedInt($in);

		return new RecipeIngredient($descriptor, $count);
	}

	public static function putRecipeIngredient(ByteBufferWriter $out, RecipeIngredient $ingredient, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$ingredient->write($out, $protocolId);
			return;
		}

		$type = $ingredient->getDescriptor();

		if($protocolId < ProtocolInfo::PROTOCOL_1_19_30){
			if($type === null){
				VarInt::writeSignedInt($out, 0);
				return;
			}
			if(!$type instanceof IntIdMetaItemDescriptor){
				throw new \InvalidArgumentException("Only integer ID/meta recipe ingredients are supported before protocol " . ProtocolInfo::PROTOCOL_1_19_30);
			}
			VarInt::writeSignedInt($out, $type->getId());
			VarInt::writeSignedInt($out, $type->getMeta() & 0x7fff);
			VarInt::writeSignedInt($out, $ingredient->getCount());
			return;
		}

		Byte::writeUnsigned($out, $type?->getTypeId() ?? 0);
		$type?->write($out, $protocolId);

		VarInt::writeSignedInt($out, $ingredient->getCount());
	}

	/**
	 * Decodes entity metadata from the stream.
	 *
	 * @return MetadataProperty[]
	 * @phpstan-return array<int, MetadataProperty>
	 *
	 * @throws PacketDecodeException
	 * @throws DataDecodeException
	 */
	public static function getEntityMetadata(ByteBufferReader $in, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : array{
		$count = VarInt::readUnsignedInt($in);
		$data = [];
		for($i = 0; $i < $count; ++$i){
			$key = VarInt::readUnsignedInt($in);
			if($protocolId < ProtocolInfo::PROTOCOL_1_19_40){
				$key = $key === 120 ? 123 : ($key >= 121 ? $key - 1 : $key);
			}
			$key = EntityMetadataProperties::fromNetworkId($key, $protocolId);
			$type = VarInt::readUnsignedInt($in);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				Byte::readUnsigned($in);
			}

			$data[$key] = self::readMetadataProperty($in, $type);
		}

		return EntityMetadataFlags::decode($data, $protocolId);
	}

	/** @throws DataDecodeException */
	private static function readMetadataProperty(ByteBufferReader $in, int $type) : MetadataProperty{
		return match($type){
			ByteMetadataProperty::ID => ByteMetadataProperty::read($in),
			ShortMetadataProperty::ID => ShortMetadataProperty::read($in),
			IntMetadataProperty::ID => IntMetadataProperty::read($in),
			FloatMetadataProperty::ID => FloatMetadataProperty::read($in),
			StringMetadataProperty::ID => StringMetadataProperty::read($in),
			CompoundTagMetadataProperty::ID => CompoundTagMetadataProperty::read($in),
			BlockPosMetadataProperty::ID => BlockPosMetadataProperty::read($in),
			LongMetadataProperty::ID => LongMetadataProperty::read($in),
			Vec3MetadataProperty::ID => Vec3MetadataProperty::read($in),
			default => throw new PacketDecodeException("Unknown entity metadata type " . $type),
		};
	}

	/**
	 * Writes entity metadata to the packet buffer.
	 *
	 * @param MetadataProperty[] $metadata
	 *
	 * @phpstan-param array<int, MetadataProperty> $metadata
	 */
	public static function putEntityMetadata(ByteBufferWriter $out, array $metadata, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : void{
		$metadata = EntityMetadataFlags::encode($metadata, $protocolId);
		VarInt::writeUnsignedInt($out, count($metadata));
		foreach($metadata as $key => $d){
			$key = EntityMetadataProperties::toNetworkId($key, $protocolId);
			if($protocolId < ProtocolInfo::PROTOCOL_1_19_40){
				$key = $key >= 120 ? $key + 1 : $key;
				$key = $key === 124 ? 120 : $key;
			}
			VarInt::writeUnsignedInt($out, $key);
			VarInt::writeUnsignedInt($out, $d->getTypeId());
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				Byte::writeUnsigned($out, $d->getTypeId());
			}
			$d->write($out);
		}
	}

	/** @throws DataDecodeException */
	public static function getActorUniqueId(ByteBufferReader $in) : int{
		return VarInt::readSignedLong($in);
	}

	public static function putActorUniqueId(ByteBufferWriter $out, int $eid) : void{
		VarInt::writeSignedLong($out, $eid);
	}

	/** @throws DataDecodeException */
	public static function getActorRuntimeId(ByteBufferReader $in) : int{
		return VarInt::readUnsignedLong($in);
	}

	public static function putActorRuntimeId(ByteBufferWriter $out, int $eid) : void{
		VarInt::writeUnsignedLong($out, $eid);
	}

	/**
	 * Reads a block position
	 *
	 * @throws DataDecodeException
	 */
	public static function getBlockPosition(ByteBufferReader $in, bool $signedY = true) : BlockPosition{
		$x = VarInt::readSignedInt($in);
		$y = $signedY ? VarInt::readSignedInt($in) : Binary::signInt(VarInt::readUnsignedInt($in));
		$z = VarInt::readSignedInt($in);
		return new BlockPosition($x, $y, $z);
	}

	/**
	 * Writes a block position
	 */
	public static function putBlockPosition(ByteBufferWriter $out, BlockPosition $blockPosition, bool $signedY = true) : void{
		VarInt::writeSignedInt($out, $blockPosition->getX());
		if($signedY){
			VarInt::writeSignedInt($out, $blockPosition->getY());
		}else{
			VarInt::writeUnsignedInt($out, Binary::unsignInt($blockPosition->getY()));
		}
		VarInt::writeSignedInt($out, $blockPosition->getZ());
	}

	/**
	 * Reads a floating-point Vector3 object with coordinates rounded to 4 decimal places.
	 *
	 * @throws DataDecodeException
	 */
	public static function getVector3(ByteBufferReader $in) : Vector3{
		$x = LE::readFloat($in);
		$y = LE::readFloat($in);
		$z = LE::readFloat($in);
		return new Vector3($x, $y, $z);
	}

	/**
	 * Reads a floating-point Vector2 object with coordinates rounded to 4 decimal places.
	 *
	 * @throws DataDecodeException
	 */
	public static function getVector2(ByteBufferReader $in) : Vector2{
		$x = LE::readFloat($in);
		$y = LE::readFloat($in);
		return new Vector2($x, $y);
	}

	/**
	 * Writes a floating-point Vector3 object, or 3x zero if null is given.
	 *
	 * Note: ONLY use this where it is reasonable to allow not specifying the vector.
	 * For all other purposes, use the non-nullable version.
	 *
	 * @see CommonTypes::putVector3()
	 */
	public static function putVector3Nullable(ByteBufferWriter $out, ?Vector3 $vector) : void{
		if($vector !== null){
			self::putVector3($out, $vector);
		}else{
			LE::writeFloat($out, 0.0);
			LE::writeFloat($out, 0.0);
			LE::writeFloat($out, 0.0);
		}
	}

	/**
	 * Writes a floating-point Vector3 object
	 */
	public static function putVector3(ByteBufferWriter $out, Vector3 $vector) : void{
		LE::writeFloat($out, $vector->x);
		LE::writeFloat($out, $vector->y);
		LE::writeFloat($out, $vector->z);
	}

	/**
	 * Writes a floating-point Vector2 object
	 */
	public static function putVector2(ByteBufferWriter $out, Vector2 $vector2) : void{
		LE::writeFloat($out, $vector2->x);
		LE::writeFloat($out, $vector2->y);
	}

	/** @throws DataDecodeException */
	public static function getRotationByte(ByteBufferReader $in) : float{
		return Byte::readUnsigned($in) * (360 / 256);
	}

	public static function putRotationByte(ByteBufferWriter $out, float $rotation) : void{
		Byte::writeUnsigned($out, (int) ($rotation / (360 / 256)));
	}

	/** @throws DataDecodeException */
	private static function readGameRule(ByteBufferReader $in, int $protocolId, int $type, bool $isPlayerModifiable, bool $isStartGame) : GameRule{
		return match($type){
			BoolGameRule::ID => BoolGameRule::decode($in, $protocolId, $isPlayerModifiable),
			IntGameRule::ID => IntGameRule::decode($in, $protocolId, $isPlayerModifiable, $isStartGame),
			FloatGameRule::ID => FloatGameRule::decode($in, $protocolId, $isPlayerModifiable),
			default => throw new PacketDecodeException("Unknown gamerule type $type"),
		};
	}

	/**
	 * Reads gamerules
	 *
	 * @return GameRule[] game rule name => value
	 * @phpstan-return array<string, GameRule>
	 *
	 * @throws PacketDecodeException
	 * @throws DataDecodeException
	 */
	public static function getGameRules(ByteBufferReader $in, int $protocolId, bool $isStartGame) : array{
		$count = VarInt::readUnsignedInt($in);
		$rules = [];
		for($i = 0; $i < $count; ++$i){
			$name = self::getString($in);
			$isPlayerModifiable = $protocolId >= ProtocolInfo::PROTOCOL_1_17_0 ?
				self::getBool($in) :
				false;
			$type = VarInt::readUnsignedInt($in);
			$rules[$name] = self::readGameRule($in, $protocolId, $type, $isPlayerModifiable, $isStartGame);
		}

		return $rules;
	}

	/**
	 * Writes a gamerule array
	 *
	 * @param GameRule[] $rules
	 * @phpstan-param array<string, GameRule> $rules
	 */
	public static function putGameRules(ByteBufferWriter $out, int $protocolId, array $rules, bool $isStartGame) : void{
		VarInt::writeUnsignedInt($out, count($rules));
		foreach($rules as $name => $rule){
			self::putString($out, $name);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_17_0){
				self::putBool($out, $rule->isPlayerModifiable());
			}
			VarInt::writeUnsignedInt($out, $rule->getTypeId());
			$rule->encode($out, $protocolId, $isStartGame);
		}
	}

	/** @throws DataDecodeException */
	public static function getEntityLink(ByteBufferReader $in, int $protocolId) : EntityLink{
		$fromActorUniqueId = self::getActorUniqueId($in);
		$toActorUniqueId = self::getActorUniqueId($in);
		$type = Byte::readUnsigned($in);
		$immediate = self::getBool($in);
		$causedByRider = $protocolId >= ProtocolInfo::PROTOCOL_1_16_0 ? self::getBool($in) : false;
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			$vehicleAngularVelocity = LE::readFloat($in);
		}
		return new EntityLink($fromActorUniqueId, $toActorUniqueId, $type, $immediate, $causedByRider, $vehicleAngularVelocity ?? 0);
	}

	public static function putEntityLink(ByteBufferWriter $out, int $protocolId, EntityLink $link) : void{
		self::putActorUniqueId($out, $link->fromActorUniqueId);
		self::putActorUniqueId($out, $link->toActorUniqueId);
		Byte::writeUnsigned($out, $link->type);
		self::putBool($out, $link->immediate);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_16_0){
			self::putBool($out, $link->causedByRider);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			LE::writeFloat($out, $link->vehicleAngularVelocity);
		}
	}

	/** @throws DataDecodeException */
	public static function getCommandOriginData(ByteBufferReader $in, int $protocolId) : CommandOriginData{
		$result = new CommandOriginData();

		$result->type = $protocolId >= ProtocolInfo::PROTOCOL_1_21_130 ? CommonTypes::getString($in) : CommandOriginData::getTypeFromId(VarInt::readUnsignedInt($in));
		$result->uuid = self::getUUID($in);
		$result->requestId = self::getString($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			$result->playerActorUniqueId = LE::readSignedLong($in);
		}elseif($result->type === CommandOriginData::ORIGIN_DEV_CONSOLE or $result->type === CommandOriginData::ORIGIN_TEST){
			$result->playerActorUniqueId = VarInt::readSignedLong($in);
		}

		return $result;
	}

	public static function putCommandOriginData(ByteBufferWriter $out, CommandOriginData $data, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			self::putString($out, $data->type);
		}else{
			VarInt::writeUnsignedInt($out, CommandOriginData::getIdFromType($data->type));
		}
		self::putUUID($out, $data->uuid);
		self::putString($out, $data->requestId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			LE::writeSignedLong($out, $data->playerActorUniqueId);
		}elseif($data->type === CommandOriginData::ORIGIN_DEV_CONSOLE or $data->type === CommandOriginData::ORIGIN_TEST){
			VarInt::writeSignedLong($out, $data->playerActorUniqueId);
		}
	}

	/** @throws DataDecodeException */
	public static function getStructureSettings(ByteBufferReader $in, int $protocolId) : StructureSettings{
		$result = new StructureSettings();

		$result->paletteName = self::getString($in);

		$result->ignoreEntities = self::getBool($in);
		$result->ignoreBlocks = self::getBool($in);
		$result->allowNonTickingChunks = $protocolId >= ProtocolInfo::PROTOCOL_1_19_50 ?
			self::getBool($in) :
			false;

		$result->dimensions = self::getBlockPosition($in, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);
		$result->offset = self::getBlockPosition($in, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);

		$result->lastTouchedByPlayerID = self::getActorUniqueId($in);
		$result->rotation = Byte::readUnsigned($in);
		$result->mirror = Byte::readUnsigned($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_17_0){
			$result->animationMode = Byte::readUnsigned($in);
			$result->animationSeconds = LE::readFloat($in);
		}
		$result->integrityValue = LE::readFloat($in);
		$result->integritySeed = LE::readUnsignedInt($in);
		$result->pivot = $protocolId >= ProtocolInfo::PROTOCOL_1_13_0 ? self::getVector3($in) : new Vector3(0, 0, 0);

		return $result;
	}

	public static function putStructureSettings(ByteBufferWriter $out, StructureSettings $structureSettings, int $protocolId) : void{
		self::putString($out, $structureSettings->paletteName);

		self::putBool($out, $structureSettings->ignoreEntities);
		self::putBool($out, $structureSettings->ignoreBlocks);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_19_50){
			self::putBool($out, $structureSettings->allowNonTickingChunks);
		}

		self::putBlockPosition($out, $structureSettings->dimensions, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);
		self::putBlockPosition($out, $structureSettings->offset, $protocolId >= ProtocolInfo::PROTOCOL_1_26_10);

		self::putActorUniqueId($out, $structureSettings->lastTouchedByPlayerID);
		Byte::writeUnsigned($out, $structureSettings->rotation);
		Byte::writeUnsigned($out, $structureSettings->mirror);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_17_0){
			Byte::writeUnsigned($out, $structureSettings->animationMode);
			LE::writeFloat($out, $structureSettings->animationSeconds);
		}
		LE::writeFloat($out, $structureSettings->integrityValue);
		LE::writeUnsignedInt($out, $structureSettings->integritySeed);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_13_0){
			self::putVector3($out, $structureSettings->pivot);
		}
	}

	/** @throws DataDecodeException */
	public static function getStructureEditorData(ByteBufferReader $in, int $protocolId) : StructureEditorData{
		$result = new StructureEditorData();

		$result->structureName = self::getString($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_60){
			$result->filteredStructureName = self::getString($in);
		}
		$result->structureDataField = self::getString($in);

		$result->includePlayers = self::getBool($in);
		$result->showBoundingBox = self::getBool($in);

		$result->structureBlockType = VarInt::readSignedInt($in);
		$result->structureSettings = self::getStructureSettings($in, $protocolId);
		$result->structureRedstoneSaveMode = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40 ?
			Byte::readUnsigned($in) :
			VarInt::readSignedInt($in);

		return $result;
	}

	public static function putStructureEditorData(ByteBufferWriter $out, int $protocolId, StructureEditorData $structureEditorData) : void{
		self::putString($out, $structureEditorData->structureName);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_60){
			self::putString($out, $structureEditorData->filteredStructureName);
		}
		self::putString($out, $structureEditorData->structureDataField);

		self::putBool($out, $structureEditorData->includePlayers);
		self::putBool($out, $structureEditorData->showBoundingBox);

		VarInt::writeSignedInt($out, $structureEditorData->structureBlockType);
		self::putStructureSettings($out, $structureEditorData->structureSettings, $protocolId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			Byte::writeUnsigned($out, $structureEditorData->structureRedstoneSaveMode);
		}else{
			VarInt::writeSignedInt($out, $structureEditorData->structureRedstoneSaveMode);
		}
	}

	/** @throws PacketDecodeException */
	public static function getNbtRoot(ByteBufferReader $in) : TreeRoot{
		$offset = $in->getOffset();
		try{
			return (new NetworkNbtSerializer())->read($in->getData(), $offset, 512);
		}catch(NbtDataException $e){
			throw PacketDecodeException::wrap($e, "Failed decoding NBT root");
		}finally{
			$in->setOffset($offset);
		}
	}

	public static function getNbtCompoundRoot(ByteBufferReader $in) : CompoundTag{
		try{
			return self::getNbtRoot($in)->mustGetCompoundTag();
		}catch(NbtDataException $e){
			throw PacketDecodeException::wrap($e, "Expected TAG_Compound NBT root");
		}
	}

	/** @throws DataDecodeException */
	public static function readRecipeNetId(ByteBufferReader $in) : int{
		return VarInt::readUnsignedInt($in);
	}

	public static function writeRecipeNetId(ByteBufferWriter $out, int $id) : void{
		VarInt::writeUnsignedInt($out, $id);
	}

	/** @throws DataDecodeException */
	public static function readCreativeItemNetId(ByteBufferReader $in) : int{
		return VarInt::readUnsignedInt($in);
	}

	public static function writeCreativeItemNetId(ByteBufferWriter $out, int $id) : void{
		VarInt::writeUnsignedInt($out, $id);
	}

	/**
	 * This is a union of ItemStackRequestId, LegacyItemStackRequestId, and ServerItemStackId, used in serverbound
	 * packets to allow the client to refer to server known items, or items which may have been modified by a previous
	 * as-yet unacknowledged request from the client.
	 *
	 * - Server itemstack ID is positive
	 * - InventoryTransaction "legacy" request ID is negative and even
	 * - ItemStackRequest request ID is negative and odd
	 * - 0 refers to an empty itemstack (air)
	 *
	 * @throws DataDecodeException
	 */
	public static function readItemStackNetIdVariant(ByteBufferReader $in) : int{
		return VarInt::readSignedInt($in);
	}

	/**
	 * This is a union of ItemStackRequestId, LegacyItemStackRequestId, and ServerItemStackId, used in serverbound
	 * packets to allow the client to refer to server known items, or items which may have been modified by a previous
	 * as-yet unacknowledged request from the client.
	 */
	public static function writeItemStackNetIdVariant(ByteBufferWriter $out, int $id) : void{
		VarInt::writeSignedInt($out, $id);
	}

	/** @throws DataDecodeException */
	public static function readItemStackRequestId(ByteBufferReader $in) : int{
		return VarInt::readSignedInt($in);
	}

	public static function writeItemStackRequestId(ByteBufferWriter $out, int $id) : void{
		VarInt::writeSignedInt($out, $id);
	}

	/** @throws DataDecodeException */
	public static function readLegacyItemStackRequestId(ByteBufferReader $in) : int{
		return VarInt::readSignedInt($in);
	}

	public static function writeLegacyItemStackRequestId(ByteBufferWriter $out, int $id) : void{
		VarInt::writeSignedInt($out, $id);
	}

	/** @throws DataDecodeException */
	public static function readServerItemStackId(ByteBufferReader $in) : int{
		return VarInt::readSignedInt($in);
	}

	public static function writeServerItemStackId(ByteBufferWriter $out, int $id) : void{
		VarInt::writeSignedInt($out, $id);
	}

	/**
	 * @phpstan-template T
	 * @phpstan-param \Closure(ByteBufferReader) : (T|null) $reader
	 * @phpstan-return T|null
	 * @throws DataDecodeException
	 */
	public static function readOptional(ByteBufferReader $in, \Closure $reader) : mixed{
		if(self::getBool($in)){
			return $reader($in);
		}
		return null;
	}

	/**
	 * @phpstan-template T
	 * @phpstan-param T|null $value
	 * @phpstan-param \Closure(ByteBufferWriter, T) : void $writer
	 */
	public static function writeOptional(ByteBufferWriter $out, mixed $value, \Closure $writer) : void{
		if($value !== null){
			self::putBool($out, true);
			$writer($out, $value);
		}else{
			self::putBool($out, false);
		}
	}
}
