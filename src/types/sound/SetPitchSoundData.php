<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\sound;

use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;

final class SetPitchSoundData extends SoundData{
	public function __construct(private float $pitch){}
	public function getPitch() : float{ return $this->pitch; }
	public function getEvent() : SoundDataEvent{ return SoundDataEvent::SET_PITCH; }
	protected function writeData(ByteBufferWriter $out) : void{ LE::writeFloat($out, $this->pitch); }
}
