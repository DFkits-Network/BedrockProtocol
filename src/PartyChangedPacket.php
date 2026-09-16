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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class PartyChangedPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::PARTY_CHANGED_PACKET;

	private ?string $partyId = null;
	private ?bool $partyLeader = null;

	/**
	 * @generate-create-func
	 */
	public static function create(?string $partyId, ?bool $partyLeader) : self{
		$result = new self;
		$result->partyId = $partyId;
		$result->partyLeader = $partyLeader;
		return $result;
	}

	public function getPartyId() : ?string{ return $this->partyId; }

	public function isPartyLeader() : ?bool{ return $this->partyLeader; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			if(CommonTypes::getBool($in)){
				$this->partyId = CommonTypes::getString($in);
				$this->partyLeader = CommonTypes::getBool($in);
			}
			return;
		}

		$this->partyId = CommonTypes::getString($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			$this->partyLeader = CommonTypes::getBool($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			if($this->partyId === null || $this->partyLeader === null){
				CommonTypes::putBool($out, false);
			}else{
				CommonTypes::putBool($out, true);
				CommonTypes::putString($out, $this->partyId);
				CommonTypes::putBool($out, $this->partyLeader);
			}
			return;
		}

		CommonTypes::putString($out, $this->partyId ?? throw new \InvalidArgumentException("Party ID is required before protocol 1.26.50"));
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			CommonTypes::putBool($out, $this->partyLeader ?? throw new \InvalidArgumentException("Party leader state is required before protocol 1.26.50"));
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePartyChanged($this);
	}
}
