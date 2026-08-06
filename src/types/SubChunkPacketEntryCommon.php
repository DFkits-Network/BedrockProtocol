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

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class SubChunkPacketEntryCommon{

	public function __construct(
		private SubChunkPositionOffset $offset,
		private int $requestResult,
		private string $terrainData,
		private ?SubChunkPacketHeightMapInfo $heightMap,
		private ?SubChunkPacketHeightMapInfo $renderHeightMap
	){}

	public function getOffset() : SubChunkPositionOffset{ return $this->offset; }

	public function getRequestResult() : int{ return $this->requestResult; }

	public function getTerrainData() : string{ return $this->terrainData; }

	public function getHeightMap() : ?SubChunkPacketHeightMapInfo{ return $this->heightMap; }

	public function getRenderHeightMap() : ?SubChunkPacketHeightMapInfo{ return $this->renderHeightMap; }

	private static function readHeightMap(ByteBufferReader $in, int $protocolId) : ?SubChunkPacketHeightMapInfo{
		$heightMapDataType = Byte::readUnsigned($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			// 1.26.40+: type byte + optional<bool, heightMap payload>
			$hasData = CommonTypes::getBool($in);
			return match ($heightMapDataType) {
				SubChunkPacketHeightMapType::NO_DATA => null,
				SubChunkPacketHeightMapType::DATA => $hasData ? SubChunkPacketHeightMapInfo::read($in) : null,
				SubChunkPacketHeightMapType::ALL_TOO_HIGH => SubChunkPacketHeightMapInfo::allTooHigh(),
				SubChunkPacketHeightMapType::ALL_TOO_LOW => SubChunkPacketHeightMapInfo::allTooLow(),
				SubChunkPacketHeightMapType::ALL_COPIED => null,
				default => throw new PacketDecodeException("Unknown heightmap data type $heightMapDataType")
			};
		}

		return match ($heightMapDataType) {
			SubChunkPacketHeightMapType::NO_DATA => null,
			SubChunkPacketHeightMapType::DATA => SubChunkPacketHeightMapInfo::read($in),
			SubChunkPacketHeightMapType::ALL_TOO_HIGH => SubChunkPacketHeightMapInfo::allTooHigh(),
			SubChunkPacketHeightMapType::ALL_TOO_LOW => SubChunkPacketHeightMapInfo::allTooLow(),
			default => throw new PacketDecodeException("Unknown heightmap data type $heightMapDataType")
		};
	}

	private static function writeHeightMap(ByteBufferWriter $out, int $protocolId, ?SubChunkPacketHeightMapInfo $heightMap, bool $copied = false) : void{
		if($heightMap === null){
			Byte::writeUnsigned($out, $copied ? SubChunkPacketHeightMapType::ALL_COPIED : SubChunkPacketHeightMapType::NO_DATA);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				CommonTypes::putBool($out, false);
			}
			return;
		}
		if($heightMap->isAllTooLow()){
			Byte::writeUnsigned($out, SubChunkPacketHeightMapType::ALL_TOO_LOW);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				CommonTypes::putBool($out, false);
			}
			return;
		}
		if($heightMap->isAllTooHigh()){
			Byte::writeUnsigned($out, SubChunkPacketHeightMapType::ALL_TOO_HIGH);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				CommonTypes::putBool($out, false);
			}
			return;
		}

		Byte::writeUnsigned($out, SubChunkPacketHeightMapType::DATA);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::putBool($out, true);
		}
		$heightMap->write($out);
	}

	public static function read(ByteBufferReader $in, int $protocolId, bool $cacheEnabled) : self{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$offset = SubChunkPositionOffset::read($in);
			$requestResult = Byte::readUnsigned($in);
			// optional terrain payload
			$data = CommonTypes::getBool($in) ? CommonTypes::getString($in) : "";
			$heightMapData = self::readHeightMap($in, $protocolId);
			$renderHeightMapData = self::readHeightMap($in, $protocolId);
			// blob id is handled by SubChunkPacketEntryWithCache / WithoutCache via optional at entry level
			// but Cloudburst puts blob optional inside the common entry - caller still reads it for cache list type
			return new self($offset, $requestResult, $data, $heightMapData, $renderHeightMapData);
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_18_10){
			$offset = SubChunkPositionOffset::read($in);
			$requestResult = Byte::readUnsigned($in);
			$data = !$cacheEnabled || $requestResult !== SubChunkRequestResult::SUCCESS_ALL_AIR ? CommonTypes::getString($in) : "";
		}else{
			$offset = new SubChunkPositionOffset(0, 0, 0);
			$data = CommonTypes::getString($in);
			$requestResult = VarInt::readSignedInt($in);
		}

		$heightMapData = self::readHeightMap($in, $protocolId);

		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_90){
			$renderHeightMapDataType = Byte::readUnsigned($in);
			$renderHeightMapData = match ($renderHeightMapDataType) {
				SubChunkPacketHeightMapType::NO_DATA => null,
				SubChunkPacketHeightMapType::DATA => SubChunkPacketHeightMapInfo::read($in),
				SubChunkPacketHeightMapType::ALL_TOO_HIGH => SubChunkPacketHeightMapInfo::allTooHigh(),
				SubChunkPacketHeightMapType::ALL_TOO_LOW => SubChunkPacketHeightMapInfo::allTooLow(),
				SubChunkPacketHeightMapType::ALL_COPIED => $heightMapData,
				default => throw new PacketDecodeException("Unknown render heightmap data type $renderHeightMapDataType")
			};
		}

		return new self(
			$offset,
			$requestResult,
			$data,
			$heightMapData,
			$renderHeightMapData ?? null,
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId, bool $cacheEnabled) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->offset->write($out);
			Byte::writeUnsigned($out, $this->requestResult);
			$hasData = $this->terrainData !== "";
			CommonTypes::putBool($out, $hasData);
			if($hasData){
				CommonTypes::putString($out, $this->terrainData);
			}
			self::writeHeightMap($out, $protocolId, $this->heightMap);
			self::writeHeightMap($out, $protocolId, $this->renderHeightMap, copied: $this->renderHeightMap === null);
			return;
		}

		if($protocolId >= ProtocolInfo::PROTOCOL_1_18_10){
			$this->offset->write($out);
			Byte::writeUnsigned($out, $this->requestResult);
			if(!$cacheEnabled || $this->requestResult !== SubChunkRequestResult::SUCCESS_ALL_AIR){
				CommonTypes::putString($out, $this->terrainData);
			}
		}else{
			CommonTypes::putString($out, $this->terrainData);
			VarInt::writeSignedInt($out, $this->requestResult);
		}

		self::writeHeightMap($out, $protocolId, $this->heightMap);

		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_90){
			if($this->renderHeightMap === null){
				Byte::writeUnsigned($out, SubChunkPacketHeightMapType::ALL_COPIED);
			}elseif($this->renderHeightMap->isAllTooLow()){
				Byte::writeUnsigned($out, SubChunkPacketHeightMapType::ALL_TOO_LOW);
			}elseif($this->renderHeightMap->isAllTooHigh()){
				Byte::writeUnsigned($out, SubChunkPacketHeightMapType::ALL_TOO_HIGH);
			}else{
				$renderHeightMapData = $this->renderHeightMap;
				Byte::writeUnsigned($out, SubChunkPacketHeightMapType::DATA);
				$renderHeightMapData->write($out);
			}
		}
	}
}
