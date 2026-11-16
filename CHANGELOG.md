# Changelog

All notable changes to this project will be documented in this file.

## [v2026-11-1] - PiruHost Fork Release

### PiruHost Fork

- Adopt `v{year}-{month}-{change}` versioning scheme (`v2026-11-1` / package `2026-11-1`)
- Sync with upstream HestiaCP `1.10.6` and `1.10.7`
- Add native WireGuard VPN server and client management (`v-add-sys-vpn`, `v-change-sys-vpn`, `v-list-sys-vpn`, `v-add-vpn-client`, `v-list-vpn-clients`, `v-suspend-vpn-client`, `v-unsuspend-vpn-client`, `v-delete-vpn-client`) and web UI
- Add native Git/GitHub/GitLab domain integration with SSH deploy keys, webhook receiver (`web/api/webhook/index.php`), and Composer/PNPM/Node.js build pipeline
- Add system File Manager installation and repair scripts (`v-add-sys-filemanager`, `v-repair-sys-filemanager`, `v-delete-sys-filemanager`) with custom FileGator theme
- Add dark theme, responsive UI styles, semantic Bootstrap Icons action colors, and `guias/` documentation
- Sync `package-lock.json` with `bootstrap-icons` for clean `npm ci` builds
