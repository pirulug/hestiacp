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

	<form id="main-form" name="v_add_vpn_client" method="post">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="ok" value="Add">

		<div class="form-container">
			<h1 class="u-mb20"><?= tohtml(_("Add WireGuard VPN Client")) ?></h1>
			<?php show_alert_message($_SESSION); ?>

			<div class="u-mb10">
				<label for="v_client" class="form-label">
					<?= tohtml(_("Client Name")) ?> <span class="hint">(e.g. windows-pc, homelab, phone-user)</span>
				</label>
				<input type="text" class="form-control" name="v_client" id="v_client" value="<?= tohtml($v_client ?? "") ?>" required autofocus>
			</div>

			<div class="u-mb10">
				<label for="v_tunnel_mode" class="form-label"><?= tohtml(_("Tunnel Mode")) ?></label>
				<select class="form-select" name="v_tunnel_mode" id="v_tunnel_mode">
					<option value="split" <?= (($v_tunnel_mode ?? "split") === "split") ? "selected" : "" ?>>
						<?= tohtml(_("Split Tunnel (Route VPN & private subnets only)")) ?>
					</option>
					<option value="full" <?= (($v_tunnel_mode ?? "") === "full") ? "selected" : "" ?>>
						<?= tohtml(_("Full Tunnel (Route all Internet traffic 0.0.0.0/0 through VPN)")) ?>
					</option>
				</select>
			</div>

			<div class="u-mb10">
				<label for="v_routed_nets" class="form-label">
					<?= tohtml(_("Routed Subnets behind this peer")) ?>
					<span class="hint">(<?= tohtml(_("Optional, e.g. 192.168.1.0/24 for Homelab routers/servers")) ?>)</span>
				</label>
				<input type="text" class="form-control" name="v_routed_nets" id="v_routed_nets" value="<?= tohtml($v_routed_nets ?? "") ?>" placeholder="192.168.1.0/24">
			</div>

			<div class="u-mb10">
				<label for="v_dns" class="form-label">
					<?= tohtml(_("Custom DNS")) ?>
					<span class="hint">(<?= tohtml(_("Leave blank to inherit VPN server default DNS")) ?>)</span>
				</label>
				<input type="text" class="form-control" name="v_dns" id="v_dns" value="<?= tohtml($v_dns ?? "") ?>" placeholder="1.1.1.1, 8.8.8.8">
			</div>

			<div class="u-mb10">
				<label for="v_comment" class="form-label"><?= tohtml(_("Comment")) ?></label>
				<input type="text" class="form-control" name="v_comment" id="v_comment" value="<?= tohtml($v_comment ?? "") ?>">
			</div>

		</div>
	</form>

</div>

