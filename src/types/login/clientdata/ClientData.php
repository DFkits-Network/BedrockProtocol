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

namespace pocketmine\network\mcpe\protocol\types\login\clientdata;

/**
 * Model class for LoginPacket JSON data for JsonMapper
 */
final class ClientData{

	/**
	 * @var ClientDataAnimationFrame[]
	 * >= PROTOCOL_1_13_0
	 */
	public array $AnimatedImageData = [];

	/** >= PROTOCOL_1_14_60 */
	public string $ArmSize;

	/** @required */
	public string $CapeData;

	/** >= PROTOCOL_1_13_0 */
	public string $CapeId;

	/** >= PROTOCOL_1_13_0 */
	public int $CapeImageHeight;

	/** >= PROTOCOL_1_13_0 */
	public int $CapeImageWidth;

	/** >= PROTOCOL_1_13_0 */
	public bool $CapeOnClassicSkin;

	/** >= ProtocolInfo::PROTOCOL_1_26_30 */
	public int $ClientEditorConnectionIntent;

	/** >= ProtocolInfo::PROTOCOL_1_26_30 */
	public bool $ClientIsEditorCapable;

	/** @required */
	public int $ClientRandomId;

	/** >= ProtocolInfo::PROTOCOL_1_19_80 */
	public bool $CompatibleWithClientSideChunkGen = false;

	/** @required */
	public int $CurrentInputMode;

	/** @required */
	public int $DefaultInputMode;

	/** @required */
	public string $DeviceId;

	/** @required */
	public string $DeviceModel;

	/** @required */
	public int $DeviceOS;

	/** >= ProtocolInfo::PROTOCOL_1_26_20 */
	public bool $FilterProfanity;

	/** @required */
	public string $GameVersion;

	/** >= ProtocolInfo::PROTOCOL_1_21_70 */
	public int $GraphicsMode;

	/** @required */
	public int $GuiScale;

	/** <= ProtocolInfo::PROTOCOL_1_26_20 */
	public bool $IsEditorMode = false;

	/** @required */
	public string $LanguageCode;

	/** >= ProtocolInfo::PROTOCOL_1_21_40 */
	public int $MaxViewDistance;

	/** >= ProtocolInfo::PROTOCOL_1_21_40 */
	public int $MemoryTier;

	public bool $OverrideSkin;

	public string $PartyId;
	/** >= ProtocolInfo::PROTOCOL_1_26_20 */
	public bool $IsPartyLeader;

	/**
	 * @var ClientDataPersonaSkinPiece[]
	 * >= PROTOCOL_1_14_60
	 */
	public array $PersonaPieces;

	/** >= PROTOCOL_1_13_0 */
	public bool $PersonaSkin;

	/**
	 * @var ClientDataPersonaPieceTintColor[]
	 * >= PROTOCOL_1_14_60
	 */
	public array $PieceTintColors;

	/** @required */
	public string $PlatformOfflineId;

	/** @required */
	public string $PlatformOnlineId;

	/** >= ProtocolInfo::PROTOCOL_1_21_40 */
	public int $PlatformType;

	public string $PlatformUserId = ""; //xbox-only, apparently

	/** < ProtocolInfo::PROTOCOL_1_21_111 */
	public string $PlayFabId;

	/** @required */
	public bool $PremiumSkin = false;

	/** @required */
	public string $SelfSignedId;

	/** @required */
	public string $ServerAddress;

	/** >= PROTOCOL_1_13_0 */
	public string $SkinAnimationData;

	/** >= PROTOCOL_1_14_60 */
	public string $SkinColor;

	/** @required */
	public string $SkinData;

	/** <= PROTOCOL_1_12_0 */
	public string $SkinGeometryName;

	/** <= PROTOCOL_1_12_0 */
	public string $SkinGeometry;

	/** >= PROTOCOL_1_13_0 */
	public string $SkinGeometryData;

	/** >= ProtocolInfo::PROTOCOL_1_17_30 */
	public string $SkinGeometryDataEngineVersion;

	/** @required */
	public string $SkinId;

	/** >= PROTOCOL_1_13_0 */
	public int $SkinImageHeight;

	/** >= PROTOCOL_1_13_0 */
	public int $SkinImageWidth;

	/** >= PROTOCOL_1_13_0 */
	public string $SkinResourcePatch;

	/** @required */
	public string $ThirdPartyName;

	/** <= ProtocolInfo::PROTOCOL_1_21_80 */
	public bool $ThirdPartyNameOnly;

	/** >= ProtocolInfo::PROTOCOL_1_19_20 */
	public bool $TrustedSkin = false;

	/** >= ProtocolInfo::PROTOCOL_1_26_40 */
	public string $ProfileHash = "";

	/** >= ProtocolInfo::PROTOCOL_1_26_40 */
	public string $Nonce = "";

	/** @required */
	public int $UIProfile;
}
