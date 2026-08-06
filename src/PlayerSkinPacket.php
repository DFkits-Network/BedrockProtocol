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
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use Ramsey\Uuid\UuidInterface;

class PlayerSkinPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_SKIN_PACKET;

	public UuidInterface $uuid;
	public string $oldSkinName = "";
	public string $newSkinName = "";
	public SkinData $skin;

	/**
	 * @generate-create-func
	 */
	public static function create(UuidInterface $uuid, string $oldSkinName, string $newSkinName, SkinData $skin) : self{
		$result = new self;
		$result->uuid = $uuid;
		$result->oldSkinName = $oldSkinName;
		$result->newSkinName = $newSkinName;
		$result->skin = $skin;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->uuid = CommonTypes::getUUID($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->skin = CommonTypes::getSkin($in, $protocolId);
			$this->newSkinName = CommonTypes::getString($in);
			$this->oldSkinName = CommonTypes::getString($in);
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_13_0){
			$this->skin = CommonTypes::getSkin($in, $protocolId);
			$this->newSkinName = CommonTypes::getString($in);
			$this->oldSkinName = CommonTypes::getString($in);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_14_60){
				$this->skin->setVerified(CommonTypes::getBool($in));
			}
		}else{
			$skinId = CommonTypes::getString($in);
			$this->newSkinName = CommonTypes::getString($in);
			$this->oldSkinName = CommonTypes::getString($in);
			$this->skin = CommonTypes::getSkin($in, $protocolId);
			$this->skin->setSkinId($skinId);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putUUID($out, $this->uuid);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::putSkin($out, $this->skin, $protocolId);
			CommonTypes::putString($out, $this->newSkinName);
			CommonTypes::putString($out, $this->oldSkinName);
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_13_0){
			CommonTypes::putSkin($out, $this->skin, $protocolId);
			CommonTypes::putString($out, $this->newSkinName);
			CommonTypes::putString($out, $this->oldSkinName);
			if($protocolId >= ProtocolInfo::PROTOCOL_1_14_60){
				CommonTypes::putBool($out, $this->skin->isVerified());
			}
		}else{
			CommonTypes::putString($out, $this->skin->getSkinId());
			CommonTypes::putString($out, $this->newSkinName);
			CommonTypes::putString($out, $this->oldSkinName);
			CommonTypes::putSkin($out, $this->skin, $protocolId);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerSkin($this);
	}
}
