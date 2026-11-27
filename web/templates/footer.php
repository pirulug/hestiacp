		</main>
		<?php require $_SERVER["HESTIA"] . "/web/templates/includes/app-footer.php"; ?>
	</div> <?php // Closes `<div class="app">` in header.php ?>
<?php if (
	$_SESSION["userContext"] === "admin" &&
	$_SESSION["POLICY_SYSTEM_HIDE_SERVICES"] !== "yes" &&
	$_SESSION["UPDATE_AVAILABLE"] === "yes"
) {
?>
	<p x-data="{ open: true }" x-cloak x-show="open" class="updates-banner">
		<span class="u-text-bold">New updates are available!</span> To upgrade your server now, run
		<code>apt update && apt upgrade</code> from a shell session.
		(<button type="button" class="u-text-bold" x-on:click="open = false">
			hide
		</button>)
	</p>
<?php } ?>
	<div class="spinner-overlay js-spinner">
		<i class="bi bi-arrow-repeat bi-spin"></i>
	</div>

	<?php require $_SERVER["HESTIA"] . "/web/templates/includes/jump-to-top-link.php"; ?>

	<?php if ($_SESSION["DEBUG_MODE"] == "true") {
		require $_SERVER["HESTIA"] . "/web/templates/pages/debug_panel.php";
	} ?>

</body>
</html>
