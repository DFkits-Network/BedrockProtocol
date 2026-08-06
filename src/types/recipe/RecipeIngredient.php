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

namespace pocketmine\network\mcpe\protocol\types\recipe;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function get_class;

final class RecipeIngredient{
	private const EMPTY_AUX_VALUE = 0x7fff;

	public function __construct(
		private ?ItemDescriptor $descriptor,
		private int $count
	){}

	public function getDescriptor() : ?ItemDescriptor{
		return $this->descriptor;
	}

	public function getCount() : int{
		return $this->count;
	}

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			return CommonTypes::getRecipeIngredient($in, $protocolId);
		}
		if(VarInt::readUnsignedInt($in) === 0){
			VarInt::readSignedInt($in);
			return new self(null, VarInt::readSignedInt($in));
		}
		$type = CommonTypes::getString($in);
		$descriptor = match($type){
			"name" => NameItemDescriptor::read($in),
			"molang" => MolangItemDescriptor::read($in, $protocolId),
			"item_tag" => TagItemDescriptor::read($in),
			default => throw new PacketDecodeException("Unknown item descriptor type \"$type\""),
		};
		if($descriptor instanceof TagItemDescriptor){
			VarInt::readSignedInt($in);
		}
		return new self($descriptor, VarInt::readSignedInt($in));
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			CommonTypes::putRecipeIngredient($out, $this, $protocolId);
			return;
		}
		if($this->descriptor === null){
			VarInt::writeUnsignedInt($out, 0);
			VarInt::writeSignedInt($out, self::EMPTY_AUX_VALUE);
			VarInt::writeSignedInt($out, $this->count);
			return;
		}
		VarInt::writeUnsignedInt($out, 1);
		CommonTypes::putString($out, match(true){
			$this->descriptor instanceof NameItemDescriptor => "name",
			$this->descriptor instanceof MolangItemDescriptor => "molang",
			$this->descriptor instanceof TagItemDescriptor => "item_tag",
			default => throw new \LogicException("Unknown item descriptor type " . get_class($this->descriptor)),
		});
		$this->descriptor->write($out, $protocolId);
		if($this->descriptor instanceof TagItemDescriptor){
			VarInt::writeSignedInt($out, self::EMPTY_AUX_VALUE);
		}
		VarInt::writeSignedInt($out, $this->count);
	}
}
