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

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\ByteBufferWriter;

/**
 * This is used for PlayerAuthInput block actions which don't contain a block position or face.
 */
final class PlayerBlockActionWithoutBlockInfo implements PlayerBlockAction{

	public function __construct(
		private int $actionType
	){
		if(!self::isValidActionType($actionType)){
			throw new \InvalidArgumentException("Invalid action type for " . self::class);
		}
	}

	public function getActionType() : int{
		return $this->actionType;
	}

	public function write(ByteBufferWriter $out) : void{
		//NOOP
	}

	public static function isValidActionType(int $actionType) : bool{
		return match($actionType){
			PlayerAction::GET_UPDATED_BLOCK,
			PlayerAction::DROP_ITEM,
			PlayerAction::START_SLEEPING,
			PlayerAction::STOP_SLEEPING,
			PlayerAction::RESPAWN,
			PlayerAction::JUMP,
			PlayerAction::START_SPRINT,
			PlayerAction::STOP_SPRINT,
			PlayerAction::START_SNEAK,
			PlayerAction::STOP_SNEAK,
			PlayerAction::CREATIVE_PLAYER_DESTROY_BLOCK,
			PlayerAction::DIMENSION_CHANGE_ACK,
			PlayerAction::START_GLIDE,
			PlayerAction::STOP_GLIDE,
			PlayerAction::BUILD_DENIED,
			PlayerAction::CHANGE_SKIN,
			PlayerAction::SET_ENCHANTMENT_SEED,
			PlayerAction::START_SWIMMING,
			PlayerAction::STOP_SWIMMING,
			PlayerAction::START_SPIN_ATTACK,
			PlayerAction::STOP_SPIN_ATTACK,
			PlayerAction::INTERACT_BLOCK,
			PlayerAction::START_ITEM_USE_ON,
			PlayerAction::STOP_ITEM_USE_ON,
			PlayerAction::HANDLED_TELEPORT,
			PlayerAction::MISSED_SWING,
			PlayerAction::START_CRAWLING,
			PlayerAction::STOP_CRAWLING,
			PlayerAction::START_FLYING,
			PlayerAction::STOP_FLYING,
			PlayerAction::START_USING_ITEM => true,
			default => false
		};
	}
}
