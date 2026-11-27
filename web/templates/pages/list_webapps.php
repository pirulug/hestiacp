<?php
$webapp_catalog_meta = [
	"WordPress"    => ["cat" => "cms",        "label" => _("CMS & Blogs"),        "desc" => _("Most popular blog and website CMS")],
	"ClassicPress" => ["cat" => "cms",        "label" => _("CMS & Blogs"),        "desc" => _("Business-focused CMS forked from WordPress")],
	"Joomla"       => ["cat" => "cms",        "label" => _("CMS & Blogs"),        "desc" => _("Flexible CMS for websites and portals")],
	"Drupal"       => ["cat" => "cms",        "label" => _("CMS & Blogs"),        "desc" => _("Enterprise-grade modular CMS")],
	"Grav"         => ["cat" => "cms",        "label" => _("CMS & Blogs"),        "desc" => _("Fast flat-file CMS with Markdown")],
	"ConcreteCMS"  => ["cat" => "cms",        "label" => _("CMS & Blogs"),        "desc" => _("In-context editing CMS for teams")],
	"Vvveb"        => ["cat" => "cms",        "label" => _("CMS & Blogs"),        "desc" => _("Visual drag-and-drop website CMS")],
	"DokuWiki"     => ["cat" => "wiki",       "label" => _("Cloud & Docs"),       "desc" => _("Lightweight wiki without database")],
	"MediaWiki"    => ["cat" => "wiki",       "label" => _("Cloud & Docs"),       "desc" => _("Collaborative wiki engine")],
	"Nextcloud"    => ["cat" => "wiki",       "label" => _("Cloud & Docs"),       "desc" => _("Self-hosted cloud storage and suite")],
	"Castopod"     => ["cat" => "wiki",       "label" => _("Cloud & Docs"),       "desc" => _("Open-source podcast hosting platform")],
	"Dolibarr"     => ["cat" => "wiki",       "label" => _("Cloud & Docs"),       "desc" => _("Modern ERP and CRM for businesses")],
	"Moodle"       => ["cat" => "wiki",       "label" => _("Cloud & Docs"),       "desc" => _("Open-source e-learning LMS platform")],
	"PrestaShop"   => ["cat" => "ecommerce",  "label" => _("E-Commerce"),         "desc" => _("Full-featured online store platform")],
	"OpenCart"     => ["cat" => "ecommerce",  "label" => _("E-Commerce"),         "desc" => _("Lightweight open-source shopping cart")],
	"Shopware"     => ["cat" => "ecommerce",  "label" => _("E-Commerce"),         "desc" => _("Modular e-commerce platform")],
	"ThirtyBees"   => ["cat" => "ecommerce",  "label" => _("E-Commerce"),         "desc" => _("Fast e-commerce fork of PrestaShop")],
	"QloApps"      => ["cat" => "ecommerce",  "label" => _("E-Commerce"),         "desc" => _("Hotel booking and reservation system")],
	"Laravel"      => ["cat" => "frameworks", "label" => _("Frameworks"),         "desc" => _("Modern PHP web application framework")],
	"Symfony"      => ["cat" => "frameworks", "label" => _("Frameworks"),         "desc" => _("High-performance PHP framework")],
	"Flarum"       => ["cat" => "community",  "label" => _("Community"),          "desc" => _("Modern and fast discussion forum")],
	"phpBB"        => ["cat" => "community",  "label" => _("Community"),          "desc" => _("Classic open-source bulletin board forum")],
	"NamelessMC"   => ["cat" => "community",  "label" => _("Community"),          "desc" => _("Community website for gaming servers")],
	"osTicket"     => ["cat" => "community",  "label" => _("Community"),          "desc" => _("Support ticket management system")],
];

$user_query_param = !empty($_GET["user"]) ? ["user" => $_GET["user"]] : [];
$back_query = http_build_query(["domain" => $v_domain] + $user_query_param);
?>
<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/edit/web/?<?= tohtml($back_query) ?>">
				<i class="bi bi-arrow-left icon-blue"></i><?= tohtml(_("Back")) ?>
			</a>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container">

	<div class="form-container form-container-wide">
		<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
			<div>
				<h1 style="margin-bottom: 4px;"><?= tohtml(_("Quick Install App")) ?></h1>
				<span style="font-size: 13px; opacity: 0.75;"><?= tohtml($v_domain) ?> (<span id="webapp_visible_count"><?= count($v_web_apps) ?></span> <?= tohtml(_("apps")) ?>)</span>
			</div>
			<div style="min-width: 240px; flex: 0 1 320px;">
				<input
					type="search"
					id="webapp_search_input"
					class="form-control"
					placeholder="<?= tohtml(_("Search app...")) ?>"
					oninput="filterWebappsCatalog()"
				>
			</div>
		</div>

		<?php show_alert_message($_SESSION); ?>

		<!-- Category filter pills -->
		<div id="webapp_category_filters" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px;">
			<button type="button" class="button" data-cat="all" onclick="setWebappCategory('all', this)"><?= tohtml(_("All")) ?></button>
			<button type="button" class="button button-secondary" data-cat="cms" onclick="setWebappCategory('cms', this)"><?= tohtml(_("CMS & Blogs")) ?></button>
			<button type="button" class="button button-secondary" data-cat="ecommerce" onclick="setWebappCategory('ecommerce', this)"><?= tohtml(_("E-Commerce")) ?></button>
			<button type="button" class="button button-secondary" data-cat="frameworks" onclick="setWebappCategory('frameworks', this)"><?= tohtml(_("Frameworks")) ?></button>
			<button type="button" class="button button-secondary" data-cat="wiki" onclick="setWebappCategory('wiki', this)"><?= tohtml(_("Cloud & Docs")) ?></button>
			<button type="button" class="button button-secondary" data-cat="community" onclick="setWebappCategory('community', this)"><?= tohtml(_("Community")) ?></button>
		</div>

		<div class="cards" id="webapp_cards_grid">
			<!-- List available web apps -->
			<?php foreach ($v_web_apps as $webapp): ?>
				<?php
					$is_installable = $webapp->isInstallable();
					$meta = $webapp_catalog_meta[$webapp->name] ?? ["cat" => "other", "label" => _("App"), "desc" => ""];
					$setup_query = http_build_query(["app" => $webapp->name, "domain" => $v_domain] + $user_query_param);
				?>
				<div
					class="card js-webapp-card <?= tohtml($is_installable ? "" : "disabled") ?>"
					data-name="<?= tohtml(strtolower($webapp->name)) ?>"
					data-desc="<?= tohtml(strtolower($meta["desc"])) ?>"
					data-cat="<?= tohtml($meta["cat"]) ?>"
				>
					<div class="card-thumb">
						<img src="/src/app/WebApp/Installers/<?= tohtml($webapp->name) ?>/<?= tohtml($webapp->thumbnail) ?>" alt="<?= tohtml($webapp->name) ?>" loading="lazy">
					</div>
					<div class="card-content">
						<div style="display: flex; justify-content: space-between; align-items: center; gap: 6px; margin-bottom: 4px;">
							<p class="card-title" style="margin: 0;"><?= tohtml($webapp->name) ?></p>
							<span class="badge" style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: rgba(128, 128, 128, 0.2);"><?= tohtml($meta["label"]) ?></span>
						</div>
						<p style="font-size: 12px; opacity: 0.78; margin-bottom: 6px; min-height: 32px;"><?= tohtml($meta["desc"]) ?></p>
						<p class="u-mb10" style="font-size: 12px;"><?= tohtml(_("Version")) ?>: <strong><?= tohtml($webapp->version) ?></strong></p>
						<?php if ($is_installable): ?>
							<a class="button" href="/add/webapp/?<?= tohtml($setup_query) ?>">
								<i class="bi bi-download u-mr5"></i><?= tohtml(_("Setup")) ?>
							</a>
						<?php else: ?>
							<button type="button" class="button button-secondary" disabled title="<?= tohtml(_("Required PHP version is not installed")) ?>">
								<i class="bi bi-exclamation-triangle icon-yellow u-mr5"></i><?= tohtml(_("PHP incompatible")) ?>
							</button>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<p id="webapp_empty_state" style="display: none; text-align: center; padding: 24px; opacity: 0.75;">
			<?= tohtml(_("No applications match your search filter.")) ?>
		</p>
	</div>

</div>

<script>
var _activeWebappCat = "all";

function setWebappCategory(cat, btnEl) {
	_activeWebappCat = cat;
	var buttons = document.querySelectorAll("#webapp_category_filters button");
	buttons.forEach(function (btn) {
		if (btn === btnEl) {
			btn.classList.remove("button-secondary");
		} else {
			btn.classList.add("button-secondary");
		}
	});
	filterWebappsCatalog();
}

function filterWebappsCatalog() {
	var query = (document.getElementById("webapp_search_input").value || "").trim().toLowerCase();
	var cards = document.querySelectorAll(".js-webapp-card");
	var visible = 0;

	cards.forEach(function (card) {
		var name = card.getAttribute("data-name") || "";
		var desc = card.getAttribute("data-desc") || "";
		var cat = card.getAttribute("data-cat") || "";
		var matchesCat = _activeWebappCat === "all" || cat === _activeWebappCat;
		var matchesQuery = !query || name.indexOf(query) !== -1 || desc.indexOf(query) !== -1;

		if (matchesCat && matchesQuery) {
			card.style.display = "";
			visible++;
		} else {
			card.style.display = "none";
		}
	});

	var countEl = document.getElementById("webapp_visible_count");
	if (countEl) countEl.textContent = String(visible);
	var emptyEl = document.getElementById("webapp_empty_state");
	if (emptyEl) emptyEl.style.display = visible === 0 ? "block" : "none";
}
</script>
