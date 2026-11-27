<?php

declare(strict_types=1);

namespace Hestia\WebApp\Installers\Moodle;

use Hestia\System\Util;
use Hestia\WebApp\BaseSetup;
use Hestia\WebApp\InstallationTarget\InstallationTarget;

class MoodleSetup extends BaseSetup {
	protected array $info = [
		"name" => "Moodle",
		"group" => "cms",
		"version" => "4.5-latest",
		"thumbnail" => "moodle-logo.svg",
	];

	protected array $config = [
		"form" => [
			"site_name" => ["type" => "text", "value" => "Moodle LMS"],
			"short_name" => ["type" => "text", "value" => "Moodle"],
			"admin_username" => ["type" => "text", "value" => "admin"],
			"admin_password" => "password",
			"admin_email" => "text",
			"language" => ["type" => "text", "value" => "es"],
		],
		"database" => true,
		"resources" => [
			"archive" => [
				"src" => "https://download.moodle.org/download.php/direct/stable405/moodle-latest-405.zip",
			],
		],
		"server" => [
			"nginx" => [
				"template" => "moodle",
			],
			"php" => [
				"supported" => ["8.2", "8.3", "8.4"],
			],
		],
	];

	protected function setupApplication(InstallationTarget $target, array $options): void {
		$this->appcontext->copyDirectory(
			$target->getDocRoot("/moodle/."),
			$target->getDocRoot(),
		);
		$this->appcontext->deleteDirectory($target->getDocRoot("/moodle/"));

		$moodledata = Util::joinPaths($target->domain->domainPath, "private", "moodledata");
		$this->appcontext->addDirectory($moodledata);

		$this->appcontext->runPHP(
			$options["php_version"],
			$target->getDocRoot("admin/cli/install.php"),
			[
				"--non-interactive",
				"--agree-license",
				"--lang=" . ($options["language"] ?: "es"),
				"--wwwroot=" . $target->getUrl(),
				"--dataroot=" . $moodledata,
				"--dbtype=mariadb",
				"--dbhost=" . $target->database->host,
				"--dbname=" . $target->database->name,
				"--dbuser=" . $target->database->user,
				"--dbpass=" . $target->database->password,
				"--fullname=" . $options["site_name"],
				"--shortname=" . $options["short_name"],
				"--adminuser=" . $options["admin_username"],
				"--adminpass=" . $options["admin_password"],
				"--adminemail=" . $options["admin_email"],
			],
		);
	}
}
