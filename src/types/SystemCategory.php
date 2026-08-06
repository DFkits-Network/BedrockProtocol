<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class SystemCategory{
	public function __construct(private string $categoryName, private int $systemIndex){}
	public function getCategoryName() : string{ return $this->categoryName; }
	public function getSystemIndex() : int{ return $this->systemIndex; }
	public static function read(ByteBufferReader $in) : self{
		return new self(CommonTypes::getString($in), LE::readUnsignedLong($in));
	}
	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->categoryName);
		LE::writeUnsignedLong($out, $this->systemIndex);
	}
}
