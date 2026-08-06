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
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use Ramsey\Uuid\UuidInterface;
use function count;

final class ShapelessRecipe extends RecipeWithTypeId{
	/**
	 * @param RecipeIngredient[] $inputs
	 * @param ItemStack[]        $outputs
	 * @phpstan-param list<RecipeIngredient> $inputs
	 * @phpstan-param list<ItemStack> $outputs
	 */
	public function __construct(
		int $typeId,
		private string $recipeId,
		private array $inputs,
		private array $outputs,
		private UuidInterface $uuid,
		private string $blockName,
		private int $priority,
		private ?RecipeUnlockingRequirement $unlockingRequirement,
		private int $recipeNetId
	){
		parent::__construct($typeId);
	}

	public function getRecipeId() : string{
		return $this->recipeId;
	}

	/**
	 * @return RecipeIngredient[]
	 * @phpstan-return list<RecipeIngredient>
	 */
	public function getInputs() : array{
		return $this->inputs;
	}

	/**
	 * @return ItemStack[]
	 * @phpstan-return list<ItemStack>
	 */
	public function getOutputs() : array{
		return $this->outputs;
	}

	public function getUuid() : UuidInterface{
		return $this->uuid;
	}

	public function getBlockName() : string{
		return $this->blockName;
	}

	public function getPriority() : int{
		return $this->priority;
	}

	public function getUnlockingRequirement() : ?RecipeUnlockingRequirement{ return $this->unlockingRequirement; }

	public function getRecipeNetId() : int{
		return $this->recipeNetId;
	}

	public static function decode(int $recipeType, ByteBufferReader $in, int $protocolId) : self{
		$recipeId = CommonTypes::getString($in);
		$input = [];
		$ingredientCount = VarInt::readUnsignedInt($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40 && $ingredientCount > 128){
			throw new PacketDecodeException("Shapeless recipe ingredient count $ingredientCount exceeds the maximum of 128");
		}
		for($j = 0; $j < $ingredientCount; ++$j){
			$input[] = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40
				? RecipeIngredient::read($in, $protocolId)
				: CommonTypes::getRecipeIngredient($in, $protocolId);
		}
		$output = [];
		for($k = 0, $resultCount = VarInt::readUnsignedInt($in); $k < $resultCount; ++$k){
			$output[] = CommonTypes::getItemStackWithoutStackId($in, $protocolId);
		}
		$uuid = CommonTypes::getUUID($in);
		$block = CommonTypes::getString($in);
		$priority = VarInt::readSignedInt($in);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$hasUnlockingRequirement = CommonTypes::getBool($in);
			$expectedUnlockingRequirement = $recipeType === CraftingDataPacket::ENTRY_SHAPELESS ||
				$recipeType === CraftingDataPacket::ENTRY_USER_DATA_SHAPELESS;
			if($hasUnlockingRequirement !== $expectedUnlockingRequirement){
				throw new PacketDecodeException("Unlocking requirement presence does not match shapeless recipe type $recipeType");
			}
			$unlockingRequirement = $hasUnlockingRequirement ? RecipeUnlockingRequirement::read($in, $protocolId) : null;
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_0){
			$unlockingRequirement = RecipeUnlockingRequirement::read($in, $protocolId);
		}

		$recipeNetId = CommonTypes::readRecipeNetId($in);

		$resolvedUnlockingRequirement = $protocolId >= ProtocolInfo::PROTOCOL_1_26_40
			? ($unlockingRequirement ?? null)
			: ($unlockingRequirement ?? new RecipeUnlockingRequirement(null));
		return new self($recipeType, $recipeId, $input, $output, $uuid, $block, $priority, $resolvedUnlockingRequirement, $recipeNetId);
	}

	public function encode(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->recipeId);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40 && count($this->inputs) > 128){
			throw new \InvalidArgumentException("Shapeless recipe ingredient count exceeds the maximum of 128");
		}
		VarInt::writeUnsignedInt($out, count($this->inputs));
		foreach($this->inputs as $item){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				$item->write($out, $protocolId);
			}else{
				CommonTypes::putRecipeIngredient($out, $item, $protocolId);
			}
		}

		VarInt::writeUnsignedInt($out, count($this->outputs));
		foreach($this->outputs as $item){
			CommonTypes::putItemStackWithoutStackId($out, $item, $protocolId);
		}

		CommonTypes::putUUID($out, $this->uuid);
		CommonTypes::putString($out, $this->blockName);
		VarInt::writeSignedInt($out, $this->priority);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$hasUnlockingRequirement = $this->getTypeId() === CraftingDataPacket::ENTRY_SHAPELESS ||
				$this->getTypeId() === CraftingDataPacket::ENTRY_USER_DATA_SHAPELESS;
			CommonTypes::putBool($out, $hasUnlockingRequirement);
			if($hasUnlockingRequirement){
				($this->unlockingRequirement ?? new RecipeUnlockingRequirement([]))->write($out, $protocolId);
			}
		}elseif($protocolId >= ProtocolInfo::PROTOCOL_1_21_0){
			($this->unlockingRequirement ?? new RecipeUnlockingRequirement(null))->write($out, $protocolId);
		}

		CommonTypes::writeRecipeNetId($out, $this->recipeNetId);
	}
}
