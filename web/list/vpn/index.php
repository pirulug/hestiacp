<?php
$TAB = "SERVER";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user context
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user/");
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

