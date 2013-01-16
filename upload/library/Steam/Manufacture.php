<?php
/**
 *      This file is part of Steam Authentication for XenForo
 *
 *      Written by Morgan Humes <morgan@lanaddict.com>
 *      Copyright 2012 Morgan Humes
 *
 *      Steam Authentication for XenForo is free software: you can redistribute
 *      it and/or modify it under the terms of the GNU General Public License
 *      as published by the Free Software Foundation, either version 3 of the
 *      License, or (at your option) any later version.
 *
 *      Steam Authentication for XenForo is distributed in the hope that it
 *      will be useful, but WITHOUT ANY WARRANTY; without even the implied
 *      warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *      See the GNU General Public License for more details.
 *
 *      You should have received a copy of the GNU General Public License
 *      along with SteamProfile.  If not, see <http://www.gnu.org/licenses/>.
 */

class Steam_Manufacture {

	private static $_instance;
	protected $_db;

	public static final function getInstance() {
		if(!self::$_instance) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	protected function _getDb() {
		if($this->_db === null) {
			$this->_db = XenForo_Application::get('db');
		}

		return $this->_db;
	}

	public static function build($existingAddOn, $addOnData) {
		$startVersion = 1;
		$endVersion = $addOnData['version_id'];

		if($existingAddOn) {
			$startVersion = $existingAddOn['version_id'] +1;
		}

		$install = self::getInstance();

		for($i = $startVersion; $i <= $endVersion; $i++) {
			$method = "_installVersion$i";
			if(method_exists($install, $method) === false) {
				continue;
			}

			$install->$method();
		}
	}

	protected function _installVersion1() {
		$db = $this->_getDb();
		
		self::addColumnIfNotExists('xf_user_profile', 'steam_auth_id', 'BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT 0', 'facebook_auth_id');
		
		// Sync external auth in case of previous addons
		$db->query("UPDATE xf_user_profile p1 JOIN xf_user_external_auth p2 ON(p1.user_id = p2.user_id) SET p1.steam_auth_id = p2.provider_key WHERE provider = 'steam'");
	}

	protected function _installVersion4() {
		$db = $this->_getDb();

		// Create the steam game table
		$db->query("CREATE TABLE IF NOT EXISTS xf_steam_games (
						game_id int(10) unsigned PRIMARY KEY,
						game_name VARCHAR(256) NOT NULL,
						game_logo VARCHAR(256) NOT NULL,
						game_link VARCHAR(256)
					)");

		// Create the steam user games table
		$db->query("CREATE TABLE IF NOT EXISTS xf_user_steam_games (
						user_id int(10) unsigned NOT NULL,
						game_id int(10) unsigned NOT NULL,
						game_hours int unsigned NOT NULL,
						PRIMARY KEY (user_id, game_id)
					)");

		// Run Initial Cron Job for Steam!
		// Steam_Cron::update();
	}

	protected function _installVersion8() {
		$db = $this->_getDb();

		// Add columns to steam user games table		
		self::addColumnIfNotExists('xf_user_steam_games', 'game_hours_recent', 'INT UNSIGNED NOT NULL DEFAULT 0', 'game_hours');
		
		// Run Initial Cron Job for Steam!
		Steam_Cron::update();
	}

	public static function destroy() {
		$lastUninstallStep = 1;

		$uninstall = self::getInstance();

		for($i = 1; $i <= $lastUninstallStep; $i++) {
			$method = "_uninstallStep$i";
			if(method_exists($uninstall, $method) === false) {
				continue;
			}

			$uninstall->$method();
		}
	}

	protected function _uninstallStep1() {
		$db = $this->_getDb();

		$db->query("ALTER TABLE xf_user_profile DROP steam_auth_id");
	}

	protected function _uninstallStep4() {
		$db = $this->_getDb();

		// Drop xf_steam_games
		$db->query("DROP TABLE IF EXISTS xf_steam_games");
		
		// Drop xf_user_steam_games
		$db->query("DROP TABLE IF EXISTS xf_user_steam_games");
	}
	
    public static function addColumnIfNotExists($tableName, $fieldName, $fieldDef, $after)
    {
    	$db = XenForo_Application::get('db');
    
    	$exists = $db->fetchRow("
			SHOW COLUMNS
			FROM {$tableName}
			WHERE Field = ?
		", $fieldName);
    
    	if (!$exists)
    	{
    		$db->query("
    				ALTER TABLE {$tableName} ADD {$fieldName} {$fieldDef} AFTER {$after}
    		");
    	}
    }	

}

?>
