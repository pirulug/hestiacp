<footer class="app-footer">
	<div class="container">
		<p>
			<span class="app-footer-link">
				<?= !empty($_SESSION["APP_NAME"]) ? htmlentities($_SESSION["APP_NAME"]) : "Control Panel" ?>
			</span>
			v<?= $_SESSION["VERSION"] ?>
		</p>
	</div>
</footer>
