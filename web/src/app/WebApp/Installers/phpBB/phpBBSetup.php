<?php

declare(strict_types=1);

namespace Hestia\WebApp\Installers\phpBB;

use Hestia\WebApp\BaseSetup;
use Hestia\WebApp\InstallationTarget\InstallationTarget;

class phpBBSetup extends BaseSetup {
	protected array $info = [
		"name" => "phpBB",
		"group" => "forum",
		"version" => "3.3.16",
		"thumbnail" => "phpbb-logo.svg",
	];

	protected array $config = [
		"form" => [
			"board_name" => ["type" => "text", "value" => "phpBB Forum"],
			"board_description" => ["type" => "text", "value" => "Community forum"],
			"admin_username" => ["type" => "text", "value" => "admin"],
			"admin_password" => "password",
			"admin_email" => "text",
			"language" => ["type" => "text", "value" => "en"],
		],
		"database" => true,
		"resources" => [
			"archive" => [
				"src" => "https://download.phpbb.com/pub/release/3.3/3.3.16/phpBB-3.3.16.zip",
			],
		],
		"server" => [
			"nginx" => [
				"template" => "default",
			],
			"php" => [
				"supported" => ["8.2", "8.3", "8.4"],
			],
		],
	];

	protected function setupApplication(InstallationTarget $target, array $options): void {
		$this->appcontext->copyDirectory(
			$target->getDocRoot("/phpBB3/."),
			$target->getDocRoot(),
		);
		$this->appcontext->deleteDirectory($target->getDocRoot("/phpBB3/"));

		$parsedUrl = parse_url($target->getUrl());
		$serverProtocol = ($parsedUrl["scheme"] ?? "https") . "://";
		$serverName = $parsedUrl["host"] ?? $target->domain->domainName;
		$serverPort = $target->domain->isSslEnabled ? 443 : 80;

		$yamlQuote = static function (string $val): string {
			return "'" . str_replace("'", "''", $val) . "'";
		};

		$installConfig = implode("\n", [
			"installer:",
			"  admin:",
			"    name: " . $yamlQuote((string) $options["admin_username"]),
			"    password: " . $yamlQuote((string) $options["admin_password"]),
			"    email: " . $yamlQuote((string) $options["admin_email"]),
			"  board:",
			"    lang: " . $yamlQuote((string) ($options["language"] ?: "en")),
			"    name: " . $yamlQuote((string) $options["board_name"]),
			"    description: " . $yamlQuote((string) $options["board_description"]),
			"  database:",
			"    dbms: mysqli",
			"    dbhost: " . $yamlQuote($target->database->host),
			"    dbport: ''",
			"    dbuser: " . $yamlQuote($target->database->user),
			"    dbpasswd: " . $yamlQuote($target->database->password),
			"    dbname: " . $yamlQuote($target->database->name),
			"    table_prefix: 'phpbb_'",
			"  email:",
			"    enabled: false",
			"    smtp_delivery: false",
			"  server:",
			"    cookie_secure: " . ($target->domain->isSslEnabled ? "true" : "false"),
			"    server_protocol: " . $yamlQuote($serverProtocol),
			"    force_server_vars: false",
			"    server_name: " . $yamlQuote($serverName),
			"    server_port: " . $serverPort,
			"    script_path: '/'",
			"",
		]);

		$configPath = $target->getDocRoot("install/install-config.yml");
		$this->appcontext->createFile($configPath, $installConfig);

		$this->appcontext->runPHP(
			$options["php_version"],
			$target->getDocRoot("install/phpbbcli.php"),
			["install", $configPath],
		);

		$this->appcontext->deleteDirectory($target->getDocRoot("/install/"));
	}
}
