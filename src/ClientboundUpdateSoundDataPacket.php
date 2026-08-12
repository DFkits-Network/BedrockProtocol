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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\sound\SoundData;

class ClientboundUpdateSoundDataPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_UPDATE_SOUND_DATA_PACKET;

	private int $serverSoundHandle;
	private string $soundEvent;
	private ?SoundData $stop = null;
	private ?SoundData $setVolume = null;
	private ?SoundData $setPitch = null;
	private ?SoundData $fade = null;
	private ?SoundData $seekTo = null;
	private ?SoundData $pause = null;
	private ?SoundData $resume = null;

	public static function create(int $serverSoundHandle, string $soundEvent) : self{
		$result = new self;
		$result->serverSoundHandle = $serverSoundHandle;
		$result->soundEvent = $soundEvent;
		return $result;
	}

	public static function createWithUpdates(int $serverSoundHandle, ?SoundData $stop, ?SoundData $setVolume, ?SoundData $setPitch, ?SoundData $fade, ?SoundData $seekTo, ?SoundData $pause, ?SoundData $resume) : self{
		$result = new self;
		$result->serverSoundHandle = $serverSoundHandle;
		$result->soundEvent = "";
		$result->stop = $stop;
		$result->setVolume = $setVolume;
		$result->setPitch = $setPitch;
		$result->fade = $fade;
		$result->seekTo = $seekTo;
		$result->pause = $pause;
		$result->resume = $resume;
		return $result;
	}

	public function getServerSoundHandle() : int{ return $this->serverSoundHandle; }

	public function getSoundEvent() : string{ return $this->soundEvent; }
	public function getStop() : ?SoundData{ return $this->stop; }
	public function getSetVolume() : ?SoundData{ return $this->setVolume; }
	public function getSetPitch() : ?SoundData{ return $this->setPitch; }
	public function getFade() : ?SoundData{ return $this->fade; }
	public function getSeekTo() : ?SoundData{ return $this->seekTo; }
	public function getPause() : ?SoundData{ return $this->pause; }
	public function getResume() : ?SoundData{ return $this->resume; }

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		$this->serverSoundHandle = LE::readUnsignedLong($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$this->soundEvent = "";
			$this->stop = CommonTypes::readOptional($in, SoundData::read(...));
			$this->setVolume = CommonTypes::readOptional($in, SoundData::read(...));
			$this->setPitch = CommonTypes::readOptional($in, SoundData::read(...));
			$this->fade = CommonTypes::readOptional($in, SoundData::read(...));
			$this->seekTo = CommonTypes::readOptional($in, SoundData::read(...));
			$this->pause = CommonTypes::readOptional($in, SoundData::read(...));
			$this->resume = CommonTypes::readOptional($in, SoundData::read(...));
			return;
		}
		$this->soundEvent = CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		LE::writeUnsignedLong($out, $this->serverSoundHandle);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			foreach([$this->stop, $this->setVolume, $this->setPitch, $this->fade, $this->seekTo, $this->pause, $this->resume] as $data){
				CommonTypes::writeOptional($out, $data, static fn(ByteBufferWriter $out, SoundData $data) => $data->write($out));
			}
			return;
		}
		CommonTypes::putString($out, $this->soundEvent);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundUpdateSoundData($this);
	}
}
