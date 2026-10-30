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

	<?php if (!empty($vpn_server)): ?>
		<div class="server-summary u-mb20">
			<div class="server-summary-icon">
				<i class="bi bi-shield-lock"></i>
			</div>
			<div class="server-summary-content">
				<h1 class="server-summary-title">WireGuard VPN (<?= tohtml($vpn_server["INTERFACE"] ?? "wg0") ?>)</h1>
				<ul class="server-summary-list">
					<li class="server-summary-item">
						<span class="server-summary-list-label"><?= tohtml(_("Status")) ?></span>
						<span class="server-summary-list-value u-text-bold">
							<?php if (($vpn_server["STATUS"] ?? "") === "running"): ?>
								<span class="status status-active"><?= tohtml(_("Running")) ?></span>
							<?php else: ?>
								<span class="status status-suspended"><?= tohtml(_("Stopped")) ?></span>
							<?php endif; ?>
						</span>
					</li>
					<li class="server-summary-item">
						<span class="server-summary-list-label"><?= tohtml(_("Port")) ?></span>
						<span class="server-summary-list-value"><?= tohtml($vpn_server["PORT"] ?? "51820") ?> (UDP)</span>
					</li>
					<li class="server-summary-item">
						<span class="server-summary-list-label"><?= tohtml(_("Subnet")) ?></span>
						<span class="server-summary-list-value"><?= tohtml($vpn_server["SUBNET"] ?? "10.66.66.0/24") ?></span>
					</li>
					<li class="server-summary-item">
						<span class="server-summary-list-label"><?= tohtml(_("Server IP")) ?></span>
						<span class="server-summary-list-value"><?= tohtml($vpn_server["SERVER_IP"] ?? "") ?></span>
					</li>
					<li class="server-summary-item">
						<span class="server-summary-list-label"><?= tohtml(_("Public Endpoint")) ?></span>
						<span class="server-summary-list-value"><?= tohtml($vpn_server["PUBLIC_IP"] ?? "") ?>:<?= tohtml($vpn_server["PORT"] ?? "") ?></span>
					</li>
					<li class="server-summary-item">
						<span class="server-summary-list-label"><?= tohtml(_("Peers")) ?></span>
						<span class="server-summary-list-value"><?= count($data) ?></span>
					</li>
				</ul>
			</div>
		</div>
	<?php endif; ?>

	<h1 class="u-text-center u-hide-desktop u-pr30 u-mb20 u-pl30"><?= tohtml(_("VPN Clients")) ?></h1>

	<div class="units-table js-units-container">
		<div class="units-table-header">
			<div class="units-table-cell"><?= tohtml(_("Client")) ?></div>
			<div class="units-table-cell"></div>
			<div class="units-table-cell"><?= tohtml(_("VPN IP")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("Routed Subnets")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("Mode")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("Comment")) ?></div>
			<div class="units-table-cell"><?= tohtml(_("Date")) ?></div>
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
						<!-- Actions menu -->
						<div class="actions-panel">
							<a
								class="actions-panel-item"
								href="/download/vpn/?client=<?= urlencode($client) ?>"
								title="<?= tohtml(_("Download Configuration (.conf)")) ?>"
							>
								<i class="bi bi-download"></i>
								<span class="actions-panel-label"><?= tohtml(_("Download")) ?></span>
							</a>
							<?php if (!$is_suspended): ?>
								<a
									class="actions-panel-item"
									href="/suspend/vpn/?client=<?= urlencode($client) ?>&token=<?= tohtml($_SESSION["token"]) ?>"
									title="<?= tohtml(_("Suspend")) ?>"
								>
									<i class="bi bi-pause icon-highlight"></i>
									<span class="actions-panel-label"><?= tohtml(_("Suspend")) ?></span>
								</a>
							<?php else: ?>
								<a
									class="actions-panel-item"
									href="/unsuspend/vpn/?client=<?= urlencode($client) ?>&token=<?= tohtml($_SESSION["token"]) ?>"
									title="<?= tohtml(_("Unsuspend")) ?>"
								>
									<i class="bi bi-play icon-green"></i>
									<span class="actions-panel-label"><?= tohtml(_("Unsuspend")) ?></span>
								</a>
							<?php endif; ?>
							<a
								class="actions-panel-item actions-panel-item-danger js-confirm-action"
								href="/delete/vpn/?client=<?= urlencode($client) ?>&token=<?= tohtml($_SESSION["token"]) ?>"
								title="<?= tohtml(_("Delete")) ?>"
								data-confirm-title="<?= tohtml(_("Delete Client")) ?>"
								data-confirm-message="<?= tohtml(sprintf(_("Are you sure you want to delete client %s?"), $client)) ?>"
							>
								<i class="bi bi-trash icon-red"></i>
								<span class="actions-panel-label"><?= tohtml(_("Delete")) ?></span>
							</a>
						</div>
					</div>

					<div class="units-table-cell">
						<code><?= tohtml($item["IP"] ?? "") ?></code>
					</div>

					<div class="units-table-cell">
						<code><?= tohtml(!empty($item["ROUTED_NETS"]) ? $item["ROUTED_NETS"] : "-") ?></code>
					</div>

					<div class="units-table-cell">
						<span><?= tohtml(ucfirst($item["TUNNEL_MODE"] ?? "split")) ?></span>
					</div>

					<div class="units-table-cell">
						<span><?= tohtml($item["COMMENT"] ?? "-") ?></span>
					</div>

					<div class="units-table-cell">
						<span><?= tohtml($item["DATE"] ?? "") ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

