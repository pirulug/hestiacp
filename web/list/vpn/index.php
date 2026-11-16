<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
$TAB = "VPN";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user context
if ($_SESSION["userContext"] !== "admin" || !empty($_SESSION["look"])) {
	header("Location: /list/user/");
	exit();
}

// Handle key rotation action
$action = $_GET["action"] ?? "";
if ($action === "rotate_key" && !empty($_GET["client"])) {
	verify_csrf($_GET);
	$v_client = trim($_GET["client"]);
	exec(HESTIA_CMD . "v-change-vpn-client-key " . quoteshellarg($v_client), $output, $return_var);
	check_return_code($return_var, $output);
	unset($output);
	if (empty($_SESSION["error_msg"])) {
		$_SESSION["ok_msg"] = sprintf(_("Keys rotated successfully for VPN client %s. Download or scan the new configuration."), htmlspecialchars($v_client));
	}
	header("Location: /list/vpn/");
	exit();
}

// Fetch WireGuard server details
exec(HESTIA_CMD . "v-list-sys-vpn json", $output_server, $return_var_server);
$server = json_decode(implode("", $output_server), true);
$vpn_server = !empty($server["server"]) ? $server["server"] : null;
unset($output_server);

// Fetch client peers
exec(HESTIA_CMD . "v-list-vpn-clients json", $output_clients, $return_var_clients);
$data = json_decode(implode("", $output_clients), true);
if (empty($data)) {
	$data = [];
}
unset($output_clients);

// Render page
render_page($user, $TAB, "list_vpn");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];

// Flush session messages
unset($_SESSION["error_msg"]);
unset($_SESSION["ok_msg"]);

