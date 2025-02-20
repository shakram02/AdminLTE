<?php
/*   Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license */

$api = true;
require_once("scripts/pi-hole/php/FTL.php");
require_once("scripts/pi-hole/php/password.php");
require_once("scripts/pi-hole/php/database.php");
require_once("scripts/pi-hole/php/auth.php");
check_cors();

$FTL_IP = "127.0.0.1";

$data = array();

// Common API functions
if (isset($_GET['status'])) {
    $data = array_merge($data, array("status" => piholeStatusAPI()));
} elseif (isset($_GET['enable']) && $auth) {
	// if(isset($_GET["auth"]))
	// {
	// if($_GET["auth"] !== $pwhash)
	// 	die("Not authorized!");
	// }
	// else
	// {
	// 	// Skip token validation if explicit auth string is given
	// 	check_csrf($_GET['token']);
	// }
	pihole_execute('enable');
	$data = array_merge($data, array("status" => "enabled"));
	if (file_exists("../custom_disable_timer"))
	{
		unlink("../custom_disable_timer");
	}
}
elseif (isset($_GET['disable']) && $auth)
{
	// if(isset($_GET["auth"]))
	// {
	// 	if($_GET["auth"] !== $pwhash)
	// 		die("Not authorized!");
	// }
	// else
	// {
	// 	// Skip token validation if explicit auth string is given
	// 	check_csrf($_GET['token']);
	// }
	$disable = intval($_GET['disable']);
	// intval returns the integer value on success, or 0 on failure
	if($disable > 0)
	{
		$timestamp = time();
		pihole_execute("disable ".$disable."s");
		file_put_contents("../custom_disable_timer",($timestamp+$disable)*1000);
	}
	else
	{
		pihole_execute('disable');
		if (file_exists("../custom_disable_timer"))
		{
			unlink("../custom_disable_timer");
		}
	}
	$data = array_merge($data, array("status" => "disabled"));
}
elseif (isset($_GET['versions']))
{
	// Determine if updates are available for Pi-hole
	// using the same script that we use for the footer
	// on the dashboard (update notifications are
	// suppressed if on development branches)
	require "scripts/pi-hole/php/update_checker.php";
	$updates = array("core_update" => $core_update,
	                 "web_update" => $web_update,
	                 "FTL_update" => $FTL_update);
	$current = array("core_current" => $core_current,
	                 "web_current" => $web_current,
	                 "FTL_current" => $FTL_current);
	$latest = array("core_latest" => $core_latest,
	                "web_latest" => $web_latest,
	                "FTL_latest" => $FTL_latest);
	$branches = array("core_branch" => $core_branch,
	                  "web_branch" => $web_branch,
	                  "FTL_branch" => $FTL_branch);
	$data = array_merge($data, $updates);
	$data = array_merge($data, $current);
	$data = array_merge($data, $latest);
	$data = array_merge($data, $branches);
}
elseif (isset($_GET['list']))
{
	if (!$auth)
		die("Not authorized!");

	if(!isset($_GET["list"]))
		die("List has not been specified.");

	switch ($_GET["list"]) {
		case 'black':
			$_POST['type'] = ListType::blacklist;
			break;
		case 'regex_black':
			$_POST['type'] = ListType::regex_blacklist;
			break;
		case 'white':
			$_POST['type'] = ListType::whitelist;
			break;
		case 'regex_white':
			$_POST['type'] = ListType::regex_whitelist;
			break;
		case 'blockables':
			$_POST['type'] = $_GET['type'];
			break;
		case 'adlist':
			$_POST['type'] = null;
			break;
		default:
			die("Invalid list [supported: black, regex_black, white, regex_white]");
	}

	if (isset($_GET['add']))
	{
		// Set POST parameters and invoke script to add domain to list
		$_POST['domain'] = $_GET['add'];
		$_POST['action'] = 'add_domain';
		require("scripts/pi-hole/php/groups.php");
	}
	elseif (isset($_GET['sub']))
	{
		// Set POST parameters and invoke script to remove domain from list
		$_POST['domain'] = $_GET['sub'];
		$_POST['action'] = 'delete_domain_string';
		require("scripts/pi-hole/php/groups.php");
	}
	elseif (isset($_GET['get_adlists']))
	{
		$_POST['action'] = 'get_adlists';
		require("scripts/pi-hole/php/groups.php");
	}
	elseif (isset($_GET['add_adlist']))
	{
		$_POST['action'] = 'add_adlist';
		$_POST['address'] = $_GET['add_adlist'];
		require("scripts/pi-hole/php/groups.php");
		require("scripts/pi-hole/php/silent_restart_gravity.sh.php");
	}
	elseif(isset($_GET['delete_adlist']))
	{
		$_POST['action'] = 'delete_adlist';
		$_POST['id'] = $_GET['delete_adlist'];
		require("scripts/pi-hole/php/groups.php");
		require("scripts/pi-hole/php/silent_restart_gravity.sh.php");
	}
	else
	{
		// Set POST parameters and invoke script to get all domains
		$_POST['action'] = 'get_domains';
		require("scripts/pi-hole/php/groups.php");
	}

	return;
}
elseif(isset($_GET['customdns']) && $auth)
{
	if (isset($_GET["auth"])) {
		if ($_GET["auth"] !== $pwhash) {
			die("Not authorized!");
		}
	} else {
		// Skip token validation if explicit auth string is given
		check_csrf($_GET['token']);
	}

	switch ($_GET["action"]) {
		case 'get':
			$_POST['action'] = 'get';
			break;
		case 'add':
			$_POST['action'] = 'add';
			break;
		case 'delete':
			$_POST['action'] = 'delete';
			break;
		}

	require("scripts/pi-hole/php/customdns.php");
}
elseif(isset($_GET['customcname']) && $auth)
{
	if (isset($_GET["auth"])) {
		if ($_GET["auth"] !== $pwhash) {
			die("Not authorized!");
		}
	} else {
		// Skip token validation if explicit auth string is given
		check_csrf($_GET['token']);
	}

	switch ($_GET["action"]) {
		case 'get':
			$_POST['action'] = 'get';
			break;
		case 'add':
			$_POST['action'] = 'add';
			break;
		case 'delete':
			$_POST['action'] = 'delete';
			break;
		}

	require("scripts/pi-hole/php/customcname.php");
}

// Other API functions
require("api_FTL.php");

header('Content-type: application/json');
if(isset($_GET["jsonForceObject"])) {
    echo json_encode($data, JSON_FORCE_OBJECT);
} else {
    echo json_encode($data);
}
