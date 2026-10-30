<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/vpn/">
				<i class="bi bi-arrow-left icon-blue"></i><?= tohtml(_("Back")) ?>
			</a>
		</div>
		<div class="toolbar-buttons">
			<button type="submit" class="button" form="main-form">
				<i class="bi bi-floppy icon-purple"></i><?= tohtml(_("Save")) ?>
			</button>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<form id="main-form" name="v_edit_vpn" method="post">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="ok" value="Save">

		<div class="form-container">
			<h1 class="u-mb20"><?= tohtml(_("Configure WireGuard VPN Server")) ?></h1>
			<?php show_alert_message($_SESSION); ?>

			<div class="u-mb10">
				<label for="v_port" class="form-label"><?= tohtml(_("Listen Port (UDP)")) ?></label>
				<input type="text" class="form-control" name="v_port" id="v_port" value="<?= tohtml($v_port ?? "51820") ?>" required>
			</div>

			<div class="u-mb10">
				<label for="v_public_ip" class="form-label">
					<?= tohtml(_("Public IP or Domain Endpoint")) ?>
					<span class="hint">(<?= tohtml(_("Address used by remote peers to connect to this server")) ?>)</span>
				</label>
				<input type="text" class="form-control" name="v_public_ip" id="v_public_ip" value="<?= tohtml($v_public_ip ?? "") ?>" required>
			</div>

			<div class="u-mb10">
				<label for="v_dns" class="form-label"><?= tohtml(_("Default DNS Servers")) ?></label>
				<input type="text" class="form-control" name="v_dns" id="v_dns" value="<?= tohtml($v_dns ?? "1.1.1.1, 8.8.8.8") ?>">
			</div>

		</div>
	</form>

</div>

