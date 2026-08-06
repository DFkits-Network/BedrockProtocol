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

namespace pocketmine\network\mcpe\protocol\types\inventory\stackrequest;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use function count;

/**
 * Tells that the current transaction crafted the specified recipe, using the recipe book. This is effectively the same
 * as the regular crafting result action.
 */
final class CraftRecipeAutoStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_RECIPE_AUTO;

	/**
	 * @param RecipeIngredient[] $ingredients
	 * @phpstan-param list<RecipeIngredient> $ingredients
	 */
	final public function __construct(
		private int $recipeId,
		private int $repetitions,
		private int $repetitions2,
		private array $ingredients
	){}

	public function getRecipeId() : int{ return $this->recipeId; }

	public function getRepetitions() : int{ return $this->repetitions; }

	public function getRepetitions2() : int{ return $this->repetitions2; }

	/**
	 * @return RecipeIngredient[]
	 * @phpstan-return list<RecipeIngredient>
	 */
	public function getIngredients() : array{ return $this->ingredients; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$recipeId = CommonTypes::readRecipeNetId($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			//v2168 sends the repetitions property only once (the count duplication was removed)
			$repetitions = Byte::readUnsigned($in);
			$repetitions2 = 0;
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			$repetitions = Byte::readUnsigned($in);
			$repetitions2 = Byte::readUnsigned($in); //repetitions property is sent twice, mojang...
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_17_10){
			$repetitions = Byte::readUnsigned($in);
		}
		$ingredients = [];
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
				$ingredients[] = CommonTypes::getRecipeIngredient($in, $protocolId);
			}
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_19_40){
			for($i = 0, $count = Byte::readUnsigned($in); $i < $count; ++$i){
				$ingredients[] = CommonTypes::getRecipeIngredient($in);
			}
		}
		return new self($recipeId, $repetitions ?? 1, $repetitions2 ?? 0, $ingredients);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::writeRecipeNetId($out, $this->recipeId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			Byte::writeUnsigned($out, $this->repetitions);
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_20){
			Byte::writeUnsigned($out, $this->repetitions);
			Byte::writeUnsigned($out, $this->repetitions2);
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_17_10){
			Byte::writeUnsigned($out, $this->repetitions);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			VarInt::writeUnsignedInt($out, count($this->ingredients));
			foreach($this->ingredients as $ingredient){
				CommonTypes::putRecipeIngredient($out, $ingredient, $protocolId);
			}
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_19_40){
			Byte::writeUnsigned($out, count($this->ingredients));
			foreach($this->ingredients as $ingredient){
				CommonTypes::putRecipeIngredient($out, $ingredient);
			}
		}
	}
}
