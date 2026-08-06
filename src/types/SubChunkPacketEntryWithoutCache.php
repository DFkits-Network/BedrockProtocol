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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class SubChunkPacketEntryWithoutCache{

	public function __construct(
		private SubChunkPacketEntryCommon $base
	){}

	public function getBase() : SubChunkPacketEntryCommon{ return $this->base; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$base = SubChunkPacketEntryCommon::read($in, $protocolId, false);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			// optionalNull blobId — absent when cache disabled
			if(CommonTypes::getBool($in)){
				LE::readUnsignedLong($in);
			}
		}
		return new self($base);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		$this->base->write($out, $protocolId, false);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::putBool($out, false); // no blobId
		}
	}
}
