<?php
header("Content-Type: application/json; charset=utf-8");

define("HESTIA_DIR_BIN", "/usr/local/hestia/bin/");
define("HESTIA_CMD", "/usr/bin/sudo /usr/local/hestia/bin/");

use function Hestiacp\quoteshellarg\quoteshellarg;

function log_webhook_entry($user, $domain, $event_type, $status, $details = []) {
	if (empty($user) || empty($domain)) {
		return;
	}
	$log_dir = "/home/" . $user . "/web/" . $domain . "/log";
	if (!is_dir($log_dir)) {
		@mkdir($log_dir, 0755, true);
	}
	$log_file = $log_dir . "/git_webhook.log";

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

if (empty($user) || empty($domain) || empty($secret)) {
	http_response_code(400);
	$msg = "Missing required parameters (user, domain, secret)";
	echo json_encode(["status" => "error", "message" => $msg]);
	exit();
}

// Sanitize inputs
$user_safe = preg_replace("/[^a-zA-Z0-9_-]/", "", $user);
$domain_safe = preg_replace("/[^a-zA-Z0-9.-]/", "", $domain);

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

// Verify secret token
if (!hash_equals($git_data["WEBHOOK_SECRET"], $secret)) {
	http_response_code(403);
	$msg = "Invalid webhook secret token";
	log_webhook_entry($user_safe, $domain_safe, $event_type, "error (HTTP 403)", [
		"Delivery" => $delivery_id,
		"Message" => $msg,
	]);
	echo json_encode(["status" => "error", "message" => $msg]);
	exit();
}

// Parse payload body
$payload_raw = file_get_contents("php://input");
$payload = !empty($payload_raw) ? json_decode($payload_raw, true) : [];

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

// Trigger update / deployment
exec(HESTIA_CMD . "v-update-web-domain-git " . quoteshellarg($user_safe) . " " . quoteshellarg($domain_safe), $deploy_output, $deploy_status);

if ($deploy_status === 0) {
	// Fetch updated commit info
	exec(HESTIA_CMD . "v-list-web-domain-git " . quoteshellarg($user_safe) . " " . quoteshellarg($domain_safe) . " json", $info_out, $info_status);
	$updated_info = json_decode(implode("", $info_out), true);

	$final_commit = $updated_info["LAST_COMMIT"] ?? $commit_id;
	$final_msg = $updated_info["LAST_COMMIT_MSG"] ?? $commit_msg;
	$final_author = $updated_info["LAST_COMMIT_AUTHOR"] ?? $commit_author;

	log_webhook_entry($user_safe, $domain_safe, $event_type, "success (HTTP 200)", [
		"Delivery" => $delivery_id,
		"Sender" => $sender_name,
		"Branch" => $git_data["BRANCH"] ?? "main",
		"Commit" => $final_commit . " - " . $final_msg,
		"Author" => $final_author,
		"Deploy Status" => "Success (Exit code: 0)",
	]);

	http_response_code(200);
	echo json_encode([
		"status" => "success",
		"message" => "Deployment completed successfully",
		"domain" => $domain_safe,
		"branch" => $git_data["BRANCH"] ?? "main",
		"commit" => $final_commit,
		"commit_message" => $final_msg,
		"commit_author" => $final_author,
		"deployed_at" => date("Y-m-d H:i:s"),
	]);
} else {
	$err_text = implode(" ", $deploy_output);
	log_webhook_entry($user_safe, $domain_safe, $event_type, "error (HTTP 500)", [
		"Delivery" => $delivery_id,
		"Sender" => $sender_name,
		"Branch" => $git_data["BRANCH"] ?? "main",
		"Deploy Status" => "Failed (Exit code: " . $deploy_status . ")",
		"Error Details" => $err_text,
	]);

	http_response_code(500);
	echo json_encode([
		"status" => "error",
		"message" => "Deployment failed: " . $err_text,
	]);
}

