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

namespace pocketmine\network\mcpe\protocol\types\inventory\stackrequest;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;

final class ItemStackRequestSlotInfo{
	public function __construct(
		private FullContainerName $containerName,
		private int $slotId,
		private int $stackId
	){}

	public function getContainerName() : FullContainerName{ return $this->containerName; }

	public function getSlotId() : int{ return $this->slotId; }

	public function getStackId() : int{ return $this->stackId; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$containerName = FullContainerName::read($in, $protocolId);
		$slotId = Byte::readUnsigned($in);
		$stackId = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40 ? LE::readSignedInt($in) : CommonTypes::readItemStackNetIdVariant($in);
		return new self($containerName, $slotId, $stackId);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		$this->containerName->write($out, $protocolId);
		Byte::writeUnsigned($out, $this->slotId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			LE::writeSignedInt($out, $this->stackId);
		}else{
			CommonTypes::writeItemStackNetIdVariant($out, $this->stackId);
		}
	}
}
