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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraDataShield;

final class LegacyItemStackTest extends TestCase{

	#[DataProvider("legacyItemProvider")]
	public function testProtocolSpecificShieldRuntimeId(int $protocolId, int $itemId, bool $shield) : void{
		$extraData = $shield ?
			new ItemStackExtraDataShield(null, [], [], 0) :
			new ItemStackExtraData(null, [], []);
		$extraDataWriter = new ByteBufferWriter();
		$extraData->write($extraDataWriter);
		$rawExtraData = $extraDataWriter->getData();

		$itemStack = new ItemStack($itemId, 0, 1, 0, $rawExtraData);
		$packetWriter = new ByteBufferWriter();
		CommonTypes::putItemStackWithoutStackId($packetWriter, $itemStack, $protocolId);

		$decoded = CommonTypes::getItemStackWithoutStackId(
			new ByteBufferReader($packetWriter->getData()),
			$protocolId
		);
		self::assertSame($itemId, $decoded->getId());
		self::assertSame($rawExtraData, $decoded->getRawExtraData());
	}

	/**
	 * @return iterable<string, array{int, int, bool}>
	 */
	public static function legacyItemProvider() : iterable{
		yield "1.12 bed uses runtime ID 355" => [ProtocolInfo::PROTOCOL_1_12_0, 355, false];
		yield "1.12 shield uses runtime ID 513" => [ProtocolInfo::PROTOCOL_1_12_0, 513, true];
		yield "1.16.100 shield uses runtime ID 355" => [ProtocolInfo::PROTOCOL_1_16_100, 355, true];
	}
}
