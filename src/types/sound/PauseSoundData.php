<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\sound;

final class PauseSoundData extends SoundData{
	public function getEvent() : SoundDataEvent{ return SoundDataEvent::PAUSE; }
}
