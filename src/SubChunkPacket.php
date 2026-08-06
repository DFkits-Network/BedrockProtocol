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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\SubChunkPacketEntryWithCache as EntryWithBlobHash;
use pocketmine\network\mcpe\protocol\types\SubChunkPacketEntryWithCacheList as ListWithBlobHashes;
use pocketmine\network\mcpe\protocol\types\SubChunkPacketEntryWithoutCache as EntryWithoutBlobHash;
use pocketmine\network\mcpe\protocol\types\SubChunkPacketEntryWithoutCacheList as ListWithoutBlobHashes;
use pocketmine\network\mcpe\protocol\types\SubChunkPosition;
use function count;

class SubChunkPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::SUB_CHUNK_PACKET;

	private int $dimension;
	private SubChunkPosition $baseSubChunkPosition;
	private ListWithBlobHashes|ListWithoutBlobHashes $entries;

	/**
	 * @generate-create-func
	 */
	public static function create(int $dimension, SubChunkPosition $baseSubChunkPosition, ListWithBlobHashes|ListWithoutBlobHashes $entries) : self{
		$result = new self;
		$result->dimension = $dimension;
		$result->baseSubChunkPosition = $baseSubChunkPosition;
		$result->entries = $entries;
		return $result;
	}

	public function isCacheEnabled() : bool{ return $this->entries instanceof ListWithBlobHashes; }

	public function getDimension() : int{ return $this->dimension; }

	public function getBaseSubChunkPosition() : SubChunkPosition{ return $this->baseSubChunkPosition; }

	public function getEntries() : ListWithBlobHashes|ListWithoutBlobHashes{ return $this->entries; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$newSubChunkFormat = $protocolId >= ProtocolInfo::PROTOCOL_1_18_10;
		$cacheEnabled = $newSubChunkFormat ? CommonTypes::getBool($in) : $protocolId === ProtocolInfo::PROTOCOL_1_18_0;
		$this->dimension = VarInt::readSignedInt($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->baseSubChunkPosition = SubChunkPosition::readFixedInts($in);
			$count = VarInt::readUnsignedInt($in);
		}elseif($newSubChunkFormat){
			$this->baseSubChunkPosition = SubChunkPosition::readVarInts($in);
			$count = LE::readUnsignedInt($in);
		}else{
			$this->baseSubChunkPosition = new SubChunkPosition(0, 0, 0);
			$count = 1;
		}
		if($cacheEnabled){
			$entries = [];
			for($i = 0; $i < $count; $i++){
				$entries[] = EntryWithBlobHash::read($in, $protocolId);
			}
			$this->entries = new ListWithBlobHashes($entries);
		}else{
			$entries = [];
			for($i = 0; $i < $count; $i++){
				$entries[] = EntryWithoutBlobHash::read($in, $protocolId);
			}
			$this->entries = new ListWithoutBlobHashes($entries);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		$newSubChunkFormat = $protocolId >= ProtocolInfo::PROTOCOL_1_18_10;
		if($newSubChunkFormat){
			CommonTypes::putBool($out, $this->entries instanceof ListWithBlobHashes);
		}elseif($this->entries instanceof ListWithBlobHashes && $protocolId !== ProtocolInfo::PROTOCOL_1_18_0){
			throw new \InvalidArgumentException("SubChunkPacket does not support blob hashes before protocol " . ProtocolInfo::PROTOCOL_1_18_0);
		}
		VarInt::writeSignedInt($out, $this->dimension);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->baseSubChunkPosition->writeFixedInts($out);
			VarInt::writeUnsignedInt($out, count($this->entries->getEntries()));
		}elseif($newSubChunkFormat){
			$this->baseSubChunkPosition->writeVarInts($out);
			LE::writeUnsignedInt($out, count($this->entries->getEntries()));
		}elseif(count($this->entries->getEntries()) !== 1){
			throw new \InvalidArgumentException("Legacy SubChunkPacket must contain exactly one entry");
		}

		foreach($this->entries->getEntries() as $entry){
			$entry->write($out, $protocolId);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSubChunk($this);
	}
}
