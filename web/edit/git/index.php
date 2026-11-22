<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
unset($_SESSION["error_msg"]);
$TAB = "WEB";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

$v_domain = !empty($_GET["domain"]) ? trim($_GET["domain"]) : "";

// Check domain argument
if (empty($v_domain)) {
	header("Location: /list/web/");
	exit();
}

// Edit as someone else?
if ($_SESSION["userContext"] === "admin") {
	if (!empty($_GET["user"])) {
		$user = quoteshellarg($_GET["user"]);
		$user_plain = htmlentities($_GET["user"]);
	} else if (!empty($v_domain)) {
		exec(
			HESTIA_CMD . "v-search-domain-owner " . quoteshellarg($v_domain) . " web",
			$owner_out,
			$owner_status,
		);
		if ($owner_status === 0 && !empty($owner_out[0])) {
			$user_plain = trim($owner_out[0]);
			$user = quoteshellarg($user_plain);
		}
		unset($owner_out);
	}
}

$user_param = (!empty($_GET["user"])) ? "&user=" . urlencode($_GET["user"]) : ((!empty($user_plain) && isset($_SESSION["user"]) && $user_plain !== $_SESSION["user"]) ? "&user=" . urlencode($user_plain) : "");

// Verify that domain exists
exec(
	HESTIA_CMD . "v-list-web-domain " . $user . " " . quoteshellarg($v_domain) . " json",
	$output,
	$return_var,
);
check_return_code_redirect($return_var, $output, "/list/web/");
unset($output);

/**
 * Read only the last N lines of a log file (capped at $max_bytes) to keep panel memory/IO low.
 * Rejects symlinks and paths escaping the domain log directory.
 */
function hestia_read_log_tail($filepath, $expected_dir = "", $max_lines = 120, $max_bytes = 65536) {
	$core_bin = HESTIA_DIR_BIN . "hestia-core";
	if (is_executable($core_bin)) {
		$cmd = quoteshellarg($core_bin) . " log-tail " . quoteshellarg($filepath) .
			" --lines " . (int) $max_lines .
			" --max-bytes " . (int) $max_bytes;
		if (!empty($expected_dir)) {
			$cmd .= " --expected-dir " . quoteshellarg($expected_dir);
		}
		$out = [];
		$ret = 0;
		exec($cmd, $out, $ret);
		if ($ret === 0) {
			return implode("\n", $out);
		}
	}

	$parent = dirname($filepath);
	if (is_link($parent) || is_link($filepath) || !file_exists($filepath) || !is_readable($filepath)) {
		return "";
	}
	if (!empty($expected_dir)) {
		$real_dir = realpath($expected_dir);
		$real_file = realpath($filepath);
		if ($real_dir === false || $real_file === false || !str_starts_with($real_file, $real_dir . DIRECTORY_SEPARATOR)) {
			return "";
		}
	}
	$size = filesize($filepath);
	if ($size === false || $size === 0) {
		return "";
	}
	$fp = @fopen($filepath, "rb");
	if (!$fp) {
		return "";
	}
	$offset = max(0, $size - $max_bytes);
	if ($offset > 0) {
		fseek($fp, $offset, SEEK_SET);
	}
	$chunk = fread($fp, $max_bytes);
	fclose($fp);
	if ($chunk === false || $chunk === "") {
		return "";
	}
	$lines = explode("\n", str_replace("\r\n", "\n", $chunk));
	if ($offset > 0 && count($lines) > 1) {
		// Drop potentially partial first line when seeking mid-file
		array_shift($lines);
	}
	if (count($lines) > $max_lines) {
		$lines = array_slice($lines, -$max_lines);
	}
	return ($offset > 0 ? "... (showing last " . $max_lines . " lines)\n" : "") . implode("\n", $lines);
}

// Handle manual actions
$action = $_GET["action"] ?? ($_GET["amp;action"] ?? "");

// Lightweight AJAX log refresh endpoint
if ($action === "fetch_logs") {
	verify_csrf($_GET);
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	header("Content-Type: application/json; charset=utf-8");
	$owner = !empty($user_plain) ? $user_plain : $_SESSION["user"];
	if (!preg_match("/^[a-zA-Z0-9_-]+$/", $owner) || !preg_match("/^[a-zA-Z0-9.-]+$/", $v_domain) || str_contains($v_domain, "..")) {
		http_response_code(400);
		echo json_encode(["status" => "error"]);
		exit();
	}
	$log_base = "/home/" . $owner . "/web/" . $v_domain . "/log";
	$build_log_path = $log_base . "/git_build.log";
	$webhook_log_path = $log_base . "/git_webhook.log";

	exec(HESTIA_CMD . "v-list-web-domain-git " . $user . " " . quoteshellarg($v_domain) . " json", $st_out, $st_ret);
	$st_info = ($st_ret === 0) ? (json_decode(implode("", $st_out), true) ?: []) : [];
	unset($st_out);

	echo json_encode([
		"status" => "ok",
		"build_log" => hestia_read_log_tail($build_log_path, $log_base),
		"github_log" => hestia_read_log_tail($webhook_log_path, $log_base),
		"deploy_status" => $st_info["LAST_DEPLOY_STATUS"] ?? "success",
		"deploy_duration" => $st_info["LAST_DEPLOY_DURATION"] ?? "0",
		"updated_at" => date("H:i:s"),
	]);
	exit();
}

// Handle manual action: Pull / Deploy
if ($action === "pull") {
	verify_csrf($_GET);
	$run_build = (isset($_GET["build"]) && in_array(strtolower($_GET["build"]), ["no", "0", "false", "skip"], true)) ? "no" : "yes";
	$cmd = HESTIA_CMD . "v-update-web-domain-git " . $user . " " . quoteshellarg($v_domain) . " " . quoteshellarg("") . " " . quoteshellarg($run_build);
	exec($cmd, $output, $return_var);
	if ($return_var === 0) {
		if ($run_build === "no") {
			$_SESSION["ok_msg"] = _("Git repository updated successfully (Build Pipeline skipped).");
		} else {
			$_SESSION["ok_msg"] = _("Git repository updated and deployed successfully.");
		}
	} else {
		$_SESSION["error_msg"] = _("Failed to update Git repository: ") . implode(" ", $output);
	}
	unset($output);
	header("Location: /edit/git/?domain=" . urlencode($v_domain) . $user_param);
	exit();
}

// Handle manual action: Generate Deploy Key
if ($action === "generate_key") {
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
	header("Location: /edit/git/?domain=" . urlencode($v_domain) . $user_param);
	exit();
}

// Handle manual action: Rotate Webhook Secret
if ($action === "rotate_secret") {
	verify_csrf($_GET);
	exec(
		HESTIA_CMD . "v-change-web-domain-git-secret " . $user . " " . quoteshellarg($v_domain),
		$output,
		$return_var,
	);
	if ($return_var === 0) {
		$_SESSION["ok_msg"] = _("Webhook secret rotated successfully.");
	} else {
		$_SESSION["error_msg"] = _("Failed to rotate webhook secret: ") . implode(" ", $output);
	}
	unset($output);
	header("Location: /edit/git/?domain=" . urlencode($v_domain) . $user_param);
	exit();
}

// Handle manual action: Rollback release
if ($action === "rollback") {
	verify_csrf($_GET);
	$rel = trim($_GET["release"] ?? "");
	$cmd = HESTIA_CMD . "v-rollback-web-domain-git " . $user . " " . quoteshellarg($v_domain);
	if (!empty($rel)) {
		$cmd .= " " . quoteshellarg($rel);
	}
	exec($cmd, $output, $return_var);
	if ($return_var === 0) {
		$_SESSION["ok_msg"] = _("Rolled back Git release successfully.");
	} else {
		$_SESSION["error_msg"] = _("Failed to rollback Git release: ") . implode(" ", $output);
	}
	unset($output);
	header("Location: /edit/git/?domain=" . urlencode($v_domain) . $user_param);
	exit();
}

// Handle manual action: Delete / Disconnect Git
if ($action === "delete") {
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
	header("Location: /edit/git/?domain=" . urlencode($v_domain) . $user_param);
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
	$v_zero_downtime = !empty($_POST["v_zero_downtime"]) ? "yes" : "no";

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
			quoteshellarg($v_auto_deploy) . " " .
			quoteshellarg($v_zero_downtime);

		exec($cmd, $output, $return_var);

		if ($return_var === 0) {
			$_SESSION["ok_msg"] = _("Git repository connected and deployed successfully.");
			unset($output);
			header("Location: /edit/git/?domain=" . urlencode($v_domain) . $user_param);
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
$v_last_deploy_status = $git_info["LAST_DEPLOY_STATUS"] ?? "success";
$v_last_deploy_duration = $git_info["LAST_DEPLOY_DURATION"] ?? "0";
$v_zero_downtime = ($git_info["ZERO_DOWNTIME"] ?? "no") === "yes";
$v_current_release = $git_info["CURRENT_RELEASE"] ?? "";
$v_releases = is_array($git_info["RELEASES"] ?? null) ? $git_info["RELEASES"] : [];
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
if (!str_contains($panel_host, ":") && !empty($backend_port) && $backend_port !== "80" && $backend_port !== "443") {
	$webhook_host = $panel_host . ":" . $backend_port;
} else {
	$webhook_host = $panel_host;
}
$v_webhook_url_clean = "https://" . $webhook_host . "/api/webhook/?user=" . urlencode($user_plain) . "&domain=" . urlencode($v_domain);
$v_webhook_url = $v_webhook_url_clean . "&secret=" . urlencode($v_webhook_secret);

// Read build & webhook log tails if available
$owner_name = !empty($user_plain) ? $user_plain : $_SESSION["user"];
$log_base = "/home/" . $owner_name . "/web/" . $v_domain . "/log";
$v_build_log = hestia_read_log_tail($log_base . "/git_build.log", $log_base);
$v_github_log = hestia_read_log_tail($log_base . "/git_webhook.log", $log_base);

// Render page
render_page($user, $TAB, "edit_web_git");

// Flush session messages
unset($_SESSION["error_msg"]);
unset($_SESSION["ok_msg"]);
