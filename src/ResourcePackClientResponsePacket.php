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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

class ResourcePackClientResponsePacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_CLIENT_RESPONSE_PACKET;

	public const STATUS_REFUSED = 1;
	public const STATUS_SEND_PACKS = 2;
	public const STATUS_HAVE_ALL_PACKS = 3;
	public const STATUS_COMPLETED = 4;

	public int $status;
	/** @var string[] */
	public array $packIds = [];

	/**
	 * @generate-create-func
	 * @param string[] $packIds
	 */
	public static function create(int $status, array $packIds) : self{
		$result = new self;
		$result->status = $status;
		$result->packIds = $packIds;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			//wire: 0=REFUSED, 1=SEND_PACKS, 2=HAVE_ALL, 3=COMPLETED
			$this->status = VarInt::readUnsignedInt($in) + 1;
			CommonTypes::getString($in); //status response string
			$this->packIds = [];
			if($this->status === self::STATUS_SEND_PACKS){
				$entryCount = VarInt::readUnsignedInt($in);
				while($entryCount-- > 0){
					$this->packIds[] = CommonTypes::getString($in);
				}
			}
			return;
		}

		$this->status = Byte::readUnsigned($in);
		$entryCount = LE::readUnsignedShort($in);
		$this->packIds = [];
		while($entryCount-- > 0){
			$this->packIds[] = CommonTypes::getString($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			//wire: 0=REFUSED, 1=SEND_PACKS, 2=HAVE_ALL, 3=COMPLETED
			VarInt::writeUnsignedInt($out, $this->status - 1);
			CommonTypes::putString($out, match($this->status){
				self::STATUS_REFUSED => "cancel",
				self::STATUS_SEND_PACKS => "downloading",
				self::STATUS_HAVE_ALL_PACKS => "downloadingfinished",
				self::STATUS_COMPLETED => "resourcepackstackfinished",
				default => "",
			});
			if($this->status === self::STATUS_SEND_PACKS){
				VarInt::writeUnsignedInt($out, count($this->packIds));
				foreach($this->packIds as $id){
					CommonTypes::putString($out, $id);
				}
			}
			return;
		}

		Byte::writeUnsigned($out, $this->status);
		LE::writeUnsignedShort($out, count($this->packIds));
		foreach($this->packIds as $id){
			CommonTypes::putString($out, $id);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleResourcePackClientResponse($this);
	}
}
