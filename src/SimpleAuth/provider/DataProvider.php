<?php

/*
 * SimpleAuth plugin for PocketMine-MP
 * Copyright (C) 2014 PocketMine Team <https://github.com/PocketMine/SimpleAuth>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*/

declare(strict_types=1);

namespace SimpleAuth\provider;

use pocketmine\IPlayer;
use pocketmine\Player;
use pocketmine\OfflinePlayer;

interface DataProvider{

	/**
	 * @param IPlayer $player
	 *
	 * @return array, or null if it does not exist
	 */
	public function getPlayer(IPlayer $player);

	/**
	 * @param string $playerign
	 *
	 * @return array, or null if it does not exist
	 */
	public function getPlayerData(string $playerign);

	/**
	 * @param IPlayer $player
	 *
	 * @return bool
	 */
	public function isPlayerRegistered(IPlayer $player);

	/**
	 * @param IPlayer $player
	 * @param string  $hash
	 *
	 * @return array, or null if error happened
	 */
	public function registerPlayer(IPlayer $player, $hash);

	/**
	 * @param IPlayer $player
	 */
	public function unregisterPlayer(IPlayer $player);

	/**
	 * @param string $name
	 * @param array  $config
	 */
	public function savePlayer(string $name, array $config);

	/**
	 * @param IPlayer $player
	 * @param string  $lastIp
	 * @param string  $ip
	 * @param int     $loginDate
	 * @param string  $linkedign
	 * @return bool
	 */
	public function updatePlayer(IPlayer $player, string $lastIp = null, string $ip = null, int $loginDate = null, string $linkedign = null) : bool;

	/**
	 * @param string $name
	 *
	 * @return string or null
	 */
	public function getLinked(string $name);

	/**
	 * @param Player        $sender
	 * @param OfflinePlayer $oldPlayer
	 * @param string        $oldIGN
	 *
	 * @return bool $success
	 */
	public function linkXBL(Player $sender, OfflinePlayer $oldPlayer, string $oldIGN);

	/**
	 * @param string $playerign
	 *
	 * @return string or null if not linked
	 */
	public function unlinkXBL(string $playerign);

	/**
	 * @return bool for DB supports linking
	 */
	public function isDBLinkingReady() : bool;

	public function close();
}
