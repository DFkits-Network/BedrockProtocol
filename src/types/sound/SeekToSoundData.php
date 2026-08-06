<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\sound;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;

final class SeekToSoundData extends SoundData{
	public function __construct(private float $seconds){}
	public function getSeconds() : float{ return $this->seconds; }
	public function getEvent() : SoundDataEvent{ return SoundDataEvent::SEEK_TO; }
	protected function writeData(ByteBufferWriter $out) : void{ LE::writeFloat($out, $this->seconds); }
}
