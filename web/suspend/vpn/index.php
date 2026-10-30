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

// Check CSRF token
verify_csrf($_GET);

if (!empty($_GET["client"])) {
	$v_client = quoteshellarg($_GET["client"]);
	exec(HESTIA_CMD . "v-suspend-vpn-client " . $v_client, $output, $return_var);
	check_return_code($return_var, $output);
	unset($output);
}

header("Location: /list/vpn/");
exit();

