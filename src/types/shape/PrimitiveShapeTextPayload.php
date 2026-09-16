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

namespace pocketmine\network\mcpe\protocol\types\shape;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;

final class PrimitiveShapeTextPayload extends PrimitiveShapePayload{
	use GetTypeIdFromConstTrait;

	public const ID = PrimitiveShapeType::PAYLOAD_TYPE_TEXT;

	public function __construct(
		private string $text,
		private bool $useRotation,
		private ?Color $backgroundColor,
		private bool $depthTest,
		private bool $showBackface,
		private bool $showTextBackface,
		private float $lineGapHeight = 0.0,
	){}

	public function getText() : string{ return $this->text; }

	public function useRotation() : bool{ return $this->useRotation; }

	public function getBackgroundColor() : ?Color{ return $this->backgroundColor; }

	public function hasDepthTest() : bool{ return $this->depthTest; }

	public function hasShowBackface() : bool{ return $this->showBackface; }

	public function hasShowTextBackface() : bool{ return $this->showTextBackface; }

	public function getLineGapHeight() : float{ return $this->lineGapHeight; }

	public static function read(ByteBufferReader $in, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : self{
		$text = CommonTypes::getString($in);
		$useRotation = CommonTypes::getBool($in);
		$backgroundColor = CommonTypes::readOptional($in, fn() => Color::fromARGB(LE::readUnsignedInt($in)));
		$lineGapHeight = $protocolId >= ProtocolInfo::PROTOCOL_1_26_50 ? LE::readFloat($in) : 0.0;
		$depthTest = CommonTypes::getBool($in);
		$showBackface = CommonTypes::getBool($in);
		$showTextBackface = CommonTypes::getBool($in);

		return new self($text, $useRotation, $backgroundColor, $depthTest, $showBackface, $showTextBackface, $lineGapHeight);
	}

	public function write(ByteBufferWriter $out, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : void{
		CommonTypes::putString($out, $this->text);
		CommonTypes::putBool($out, $this->useRotation);
		CommonTypes::writeOptional($out, $this->backgroundColor, fn(ByteBufferWriter $out, Color $color) => LE::writeUnsignedInt($out, $color->toARGB()));
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_50){
			LE::writeFloat($out, $this->lineGapHeight);
		}
		CommonTypes::putBool($out, $this->depthTest);
		CommonTypes::putBool($out, $this->showBackface);
		CommonTypes::putBool($out, $this->showTextBackface);
	}
}
