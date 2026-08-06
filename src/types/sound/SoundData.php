<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\sound;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;

abstract class SoundData{
	abstract public function getEvent() : SoundDataEvent;

	public static function read(ByteBufferReader $in) : self{
		return match(SoundDataEvent::fromPacket(VarInt::readUnsignedInt($in))){
			SoundDataEvent::STOP => new StopSoundData(),
			SoundDataEvent::SET_VOLUME => new SetVolumeSoundData(LE::readFloat($in)),
			SoundDataEvent::SET_PITCH => new SetPitchSoundData(LE::readFloat($in)),
			SoundDataEvent::FADE => new FadeSoundData(LE::readFloat($in), LE::readFloat($in)),
			SoundDataEvent::SEEK_TO => new SeekToSoundData(LE::readFloat($in)),
			SoundDataEvent::PAUSE => new PauseSoundData(),
			SoundDataEvent::RESUME => new ResumeSoundData(),
		};
	}

	public function write(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->getEvent()->value);
		$this->writeData($out);
	}

	protected function writeData(ByteBufferWriter $out) : void{}
}
