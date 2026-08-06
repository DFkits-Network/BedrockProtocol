<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\sound;

use pocketmine\network\mcpe\protocol\types\PacketIntEnumTrait;

enum SoundDataEvent : int{
	use PacketIntEnumTrait;

	case STOP = 0;
	case SET_VOLUME = 1;
	case SET_PITCH = 2;
	case FADE = 3;
	case SEEK_TO = 4;
	case PAUSE = 5;
	case RESUME = 6;
}
