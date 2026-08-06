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

/**
 * @see MemoryCategoryCounter
 */
final class MemoryCategory{
	public const UNKNOWN = 0;
	public const INVALID_SIZE_UNKNOWN = 1;
	public const ACTOR = 2;
	public const ACTOR_ANIMATION = 3;
	public const ACTOR_RENDERING = 4;
	public const BALANCER = 5;
	public const BLOCK_TICKING_QUEUES = 6;
	public const BIOME_STORAGE = 7;
	public const CEREAL = 8;
	public const CIRCUIT_SYSTEM = 9;
	public const CLIENT = 10;
	public const COMMANDS = 11;
	public const DB_STORAGE = 12;
	public const DEBUG = 13;
	public const DOCUMENTATION = 14;
	public const ECS_SYSTEMS = 15;
	public const FMOD = 16;
	public const FONTS = 17;
	public const IM_GUI = 18;
	public const INPUT = 19;
	public const JSON_UI = 20;
	public const JSON_UI_CONTROL_FACTORY_JSON = 21;
	public const JSON_UI_CONTROL_TREE = 22;
	public const JSON_UI_CONTROL_TREE_CONTROL_ELEMENT = 23;
	public const JSON_UI_CONTROL_TREE_POPULATE_DATA_BINDING = 24;
	public const JSON_UI_CONTROL_TREE_POPULATE_FOCUS = 25;
	public const JSON_UI_CONTROL_TREE_POPULATE_LAYOUT = 26;
	public const JSON_UI_CONTROL_TREE_POPULATE_OTHER = 27;
	public const JSON_UI_CONTROL_TREE_POPULATE_SPRITE = 28;
	public const JSON_UI_CONTROL_TREE_POPULATE_TEXT = 29;
	public const JSON_UI_CONTROL_TREE_POPULATE_TTS = 30;
	public const JSON_UI_CONTROL_TREE_VISIBILITY = 31;
	public const JSON_UI_CREATE_UI = 32;
	public const JSON_UI_DEFS = 33;
	public const JSON_UI_LAYOUT_MANAGER = 34;
	public const JSON_UI_LAYOUT_MANAGER_REMOVE_DEPENDENCIES = 35;
	public const JSON_UI_LAYOUT_MANAGER_INIT_VARIABLE = 36;
	public const LANGUAGES = 37;
	public const LEVEL = 38;
	public const LEVEL_STRUCTURES = 39;
	public const LEVEL_CHUNK = 40;
	public const LEVEL_CHUNK_GEN = 41;
	public const LEVEL_CHUNK_GEN_THREAD_LOCAL = 42;
	public const NETWORK = 43;
	public const MARKETPLACE = 44;
	public const MATERIAL_DRAGON_COMPILED_DEFINITION = 45;
	public const MATERIAL_DRAGON_MATERIAL = 46;
	public const MATERIAL_DRAGON_RESOURCE = 47;
	public const MATERIAL_DRAGON_UNIFORM_MAP = 48;
	public const MATERIAL_RENDER_MATERIAL = 49;
	public const MATERIAL_RENDER_MATERIAL_GROUP = 50;
	public const MATERIAL_VARIATION_MANAGER = 51;
	public const MOLANG = 52;
	public const ORE_UI = 53;
	public const PERSONA = 54;
	public const PLAYER = 55;
	public const RENDER_CHUNK = 56;
	public const RENDER_CHUNK_INDEX_BUFFER = 57;
	public const RENDER_CHUNK_VERTEX_BUFFER = 58;
	public const RENDERING = 59;
	public const RENDERING_LIBRARY = 60;
	public const REQUEST_LOG = 61;
	public const RESOURCE_PACKS = 62;
	public const SOUND = 63;
	public const SUB_CHUNK_BIOME_DATA = 64;
	public const SUB_CHUNK_BLOCK_DATA = 65;
	public const SUB_CHUNK_LIGHT_DATA = 66;
	public const TEXTURES = 67;
	public const VR = 68;
	public const WEATHER_RENDERER = 69;
	public const WORLD_GENERATOR = 70;
	public const TASKS = 71;
	public const TEST = 72;
	public const SCRIPTING = 73;
	public const SCRIPTING_RUNTIME = 74;
	public const SCRIPTING_CONTEXT = 75;
	public const SCRIPTING_CONTEXT_BINDINGS_MC = 76;
	public const SCRIPTING_CONTEXT_BINDINGS_GT = 77;
	public const SCRIPTING_CONTEXT_RUN = 78;
	public const DATA_DRIVEN_UI = 79;
	public const DATA_DRIVEN_UI_DEFS = 80;

	/*
	 * Memory category IDs were reordered in 1.26.40. The unprefixed constants
	 * above intentionally retain their pre-1.26.40 values so existing callers
	 * keep writing the same legacy IDs. Use these constants for 1.26.40 data.
	 * IDs not listed here are unchanged between the two versions.
	 */
	public const V1_26_40_BLOCK_TICKING_QUEUES = 5;
	public const V1_26_40_BIOME_STORAGE = 6;
	public const V1_26_40_BLOBS = 7;
	public const V1_26_40_LIGHT_VOLUME_MANAGER = 43;
	public const V1_26_40_NETWORK = 44;
	public const V1_26_40_MARKETPLACE = 45;
	public const V1_26_40_MATERIAL_DRAGON_COMPILED_DEFINITION = 46;
	public const V1_26_40_MATERIAL_DRAGON_MATERIAL = 47;
	public const V1_26_40_MATERIAL_DRAGON_RESOURCE = 48;
	public const V1_26_40_MATERIAL_DRAGON_UNIFORM_MAP = 49;
	public const V1_26_40_MATERIAL_RENDER_MATERIAL = 50;
	public const V1_26_40_MATERIAL_RENDER_MATERIAL_GROUP = 51;
	public const V1_26_40_MATERIAL_VARIATION_MANAGER = 52;
	public const V1_26_40_MOLANG = 53;
	public const V1_26_40_ORE_UI = 54;
	public const V1_26_40_ORE_UI_CLIENT = 55;
	public const V1_26_40_PERSONA_PIECES = 56;
	public const V1_26_40_PERSONA_ANIMATIONS = 57;
	public const V1_26_40_PERSONA_TEXTURES = 58;
	public const V1_26_40_PERSONA_CHARACTERS = 59;
	public const V1_26_40_PERSONA_SKIN_PACKS = 60;
	public const V1_26_40_PERSONA_REPO = 61;
	public const V1_26_40_PLAYER = 62;
	public const V1_26_40_RENDER_CHUNK = 63;
	public const V1_26_40_RENDER_CHUNK_INDEX_BUFFER = 64;
	public const V1_26_40_RENDER_CHUNK_VERTEX_BUFFER = 65;
	public const V1_26_40_RENDERING = 66;
	public const V1_26_40_RENDERING_BGFX_INIT = 67;
	public const V1_26_40_RENDERING_BGFX_START_FRAME = 68;
	public const V1_26_40_RENDERING_BGFX_TESSELLATOR = 69;
	public const V1_26_40_RENDERING_BGFX_END_FRAME = 70;
	public const V1_26_40_RENDERING_BGFX_GRAPHICS_TASKS_INIT = 71;
	public const V1_26_40_RENDERING_LIBRARY = 72;
	public const V1_26_40_RENDERING_POLYGON_OPERATOR_POOL = 73;
	public const V1_26_40_RENDERING_PBR_TEXTURE_DATA = 74;
	public const V1_26_40_RENDERING_RENDER_REGISTRY = 75;
	public const V1_26_40_RENDERING_SETUP = 76;
	public const V1_26_40_RENDERING_VERTICES = 77;
	public const V1_26_40_REQUEST_LOG = 78;
	public const V1_26_40_RESOURCE_PACKS = 79;
	public const V1_26_40_SOUND = 80;
	public const V1_26_40_SUB_CHUNK_BIOME_DATA = 81;
	public const V1_26_40_SUB_CHUNK_BLOCK_DATA = 82;
	public const V1_26_40_SUB_CHUNK_LIGHT_DATA = 83;
	public const V1_26_40_TEXTURES = 84;
	public const V1_26_40_WEATHER_RENDERER = 85;
	public const V1_26_40_WORLD_GENERATOR = 86;
	public const V1_26_40_TASKS = 87;
	public const V1_26_40_TEST = 88;
	public const V1_26_40_TEST_LOAD_TEST_FLAGS = 89;
	public const V1_26_40_SCRIPTING = 90;
	public const V1_26_40_SCRIPTING_RUNTIME = 91;
	public const V1_26_40_SCRIPTING_CONTEXT = 92;
	public const V1_26_40_SCRIPTING_CONTEXT_BINDINGS_MC = 93;
	public const V1_26_40_SCRIPTING_CONTEXT_BINDINGS_GT = 94;
	public const V1_26_40_SCRIPTING_CONTEXT_RUN = 95;
	public const V1_26_40_DATA_DRIVEN_UI = 96;
	public const V1_26_40_DATA_DRIVEN_UI_DEFS = 97;
	public const V1_26_40_GAMEFACE = 98;
	public const V1_26_40_GAMEFACE_SYSTEM = 99;
	public const V1_26_40_GAMEFACE_DOM = 100;
	public const V1_26_40_GAMEFACE_CSS = 101;
	public const V1_26_40_GAMEFACE_DISPLAY = 102;
	public const V1_26_40_GAMEFACE_TEMP_ALLOCATOR = 103;
	public const V1_26_40_GAMEFACE_POOL_ALLOCATOR = 104;
	public const V1_26_40_GAMEFACE_DUMP = 105;
	public const V1_26_40_GAMEFACE_MEDIA = 106;
	public const V1_26_40_GAMEFACE_JSON = 107;
	public const V1_26_40_GAMEFACE_SCRIPT_ENGINE = 108;
	public const V1_26_40_GAMEFACE_SCRIPT = 109;
	public const V1_26_40_GAMEFACE_LAYOUT = 110;
}
