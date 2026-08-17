<?php
header("Content-Type: application/json; charset=utf-8");

define("HESTIA_DIR_BIN", "/usr/local/hestia/bin/");
define("HESTIA_CMD", "/usr/bin/sudo /usr/local/hestia/bin/");

use function Hestiacp\quoteshellarg\quoteshellarg;

// Extract parameters from query string or JSON payload
$user = $_GET["user"] ?? "";
$domain = $_GET["domain"] ?? "";
$secret = $_GET["secret"] ?? "";

if (empty($user) || empty($domain) || empty($secret)) {
	http_response_code(400);
	echo json_encode(["status" => "error", "message" => "Missing required parameters (user, domain, secret)"]);
	exit();
}

// Sanitize inputs
$user_safe = preg_replace("/[^a-zA-Z0-9_-]/", "", $user);
$domain_safe = preg_replace("/[^a-zA-Z0-9.-]/", "", $domain);

// Fetch domain git config
exec(HESTIA_CMD . "v-list-web-domain-git " . quoteshellarg($user_safe) . " " . quoteshellarg($domain_safe) . " json", $output, $return_var);

if ($return_var !== 0 || empty($output)) {
	http_response_code(404);
	echo json_encode(["status" => "error", "message" => "Domain or Git configuration not found"]);
	exit();
}

$git_data = json_decode(implode("", $output), true);
unset($output);

if (($git_data["CONFIGURED"] ?? "no") !== "yes" || empty($git_data["WEBHOOK_SECRET"])) {
	http_response_code(400);
	echo json_encode(["status" => "error", "message" => "Git is not configured for this domain"]);
	exit();
}

// Verify secret token
if (!hash_equals($git_data["WEBHOOK_SECRET"], $secret)) {
	http_response_code(403);
	echo json_encode(["status" => "error", "message" => "Invalid webhook secret token"]);
	exit();
}

// Check if automatic deployment is enabled
if (($git_data["AUTO_DEPLOY"] ?? "yes") === "no") {
	echo json_encode(["status" => "ignored", "message" => "Automatic deployment is disabled for this domain"]);
	exit();
}

// Check payload branch if available (GitHub / GitLab webhook payload)
$payload_raw = file_get_contents("php://input");
if (!empty($payload_raw)) {
	$payload = json_decode($payload_raw, true);
	if (!empty($payload["ref"])) {
		$pushed_branch = str_replace("refs/heads/", "", $payload["ref"]);
		$target_branch = $git_data["BRANCH"] ?? "main";
		if ($pushed_branch !== $target_branch) {
			echo json_encode([
				"status" => "ignored",
				"message" => "Pushed branch ($pushed_branch) does not match configured target branch ($target_branch)",
			]);
			exit();
		}
	}
}

// Trigger update / deployment
exec(HESTIA_CMD . "v-update-web-domain-git " . quoteshellarg($user_safe) . " " . quoteshellarg($domain_safe), $deploy_output, $deploy_status);

if ($deploy_status === 0) {
	// Fetch updated commit info
	exec(HESTIA_CMD . "v-list-web-domain-git " . quoteshellarg($user_safe) . " " . quoteshellarg($domain_safe) . " json", $info_out, $info_status);
	$updated_info = json_decode(implode("", $info_out), true);

	http_response_code(200);
	echo json_encode([
		"status" => "success",
		"message" => "Deployment completed successfully",
		"domain" => $domain_safe,
		"branch" => $git_data["BRANCH"] ?? "main",
		"commit" => $updated_info["LAST_COMMIT"] ?? "latest",
		"commit_message" => $updated_info["LAST_COMMIT_MSG"] ?? "",
		"commit_author" => $updated_info["LAST_COMMIT_AUTHOR"] ?? "",
		"deployed_at" => date("Y-m-d H:i:s"),
	]);
} else {
	http_response_code(500);
	echo json_encode([
		"status" => "error",
		"message" => "Deployment failed: " . implode(" ", $deploy_output),
	]);
}
