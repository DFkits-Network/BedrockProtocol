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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * Client-side furnace recipe book preferences, introduced in 1.26.50.
 */
class SetPlayerFurnaceOptionsPacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::SET_PLAYER_FURNACE_OPTIONS_PACKET;

	public const TYPE_NONE = 0;
	public const TYPE_FURNACE = 1;
	public const TYPE_BLAST_FURNACE = 2;
	public const TYPE_SMOKER = 3;

	public const TAB_NONE = 0;
	public const TAB_FOOD = 1;
	public const TAB_ITEMS = 2;
	public const TAB_BLOCKS = 3;
	public const TAB_SEARCH = 4;
	public const TAB_INVENTORY = 5;

	public const LAYOUT_NONE = 0;
	public const LAYOUT_INVENTORY_ONLY = 1;
	public const LAYOUT_DEFAULT = 2;

	public int $furnaceType;
	public int $leftTab;
	public bool $filtering;
	public int $layout;

	/**
	 * @generate-create-func
	 */
	public static function create(int $furnaceType, int $leftTab, bool $filtering, int $layout) : self{
		$result = new self;
		$result->furnaceType = $furnaceType;
		$result->leftTab = $leftTab;
		$result->filtering = $filtering;
		$result->layout = $layout;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->furnaceType = Byte::readUnsigned($in);
		$this->leftTab = VarInt::readSignedInt($in);
		$this->filtering = CommonTypes::getBool($in);
		$this->layout = VarInt::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		Byte::writeUnsigned($out, $this->furnaceType);
		VarInt::writeSignedInt($out, $this->leftTab);
		CommonTypes::putBool($out, $this->filtering);
		VarInt::writeSignedInt($out, $this->layout);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetPlayerFurnaceOptions($this);
	}
}
