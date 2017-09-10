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

namespace SimpleAuth\provider;

use pocketmine\IPlayer;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\OfflinePlayer;
use SimpleAuth\SimpleAuth;
use SimpleAuth\task\MySQLPingTask;

class MySQLDataProvider implements DataProvider{

	/** @var SimpleAuth */
	protected $plugin;

	/** @var \mysqli */
	protected $database;


	public function __construct(SimpleAuth $plugin){
		$this->plugin = $plugin;
		$config = $this->plugin->getConfig()->get("dataProviderSettings");

		if(!isset($config["host"]) or !isset($config["user"]) or !isset($config["password"]) or !isset($config["database"])){
			$this->plugin->getLogger()->critical("Invalid MySQL settings");
			$this->plugin->setDataProvider(new DummyDataProvider($this->plugin));
			return;
		}

		$this->database = new \mysqli($config["host"], $config["user"], $config["password"], $config["database"], isset($config["port"]) ? $config["port"] : 3306);
		if($this->database->connect_error){
			$this->plugin->getLogger()->critical("Couldn't connect to MySQL: ". $this->database->connect_error);
			$this->plugin->setDataProvider(new DummyDataProvider($this->plugin));
			return;
		}

		$resource = $this->plugin->getResource("mysql.sql");
		$this->database->query(stream_get_contents($resource));

		fclose($resource);

		$this->plugin->getServer()->getScheduler()->scheduleRepeatingTask(new MySQLPingTask($this->plugin, $this->database), 600); //Each 30 seconds
		$this->plugin->getLogger()->info("Connected to MySQL server");
	}

	public function getPlayer(string $name){
		$name = trim(strtolower($name));

		$result = $this->database->query("SELECT * FROM simpleauth_players WHERE name = '" . $this->database->escape_string($name)."'");

		if($result instanceof \mysqli_result){
			$data = $result->fetch_assoc();
			$result->free();
			if(isset($data["name"]) and strtolower($data["name"]) === $name){
				unset($data["name"]);
				return $data;
			}
		}

		return null;
	}

	public function isPlayerRegistered(IPlayer $player){
		return $this->getPlayer($player->getName()) !== null;
	}

	public function unregisterPlayer(IPlayer $player){
		$name = trim(strtolower($player->getName()));
		$this->database->query("DELETE FROM simpleauth_players WHERE name = '" . $this->database->escape_string($name)."'");
	}

    public function registerPlayer(IPlayer $player, $hash) {
        $name = trim(strtolower($player->getName()));
        $data = [
            "registerdate" => time(),
            "logindate" => time(),
            "lastip" => null,
            "hash" => $hash,
            "ip" => $player->getAddress(),
            "cid" => $player->getClientId(),
            "skinhash" => hash("md5", $player->getSkinData()),
            "pin" => null
        ];



        $result = $this->database->query("INSERT INTO simpleauth_players
			(name, registerdate, ip, cid, skinhash, pin, logindate, lastip, hash)
			VALUES
			('" . $this->database->escape_string($name) . "', " . intval($data["registerdate"]) . ", '" . $this->database->escape_string($data["ip"]) . "', " . $data["cid"] . ", '" . $this->database->escape_string($data["skinhash"]) . "', " . intval($data["pin"]) . ", " . intval($data["logindate"]) . ", '', '" . $hash . "')");
		return $data;
	}

    public function savePlayer(string $name, array $config) {
        $name = trim(strtolower($name));
        $this->database->query("UPDATE simpleauth_players SET ip = '" . $this->database->escape_string($config["ip"]) . "', cid = ". $config["cid"] . ", skinhash = '" . $this->database->escape_string($config["skinhash"]) . "', pin = " . intval($config["pin"]) . ", registerdate = " . intval($config["registerdate"]) . ", logindate = " . intval($config["logindate"]) . ", lastip = '" . $this->database->escape_string($config["lastip"]) . "', hash = '" . $this->database->escape_string($config["hash"]) . "', linkedign = '" . $this->database->escape_string($config["linkedign"]) . "' WHERE name = '" . $this->database->escape_string($name) . "'");
    }

    public function updatePlayer(IPlayer $player, $lastIP = null, $ip = null, $loginDate = null, $cid = null, $skinhash = null, $pin = null, $linkedign = null) {

	    $name = trim(strtolower($player->getName()));
        if ($lastIP !== null) {
            $this->database->query("UPDATE simpleauth_players SET lastip = '" . $this->database->escape_string($lastIP) . "' WHERE name = '" . $this->database->escape_string($name) . "'");
        }
        if ($loginDate !== null) {
            $this->database->query("UPDATE simpleauth_players SET logindate = " . intval($loginDate) . " WHERE name = '" . $this->database->escape_string($name) . "'");
        }
        if ($ip !== null) {
            $this->database->query("UPDATE simpleauth_players SET ip = '" . $this->database->escape_string($ip) . "' WHERE name = '" . $this->database->escape_string($name) . "'");
        }
        if ($cid !== null) {
            $this->database->query("UPDATE simpleauth_players SET cid = " . intval($cid) . " WHERE name = '" . $this->database->escape_string($name) . "'");
        }
        if ($skinhash !== null) {
            $this->database->query("UPDATE simpleauth_players SET skinhash = '" . $this->database->escape_string($skinhash) . "' WHERE name = '" . $this->database->escape_string($name) . "'");
        }
        if ($pin !== null) {
            $this->database->query("UPDATE simpleauth_players SET pin = " . intval($pin) . " WHERE name = '" . $this->database->escape_string($name) . "'");
        }
        if ($linkedign !== null) {
            $this->database->query("UPDATE simpleauth_players SET linkedign ='" . $this->database->escape_string($linkedign) . "' WHERE name = '" . $this->database->escape_string($name) . "'");
        }
        if (isset($pin) && (intval($pin) === 0)) {
            $this->database->query("UPDATE simpleauth_players SET pin = NULL WHERE name = '" . $this->database->escape_string($name) . "'");
        }

    }

    public function getLinked(string $name) {
        $name = trim(strtolower($name));
        $linked = $this->database->query("SELECT linkedign FROM simpleauth_players WHERE name = '" . $this->database->escape_string($name) . "'")->fetch_assoc();
        return $linked["linkedign"] ?? null;
	}

    public function linkXBL(Player $sender, OfflinePlayer $oldPlayer, string $oldIGN) {
        $this->updatePlayer($sender, null, null, null, null, null, null, $oldIGN);
        $this->updatePlayer($oldPlayer, null, null, null, null, null, null, $sender->getName());
    }

    public function unlinkXBL(Player $player) {
        $xblIGN = $this->getLinked($player->getName());
        $pmIGN = $this->getLinked($xblIGN);
        if (!isset($xblIGN)){
            return null;
        }
        $xbldata = $this->getPlayer($xblIGN);
        if (isset($xblIGN) && isset($xbldata)) {
            $xbldata["linkedign"] = "";
                $this->savePlayer($xblIGN, $xbldata);
        }
        if (isset($pmIGN)){
            $pmdata = $this->getPlayer($pmIGN);
            if (isset($pmdata)) {
                $pmdata["linkedign"] = "";
                $this->savePlayer($pmIGN, $pmdata);
            }
        }
        return $xblIGN;
    }

	public function close(){
		$this->database->close();
	}
}
