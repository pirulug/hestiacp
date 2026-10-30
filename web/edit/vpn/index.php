<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
$TAB = "SERVER";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user context
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user/");
	exit();
}

// Fetch current VPN server settings
exec(HESTIA_CMD . "v-list-sys-vpn json", $output, $return_var);
$server_data = json_decode(implode("", $output), true);
unset($output);

$server = $server_data["server"] ?? [];
$v_port = $server["PORT"] ?? "51820";
$v_dns = $server["DNS"] ?? "1.1.1.1, 8.8.8.8";
$v_public_ip = $server["PUBLIC_IP"] ?? "";

// Check POST request
if (!empty($_POST["ok"])) {
	verify_csrf($_POST);

	$v_port = trim($_POST["v_port"] ?? "51820");
	$v_dns = trim($_POST["v_dns"] ?? "");
	$v_public_ip = trim($_POST["v_public_ip"] ?? "");

	if (empty($v_port)) {
		$_SESSION["error_msg"] = _("Port cannot be blank.");
	}

	if (empty($_SESSION["error_msg"])) {
		$v_port_arg = quoteshellarg($v_port);
		$v_dns_arg = quoteshellarg($v_dns);
		$v_public_ip_arg = quoteshellarg($v_public_ip);

		exec(
			HESTIA_CMD . "v-change-sys-vpn " .
			$v_port_arg . " " .
			$v_dns_arg . " " .
			$v_public_ip_arg,
			$output,
			$return_var
		);
		check_return_code($return_var, $output);
		unset($output);

		if (empty($_SESSION["error_msg"])) {
			$_SESSION["ok_msg"] = _("WireGuard VPN settings have been updated successfully.");
			header("Location: /list/vpn/");
			exit();
		}
	}
}

// Render page
render_page($user, $TAB, "edit_vpn");

