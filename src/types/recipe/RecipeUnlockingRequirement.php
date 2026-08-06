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

namespace pocketmine\network\mcpe\protocol\types\recipe;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

final class RecipeUnlockingRequirement{
	public const CONTEXT_NONE = 0;
	public const CONTEXT_ALWAYS_UNLOCKED = 1;
	public const CONTEXT_PLAYER_IN_WATER = 2;
	public const CONTEXT_PLAYER_HAS_MANY_ITEMS = 3;

	/**
	 * @param RecipeIngredient[]|null $unlockingIngredients
	 * @phpstan-param list<RecipeIngredient>|null $unlockingIngredients
	 */
	public function __construct(
		private ?array $unlockingIngredients,
		private int $unlockingContext = self::CONTEXT_NONE,
	){}

	public function getUnlockingContext() : int{ return $this->unlockingContext; }

	/**
	 * @return RecipeIngredient[]|null
	 * @phpstan-return list<RecipeIngredient>|null
	 */
	public function getUnlockingIngredients() : ?array{ return $this->unlockingIngredients; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$context = VarInt::readSignedInt($in);
			if($context < self::CONTEXT_NONE || $context > self::CONTEXT_PLAYER_HAS_MANY_ITEMS){
				throw new PacketDecodeException("Unknown recipe unlocking context $context");
			}
			$ingredients = null;
			$hasIngredients = CommonTypes::getBool($in);
			if($hasIngredients !== ($context === self::CONTEXT_NONE)){
				throw new PacketDecodeException("Unlocking ingredients presence does not match context $context");
			}
			if($hasIngredients){
				$ingredients = [];
				$count = VarInt::readUnsignedInt($in);
				if($count > 128){
					throw new PacketDecodeException("Recipe unlocking ingredient count $count exceeds the maximum of 128");
				}
				for($i = 0; $i < $count; ++$i){
					$ingredients[] = RecipeIngredient::read($in, $protocolId);
				}
			}
			return new self($ingredients, $context);
		}
		$unlockingContext = CommonTypes::getBool($in);
		$unlockingIngredients = null;
		if(!$unlockingContext){
			$unlockingIngredients = [];
			for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; $i++){
				$unlockingIngredients[] = CommonTypes::getRecipeIngredient($in, $protocolId);
			}
		}

		return new self($unlockingIngredients);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			if($this->unlockingContext < self::CONTEXT_NONE || $this->unlockingContext > self::CONTEXT_PLAYER_HAS_MANY_ITEMS){
				throw new \InvalidArgumentException("Unknown recipe unlocking context " . $this->unlockingContext);
			}
			VarInt::writeSignedInt($out, $this->unlockingContext);
			$hasIngredients = $this->unlockingContext === self::CONTEXT_NONE;
			CommonTypes::putBool($out, $hasIngredients);
			if($hasIngredients){
				$ingredients = $this->unlockingIngredients ?? [];
				if(count($ingredients) > 128){
					throw new \InvalidArgumentException("Recipe unlocking ingredient count exceeds the maximum of 128");
				}
				VarInt::writeUnsignedInt($out, count($ingredients));
				foreach($ingredients as $ingredient){
					$ingredient->write($out, $protocolId);
				}
			}
			return;
		}
		CommonTypes::putBool($out, $this->unlockingIngredients === null);
		if($this->unlockingIngredients !== null){
			VarInt::writeUnsignedInt($out, count($this->unlockingIngredients));
			foreach($this->unlockingIngredients as $ingredient){
				CommonTypes::putRecipeIngredient($out, $ingredient, $protocolId);
			}
		}
	}
}
