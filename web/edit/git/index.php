<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
unset($_SESSION["error_msg"]);
$TAB = "WEB";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check domain argument
if (empty($_GET["domain"])) {
	header("Location: /list/web/");
	exit();
}

// Edit as someone else?
if ($_SESSION["userContext"] === "admin" && !empty($_GET["user"])) {
	$user = quoteshellarg($_GET["user"]);
	$user_plain = htmlentities($_GET["user"]);
}

$v_domain = trim($_GET["domain"]);

// Verify that domain exists
exec(
	HESTIA_CMD . "v-list-web-domain " . $user . " " . quoteshellarg($v_domain) . " json",
	$output,
	$return_var,
);
check_return_code_redirect($return_var, $output, "/list/web/");
unset($output);

// Handle manual action: Pull / Deploy
if (isset($_GET["action"]) && $_GET["action"] === "pull") {
	verify_csrf($_GET);
	exec(
		HESTIA_CMD . "v-update-web-domain-git " . $user . " " . quoteshellarg($v_domain),
		$output,
		$return_var,
	);
	if ($return_var === 0) {
		$_SESSION["ok_msg"] = _("Git repository updated successfully.");
	} else {
		$_SESSION["error_msg"] = _("Failed to update Git repository: ") . implode(" ", $output);
	}
	unset($output);
	header("Location: /edit/git/?domain=" . urlencode($v_domain) . (!empty($_GET["user"]) ? "&user=" . urlencode($_GET["user"]) : ""));
	exit();
}

// Handle manual action: Generate Deploy Key
if (isset($_GET["action"]) && $_GET["action"] === "generate_key") {
	verify_csrf($_GET);
	exec(
		HESTIA_CMD . "v-make-web-domain-git-key " . $user . " " . quoteshellarg($v_domain) . " yes",
		$output,
		$return_var,
	);
	if ($return_var === 0) {
		$_SESSION["ok_msg"] = _("SSH Deploy Key generated successfully.");
	} else {
		$_SESSION["error_msg"] = _("Failed to generate SSH Deploy Key: ") . implode(" ", $output);
	}
	unset($output);
	header("Location: /edit/git/?domain=" . urlencode($v_domain) . (!empty($_GET["user"]) ? "&user=" . urlencode($_GET["user"]) : ""));
	exit();
}

// Handle manual action: Delete / Disconnect Git
if (isset($_GET["action"]) && $_GET["action"] === "delete") {
	verify_csrf($_GET);
	exec(
		HESTIA_CMD . "v-delete-web-domain-git " . $user . " " . quoteshellarg($v_domain),
		$output,
		$return_var,
	);
	if ($return_var === 0) {
		$_SESSION["ok_msg"] = _("Git repository disconnected successfully.");
	} else {
		$_SESSION["error_msg"] = _("Failed to disconnect Git repository: ") . implode(" ", $output);
	}
	unset($output);
	header("Location: /edit/git/?domain=" . urlencode($v_domain) . (!empty($_GET["user"]) ? "&user=" . urlencode($_GET["user"]) : ""));
	exit();
}

// Handle Save / Connect Git POST
if (!empty($_POST["save"])) {
	verify_csrf($_POST);

	$v_repo = trim($_POST["v_repo"] ?? "");
	$v_branch = trim($_POST["v_branch"] ?? "main");
	$v_deploy_dir = trim($_POST["v_deploy_dir"] ?? "public_html");
	$v_auth_type = trim($_POST["v_auth_type"] ?? "none");
	$v_auth_token = trim($_POST["v_auth_token"] ?? "");
	$v_post_deploy = trim($_POST["v_post_deploy"] ?? "");
	$v_auto_deploy = !empty($_POST["v_auto_deploy"]) ? "yes" : "no";

	if (empty($v_repo)) {
		$_SESSION["error_msg"] = _("Repository URL is required.");
	} else {
		$cmd = HESTIA_CMD . "v-add-web-domain-git " .
			$user . " " .
			quoteshellarg($v_domain) . " " .
			quoteshellarg($v_repo) . " " .
			quoteshellarg($v_branch) . " " .
			quoteshellarg($v_deploy_dir) . " " .
			quoteshellarg($v_auth_type) . " " .
			quoteshellarg($v_auth_token) . " " .
			quoteshellarg($v_post_deploy) . " " .
			quoteshellarg($v_auto_deploy);

		exec($cmd, $output, $return_var);

		if ($return_var === 0) {
			$_SESSION["ok_msg"] = _("Git repository connected and deployed successfully.");
			unset($output);
			header("Location: /edit/git/?domain=" . urlencode($v_domain) . (!empty($_GET["user"]) ? "&user=" . urlencode($_GET["user"]) : ""));
			exit();
		} else {
			$_SESSION["error_msg"] = _("Error connecting Git repository: ") . implode(" ", $output);
			unset($output);
		}
	}
}

// Load current git status
exec(
	HESTIA_CMD . "v-list-web-domain-git " . $user . " " . quoteshellarg($v_domain) . " json",
	$output,
	$return_var,
);
$git_info = json_decode(implode("", $output), true) ?: [];
unset($output);

$v_configured = ($git_info["CONFIGURED"] ?? "no") === "yes";
$v_repo = $git_info["REPO"] ?? "";
$v_branch = $git_info["BRANCH"] ?? "main";
$v_deploy_dir = $git_info["DEPLOY_DIR"] ?? "public_html";
$v_auth_type = $git_info["AUTH_TYPE"] ?? "none";
$v_webhook_secret = $git_info["WEBHOOK_SECRET"] ?? "";
$v_last_commit = $git_info["LAST_COMMIT"] ?? "";
$v_last_commit_msg = $git_info["LAST_COMMIT_MSG"] ?? "";
$v_last_commit_author = $git_info["LAST_COMMIT_AUTHOR"] ?? "";
$v_last_commit_date = $git_info["LAST_COMMIT_DATE"] ?? "";
$v_post_deploy = !empty($git_info["POST_DEPLOY_B64"]) ? base64_decode($git_info["POST_DEPLOY_B64"]) : ($git_info["POST_DEPLOY"] ?? "");
$v_post_deploy = str_replace("\r", "", $v_post_deploy);
$v_auto_deploy = ($git_info["AUTO_DEPLOY"] ?? "yes") === "yes";
$v_deploy_key = $git_info["DEPLOY_KEY"] ?? "";

// If deploy key not created yet, generate or fetch it
if (empty($v_deploy_key)) {
	exec(
		HESTIA_CMD . "v-make-web-domain-git-key " . $user . " " . quoteshellarg($v_domain),
		$key_output,
		$key_status,
	);
	if ($key_status === 0) {
		$v_deploy_key = trim(implode("\n", $key_output));
	}
	unset($key_output);
}

// Build webhook URL
$backend_port = !empty($_SESSION["BACKEND_PORT"]) ? $_SESSION["BACKEND_PORT"] : "8083";
$panel_host = !empty($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : gethostname();
$v_webhook_url = "https://" . $panel_host . "/api/webhook/?user=" . urlencode($user_plain) . "&domain=" . urlencode($v_domain) . "&secret=" . urlencode($v_webhook_secret);

// Read build log if available
$v_build_log = "";
$log_file = "/home/" . $user_plain . "/web/" . $v_domain . "/log/git_build.log";
if (file_exists($log_file) && is_readable($log_file)) {
	$v_build_log = file_get_contents($log_file);
}

// Read GitHub / Webhook log if available
$v_github_log = "";
$webhook_log_file = "/home/" . $user_plain . "/web/" . $v_domain . "/log/git_webhook.log";
if (file_exists($webhook_log_file) && is_readable($webhook_log_file)) {
	$v_github_log = file_get_contents($webhook_log_file);
}

// Render page
render_page($user, $TAB, "edit_web_git");

// Flush session messages
unset($_SESSION["error_msg"]);
unset($_SESSION["ok_msg"]);
