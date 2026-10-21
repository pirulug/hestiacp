<!doctype html>
<html lang="en">

<head>
	<!-- Load necessary CSS and JavaScript from source -->
	<?php require $_SERVER["HESTIA"] . "/web/templates/includes/title.php"; ?>
	<?php require $_SERVER["HESTIA"] . "/web/templates/includes/css.php"; ?>
	<?php require $_SERVER["HESTIA"] . "/web/templates/includes/js.php"; ?>
</head>

<body class="page-server-info">

	<div class="app">

		<header class="app-header">
			<div class="top-bar">
				<div class="container top-bar-inner">
					<div class="top-bar-left">
						<a href="/" class="top-bar-logo" title="<?= tohtml( _("Hestia Control Panel")) ?>">
							<picture>
								<source srcset="/images/logo-header-white.svg" media="(prefers-color-scheme: dark)">
								<img src="/images/logo-header.svg" alt="<?= tohtml( _("Hestia Control Panel")) ?>" width="54" height="29">
							</picture>
						</a>
					</div>
					<div class="top-bar-right">
						<nav x-data="{ open: false }" class="top-bar-menu">
							<button
								type="button"
								class="top-bar-menu-link u-hide-tablet"
								x-on:click="open = !open">
								<i class="bi bi-list"></i>
									<span class="u-hidden" x-text="open ? <?= tohtml(json_encode(_("Close menu"), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR)) ?> : <?= tohtml(json_encode(_("Open menu"), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR)) ?>">
										<?= tohtml( _("Open menu")) ?>
									</span>
							</button>
							<div x-cloak x-show="open" x-on:click.outside="open = false" class="top-bar-menu-panel">
								<ul class="top-bar-menu-list">
									<li class="top-bar-menu-item">
										<a class="top-bar-menu-link" href="/list/rrd/" title="<?= tohtml( _("Back")) ?>">
											<i class="bi bi-arrow-left-circle"></i>
											<span class="top-bar-menu-link-label"><?= tohtml( _("Back")) ?></span>
										</a>
									</li>
									<li class="top-bar-menu-item">
										<a class="top-bar-menu-link <?php if (isset($_GET['cpu'])) echo 'active' ?>" href="/list/server/?cpu">
											<i class="bi bi-cpu"></i>
											<span class="top-bar-menu-link-label"><?= tohtml( _("CPU")) ?></span>
										</a>
									</li>
									<li class="top-bar-menu-item">
										<a class="top-bar-menu-link <?php if (isset($_GET['mem'])) echo 'active' ?>" href="/list/server/?mem">
											<i class="bi bi-memory"></i>
											<span class="top-bar-menu-link-label"><?= tohtml( _("RAM")) ?></span>
										</a>
									</li>
									<li class="top-bar-menu-item">
										<a class="top-bar-menu-link <?php if (isset($_GET['disk'])) echo 'active' ?>" href="/list/server/?disk">
											<i class="bi bi-hdd"></i>
											<span class="top-bar-menu-link-label"><?= tohtml( _("Disk")) ?></span>
										</a>
									</li>
									<li class="top-bar-menu-item">
										<a class="top-bar-menu-link <?php if (isset($_GET['net'])) echo 'active' ?>" href="/list/server/?net">
											<i class="bi bi-hdd"></i>
											<span class="top-bar-menu-link-label"><?= tohtml( _("Network")) ?></span>
										</a>
									</li>
									<?php if ((isset($_SESSION['WEB_SYSTEM'])) && (!empty($_SESSION['WEB_SYSTEM']))) { ?>
										<li class="top-bar-menu-item">
											<a class="top-bar-menu-link <?php if (isset($_GET['web'])) echo 'active' ?>" href="/list/server/?web">
												<i class="bi bi-globe-europe-africa"></i>
												<span class="top-bar-menu-link-label"><?= tohtml( _("Web")) ?></span>
											</a>
										</li>
									<?php } ?>
									<?php if ((isset($_SESSION['DNS_SYSTEM'])) && (!empty($_SESSION['DNS_SYSTEM']))) { ?>
										<li class="top-bar-menu-item">
											<a class="top-bar-menu-link <?php if (isset($_GET['dns'])) echo 'active' ?>" href="/list/server/?dns">
												<i class="bi bi-journal-bookmark"></i>
												<span class="top-bar-menu-link-label"><?= tohtml( _("DNS")) ?></span>
											</a>
										</li>
									<?php } ?>
									<?php if ((isset($_SESSION['MAIL_SYSTEM'])) && (!empty($_SESSION['MAIL_SYSTEM']))) { ?>
										<li class="top-bar-menu-item">
											<a class="top-bar-menu-link <?php if (isset($_GET['mail'])) echo 'active' ?>" href="/list/server/?mail">
												<i class="bi bi-envelope-paper"></i>
												<span class="top-bar-menu-link-label"><?= tohtml( _("Mail")) ?></span>
											</a>
										</li>
									<?php } ?>
									<?php if ((isset($_SESSION['DB_SYSTEM'])) && (!empty($_SESSION['DB_SYSTEM']))) { ?>
										<li class="top-bar-menu-item">
											<a class="top-bar-menu-link <?php if (isset($_GET['db'])) echo 'active' ?>" href="/list/server/?db">
												<i class="bi bi-database"></i>
												<span class="top-bar-menu-link-label"><?= tohtml( _("DB")) ?></span>
											</a>
										</li>
									<?php } ?>
									<li class="top-bar-menu-item">
										<a class="top-bar-menu-link" href="javascript:location.reload();" title="<?= tohtml( _("Refresh")) ?>">
											<i class="bi bi-arrow-clockwise"></i>
											<span class="u-hidden"><?= tohtml( _("Refresh")) ?></span>
										</a>
									</li>
									<li class="top-bar-menu-item">
										<a class="top-bar-menu-link top-bar-menu-link-logout" href="/logout/?<?= tohtml(http_build_query(["token" => $_SESSION["token"]])) ?>" title="<?= tohtml( _("Log out")) ?>">
											<i class="bi bi-box-arrow-right"></i>
											<span class="u-hidden"><?= tohtml( _("Log out")) ?></span>
										</a>
									</li>
								</ul>
							</div>
						</nav>
					</div>
				</div>
			</div>
		</header>

		<main class="app-content">

			<div class="logs-container">

				<div class="container">
					<pre class="console-output u-mt20">
