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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\MaterialReducerRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\MaterialReducerRecipeOutput;
use pocketmine\network\mcpe\protocol\types\recipe\MultiRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\PotionContainerChangeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\PotionTypeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeWithTypeId;
use pocketmine\network\mcpe\protocol\types\recipe\ShapedRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\ShapelessRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\SmithingTransformRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\SmithingTrimRecipe;
use function count;

class CraftingDataPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CRAFTING_DATA_PACKET;

	/**
	 * Internal recipe type IDs used by the PHP API / CraftingDataCache.
	 * These match the pre-1.26.40 wire values. For 1.26.40+, map with
	 * {@link self::internalTypeToWire()} / {@link self::wireTypeToInternal()}.
	 */
	public const ENTRY_SHAPELESS = 0;
	public const ENTRY_SHAPED = 1;
	public const ENTRY_FURNACE = 2;
	public const ENTRY_FURNACE_DATA = 3;
	public const ENTRY_MULTI = 4;
	public const ENTRY_USER_DATA_SHAPELESS = 5;
	public const ENTRY_SHAPELESS_CHEMISTRY = 6;
	public const ENTRY_SHAPED_CHEMISTRY = 7;
	public const ENTRY_SMITHING_TRANSFORM = 8;
	public const ENTRY_SMITHING_TRIM = 9;

	/**
	 * 1.26.40+ wire recipe type IDs (bucketed format; furnace types removed).
	 * SHAPED/SHAPELESS are swapped vs pre-1.26.40.
	 */
	public const WIRE_1_26_40_SHAPED = 0;
	public const WIRE_1_26_40_SHAPELESS = 1;
	public const WIRE_1_26_40_MULTI = 2;
	public const WIRE_1_26_40_USER_DATA_SHAPELESS = 3;
	public const WIRE_1_26_40_SHAPELESS_CHEMISTRY = 4;
	public const WIRE_1_26_40_SHAPED_CHEMISTRY = 5;
	public const WIRE_1_26_40_SMITHING_TRANSFORM = 6;
	public const WIRE_1_26_40_SMITHING_TRIM = 7;

	/** @var RecipeWithTypeId[] */
	public array $recipesWithTypeIds = [];
	/** @var PotionTypeRecipe[] */
	public array $potionTypeRecipes = [];
	/** @var PotionContainerChangeRecipe[] */
	public array $potionContainerRecipes = [];
	/** @var MaterialReducerRecipe[] */
	public array $materialReducerRecipes = [];
	public bool $cleanRecipes = false;

	/**
	 * @generate-create-func
	 * @param RecipeWithTypeId[]            $recipesWithTypeIds
	 * @param PotionTypeRecipe[]            $potionTypeRecipes
	 * @param PotionContainerChangeRecipe[] $potionContainerRecipes
	 * @param MaterialReducerRecipe[]       $materialReducerRecipes
	 */
	public static function create(array $recipesWithTypeIds, array $potionTypeRecipes, array $potionContainerRecipes, array $materialReducerRecipes, bool $cleanRecipes) : self{
		$result = new self;
		$result->recipesWithTypeIds = $recipesWithTypeIds;
		$result->potionTypeRecipes = $potionTypeRecipes;
		$result->potionContainerRecipes = $potionContainerRecipes;
		$result->materialReducerRecipes = $materialReducerRecipes;
		$result->cleanRecipes = $cleanRecipes;
		return $result;
	}

	/**
	 * Maps internal ENTRY_* type IDs to protocol wire values.
	 */
	public static function internalTypeToWire(int $internalType, int $protocolId) : int{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			return $internalType;
		}

		return match($internalType){
			self::ENTRY_SHAPED => self::WIRE_1_26_40_SHAPED,
			self::ENTRY_SHAPELESS => self::WIRE_1_26_40_SHAPELESS,
			self::ENTRY_MULTI => self::WIRE_1_26_40_MULTI,
			self::ENTRY_USER_DATA_SHAPELESS => self::WIRE_1_26_40_USER_DATA_SHAPELESS,
			self::ENTRY_SHAPELESS_CHEMISTRY => self::WIRE_1_26_40_SHAPELESS_CHEMISTRY,
			self::ENTRY_SHAPED_CHEMISTRY => self::WIRE_1_26_40_SHAPED_CHEMISTRY,
			self::ENTRY_SMITHING_TRANSFORM => self::WIRE_1_26_40_SMITHING_TRANSFORM,
			self::ENTRY_SMITHING_TRIM => self::WIRE_1_26_40_SMITHING_TRIM,
			default => throw new \InvalidArgumentException("Recipe type $internalType has no 1.26.40 wire mapping"),
		};
	}

	/**
	 * Maps protocol wire values back to internal ENTRY_* type IDs.
	 */
	public static function wireTypeToInternal(int $wireType, int $protocolId) : int{
		if($protocolId < ProtocolInfo::PROTOCOL_1_26_40){
			return $wireType;
		}

		return match($wireType){
			self::WIRE_1_26_40_SHAPED => self::ENTRY_SHAPED,
			self::WIRE_1_26_40_SHAPELESS => self::ENTRY_SHAPELESS,
			self::WIRE_1_26_40_MULTI => self::ENTRY_MULTI,
			self::WIRE_1_26_40_USER_DATA_SHAPELESS => self::ENTRY_USER_DATA_SHAPELESS,
			self::WIRE_1_26_40_SHAPELESS_CHEMISTRY => self::ENTRY_SHAPELESS_CHEMISTRY,
			self::WIRE_1_26_40_SHAPED_CHEMISTRY => self::ENTRY_SHAPED_CHEMISTRY,
			self::WIRE_1_26_40_SMITHING_TRANSFORM => self::ENTRY_SMITHING_TRANSFORM,
			self::WIRE_1_26_40_SMITHING_TRIM => self::ENTRY_SMITHING_TRIM,
			default => throw new PacketDecodeException("Unknown 1.26.40 recipe wire type $wireType"),
		};
	}

	private static function decodeRecipe(int $internalType, ByteBufferReader $in, int $protocolId) : RecipeWithTypeId{
		return match($internalType){
			self::ENTRY_SHAPELESS, self::ENTRY_USER_DATA_SHAPELESS, self::ENTRY_SHAPELESS_CHEMISTRY => ShapelessRecipe::decode($internalType, $in, $protocolId),
			self::ENTRY_SHAPED, self::ENTRY_SHAPED_CHEMISTRY => ShapedRecipe::decode($internalType, $in, $protocolId),
			self::ENTRY_FURNACE, self::ENTRY_FURNACE_DATA => FurnaceRecipe::decode($internalType, $in, $protocolId),
			self::ENTRY_MULTI => MultiRecipe::decode($internalType, $in, $protocolId),
			self::ENTRY_SMITHING_TRANSFORM => SmithingTransformRecipe::decode($internalType, $in, $protocolId),
			self::ENTRY_SMITHING_TRIM => SmithingTrimRecipe::decode($internalType, $in, $protocolId),
			default => throw new PacketDecodeException("Unhandled recipe type $internalType"),
		};
	}

	protected function decodePayload(ByteBufferReader $in, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			$decoders = [
				self::ENTRY_SHAPED => ShapedRecipe::decode(...),
				self::ENTRY_SHAPELESS => ShapelessRecipe::decode(...),
				self::ENTRY_MULTI => MultiRecipe::decode(...),
				self::ENTRY_USER_DATA_SHAPELESS => ShapelessRecipe::decode(...),
				self::ENTRY_SHAPELESS_CHEMISTRY => ShapelessRecipe::decode(...),
				self::ENTRY_SHAPED_CHEMISTRY => ShapedRecipe::decode(...),
				self::ENTRY_SMITHING_TRANSFORM => SmithingTransformRecipe::decode(...),
				self::ENTRY_SMITHING_TRIM => SmithingTrimRecipe::decode(...),
			];
			foreach($decoders as $typeId => $decoder){
				for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
					$this->recipesWithTypeIds[] = $decoder($typeId, $in, $protocolId);
				}
			}
		}else{
			$recipeCount = VarInt::readUnsignedInt($in);
			$previousType = "none";
			for($i = 0; $i < $recipeCount; ++$i){
				$recipeType = VarInt::readSignedInt($in);
				try{
					$this->recipesWithTypeIds[] = self::decodeRecipe($recipeType, $in, $protocolId);
				}catch(PacketDecodeException $e){
					throw new PacketDecodeException($e->getMessage() . " (previous was $previousType)", 0, $e);
				}
				$previousType = (string) $recipeType;
			}
		}
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$inputId = VarInt::readSignedInt($in);
			$inputMeta = $protocolId >= ProtocolInfo::PROTOCOL_1_16_0 ? VarInt::readSignedInt($in) : 0;
			$ingredientId = VarInt::readSignedInt($in);
			$ingredientMeta = $protocolId >= ProtocolInfo::PROTOCOL_1_16_0 ? VarInt::readSignedInt($in) : 0;
			$outputId = VarInt::readSignedInt($in);
			$outputMeta = $protocolId >= ProtocolInfo::PROTOCOL_1_16_0 ? VarInt::readSignedInt($in) : 0;
			$this->potionTypeRecipes[] = new PotionTypeRecipe($inputId, $inputMeta, $ingredientId, $ingredientMeta, $outputId, $outputMeta);
		}
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$input = VarInt::readSignedInt($in);
			$ingredient = VarInt::readSignedInt($in);
			$output = VarInt::readSignedInt($in);
			$this->potionContainerRecipes[] = new PotionContainerChangeRecipe($input, $ingredient, $output);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_17_30){
			for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
				$inputIdAndData = VarInt::readSignedInt($in);
				[$inputId, $inputMeta] = [$inputIdAndData >> 16, $inputIdAndData & 0x7fff];
				$outputs = [];
				for($j = 0, $outputCount = VarInt::readUnsignedInt($in); $j < $outputCount; ++$j){
					$outputItemId = VarInt::readSignedInt($in);
					$outputItemCount = VarInt::readSignedInt($in);
					$outputs[] = new MaterialReducerRecipeOutput($outputItemId, $outputItemCount);
				}
				$this->materialReducerRecipes[] = new MaterialReducerRecipe($inputId, $inputMeta, $outputs);
			}
		}
		$this->cleanRecipes = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
			// Bucketed by internal type in wire order (SHAPED first; furnace types removed)
			$buckets = [
				self::ENTRY_SHAPED => [],
				self::ENTRY_SHAPELESS => [],
				self::ENTRY_MULTI => [],
				self::ENTRY_USER_DATA_SHAPELESS => [],
				self::ENTRY_SHAPELESS_CHEMISTRY => [],
				self::ENTRY_SHAPED_CHEMISTRY => [],
				self::ENTRY_SMITHING_TRANSFORM => [],
				self::ENTRY_SMITHING_TRIM => [],
			];
			foreach($this->recipesWithTypeIds as $recipe){
				$typeId = $recipe->getTypeId();
				if(!isset($buckets[$typeId])){
					throw new \InvalidArgumentException("Unhandled recipe type $typeId for protocol 1.26.40");
				}
				$buckets[$typeId][] = $recipe;
			}
			foreach($buckets as $recipes){
				VarInt::writeUnsignedInt($out, count($recipes));
				foreach($recipes as $recipe){
					$recipe->encode($out, $protocolId);
				}
			}
		}else{
			VarInt::writeUnsignedInt($out, count($this->recipesWithTypeIds));
			foreach($this->recipesWithTypeIds as $d){
				VarInt::writeSignedInt($out, $d->getTypeId());
				$d->encode($out, $protocolId);
			}
		}
		VarInt::writeUnsignedInt($out, count($this->potionTypeRecipes));
		foreach($this->potionTypeRecipes as $recipe){
			VarInt::writeSignedInt($out, $recipe->getInputItemId());
			if($protocolId >= ProtocolInfo::PROTOCOL_1_16_0){
				VarInt::writeSignedInt($out, $recipe->getInputItemMeta());
			}
			VarInt::writeSignedInt($out, $recipe->getIngredientItemId());
			if($protocolId >= ProtocolInfo::PROTOCOL_1_16_0){
				VarInt::writeSignedInt($out, $recipe->getIngredientItemMeta());
			}
			VarInt::writeSignedInt($out, $recipe->getOutputItemId());
			if($protocolId >= ProtocolInfo::PROTOCOL_1_16_0){
				VarInt::writeSignedInt($out, $recipe->getOutputItemMeta());
			}
		}
		VarInt::writeUnsignedInt($out, count($this->potionContainerRecipes));
		foreach($this->potionContainerRecipes as $recipe){
			VarInt::writeSignedInt($out, $recipe->getInputItemId());
			VarInt::writeSignedInt($out, $recipe->getIngredientItemId());
			VarInt::writeSignedInt($out, $recipe->getOutputItemId());
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_17_30){
			VarInt::writeUnsignedInt($out, count($this->materialReducerRecipes));
			foreach($this->materialReducerRecipes as $recipe){
				VarInt::writeSignedInt($out, ($recipe->getInputItemId() << 16) | $recipe->getInputItemMeta());
				VarInt::writeUnsignedInt($out, count($recipe->getOutputs()));
				foreach($recipe->getOutputs() as $output){
					VarInt::writeSignedInt($out, $output->getItemId());
					VarInt::writeSignedInt($out, $output->getCount());
				}
			}
		}
		CommonTypes::putBool($out, $this->cleanRecipes);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleCraftingData($this);
	}
}
