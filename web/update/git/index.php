<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

// Init
ob_start();
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check token
verify_csrf($_GET);

// Check user permissions
if ($_SESSION["userContext"] !== "admin") {
	header("Location: /list/user/");
	exit();
}

$fork = !empty($_GET["fork"]) ? preg_replace("/[^a-zA-Z0-9_-]/", "", $_GET["fork"]) : "pirulug";
$branch = !empty($_GET["branch"]) ? preg_replace("/[^a-zA-Z0-9_.-]/", "", $_GET["branch"]) : "main";

// Execute git update command
exec(
	HESTIA_CMD . "v-update-sys-hestia-git " . quoteshellarg($fork) . " " . quoteshellarg($branch) . " install",
	$output,
	$return_var,
);

if ($return_var === 0) {
	$_SESSION["ok_msg"] = sprintf(
		_("HestiaCP has been updated successfully from GitHub repository (%s - %s)."),
		htmlentities($fork),
		htmlentities($branch),
	);
} else {
	$err_text = implode(" ", $output);
	$_SESSION["error_msg"] = !empty($err_text)
		? sprintf(_("Error updating from GitHub (%s): %s"), htmlentities($fork), htmlentities($err_text))
		: sprintf(_("Error: Update from GitHub (%s/%s) failed."), htmlentities($fork), htmlentities($branch));
}

unset($output);

$redirect_url = !empty($_SESSION["back"]) ? $_SESSION["back"] : "/list/updates/";
header("Location: " . $redirect_url);
exit();
