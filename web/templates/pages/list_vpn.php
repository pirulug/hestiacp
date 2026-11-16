<?php
if (!function_exists("hestia_vpn_format_bytes")) {
	function hestia_vpn_format_bytes($bytes) {
		$bytes = (float) $bytes;
		if ($bytes <= 0) {
			return "0 B";
		}
		$units = ["B", "KB", "MB", "GB", "TB"];
		$idx = (int) floor(log($bytes, 1024));
		$idx = min($idx, count($units) - 1);
		return round($bytes / pow(1024, $idx), 1) . " " . $units[$idx];
	}
}

if (!function_exists("hestia_vpn_handshake_info")) {
	function hestia_vpn_handshake_info($ts) {
		$ts = (int) $ts;
		if ($ts <= 0) {
			return ["state" => "never", "label" => _("Never"), "detail" => _("No handshake")];
		}
		$diff = max(0, time() - $ts);
		if ($diff < 60) {
			$ago = $diff . "s";
		} elseif ($diff < 3600) {
			$ago = floor($diff / 60) . "m";
		} elseif ($diff < 86400) {
			$ago = floor($diff / 3600) . "h";
		} else {
			$ago = floor($diff / 86400) . "d";
		}
		if ($diff <= 180) {
			return ["state" => "online", "label" => _("Online"), "detail" => $ago . " " . _("ago")];
		}
		return ["state" => "idle", "label" => _("Idle"), "detail" => $ago . " " . _("ago")];
	}
}
?>
<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/server/">
				<i class="bi bi-arrow-left icon-blue"></i><?= tohtml(_("Back")) ?>
			</a>
			<a href="/add/vpn/" class="button button-secondary js-button-create">
				<i class="bi bi-plus-circle icon-green"></i><?= tohtml(_("Add Client")) ?>
			</a>
			<a href="/edit/vpn/" class="button button-secondary">
				<i class="bi bi-gear icon-maroon"></i><?= tohtml(_("Configure Server")) ?>
			</a>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<?php show_alert_message($_SESSION); ?>

	<?php if (!empty($vpn_server)): ?>
		<div class="server-summary u-mb20">
			<div class="server-summary-icon">
				<i class="bi bi-shield-lock"></i>
			</div>
			<div class="server-summary-content" style="min-width: 0;">
				<h1 class="server-summary-title">WireGuard VPN (<?= tohtml($vpn_server["INTERFACE"] ?? "wg0") ?>)</h1>
				<ul class="server-summary-list" style="flex-wrap: wrap; gap: 12px 20px; justify-content: flex-start;">
					<li class="server-summary-item" style="min-width: 110px; flex: 1 1 120px;">
						<span class="server-summary-list-label"><?= tohtml(_("Status")) ?></span>
						<span class="server-summary-list-value u-text-bold">
							<?php if (($vpn_server["STATUS"] ?? "") === "running"): ?>
								<span class="status status-active"><?= tohtml(_("Running")) ?></span>
							<?php else: ?>
								<span class="status status-suspended"><?= tohtml(_("Stopped")) ?></span>
							<?php endif; ?>
						</span>
					</li>
					<li class="server-summary-item" style="min-width: 110px; flex: 1 1 120px;">
						<span class="server-summary-list-label"><?= tohtml(_("Port")) ?></span>
						<span class="server-summary-list-value"><?= tohtml($vpn_server["PORT"] ?? "51820") ?> (UDP)</span>
					</li>
					<li class="server-summary-item" style="min-width: 130px; flex: 1 1 140px;">
						<span class="server-summary-list-label"><?= tohtml(_("Subnet")) ?></span>
						<span class="server-summary-list-value"><?= tohtml($vpn_server["SUBNET"] ?? "10.66.66.0/24") ?></span>
					</li>
					<li class="server-summary-item" style="min-width: 110px; flex: 1 1 120px;">
						<span class="server-summary-list-label"><?= tohtml(_("Server IP")) ?></span>
						<span class="server-summary-list-value"><?= tohtml($vpn_server["SERVER_IP"] ?? "") ?></span>
					</li>
					<li class="server-summary-item" style="min-width: 160px; flex: 1 1 180px;">
						<span class="server-summary-list-label"><?= tohtml(_("Public Endpoint")) ?></span>
						<span class="server-summary-list-value"><?= tohtml($vpn_server["PUBLIC_IP"] ?? "") ?>:<?= tohtml($vpn_server["PORT"] ?? "") ?></span>
					</li>
					<li class="server-summary-item" style="min-width: 70px; flex: 0 1 90px;">
						<span class="server-summary-list-label"><?= tohtml(_("Peers")) ?></span>
						<span class="server-summary-list-value"><?= count($data) ?></span>
					</li>
					<?php if (!empty($vpn_server["MTU"])): ?>
						<li class="server-summary-item" style="min-width: 70px; flex: 0 1 90px;">
							<span class="server-summary-list-label"><?= tohtml(_("MTU")) ?></span>
							<span class="server-summary-list-value"><?= tohtml($vpn_server["MTU"]) ?></span>
						</li>
					<?php endif; ?>
				</ul>
			</div>
		</div>
	<?php endif; ?>

	<h1 class="u-text-center u-hide-desktop u-pr30 u-mb20 u-pl30"><?= tohtml(_("VPN Clients")) ?></h1>

	<div style="overflow-x: auto; max-width: 100%;">
	<div class="units-table js-units-container">
		<div class="units-table-header">
			<div class="units-table-cell"><?= tohtml(_("Client")) ?></div>
			<div class="units-table-cell"></div>
			<div class="units-table-cell"><?= tohtml(_("Status")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("VPN IP")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("Traffic (RX / TX)")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("Routed Subnets")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("Mode")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("Comment")) ?></div>
		</div>

		<?php if (empty($data)): ?>
			<div class="units-table-row">
				<div class="units-table-cell u-text-center" style="grid-column: 1 / -1; padding: 2rem;">
					<p><?= tohtml(_("No WireGuard VPN clients found. Click 'Add Client' to create one.")) ?></p>
				</div>
			</div>
		<?php else: ?>
			<?php foreach ($data as $client => $item): ?>
				<?php
					$is_suspended = ($item["SUSPENDED"] ?? "no") === "yes";
					$hs_info = hestia_vpn_handshake_info($item["LATEST_HANDSHAKE"] ?? 0);
					$rx_human = hestia_vpn_format_bytes($item["TRANSFER_RX"] ?? 0);
					$tx_human = hestia_vpn_format_bytes($item["TRANSFER_TX"] ?? 0);
				?>
				<div class="units-table-row js-unit <?= $is_suspended ? "suspended" : "" ?>">
					<div class="units-table-cell">
						<div>
							<span class="u-text-bold"><?= tohtml($client) ?></span>
							<?php if ($is_suspended): ?>
								<span class="status status-suspended"><?= tohtml(_("Suspended")) ?></span>
							<?php endif; ?>
						</div>
					</div>

					<div class="units-table-cell">
						<ul class="units-table-row-actions">
							<li class="units-table-row-action" data-key-action="js">
								<button
									type="button"
									class="units-table-row-action-link"
									style="background: none; border: none; cursor: pointer; padding: 0;"
									onclick="openVpnQrModal(<?= htmlspecialchars(json_encode($client), ENT_QUOTES, "UTF-8") ?>)"
									title="<?= tohtml(_("Show QR Code")) ?>"
								>
									<i class="bi bi-qr-code icon-purple"></i>
									<span class="u-hide-desktop"><?= tohtml(_("QR Code")) ?></span>
								</button>
							</li>
							<li class="units-table-row-action shortcut-d" data-key-action="href">
								<a
									class="units-table-row-action-link"
									href="/download/vpn/?client=<?= urlencode($client) ?>"
									title="<?= tohtml(_("Download Configuration (.conf)")) ?>"
								>
									<i class="bi bi-download icon-teal"></i>
									<span class="u-hide-desktop"><?= tohtml(_("Download")) ?></span>
								</a>
							</li>
							<li class="units-table-row-action" data-key-action="js">
								<a
									class="units-table-row-action-link data-controls js-confirm-action"
									href="/list/vpn/?action=rotate_key&client=<?= urlencode($client) ?>&token=<?= tohtml($_SESSION["token"]) ?>"
									title="<?= tohtml(_("Rotate Keys")) ?>"
									data-confirm-title="<?= tohtml(_("Rotate Client Keys")) ?>"
									data-confirm-message="<?= tohtml(sprintf(_("Regenerate WireGuard keys for %s? The assigned IP (%s) will be preserved, but the device will need the new configuration."), $client, $item["IP"] ?? "")) ?>"
								>
									<i class="bi bi-key icon-orange"></i>
									<span class="u-hide-desktop"><?= tohtml(_("Rotate Keys")) ?></span>
								</a>
							</li>
							<?php if (!$is_suspended): ?>
								<li class="units-table-row-action shortcut-s" data-key-action="js">
									<a
										class="units-table-row-action-link data-controls js-confirm-action"
										href="/suspend/vpn/?client=<?= urlencode($client) ?>&token=<?= tohtml($_SESSION["token"]) ?>"
										title="<?= tohtml(_("Suspend")) ?>"
										data-confirm-title="<?= tohtml(_("Suspend Client")) ?>"
										data-confirm-message="<?= tohtml(sprintf(_("Are you sure you want to suspend client %s?"), $client)) ?>"
									>
										<i class="bi bi-pause-fill icon-yellow"></i>
										<span class="u-hide-desktop"><?= tohtml(_("Suspend")) ?></span>
									</a>
								</li>
							<?php else: ?>
								<li class="units-table-row-action shortcut-s" data-key-action="js">
									<a
										class="units-table-row-action-link data-controls js-confirm-action"
										href="/unsuspend/vpn/?client=<?= urlencode($client) ?>&token=<?= tohtml($_SESSION["token"]) ?>"
										title="<?= tohtml(_("Unsuspend")) ?>"
										data-confirm-title="<?= tohtml(_("Unsuspend Client")) ?>"
										data-confirm-message="<?= tohtml(sprintf(_("Are you sure you want to unsuspend client %s?"), $client)) ?>"
									>
										<i class="bi bi-play-fill icon-green"></i>
										<span class="u-hide-desktop"><?= tohtml(_("Unsuspend")) ?></span>
									</a>
								</li>
							<?php endif; ?>
							<li class="units-table-row-action shortcut-delete" data-key-action="js">
								<a
									class="units-table-row-action-link data-controls js-confirm-action"
									href="/delete/vpn/?client=<?= urlencode($client) ?>&token=<?= tohtml($_SESSION["token"]) ?>"
									title="<?= tohtml(_("Delete")) ?>"
									data-confirm-title="<?= tohtml(_("Delete Client")) ?>"
									data-confirm-message="<?= tohtml(sprintf(_("Are you sure you want to delete client %s?"), $client)) ?>"
								>
									<i class="bi bi-trash3 icon-red"></i>
									<span class="u-hide-desktop"><?= tohtml(_("Delete")) ?></span>
								</a>
							</li>
						</ul>
					</div>

					<div class="units-table-cell">
						<?php if ($is_suspended): ?>
							<span class="status status-suspended"><?= tohtml(_("Suspended")) ?></span>
						<?php elseif ($hs_info["state"] === "online"): ?>
							<span class="status status-active" title="<?= tohtml($item["ENDPOINT"] ?? "") ?>"><?= tohtml($hs_info["label"]) ?></span>
							<span class="u-text-small" style="opacity: 0.75; margin-left: 4px;"><?= tohtml($hs_info["detail"]) ?></span>
						<?php elseif ($hs_info["state"] === "idle"): ?>
							<span class="status" title="<?= tohtml($item["ENDPOINT"] ?? "") ?>"><?= tohtml($hs_info["label"]) ?></span>
							<span class="u-text-small" style="opacity: 0.75; margin-left: 4px;"><?= tohtml($hs_info["detail"]) ?></span>
						<?php else: ?>
							<span style="opacity: 0.65;"><?= tohtml($hs_info["label"]) ?></span>
						<?php endif; ?>
					</div>

					<div class="units-table-cell">
						<code><?= tohtml($item["IP"] ?? "") ?></code>
					</div>

					<div class="units-table-cell">
						<span style="font-family: monospace; font-size: 12px;"><?= tohtml($rx_human . " / " . $tx_human) ?></span>
					</div>

					<div class="units-table-cell">
						<code><?= tohtml(!empty($item["ROUTED_NETS"]) ? $item["ROUTED_NETS"] : "-") ?></code>
					</div>

					<div class="units-table-cell">
						<span><?= tohtml(ucfirst($item["TUNNEL_MODE"] ?? "split")) ?></span>
						<?php if (!empty($item["MTU"])): ?>
							<span style="opacity: 0.7; font-size: 11px;">(MTU <?= tohtml($item["MTU"]) ?>)</span>
						<?php endif; ?>
					</div>

					<div class="units-table-cell">
						<span><?= tohtml(!empty($item["COMMENT"]) ? $item["COMMENT"] : "-") ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	</div>
</div>

<!-- Local WireGuard QR Code Modal (lazy-loads /js/vpnQr.js on demand) -->
<dialog id="vpn_qr_dialog" class="modal" style="max-width: 420px; padding: 0;">
	<div style="padding: 14px 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); display: flex; align-items: center; justify-content: space-between;">
		<h2 class="modal-title" style="margin: 0; padding: 0; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
			<i class="bi bi-qr-code icon-purple"></i>
			<span id="vpn_qr_title">WireGuard QR</span>
		</h2>
		<button type="button" onclick="closeVpnQrModal()" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 16px; opacity: 0.7; padding: 0;">
			<i class="bi bi-x-lg"></i>
		</button>
	</div>
	<div style="padding: 18px; text-align: center;">
		<div id="vpn_qr_container" style="display: inline-flex; align-items: center; justify-content: center; min-width: 240px; min-height: 240px; background: #fff; border-radius: 6px; padding: 10px; color: #333; font-size: 13px;">
			<?= tohtml(_("Loading...")) ?>
		</div>
		<p style="font-size: 12px; opacity: 0.8; margin-top: 10px; margin-bottom: 0;">
			<?= tohtml(_("Scan with the official WireGuard app on Android or iOS.")) ?>
		</p>
	</div>
	<div class="modal-options" style="padding: 12px 18px; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px;">
		<button type="button" class="button button-secondary" id="vpn_qr_copy_btn" onclick="copyVpnQrConfig()" disabled>
			<i class="bi bi-clipboard icon-blue u-mr5"></i><?= tohtml(_("Copy Config")) ?>
		</button>
		<a id="vpn_qr_download_link" class="button" href="#">
			<i class="bi bi-download u-mr5"></i><?= tohtml(_("Download .conf")) ?>
		</a>
	</div>
</dialog>

<script>
var _vpnQrScriptPromise = null;
var _vpnQrCurrentConfig = "";

function loadVpnQrScript() {
	if (window.HestiaVpnQr) {
		return Promise.resolve();
	}
	if (!_vpnQrScriptPromise) {
		_vpnQrScriptPromise = new Promise(function (resolve, reject) {
			var script = document.createElement("script");
			script.src = "/js/vpnQr.js";
			script.onload = resolve;
			script.onerror = function () {
				_vpnQrScriptPromise = null;
				reject(new Error("Failed to load QR generator"));
			};
			document.head.appendChild(script);
		});
	}
	return _vpnQrScriptPromise;
}

function openVpnQrModal(client) {
	var dialog = document.getElementById("vpn_qr_dialog");
	var titleEl = document.getElementById("vpn_qr_title");
	var container = document.getElementById("vpn_qr_container");
	var copyBtn = document.getElementById("vpn_qr_copy_btn");
	var dlLink = document.getElementById("vpn_qr_download_link");

	if (!dialog) return;

	_vpnQrCurrentConfig = "";
	copyBtn.disabled = true;
	titleEl.textContent = "WireGuard QR - " + client;
	container.textContent = "<?= tohtml(_("Generating QR...")) ?>";
	dlLink.href = "/download/vpn/?client=" + encodeURIComponent(client);

	dialog.showModal();

	Promise.all([
		loadVpnQrScript(),
		fetch("/download/vpn/?client=" + encodeURIComponent(client) + "&inline=1", { credentials: "same-origin" }).then(function (res) {
			if (!res.ok) throw new Error("HTTP " + res.status);
			return res.text();
		})
	]).then(function (results) {
		var confText = results[1];
		_vpnQrCurrentConfig = confText;
		copyBtn.disabled = false;
		container.innerHTML = window.HestiaVpnQr.renderSvg(confText, 240);
	}).catch(function (err) {
		container.textContent = "Error: " + err.message;
	});
}

function closeVpnQrModal() {
	var dialog = document.getElementById("vpn_qr_dialog");
	if (dialog) dialog.close();
}

function copyVpnQrConfig() {
	if (!_vpnQrCurrentConfig || !navigator.clipboard) return;
	navigator.clipboard.writeText(_vpnQrCurrentConfig).then(function () {
		var btn = document.getElementById("vpn_qr_copy_btn");
		var orig = btn.innerHTML;
		btn.textContent = "<?= tohtml(_("Copied!")) ?>";
		setTimeout(function () {
			btn.innerHTML = orig;
		}, 1800);
	});
}

document.addEventListener("DOMContentLoaded", function () {
	var dialog = document.getElementById("vpn_qr_dialog");
	if (dialog) {
		dialog.addEventListener("click", function (event) {
			if (event.target === dialog) dialog.close();
		});
	}
});
</script>

