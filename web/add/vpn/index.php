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

$v_client = "";
$v_routed_nets = "";
$v_tunnel_mode = "split";
$v_dns = "";
$v_comment = "";

// Check POST request
if (!empty($_POST["ok"])) {
	verify_csrf($_POST);

	$v_client = trim($_POST["v_client"] ?? "");
	$v_routed_nets = trim($_POST["v_routed_nets"] ?? "");
	$v_tunnel_mode = trim($_POST["v_tunnel_mode"] ?? "split");
	$v_dns = trim($_POST["v_dns"] ?? "");
	$v_comment = trim($_POST["v_comment"] ?? "");

	if (empty($v_client)) {
		$_SESSION["error_msg"] = _("Client name cannot be blank.");
	}

	if (empty($_SESSION["error_msg"])) {
		$v_client_arg = quoteshellarg($v_client);
		$v_routed_nets_arg = quoteshellarg($v_routed_nets);
		$v_tunnel_mode_arg = quoteshellarg($v_tunnel_mode);
		$v_dns_arg = quoteshellarg($v_dns);
		$v_comment_arg = quoteshellarg($v_comment);

		exec(
			HESTIA_CMD . "v-add-vpn-client " .
			$v_client_arg . " " .
			$v_routed_nets_arg . " " .
			$v_tunnel_mode_arg . " " .
			$v_dns_arg . " " .
			$v_comment_arg,
			$output,
			$return_var
		);
		check_return_code($return_var, $output);
		unset($output);

		if (empty($_SESSION["error_msg"])) {
			$_SESSION["ok_msg"] = sprintf(_("VPN client %s has been created successfully."), htmlspecialchars($v_client));
			header("Location: /list/vpn/");
			exit();
		}
	}
}

// Render page
render_page($user, $TAB, "add_vpn");

