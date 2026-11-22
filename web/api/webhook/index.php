<?php
header("Content-Type: application/json; charset=utf-8");

define("HESTIA_DIR_BIN", "/usr/local/hestia/bin/");
define("HESTIA_CMD", "/usr/bin/sudo /usr/local/hestia/bin/");

try {
	require_once dirname(__DIR__, 2) . "/inc/vendor/autoload.php";
} catch (Throwable $ex) {
	http_response_code(500);
	echo json_encode(["status" => "error", "message" => "Autoload error: " . $ex->getMessage()]);
	exit(1);
}

use function Hestiacp\quoteshellarg\quoteshellarg;

function log_webhook_entry($user, $domain, $event_type, $status, $details = []) {
	if (empty($user) || empty($domain) || str_contains($domain, "..")) {
		return;
	}
	$log_dir = "/home/" . $user . "/web/" . $domain . "/log";
	if (is_link($log_dir)) {
		return;
	}
	if (!is_dir($log_dir)) {
		@mkdir($log_dir, 0755, true);
	}
	$log_file = $log_dir . "/git_webhook.log";
	if (is_link($log_file)) {
		return;
	}

	$entry = "================================================================================\n";
	$entry .= "[" . date("Y-m-d H:i:s") . "] EVENT: " . strtoupper($event_type) . " | STATUS: " . $status . "\n";
	foreach ($details as $key => $val) {
		if (is_array($val) || is_object($val)) {
			$val = json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}
		$entry .= $key . ": " . $val . "\n";
	}
	$entry .= "================================================================================\n\n";

	// Keep log size bounded (~300 KB max)
	if (file_exists($log_file) && filesize($log_file) > 307200) {
		$content = file_get_contents($log_file);
		$lines = explode("\n", $content);
		$trimmed = implode("\n", array_slice($lines, -600));
		file_put_contents($log_file, $trimmed);
	}

	@file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
	@chown($log_file, $user);
}

// Extract parameters from query string
$user = $_GET["user"] ?? "";
$domain = $_GET["domain"] ?? "";
$secret = $_GET["secret"] ?? "";

// Detect headers
$event_type = $_SERVER["HTTP_X_GITHUB_EVENT"] ?? $_SERVER["HTTP_X_GITLAB_EVENT"] ?? $_SERVER["HTTP_X_EVENT_KEY"] ?? "push";
$delivery_id = $_SERVER["HTTP_X_GITHUB_DELIVERY"] ?? $_SERVER["HTTP_X_REQUEST_ID"] ?? "-";

if (empty($user) || empty($domain) || (empty($secret) && empty($_SERVER["HTTP_X_HUB_SIGNATURE_256"]) && empty($_SERVER["HTTP_X_GITLAB_TOKEN"]))) {
	http_response_code(400);
	$msg = "Missing required parameters (user, domain, secret)";
	echo json_encode(["status" => "error", "message" => $msg]);
	exit();
}

// Strict input validation (reject invalid characters or path traversal)
$user_safe = preg_replace("/[^a-zA-Z0-9_-]/", "", $user);
$domain_safe = preg_replace("/[^a-zA-Z0-9.-]/", "", $domain);
if ($user !== $user_safe || $domain !== $domain_safe || str_contains($domain_safe, "..")) {
	http_response_code(400);
	echo json_encode(["status" => "error", "message" => "Invalid user or domain parameter"]);
	exit();
}

// Fetch domain git config
exec(HESTIA_CMD . "v-list-web-domain-git " . quoteshellarg($user_safe) . " " . quoteshellarg($domain_safe) . " json", $output, $return_var);

if ($return_var !== 0 || empty($output)) {
	http_response_code(404);
	$msg = "Domain or Git configuration not found";
	log_webhook_entry($user_safe, $domain_safe, $event_type, "error (HTTP 404)", [
		"Delivery" => $delivery_id,
		"Message" => $msg,
	]);
	echo json_encode(["status" => "error", "message" => $msg]);
	exit();
}

$git_data = json_decode(implode("", $output), true);
unset($output);

if (($git_data["CONFIGURED"] ?? "no") !== "yes" || empty($git_data["WEBHOOK_SECRET"])) {
	http_response_code(400);
	$msg = "Git is not configured for this domain";
	log_webhook_entry($user_safe, $domain_safe, $event_type, "error (HTTP 400)", [
		"Delivery" => $delivery_id,
		"Message" => $msg,
	]);
	echo json_encode(["status" => "error", "message" => $msg]);
	exit();
}

// Parse payload body
$payload_raw = file_get_contents("php://input");
$payload = !empty($payload_raw) ? json_decode($payload_raw, true) : [];

// Verify secret token (Query param, GitHub HMAC signature or GitLab token)
$raw_secret_valid = false;
if (!empty($secret) && hash_equals($git_data["WEBHOOK_SECRET"], $secret)) {
	$raw_secret_valid = true;
} elseif (!empty($_SERVER["HTTP_X_HUB_SIGNATURE_256"])) {
	$expected_sig = "sha256=" . hash_hmac("sha256", $payload_raw, $git_data["WEBHOOK_SECRET"]);
	if (hash_equals($expected_sig, $_SERVER["HTTP_X_HUB_SIGNATURE_256"])) {
		$raw_secret_valid = true;
	}
} elseif (!empty($_SERVER["HTTP_X_GITLAB_TOKEN"])) {
	if (hash_equals($git_data["WEBHOOK_SECRET"], $_SERVER["HTTP_X_GITLAB_TOKEN"])) {
		$raw_secret_valid = true;
	}
}

if (!$raw_secret_valid) {
	http_response_code(403);
	$msg = "Invalid webhook secret token";
	log_webhook_entry($user_safe, $domain_safe, $event_type, "error (HTTP 403)", [
		"Delivery" => $delivery_id,
		"Message" => $msg,
	]);
	echo json_encode(["status" => "error", "message" => $msg]);
	exit();
}

// Handle GitHub Ping event (when adding webhook)
if ($event_type === "ping" || isset($payload["zen"]) || isset($payload["hook_id"])) {
	$zen = $payload["zen"] ?? "GitHub Webhook Ping test";
	$hook_id = $payload["hook_id"] ?? ($payload["hook"]["id"] ?? "-");
	log_webhook_entry($user_safe, $domain_safe, "ping", "success (HTTP 200)", [
		"Delivery" => $delivery_id,
		"Hook ID" => $hook_id,
		"Zen" => $zen,
		"Message" => "GitHub Webhook test connection verified successfully",
	]);
	http_response_code(200);
	echo json_encode([
		"status" => "success",
		"message" => "GitHub Webhook connection verified successfully",
		"zen" => $zen,
	]);
	exit();
}

// Check if automatic deployment is enabled
if (($git_data["AUTO_DEPLOY"] ?? "yes") === "no") {
	$msg = "Automatic deployment is disabled for this domain";
	log_webhook_entry($user_safe, $domain_safe, $event_type, "ignored (HTTP 200)", [
		"Delivery" => $delivery_id,
		"Message" => $msg,
	]);
	echo json_encode(["status" => "ignored", "message" => $msg]);
	exit();
}

// Check payload branch if available (GitHub / GitLab webhook payload)
$pushed_branch = "";
$head_commit = $payload["head_commit"] ?? ($payload["commits"][0] ?? []);
$sender_name = $payload["sender"]["login"] ?? ($payload["user_username"] ?? ($payload["pusher"]["name"] ?? "-"));
$commit_msg = $head_commit["message"] ?? "-";
$commit_id = !empty($head_commit["id"]) ? substr($head_commit["id"], 0, 7) : "-";
$commit_author = $head_commit["author"]["name"] ?? ($head_commit["author"]["username"] ?? "-");

if (!empty($payload["ref"])) {
	$pushed_branch = str_replace("refs/heads/", "", $payload["ref"]);
	$target_branch = $git_data["BRANCH"] ?? "main";
	if ($pushed_branch !== $target_branch) {
		$msg = "Pushed branch (" . $pushed_branch . ") does not match configured target branch (" . $target_branch . ")";
		log_webhook_entry($user_safe, $domain_safe, $event_type, "ignored (HTTP 200)", [
			"Delivery" => $delivery_id,
			"Sender" => $sender_name,
			"Pushed Branch" => $pushed_branch,
			"Target Branch" => $target_branch,
			"Message" => $msg,
		]);
		echo json_encode([
			"status" => "ignored",
			"message" => $msg,
		]);
		exit();
	}
}

// Dispatch update / deployment asynchronously so GitHub/GitLab webhooks do not time out
$target_branch = $git_data["BRANCH"] ?? "main";
$async_cmd = "nohup " . HESTIA_CMD . "v-update-web-domain-git " . quoteshellarg($user_safe) . " " . quoteshellarg($domain_safe) . " > /dev/null 2>&1 &";
exec($async_cmd, $dispatch_out, $dispatch_status);

if ($dispatch_status === 0) {
	log_webhook_entry($user_safe, $domain_safe, $event_type, "accepted (HTTP 202)", [
		"Delivery" => $delivery_id,
		"Sender" => $sender_name,
		"Branch" => $target_branch,
		"Commit" => $commit_id . " - " . $commit_msg,
		"Author" => $commit_author,
		"Deploy Status" => "Queued asynchronously",
	]);

	http_response_code(202);
	echo json_encode([
		"status" => "accepted",
		"message" => "Deployment queued asynchronously",
		"domain" => $domain_safe,
		"branch" => $target_branch,
		"commit" => $commit_id,
		"queued_at" => date("Y-m-d H:i:s"),
	]);
} else {
	$err_text = implode(" ", $dispatch_out);
	log_webhook_entry($user_safe, $domain_safe, $event_type, "error (HTTP 500)", [
		"Delivery" => $delivery_id,
		"Sender" => $sender_name,
		"Branch" => $target_branch,
		"Deploy Status" => "Failed to dispatch (Exit code: " . $dispatch_status . ")",
		"Error Details" => $err_text,
	]);

	http_response_code(500);
	echo json_encode([
		"status" => "error",
		"message" => "Failed to dispatch deployment: " . $err_text,
	]);
}

