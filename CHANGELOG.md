# Changelog

All notable changes to this project will be documented in this file.

## [v2026-11-6] - PiruHost Fork Release

- Add zero-dependency Rust binary `hestia-core` (`src/rust/hestia-core`) to optimize WireGuard peer JSON listing, Git status/log-tail/atomic `.conf` updates, and VPS `/proc` metrics
- Integrate `hestia-core` compilation into `src/hst_autocompile.sh` with safe Bash/PHP fallbacks

## [v2026-11-5] - PiruHost Fork Release

- Harden Git / Webhook integration with per-domain `flock` locking, asynchronous `202 Accepted` webhook dispatch, real build exit-code propagation, and symlink checks
- Add webhook secret rotation (`v-change-web-domain-git-secret`), optional Zero-Downtime symlink releases with rollback (`v-rollback-web-domain-git`), Node/Symfony/Composer presets, and active-deploy log polling in UI

## [v2026-11-4] - PiruHost Fork Release

- Bump `hestia-php` to `8.5.13`

## [v2026-11-3] - PiruHost Fork Release

- Sync with upstream HestiaCP `1.10.8` (local 2FA QR generation, Fail2Ban Dovecot filter, Nginx `1.30.7`, SnappyMail `3.1.3`, and security fixes)

## [v2026-11-2] - PiruHost Fork Release

- Sync `package-lock.json` with `bootstrap-icons` for clean `npm ci` builds
- Fix WireGuard QR code format-information bit ordering in `web/js/vpnQr.js`

## [v2026-11-1] - PiruHost Fork Release

### PiruHost Fork

- Adopt `v{year}-{month}-{change}` versioning scheme (`v2026-11-1` / package `2026-11-1`)
- Sync with upstream HestiaCP `1.10.6` and `1.10.7`
- Add native WireGuard VPN server and client management (`v-add-sys-vpn`, `v-change-sys-vpn`, `v-list-sys-vpn`, `v-add-vpn-client`, `v-list-vpn-clients`, `v-suspend-vpn-client`, `v-unsuspend-vpn-client`, `v-delete-vpn-client`) and web UI
- Add native Git/GitHub/GitLab domain integration with SSH deploy keys, webhook receiver (`web/api/webhook/index.php`), and Composer/PNPM/Node.js build pipeline
- Add system File Manager installation and repair scripts (`v-add-sys-filemanager`, `v-repair-sys-filemanager`, `v-delete-sys-filemanager`) with custom FileGator theme
- Add dark theme, responsive UI styles, semantic Bootstrap Icons action colors, and `guias/` documentation
