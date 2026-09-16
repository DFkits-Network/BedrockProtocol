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
use pmmp\encoding\LE;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class EntityDiagnosticTimingInfo{

	public function __construct(
		private string $displayName,
		private string $entity,
		private int $timeInNS,
		private int $percentOfTotal,
		private ?Vector3 $position = null,
		private string $dimension = "",
	){}

	public function getDisplayName() : string{ return $this->displayName; }

	public function getEntity() : string{ return $this->entity; }

	public function getTimeInNS() : int{ return $this->timeInNS; }

	public function getPercentOfTotal() : int{ return $this->percentOfTotal; }

	public function getPosition() : ?Vector3{ return $this->position; }

	public function getDimension() : string{ return $this->dimension; }

	public static function read(ByteBufferReader $in, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : self{
		$displayName = CommonTypes::getString($in);
		$entity = CommonTypes::getString($in);
		$timeInNS = LE::readUnsignedLong($in);
		$percentOfTotal = Byte::readUnsigned($in);

		return new self(
			$displayName,
			$entity,
			$timeInNS,
			$percentOfTotal,
			$protocolId >= ProtocolInfo::PROTOCOL_1_26_50 ? CommonTypes::getVector3($in) : null,
			$protocolId >= ProtocolInfo::PROTOCOL_1_26_50 ? CommonTypes::getString($in) : "",
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : void{
		CommonTypes::putString($out, $this->displayName);
		CommonTypes::putString($out, $this->entity);
		LE::writeUnsignedLong($out, $this->timeInNS);
		Byte::writeUnsigned($out, $this->percentOfTotal);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			CommonTypes::putVector3($out, $this->position ?? new Vector3(0, 0, 0));
			CommonTypes::putString($out, $this->dimension);
		}
	}
}
