<?php

header('content-type: application/json; charset: utf-8'); 

/**
 *     Written by Nico Bergemann <barracuda415@yahoo.de>
 *     Copyright 2011 Nico Bergemann
 *
 *     This program is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 
$startTime = microtime(true);
$fileDir = '../../';
require($fileDir . '/library/XenForo/Autoloader.php');
XenForo_Autoloader::getInstance()->setupAutoloader($fileDir . '/library');

restore_error_handler();
restore_exception_handler();

XenForo_Application::initialize($fileDir . '/library', $fileDir);
XenForo_Application::set('page_start_time', $startTime);

XenForo_Application::$externalDataPath = $fileDir . '/data';
XenForo_Application::$externalDataUrl = $fileDir . '/data';
XenForo_Application::$javaScriptUrl = $fileDir . '/js';

$options = XenForo_Application::get('options');
$API_KEY = $options->steamAPIKey;

function get_web_page( $url ) {
	$res = array();
	$options = array( 
		CURLOPT_RETURNTRANSFER => true,     // return web page 
		CURLOPT_HEADER         => false,    // do not return headers 
		CURLOPT_FOLLOWLOCATION => true,     // follow redirects 
		CURLOPT_USERAGENT      => "spider", // who am i 
		CURLOPT_AUTOREFERER    => true,     // set referer on redirect 
		CURLOPT_CONNECTTIMEOUT => 5,      // timeout on connect 
		CURLOPT_TIMEOUT        => 5,      // timeout on response 
		CURLOPT_MAXREDIRS      => 2,       // stop after 10 redirects 
		CURLOPT_ENCODING       => 'UTF-8',

	); 
	$ch      = curl_init( $url ); 
	curl_setopt_array( $ch, $options ); 
	$content = curl_exec( $ch ); 
	$err     = curl_errno( $ch ); 
	$errmsg  = curl_error( $ch ); 
	$header  = curl_getinfo( $ch ); 
	
	if ($content === false) {
		$i = 0;
		while ($content === false && $i < 2) {
			$content = curl_exec( $ch ); 
			$err     = curl_errno( $ch ); 
			$errmsg  = curl_error( $ch ); 
			$header  = curl_getinfo( $ch ); 
			$i++;
			sleep(1);
		}
	}
	
	curl_close( $ch ); 
	
	return $content;
}

if((function_exists('curl_version')) && !ini_get('safe_mode') && !ini_get('open_basedir') && !empty($_GET['steamids']))
{
	$content_json = get_web_page("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?steamids=" . $_GET['steamids'] . "&key=$API_KEY" );
}

else
{
	$content_json = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?steamids=" . $_GET['steamids'] . "&key=$API_KEY" );
	if ($content_json === false) {
		$i = 0;
		while ($content_json === false && $i < 2) {
			$content_json = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?steamids=" . $_GET['steamids'] . "&key=$API_KEY" );
			$i++;
			sleep(1);
		}
	}
}

echo $content_json;
?>
