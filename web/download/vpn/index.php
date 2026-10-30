<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user context
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user/");
	exit();
}

$client = $_GET["client"] ?? "";
if (empty($client)) {
	header("Location: /list/vpn/");
	exit();
}

// Fetch client configuration
$v_client = quoteshellarg($client);
exec(HESTIA_CMD . "v-list-vpn-client " . $v_client . " conf", $output, $return_var);
$conf_data = implode("\n", $output);
unset($output);

if ($return_var !== 0 || empty($conf_data)) {
	$_SESSION["error_msg"] = _("Configuration file could not be found.");
	header("Location: /list/vpn/");
	exit();
}

// Send as downloadable file
header("Content-Type: text/plain");
header("Content-Disposition: attachment; filename=\"" . basename($client) . ".conf\"");
header("Content-Length: " . strlen($conf_data));
echo $conf_data;
exit();

