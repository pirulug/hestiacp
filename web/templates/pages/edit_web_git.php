<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/edit/web/?<?= tohtml(http_build_query(["domain" => $v_domain, "token" => $_SESSION["token"]])) ?>">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml( _("Back")) ?>
			</a>
		</div>
		<div class="toolbar-buttons">
			<?php if ($v_configured) { ?>
				<a href="/edit/git/?<?= tohtml(http_build_query(["domain" => $v_domain, "action" => "pull", "token" => $_SESSION["token"]])) ?>" class="button button-secondary">
					<i class="fas fa-rotate icon-green"></i><?= tohtml( _("Deploy / Pull Now")) ?>
				</a>
				<a
					href="/edit/git/?<?= tohtml(http_build_query(["domain" => $v_domain, "action" => "delete", "token" => $_SESSION["token"]])) ?>"
					class="button button-secondary data-controls js-confirm-action"
					data-confirm-title="<?= tohtml( _("Disconnect Git")) ?>"
					data-confirm-message="<?= tohtml( _("Are you sure you want to disconnect Git repository from this domain?")) ?>"
				>
					<i class="fas fa-unlink icon-red"></i><?= tohtml( _("Disconnect Git")) ?>
				</a>
			<?php } ?>
			<button type="submit" class="button" form="main-form">
				<i class="fas fa-floppy-disk icon-purple"></i><?= tohtml( _("Save / Connect")) ?>
			</button>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">
	<form
		id="main-form"
		name="v_edit_git"
		method="post"
		class="js-enable-inputs-on-submit"
	>
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="save" value="save">

		<div class="form-container">
			<h1 class="u-mb20">
				<i class="fab fa-github u-mr10"></i><?= tohtml( _("Git / GitHub Integration")) ?> - <?= tohtml($v_domain) ?>
			</h1>

			<?php show_alert_message($_SESSION); ?>

			<?php if ($v_configured) { ?>
				<!-- Status Panel -->
				<div class="u-mb20" style="background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.1);">
					<h3 class="u-mb10" style="display: flex; align-items: center; justify-content: space-between;">
						<span><i class="fas fa-circle-check icon-green u-mr10"></i><?= tohtml( _("Repository Connected")) ?></span>
						<span class="badge" style="font-size: 12px; background: #2f81f7; color: #fff; padding: 3px 8px; border-radius: 4px;"><?= tohtml($v_branch) ?></span>
					</h3>
					<div style="font-size: 13px; line-height: 1.6;">
						<div><strong><?= tohtml( _("Repository")) ?>:</strong> <code><?= tohtml($v_repo) ?></code></div>
						<div><strong><?= tohtml( _("Last Commit")) ?>:</strong> <code><?= tohtml($v_last_commit) ?></code> - <?= tohtml($v_last_commit_msg) ?></div>
						<div><strong><?= tohtml( _("Author / Date")) ?>:</strong> <?= tohtml($v_last_commit_author) ?> (<?= tohtml($v_last_commit_date) ?>)</div>
					</div>
				</div>
			<?php } ?>

			<!-- Repository Details -->
			<div class="u-mb15">
				<label for="v_repo" class="form-label">
					<?= tohtml( _("Repository URL")) ?> <span class="optional">(HTTPS o SSH)</span>
				</label>
				<input
					type="text"
					class="form-control"
					name="v_repo"
					id="v_repo"
					value="<?= tohtml($v_repo) ?>"
					placeholder="https://github.com/usuario/repositorio.git o git@github.com:usuario/repositorio.git"
					required
				>
			</div>

			<div class="u-mb15" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
				<div>
					<label for="v_branch" class="form-label"><?= tohtml( _("Branch / Rama")) ?></label>
					<input
						type="text"
						class="form-control"
						name="v_branch"
						id="v_branch"
						value="<?= tohtml($v_branch) ?>"
						placeholder="main"
						required
					>
				</div>
				<div>
					<label for="v_deploy_dir" class="form-label"><?= tohtml( _("Deploy Directory (relativo al dominio)")) ?></label>
					<input
						type="text"
						class="form-control"
						name="v_deploy_dir"
						id="v_deploy_dir"
						value="<?= tohtml($v_deploy_dir) ?>"
						placeholder="public_html"
						required
					>
				</div>
			</div>

			<!-- Authentication Type -->
			<div class="u-mb20">
				<label for="v_auth_type" class="form-label"><?= tohtml( _("Authentication / Tipo de Acceso")) ?></label>
				<select class="form-select" name="v_auth_type" id="v_auth_type" onchange="toggleAuthFields(this.value)">
					<option value="none" <?= $v_auth_type === "none" ? "selected" : "" ?>><?= tohtml( _("Público (HTTPS sin credenciales)")) ?></option>
					<option value="ssh" <?= $v_auth_type === "ssh" ? "selected" : "" ?>><?= tohtml( _("Privado con Llave SSH (Deploy Key)")) ?></option>
					<option value="token" <?= $v_auth_type === "token" ? "selected" : "" ?>><?= tohtml( _("Privado con Token (Personal Access Token / PAT)")) ?></option>
				</select>
			</div>

			<!-- SSH Deploy Key Section -->
			<div id="ssh_key_section" class="u-mb20" style="<?= $v_auth_type === "ssh" ? "" : "display: none;" ?>">
				<div style="background: rgba(47, 129, 247, 0.1); border: 1px solid rgba(47, 129, 247, 0.3); border-radius: 6px; padding: 15px;">
					<h4 class="u-mb10"><i class="fas fa-key icon-blue u-mr10"></i><?= tohtml( _("SSH Deploy Key")) ?></h4>
					<p class="u-mb10" style="font-size: 13px;">
						<?= tohtml( _("Copia esta clave pública y agrégala en GitHub:")) ?><br>
						<strong>GitHub -> Tu Repositorio -> Settings -> Deploy keys -> Add deploy key</strong>
					</p>
					<div style="position: relative;">
						<textarea
							id="deploy_key_text"
							class="form-control"
							rows="3"
							readonly
							style="font-family: monospace; font-size: 12px;"
						><?= tohtml($v_deploy_key) ?></textarea>
						<button
							type="button"
							class="button button-secondary"
							style="position: absolute; right: 10px; bottom: 10px;"
							onclick="copyDeployKey()"
						>
							<i class="fas fa-copy u-mr5"></i><span id="copy_btn_text"><?= tohtml( _("Copiar Clave")) ?></span>
						</button>
					</div>
					<div class="u-mt10">
						<a href="/edit/git/?<?= tohtml(http_build_query(["domain" => $v_domain, "action" => "generate_key", "token" => $_SESSION["token"]])) ?>" class="button button-secondary" style="font-size: 12px;">
							<i class="fas fa-arrows-rotate u-mr5"></i><?= tohtml( _("Regenerar nueva llave SSH")) ?>
						</a>
					</div>
				</div>
			</div>

			<!-- Token Section -->
			<div id="token_section" class="u-mb20" style="<?= $v_auth_type === "token" ? "" : "display: none;" ?>">
				<label for="v_auth_token" class="form-label"><?= tohtml( _("GitHub Personal Access Token (PAT)")) ?></label>
				<input
					type="password"
					class="form-control"
					name="v_auth_token"
					id="v_auth_token"
					placeholder="ghp_xxxxxxxxxxxxxxxxxxxx"
				>
				<small class="form-text text-muted"><?= tohtml( _("Token de acceso de GitHub con permiso de lectura ('repo' o 'read:packages').")) ?></small>
			</div>

			<?php if ($v_configured && !empty($v_webhook_secret)) { ?>
				<!-- Webhook Integration -->
				<div class="u-mb20" style="background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.1);">
					<h4 class="u-mb10"><i class="fas fa-bolt icon-orange u-mr10"></i><?= tohtml( _("Despliegue Automático con Webhooks (GitHub / GitLab)")) ?></h4>
					<p class="u-mb10" style="font-size: 13px;">
						<?= tohtml( _("Configura el Webhook en GitHub para desplegar automáticamente al hacer 'git push':")) ?><br>
						<strong>GitHub -> Tu Repositorio -> Settings -> Webhooks -> Add webhook</strong>
					</p>

					<div class="u-mb10">
						<label class="form-label" style="font-size: 12px;">Payload URL:</label>
						<div style="display: flex; gap: 8px;">
							<input type="text" class="form-control" id="webhook_url_input" value="<?= tohtml($v_webhook_url) ?>" readonly style="font-family: monospace; font-size: 12px;">
							<button type="button" class="button button-secondary" onclick="copyWebhookUrl()">
								<i class="fas fa-copy"></i>
							</button>
						</div>
					</div>

					<div class="u-mb10">
						<label class="form-label" style="font-size: 12px;">Content type:</label>
						<code>application/json</code>
					</div>

					<div>
						<label class="form-label" style="font-size: 12px;">Secret:</label>
						<code><?= tohtml($v_webhook_secret) ?></code>
					</div>
				</div>
			<?php } ?>

			<!-- Post deploy script -->
			<div class="u-mb15">
				<label for="v_post_deploy" class="form-label"><?= tohtml( _("Comandos Post-Despliegue (Opcional)")) ?></label>
				<input
					type="text"
					class="form-control"
					name="v_post_deploy"
					id="v_post_deploy"
					value="<?= tohtml($v_post_deploy) ?>"
					placeholder="composer install --no-dev || php artisan migrate --force"
				>
				<small class="form-text text-muted"><?= tohtml( _("Comandos que se ejecutarán automáticamente en el directorio de despliegue tras cada pull.")) ?></small>
			</div>

			<!-- Auto deploy toggle -->
			<div class="form-check u-mb20">
				<input
					type="checkbox"
					class="form-check-input"
					name="v_auto_deploy"
					id="v_auto_deploy"
					<?= $v_auto_deploy ? "checked" : "" ?>
				>
				<label for="v_auto_deploy" class="form-check-label">
					<?= tohtml( _("Permitir despliegue automático al recibir Webhook")) ?>
				</label>
			</div>
		</div>
	</form>
</div>

<script>
function toggleAuthFields(val) {
	var sshSection = document.getElementById("ssh_key_section");
	var tokenSection = document.getElementById("token_section");
	if (val === "ssh") {
		sshSection.style.display = "block";
		tokenSection.style.display = "none";
	} else if (val === "token") {
		sshSection.style.display = "none";
		tokenSection.style.display = "block";
	} else {
		sshSection.style.display = "none";
		tokenSection.style.display = "none";
	}
}

function copyDeployKey() {
	var copyText = document.getElementById("deploy_key_text");
	copyText.select();
	copyText.setSelectionRange(0, 99999);
	navigator.clipboard.writeText(copyText.value);
	var btnText = document.getElementById("copy_btn_text");
	btnText.innerText = "Copiado!";
	setTimeout(function() {
		btnText.innerText = "Copiar Clave";
	}, 2000);
}

function copyWebhookUrl() {
	var copyUrl = document.getElementById("webhook_url_input");
	copyUrl.select();
	copyUrl.setSelectionRange(0, 99999);
	navigator.clipboard.writeText(copyUrl.value);
	alert("URL del Webhook copiada al portapapeles");
}
</script>
