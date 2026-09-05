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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

final class StringListPackSetting extends PackSetting{
	public const ID = PackSettingType::STRING_LIST;

	/** @param list<string> $value */
	public function __construct(string $name, private array $value){
		parent::__construct($name);
	}

	/** @return list<string> */
	public function getValue() : array{ return $this->value; }

	public function getTypeId() : PackSettingType{ return self::ID; }

	public function write(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, count($this->value));
		foreach($this->value as $value){
			CommonTypes::putString($out, $value);
		}
	}

	public static function read(ByteBufferReader $in, string $name) : self{
		$values = [];
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$values[] = CommonTypes::getString($in);
		}
		return new self($name, $values);
	}
}
