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
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

final class ItemStackRequest{
	/**
	 * @param ItemStackRequestAction[] $actions
	 * @param string[]                 $filterStrings
	 * @phpstan-param list<string> $filterStrings
	 */
	public function __construct(
		private int $requestId,
		private array $actions,
		private array $filterStrings,
		private int $filterStringCause
	){}

	public function getRequestId() : int{ return $this->requestId; }

	/** @return ItemStackRequestAction[] */
	public function getActions() : array{ return $this->actions; }

	/**
	 * @return string[]
	 * @phpstan-return list<string>
	 */
	public function getFilterStrings() : array{ return $this->filterStrings; }

	public function getFilterStringCause() : int{ return $this->filterStringCause; }

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	private static function readAction(ByteBufferReader $in, int $protocolId, int $typeId) : ItemStackRequestAction{
		return match($typeId){
			TakeStackRequestAction::ID => TakeStackRequestAction::read($in, $protocolId),
			PlaceStackRequestAction::ID => PlaceStackRequestAction::read($in, $protocolId),
			SwapStackRequestAction::ID => SwapStackRequestAction::read($in, $protocolId),
			DropStackRequestAction::ID => DropStackRequestAction::read($in, $protocolId),
			DestroyStackRequestAction::ID => DestroyStackRequestAction::read($in, $protocolId),
			CraftingConsumeInputStackRequestAction::ID => CraftingConsumeInputStackRequestAction::read($in, $protocolId),
			CraftingCreateSpecificResultStackRequestAction::ID => CraftingCreateSpecificResultStackRequestAction::read($in, $protocolId),
			PlaceIntoBundleStackRequestAction::ID => PlaceIntoBundleStackRequestAction::read($in, $protocolId),
			TakeFromBundleStackRequestAction::ID => TakeFromBundleStackRequestAction::read($in, $protocolId),
			LabTableCombineStackRequestAction::ID => LabTableCombineStackRequestAction::read($in, $protocolId),
			BeaconPaymentStackRequestAction::ID => BeaconPaymentStackRequestAction::read($in, $protocolId),
			MineBlockStackRequestAction::ID => MineBlockStackRequestAction::read($in, $protocolId),
			CraftRecipeStackRequestAction::ID => CraftRecipeStackRequestAction::read($in, $protocolId),
			CraftRecipeAutoStackRequestAction::ID => CraftRecipeAutoStackRequestAction::read($in, $protocolId),
			CreativeCreateStackRequestAction::ID => CreativeCreateStackRequestAction::read($in, $protocolId),
			CraftRecipeOptionalStackRequestAction::ID => CraftRecipeOptionalStackRequestAction::read($in, $protocolId),
			GrindstoneStackRequestAction::ID => GrindstoneStackRequestAction::read($in, $protocolId),
			LoomStackRequestAction::ID => LoomStackRequestAction::read($in, $protocolId),
			DeprecatedCraftingNonImplementedStackRequestAction::ID => DeprecatedCraftingNonImplementedStackRequestAction::read($in, $protocolId),
			DeprecatedCraftingResultsStackRequestAction::ID => DeprecatedCraftingResultsStackRequestAction::read($in, $protocolId),
			default => throw new PacketDecodeException("Unhandled item stack request action type $typeId"),
		};
	}

	/**
	 * Item stack request action IDs changed several times before 1.18.10:
	 * - 1.16.200 added the optional crafting action;
	 * - 1.16.210 inserted the mine-block action;
	 * - 1.17.40 inserted the grindstone and loom actions;
	 * - 1.18.10 inserted the two bundle actions used by the current ID table.
	 *
	 * Translate the protocol-specific value to the current canonical value before dispatching it.
	 *
	 * @throws PacketDecodeException
	 */
	private static function actionTypeFromNetwork(int $typeId, int $protocolId) : int{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_18_10){
			return $typeId;
		}

		return match($typeId){
			0, 1, 2, 3, 4, 5, 6 => $typeId,
			7 => ItemStackRequestActionType::LAB_TABLE_COMBINE,
			8 => ItemStackRequestActionType::BEACON_PAYMENT,
			9 => $protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ?
				ItemStackRequestActionType::MINE_BLOCK :
				ItemStackRequestActionType::CRAFTING_RECIPE,
			10 => $protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ?
				ItemStackRequestActionType::CRAFTING_RECIPE :
				ItemStackRequestActionType::CRAFTING_RECIPE_AUTO,
			11 => $protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ?
				ItemStackRequestActionType::CRAFTING_RECIPE_AUTO :
				ItemStackRequestActionType::CREATIVE_CREATE,
			12 => $protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ?
				ItemStackRequestActionType::CREATIVE_CREATE :
				($protocolId >= ProtocolInfo::PROTOCOL_1_16_200 ?
					ItemStackRequestActionType::CRAFTING_RECIPE_OPTIONAL :
					ItemStackRequestActionType::CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING),
			13 => $protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ?
				ItemStackRequestActionType::CRAFTING_RECIPE_OPTIONAL :
				($protocolId >= ProtocolInfo::PROTOCOL_1_16_200 ?
					ItemStackRequestActionType::CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING :
					ItemStackRequestActionType::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING),
			14 => $protocolId >= ProtocolInfo::PROTOCOL_1_17_40 ?
				ItemStackRequestActionType::CRAFTING_GRINDSTONE :
				($protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ?
					ItemStackRequestActionType::CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING :
					($protocolId >= ProtocolInfo::PROTOCOL_1_16_200 ?
						ItemStackRequestActionType::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING :
						throw new PacketDecodeException("Unhandled item stack request action type $typeId"))),
			15 => $protocolId >= ProtocolInfo::PROTOCOL_1_17_40 ?
				ItemStackRequestActionType::CRAFTING_LOOM :
				($protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ?
					ItemStackRequestActionType::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING :
					throw new PacketDecodeException("Unhandled item stack request action type $typeId")),
			16 => $protocolId >= ProtocolInfo::PROTOCOL_1_17_40 ?
				ItemStackRequestActionType::CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING :
				throw new PacketDecodeException("Unhandled item stack request action type $typeId"),
			17 => $protocolId >= ProtocolInfo::PROTOCOL_1_17_40 ?
				ItemStackRequestActionType::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING :
				throw new PacketDecodeException("Unhandled item stack request action type $typeId"),
			default => throw new PacketDecodeException("Unhandled item stack request action type $typeId"),
		};
	}

	private static function actionTypeToNetwork(int $typeId, int $protocolId) : int{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_18_10){
			return $typeId;
		}

		return match($typeId){
			0, 1, 2, 3, 4, 5, 6 => $typeId,
			ItemStackRequestActionType::LAB_TABLE_COMBINE => 7,
			ItemStackRequestActionType::BEACON_PAYMENT => 8,
			ItemStackRequestActionType::MINE_BLOCK => $protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ?
				9 :
				throw new \InvalidArgumentException("Mine-block stack request action is not supported by protocol $protocolId"),
			ItemStackRequestActionType::CRAFTING_RECIPE => $protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ? 10 : 9,
			ItemStackRequestActionType::CRAFTING_RECIPE_AUTO => $protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ? 11 : 10,
			ItemStackRequestActionType::CREATIVE_CREATE => $protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ? 12 : 11,
			ItemStackRequestActionType::CRAFTING_RECIPE_OPTIONAL => $protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ?
				13 :
				($protocolId >= ProtocolInfo::PROTOCOL_1_16_200 ?
					12 :
					throw new \InvalidArgumentException("Optional crafting stack request action is not supported by protocol $protocolId")),
			ItemStackRequestActionType::CRAFTING_GRINDSTONE => $protocolId >= ProtocolInfo::PROTOCOL_1_17_40 ?
				14 :
				throw new \InvalidArgumentException("Grindstone stack request action is not supported by protocol $protocolId"),
			ItemStackRequestActionType::CRAFTING_LOOM => $protocolId >= ProtocolInfo::PROTOCOL_1_17_40 ?
				15 :
				throw new \InvalidArgumentException("Loom stack request action is not supported by protocol $protocolId"),
			ItemStackRequestActionType::CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING => $protocolId >= ProtocolInfo::PROTOCOL_1_17_40 ?
				16 :
				($protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ?
					14 :
					($protocolId >= ProtocolInfo::PROTOCOL_1_16_200 ? 13 : 12)),
			ItemStackRequestActionType::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING => $protocolId >= ProtocolInfo::PROTOCOL_1_17_40 ?
				17 :
				($protocolId >= ProtocolInfo::PROTOCOL_1_16_210 ?
					15 :
					($protocolId >= ProtocolInfo::PROTOCOL_1_16_200 ? 14 : 13)),
			default => throw new \InvalidArgumentException("Item stack request action type $typeId is not supported by protocol $protocolId"),
		};
	}

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$requestId = CommonTypes::readItemStackRequestId($in);
		$actions = [];
		for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				//v2168: the primary type ID is a VarUInt using the compact (bundle-removed) numbering,
				//followed by a duplicate type byte (legacy numbering) which is discarded
				$typeId = ItemStackRequestActionType::fromModernTypeId(VarInt::readUnsignedInt($in));
				Byte::readUnsigned($in);
			}else{
				$typeId = self::actionTypeFromNetwork(Byte::readUnsigned($in), $protocolId);
			}
			$actions[] = self::readAction($in, $protocolId, $typeId);
		}
		$filterStrings = [];
		if($protocolId >= ProtocolInfo::PROTOCOL_1_16_200){
			for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
				$filterStrings[] = CommonTypes::getString($in);
			}
		}
		$filterStringCause = $protocolId >= ProtocolInfo::PROTOCOL_1_19_50 ?
			LE::readSignedInt($in) :
			0;
		return new self($requestId, $actions, $filterStrings, $filterStringCause);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::writeItemStackRequestId($out, $this->requestId);
		VarInt::writeUnsignedInt($out, count($this->actions));
		foreach($this->actions as $action){
			if($protocolId >= ProtocolInfo::PROTOCOL_1_26_40){
				VarInt::writeUnsignedInt($out, ItemStackRequestActionType::toModernTypeId($action->getTypeId()));
				Byte::writeUnsigned($out, $action->getTypeId()); //duplicate type byte (legacy numbering)
			}else{
				$typeId = self::actionTypeToNetwork($action->getTypeId(), $protocolId);
				Byte::writeUnsigned($out, $typeId);
			}
			$action->write($out, $protocolId);
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_16_200){
			VarInt::writeUnsignedInt($out, count($this->filterStrings));
			foreach($this->filterStrings as $string){
				CommonTypes::putString($out, $string);
			}
		}
		if($protocolId >= ProtocolInfo::PROTOCOL_1_19_50){
			LE::writeSignedInt($out, $this->filterStringCause);
		}
	}
}
