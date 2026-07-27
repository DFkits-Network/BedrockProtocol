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
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use function str_repeat;

final class LegacySkinTest extends TestCase{

	#[DataProvider("legacyProtocolProvider")]
	public function testRoundTrip(int $protocolId) : void{
		$skin = new SkinData(
			"skin-id",
			"",
			null,
			new SkinImage(32, 64, str_repeat("\x01", 32 * 64 * 4)),
			geometryData: '{"geometry":{}}',
			premium: true,
			geometryName: "geometry.test"
		);

		$out = new ByteBufferWriter();
		CommonTypes::putSkin($out, $skin, $protocolId);
		$in = new ByteBufferReader($out->getData());
		$decoded = CommonTypes::getSkin($in, $protocolId);

		self::assertSame(0, $in->getUnreadLength());
		self::assertSame($skin->getSkinImage()->getData(), $decoded->getSkinImage()->getData());
		self::assertSame($skin->getGeometryData(), $decoded->getGeometryData());
		self::assertSame($skin->isPremium(), $decoded->isPremium());
		if($protocolId === ProtocolInfo::PROTOCOL_1_12_0){
			self::assertSame("geometry.test", $decoded->getGeometryName());
		}
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function legacyProtocolProvider() : iterable{
		yield "1.12.0" => [ProtocolInfo::PROTOCOL_1_12_0];
		yield "1.13.0" => [ProtocolInfo::PROTOCOL_1_13_0];
		yield "1.14.60" => [ProtocolInfo::PROTOCOL_1_14_60];
		yield "1.16.20" => [ProtocolInfo::PROTOCOL_1_16_20];
	}
}
