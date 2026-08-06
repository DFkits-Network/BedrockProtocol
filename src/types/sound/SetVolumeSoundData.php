<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\sound;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;

final class SetVolumeSoundData extends SoundData{
	public function __construct(private float $volume){}
	public function getVolume() : float{ return $this->volume; }
	public function getEvent() : SoundDataEvent{ return SoundDataEvent::SET_VOLUME; }
	protected function writeData(ByteBufferWriter $out) : void{ LE::writeFloat($out, $this->volume); }
}
