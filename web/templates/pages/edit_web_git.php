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
				<button type="button" class="button button-secondary" onclick="openDeployModal()">
					<i class="fas fa-rotate icon-green"></i><?= tohtml( _("Deploy / Pull Now")) ?>
				</button>
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
				<img src="/images/github.svg" alt="GitHub" width="24" height="24" style="vertical-align: middle; margin-right: 8px; display: inline-block;"><?= tohtml( _("Git / GitHub Integration")) ?> - <?= tohtml($v_domain) ?>
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

			<!-- Post deploy script with Laravel presets -->
			<div class="u-mb20">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
					<label for="v_post_deploy" class="form-label" style="margin-bottom: 0;">
						<?= tohtml( _("Comandos de Construcción y Despliegue (Build Pipeline)")) ?>
					</label>
					<div style="display: flex; gap: 6px;">
						<button type="button" class="button button-secondary" style="font-size: 11px; padding: 4px 8px;" onclick="applyLaravelPreset('full')">
							<i class="fab fa-laravel u-mr5" style="color: #f55247;"></i><?= tohtml( _("Preset Laravel Full")) ?>
						</button>
						<button type="button" class="button button-secondary" style="font-size: 11px; padding: 4px 8px;" onclick="applyLaravelPreset('api')">
							<i class="fab fa-laravel u-mr5" style="color: #f55247;"></i><?= tohtml( _("Preset Laravel API")) ?>
						</button>
						<button type="button" class="button button-secondary" style="font-size: 11px; padding: 4px 8px;" onclick="applyLaravelPreset('clear')">
							<i class="fas fa-eraser u-mr5"></i><?= tohtml( _("Limpiar")) ?>
						</button>
					</div>
				</div>
				<textarea
					class="form-control"
					name="v_post_deploy"
					id="v_post_deploy"
					rows="6"
					style="font-family: monospace; font-size: 12px; line-height: 1.5; tab-size: 4;"
					placeholder="composer install --no-dev --prefer-dist --optimize-autoloader&#10;pnpm install --frozen-lockfile && pnpm run build&#10;php artisan storage:link&#10;php artisan optimize:clear&#10;php artisan config:cache"
				><?= tohtml($v_post_deploy) ?></textarea>
				<small class="form-text text-muted">
					<?= tohtml( _("Comandos ejecutados automáticamente con permisos del usuario en el directorio del proyecto tras cada pull o webhook.")) ?>
				</small>
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

			<?php if (!empty($v_github_log)) { ?>
				<!-- GitHub Webhook Log Viewer -->
				<div class="u-mb20">
					<div style="background: #1e1e1e; border: 1px solid #333; border-radius: 6px; overflow: hidden;">
						<div style="background: #2d2d2d; padding: 8px 15px; display: flex; justify-content: space-between; align-items: center;">
							<span style="font-family: monospace; font-size: 12px; color: #58a6ff;">
								<i class="fab fa-github u-mr5"></i><?= tohtml( _("Registro de Eventos GitHub / Webhooks (GitHub Log)")) ?>
							</span>
							<button type="button" class="button button-secondary" style="font-size: 11px; padding: 2px 8px;" onclick="toggleGithubLog()">
								<span id="toggle_github_log_btn"><?= tohtml( _("Ocultar / Mostrar")) ?></span>
							</button>
						</div>
						<pre id="github_log_content" style="margin: 0; padding: 15px; font-family: monospace; font-size: 11px; line-height: 1.4; color: #c9d1d9; max-height: 250px; overflow-y: auto; white-space: pre-wrap; word-break: break-all;"><?= tohtml($v_github_log) ?></pre>
					</div>
				</div>
			<?php } ?>

			<?php if (!empty($v_build_log)) { ?>
				<!-- Build and Deploy Logs Viewer -->
				<div class="u-mb20">
					<div style="background: #1e1e1e; border: 1px solid #333; border-radius: 6px; overflow: hidden;">
						<div style="background: #2d2d2d; padding: 8px 15px; display: flex; justify-content: space-between; align-items: center;">
							<span style="font-family: monospace; font-size: 12px; color: #4ade80;">
								<i class="fas fa-terminal u-mr5"></i><?= tohtml( _("Último Registro de Construcción (Build Log)")) ?>
							</span>
							<button type="button" class="button button-secondary" style="font-size: 11px; padding: 2px 8px;" onclick="toggleBuildLog()">
								<span id="toggle_log_btn"><?= tohtml( _("Ocultar / Mostrar")) ?></span>
							</button>
						</div>
						<pre id="build_log_content" style="margin: 0; padding: 15px; font-family: monospace; font-size: 11px; line-height: 1.4; color: #d4d4d4; max-height: 250px; overflow-y: auto; white-space: pre-wrap; word-break: break-all;"><?= tohtml($v_build_log) ?></pre>
					</div>
				</div>
			<?php } ?>
		</div>
	</form>
</div>

<?php if ($v_configured) { ?>
	<!-- Deploy Modal Dialog -->
	<dialog id="deploy_dialog" class="modal" style="max-width: 520px; padding: 0;">
		<div style="padding: 16px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); display: flex; align-items: center; justify-content: space-between;">
			<h2 class="modal-title" style="margin: 0; padding: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
				<i class="fas fa-rotate icon-green"></i><?= tohtml( _("Confirmar Despliegue / Git Pull")) ?>
			</h2>
			<button type="button" onclick="closeDeployModal()" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 16px; opacity: 0.7; padding: 0;">
				<i class="fas fa-xmark"></i>
			</button>
		</div>
		<div class="modal-message" style="padding: 18px 20px; text-align: left; line-height: 1.5; font-size: 13px;">
			<p class="u-mb10">
				<?= tohtml( _("¿Cómo deseas realizar el despliegue para")) ?> <strong><?= tohtml($v_domain) ?></strong>?
			</p>
			<div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 6px; padding: 12px; margin-bottom: 12px;">
				<div style="font-weight: 600; margin-bottom: 4px; color: #4ade80; display: flex; align-items: center; gap: 6px;">
					<i class="fas fa-terminal"></i><?= tohtml( _("Comandos de Construcción y Despliegue (Build Pipeline)")) ?>
				</div>
				<?php if (!empty($v_post_deploy)) { ?>
					<div style="font-size: 11px; opacity: 0.85; font-family: monospace; white-space: pre-wrap; max-height: 90px; overflow-y: auto; background: rgba(0, 0, 0, 0.25); padding: 8px; border-radius: 4px; margin-top: 6px;"><?= tohtml($v_post_deploy) ?></div>
				<?php } else { ?>
					<div style="font-size: 11px; opacity: 0.7; font-style: italic; margin-top: 4px;">
						<?= tohtml( _("No hay comandos de construcción configurados en el pipeline.")) ?>
					</div>
				<?php } ?>
			</div>
			<p style="font-size: 12px; opacity: 0.85; margin: 0;">
				<?= tohtml( _("Puedes ejecutar los comandos del Build Pipeline (Composer, PNPM, Artisan, etc.) o realizar únicamente la actualización de archivos desde Git.")) ?>
			</p>
		</div>
		<div class="modal-options" style="padding: 12px 20px; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px;">
			<button type="button" class="button button-secondary" onclick="closeDeployModal()">
				<?= tohtml( _("Cancelar")) ?>
			</button>
			<button type="button" class="button button-secondary" onclick="executeDeploy(false)">
				<i class="fas fa-download icon-blue u-mr5"></i><?= tohtml( _("Solo Pull (Omitir Pipeline)")) ?>
			</button>
			<button type="button" class="button" onclick="executeDeploy(true)">
				<i class="fas fa-play icon-green u-mr5"></i><?= tohtml( _("Pull + Ejecutar Pipeline")) ?>
			</button>
		</div>
	</dialog>
<?php } ?>

<script>
function openDeployModal() {
	var dialog = document.getElementById("deploy_dialog");
	if (dialog) {
		dialog.showModal();
	}
}

function closeDeployModal() {
	var dialog = document.getElementById("deploy_dialog");
	if (dialog) {
		dialog.close();
	}
}

function executeDeploy(runBuild) {
	closeDeployModal();
	var spinner = document.querySelector(".js-spinner");
	if (spinner) {
		spinner.classList.add("is-active");
	}
	var baseUrl = "/edit/git/?<?= tohtml(http_build_query(["domain" => $v_domain, "action" => "pull", "token" => $_SESSION["token"]] + (!empty($_GET["user"]) ? ["user" => $_GET["user"]] : []))) ?>";
	window.location.href = baseUrl + "&build=" + (runBuild ? "yes" : "no");
}

document.addEventListener("DOMContentLoaded", function() {
	var dialog = document.getElementById("deploy_dialog");
	if (dialog) {
		dialog.addEventListener("click", function(event) {
			if (event.target === dialog) {
				dialog.close();
			}
		});
	}
});

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

function toggleGithubLog() {
	var log = document.getElementById("github_log_content");
	if (log.style.display === "none") {
		log.style.display = "block";
	} else {
		log.style.display = "none";
	}
}

function toggleBuildLog() {
	var log = document.getElementById("build_log_content");
	if (log.style.display === "none") {
		log.style.display = "block";
	} else {
		log.style.display = "none";
	}
}

function applyLaravelPreset(type) {
	var textarea = document.getElementById("v_post_deploy");
	if (type === "full") {
		textarea.value = "# 1. Dependencias PHP con Composer\ncomposer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --ignore-platform-req=php\n\n# 2. Compilar assets de frontend con PNPM\nif [ -f package.json ]; then\n    pnpm approve-builds --all 2>/dev/null || true\n    pnpm install\n    pnpm rebuild 2>/dev/null || true\n    pnpm run build\nfi\n\n# 3. Enlace simbolico y optimizacion de Laravel\nphp artisan storage:link\nphp artisan optimize:clear\nphp artisan config:cache\nphp artisan route:cache\nphp artisan view:cache";
	} else if (type === "api") {
		textarea.value = "# 1. Dependencias PHP con Composer\ncomposer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --ignore-platform-req=php\n\n# 2. Enlace simbolico y optimizacion de Laravel\nphp artisan storage:link\nphp artisan optimize:clear\nphp artisan config:cache\nphp artisan route:cache";
	} else if (type === "clear") {
		textarea.value = "";
	}
}
</script>
