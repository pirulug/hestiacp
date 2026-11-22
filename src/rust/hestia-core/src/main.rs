mod json;
mod kv;

use std::collections::HashMap;
use std::env;
use std::fs::{self, File, OpenOptions};
use std::io::{Read, Seek, SeekFrom, Write};
use std::path::{Path, PathBuf};
use std::process::{self, Command};

fn main() {
    let args: Vec<String> = env::args().collect();
    if args.len() < 2 {
        eprintln!("Usage: hestia-core <wg-clients-json|git-status-json|conf-upsert|log-tail|sys-metrics-json> ...");
        process::exit(2);
    }

    let code = match args[1].as_str() {
        "wg-clients-json" => cmd_wg_clients_json(&args[2..]),
        "git-status-json" => cmd_git_status_json(&args[2..]),
        "conf-upsert" => cmd_conf_upsert(&args[2..]),
        "log-tail" => cmd_log_tail(&args[2..]),
        "sys-metrics-json" => cmd_sys_metrics_json(),
        other => {
            eprintln!("Unknown subcommand: {other}");
            2
        }
    };

    process::exit(code);
}

struct WgPeerStats {
    endpoint: String,
    handshake: String,
    rx: String,
    tx: String,
}

fn parse_wg_dump(dump: &str) -> HashMap<String, WgPeerStats> {
    let mut map = HashMap::new();
    for line in dump.lines() {
        let cols: Vec<&str> = line.split('\t').collect();
        // Peer lines in `wg show <iface> dump` have 8 tab-separated fields
        if cols.len() >= 8 && !cols[0].is_empty() {
            map.insert(
                cols[0].to_string(),
                WgPeerStats {
                    endpoint: if cols[2].is_empty() { "none".to_string() } else { cols[2].to_string() },
                    handshake: if cols[4].is_empty() { "0".to_string() } else { cols[4].to_string() },
                    rx: if cols[5].is_empty() { "0".to_string() } else { cols[5].to_string() },
                    tx: if cols[6].is_empty() { "0".to_string() } else { cols[6].to_string() },
                },
            );
        }
    }
    map
}

fn cmd_wg_clients_json(args: &[String]) -> i32 {
    if args.is_empty() {
        eprintln!("Usage: hestia-core wg-clients-json <peers_conf> [<wg_iface>]");
        return 2;
    }
    let peers_conf = &args[0];
    let wg_iface = args.get(1).map(String::as_str).unwrap_or("wg0");

    let wg_dump_text = Command::new("wg")
        .args(["show", wg_iface, "dump"])
        .output()
        .ok()
        .filter(|o| o.status.success())
        .map(|o| String::from_utf8_lossy(&o.stdout).into_owned())
        .unwrap_or_default();
    let stats_map = parse_wg_dump(&wg_dump_text);

    let content = fs::read_to_string(peers_conf).unwrap_or_default();
    let mut out = String::from("{\n");
    let mut first = true;

    for raw_line in content.lines() {
        let line = raw_line.trim();
        if line.is_empty() {
            continue;
        }
        let rec = kv::parse_record(line);
        let client = match rec.get("CLIENT") {
            Some(c) if !c.is_empty() => c.as_str(),
            _ => continue,
        };
        let pubkey = rec.get("PUBLIC_KEY").map(String::as_str).unwrap_or("");
        let st = stats_map.get(pubkey);

        if !first {
            out.push_str(",\n");
        }
        first = false;

        out.push_str("  \"");
        out.push_str(&json::escape(client));
        out.push_str("\": {");

        let get = |k: &str| rec.get(k).map(String::as_str).unwrap_or("");
        json::str_field(&mut out, "ID", get("ID"), false);
        json::str_field(&mut out, "IP", get("IP"), true);
        json::str_field(&mut out, "ROUTED_NETS", get("ROUTED_NETS"), true);
        json::str_field(&mut out, "TUNNEL_MODE", get("TUNNEL_MODE"), true);
        json::str_field(&mut out, "PUBLIC_KEY", pubkey, true);
        json::str_field(&mut out, "DNS", get("DNS"), true);
        json::str_field(&mut out, "MTU", get("MTU"), true);
        json::str_field(&mut out, "COMMENT", get("COMMENT"), true);
        json::str_field(&mut out, "SUSPENDED", get("SUSPENDED"), true);
        json::str_field(&mut out, "TIME", get("TIME"), true);
        json::str_field(&mut out, "DATE", get("DATE"), true);
        json::str_field(&mut out, "ENDPOINT", st.map(|s| s.endpoint.as_str()).unwrap_or("none"), true);
        json::str_field(&mut out, "LATEST_HANDSHAKE", st.map(|s| s.handshake.as_str()).unwrap_or("0"), true);
        json::str_field(&mut out, "TRANSFER_RX", st.map(|s| s.rx.as_str()).unwrap_or("0"), true);
        json::str_field(&mut out, "TRANSFER_TX", st.map(|s| s.tx.as_str()).unwrap_or("0"), true);
        out.push('}');
    }
    out.push_str("\n}");
    println!("{out}");
    0
}

fn cmd_git_status_json(args: &[String]) -> i32 {
    if args.len() < 4 {
        eprintln!("Usage: hestia-core git-status-json <git_conf> <domain> <home_dir> <user>");
        return 2;
    }
    let git_conf = &args[0];
    let domain = &args[1];
    let home_dir = &args[2];
    let user = &args[3];

    let clean_domain = domain.replace('.', "_");
    let pub_path = PathBuf::from(home_dir)
        .join(user)
        .join(".ssh")
        .join(format!("id_git_{clean_domain}.pub"));
    let deploy_key = fs::read_to_string(&pub_path).unwrap_or_default().trim().to_string();

    let conf_content = fs::read_to_string(git_conf).unwrap_or_default();
    let needle = format!("DOMAIN='{domain}'");
    let matched_line = conf_content.lines().find(|l| l.contains(&needle));

    let Some(line) = matched_line else {
        let mut out = String::from("{");
        json::str_field(&mut out, "CONFIGURED", "no", false);
        json::str_field(&mut out, "DEPLOY_KEY", &deploy_key, true);
        out.push('}');
        println!("{out}");
        return 0;
    };

    let rec = kv::parse_record(line);
    let get = |k: &str| rec.get(k).map(String::as_str).unwrap_or("");
    let deploy_dir = {
        let d = get("DEPLOY_DIR");
        if d.is_empty() { "public_html" } else { d }
    };

    let domain_root = PathBuf::from(home_dir).join(user).join("web").join(domain);
    let target_path = domain_root.join(deploy_dir);
    let releases_dir = domain_root.join(".git_releases");

    let current_release = if fs::symlink_metadata(&target_path)
        .map(|m| m.file_type().is_symlink())
        .unwrap_or(false)
    {
        fs::canonicalize(&target_path)
            .ok()
            .and_then(|p| p.file_name().map(|n| n.to_string_lossy().into_owned()))
            .unwrap_or_default()
    } else {
        String::new()
    };

    let mut releases: Vec<(std::time::SystemTime, String)> = Vec::new();
    if let Ok(entries) = fs::read_dir(&releases_dir) {
        for entry in entries.flatten() {
            let name = entry.file_name().to_string_lossy().into_owned();
            let mtime = entry
                .metadata()
                .and_then(|m| m.modified())
                .unwrap_or(std::time::SystemTime::UNIX_EPOCH);
            releases.push((mtime, name));
        }
    }
    releases.sort_by(|a, b| b.0.cmp(&a.0));
    releases.truncate(10);

    let mut curr_commit = get("LAST_COMMIT").to_string();
    let mut curr_msg = get("LAST_COMMIT_MSG").to_string();
    let mut curr_author = get("LAST_COMMIT_AUTHOR").to_string();
    let mut curr_date = format!("{} {}", get("DATE"), get("TIME")).trim().to_string();

    if target_path.join(".git").is_dir() {
        let tp = target_path.to_string_lossy().into_owned();
        if let Ok(out) = Command::new("git")
            .args(["-c", "safe.directory=*", "-C", &tp, "log", "-1", "--format=%h%x1f%s%x1f%an%x1f%cd", "--date=format:%Y-%m-%d %H:%M:%S"])
            .output()
        {
            if out.status.success() {
                let s = String::from_utf8_lossy(&out.stdout);
                let parts: Vec<&str> = s.trim_end().split('\x1f').collect();
                if parts.len() == 4 {
                    curr_commit = parts[0].to_string();
                    curr_msg = parts[1].to_string();
                    curr_author = parts[2].to_string();
                    curr_date = parts[3].to_string();
                }
            }
        }
    }

    let mut out = String::from("{");
    json::str_field(&mut out, "CONFIGURED", "yes", false);
    json::str_field(&mut out, "DOMAIN", get("DOMAIN"), true);
    json::str_field(&mut out, "REPO", get("REPO"), true);
    json::str_field(&mut out, "BRANCH", get("BRANCH"), true);
    json::str_field(&mut out, "DEPLOY_DIR", deploy_dir, true);
    json::str_field(&mut out, "AUTH_TYPE", get("AUTH_TYPE"), true);
    json::str_field(&mut out, "WEBHOOK_SECRET", get("WEBHOOK_SECRET"), true);
    json::str_field(&mut out, "LAST_COMMIT", &curr_commit, true);
    json::str_field(&mut out, "LAST_COMMIT_MSG", &curr_msg, true);
    json::str_field(&mut out, "LAST_COMMIT_AUTHOR", &curr_author, true);
    json::str_field(&mut out, "LAST_COMMIT_DATE", &curr_date, true);
    json::str_field(&mut out, "LAST_DEPLOY", get("LAST_DEPLOY"), true);
    let status = get("LAST_DEPLOY_STATUS");
    json::str_field(&mut out, "LAST_DEPLOY_STATUS", if status.is_empty() { "success" } else { status }, true);
    let dur = get("LAST_DEPLOY_DURATION");
    json::str_field(&mut out, "LAST_DEPLOY_DURATION", if dur.is_empty() { "0" } else { dur }, true);
    let zd = get("ZERO_DOWNTIME");
    json::str_field(&mut out, "ZERO_DOWNTIME", if zd.is_empty() { "no" } else { zd }, true);
    json::str_field(&mut out, "CURRENT_RELEASE", &current_release, true);

    out.push_str(",\"RELEASES\":[");
    for (idx, (_, rel)) in releases.iter().enumerate() {
        if idx > 0 {
            out.push(',');
        }
        out.push('"');
        out.push_str(&json::escape(rel));
        out.push('"');
    }
    out.push(']');

    json::str_field(&mut out, "POST_DEPLOY_B64", get("POST_DEPLOY_B64"), true);
    json::str_field(&mut out, "AUTO_DEPLOY", get("AUTO_DEPLOY"), true);
    json::str_field(&mut out, "DEPLOY_KEY", &deploy_key, true);
    json::str_field(&mut out, "DATE", get("DATE"), true);
    json::str_field(&mut out, "TIME", get("TIME"), true);
    out.push('}');
    println!("{out}");
    0
}

fn cmd_conf_upsert(args: &[String]) -> i32 {
    if args.len() < 4 {
        eprintln!("Usage: hestia-core conf-upsert <conf_file> <match_key> <match_val> <record_line>");
        return 2;
    }
    let conf_path = Path::new(&args[0]);
    let match_key = &args[1];
    let match_val = &args[2];
    let new_record = &args[3];
    let needle = format!("{match_key}='{match_val}'");

    let existing = fs::read_to_string(conf_path).unwrap_or_default();
    let mut next_content = String::with_capacity(existing.len() + new_record.len() + 2);
    for line in existing.lines() {
        if line.trim().is_empty() || line.contains(&needle) {
            continue;
        }
        next_content.push_str(line);
        next_content.push('\n');
    }
    next_content.push_str(new_record);
    next_content.push('\n');

    let parent = conf_path.parent().unwrap_or_else(|| Path::new("."));
    let tmp_path = parent.join(format!(".{}.tmp.{}", conf_path.file_name().unwrap_or_default().to_string_lossy(), process::id()));

    let mut file = match OpenOptions::new().write(true).create(true).truncate(true).open(&tmp_path) {
        Ok(f) => f,
        Err(e) => {
            eprintln!("Failed to open temp file: {e}");
            return 1;
        }
    };
    if let Err(e) = file.write_all(next_content.as_bytes()).and_then(|_| file.sync_all()) {
        eprintln!("Failed to write temp file: {e}");
        let _ = fs::remove_file(&tmp_path);
        return 1;
    }
    drop(file);

    if let Err(e) = fs::rename(&tmp_path, conf_path) {
        eprintln!("Failed to replace conf file: {e}");
        let _ = fs::remove_file(&tmp_path);
        return 1;
    }
    0
}

fn cmd_log_tail(args: &[String]) -> i32 {
    if args.is_empty() {
        eprintln!("Usage: hestia-core log-tail <filepath> [--lines N] [--max-bytes B] [--expected-dir D]");
        return 2;
    }
    let filepath = Path::new(&args[0]);
    let mut max_lines: usize = 120;
    let mut max_bytes: u64 = 65536;
    let mut expected_dir: Option<&str> = None;

    let mut i = 1;
    while i < args.len() {
        match args[i].as_str() {
            "--lines" if i + 1 < args.len() => {
                max_lines = args[i + 1].parse().unwrap_or(120);
                i += 2;
            }
            "--max-bytes" if i + 1 < args.len() => {
                max_bytes = args[i + 1].parse().unwrap_or(65536);
                i += 2;
            }
            "--expected-dir" if i + 1 < args.len() => {
                expected_dir = Some(&args[i + 1]);
                i += 2;
            }
            _ => i += 1,
        }
    }

    if let Some(parent) = filepath.parent() {
        if fs::symlink_metadata(parent).map(|m| m.file_type().is_symlink()).unwrap_or(false) {
            return 0;
        }
    }
    let meta = match fs::symlink_metadata(filepath) {
        Ok(m) if !m.file_type().is_symlink() && m.is_file() => m,
        _ => return 0,
    };

    if let Some(dir) = expected_dir {
        let Ok(real_dir) = fs::canonicalize(dir) else { return 0; };
        let Ok(real_file) = fs::canonicalize(filepath) else { return 0; };
        if !real_file.starts_with(&real_dir) {
            return 0;
        }
    }

    let size = meta.len();
    if size == 0 {
        return 0;
    }

    let Ok(mut f) = File::open(filepath) else { return 0; };
    let offset = size.saturating_sub(max_bytes);
    if offset > 0 && f.seek(SeekFrom::Start(offset)).is_err() {
        return 0;
    }

    let mut buf = Vec::with_capacity((size - offset) as usize);
    if f.read_to_end(&mut buf).is_err() {
        return 0;
    }

    let text = String::from_utf8_lossy(&buf).replace("\r\n", "\n");
    let mut lines: Vec<&str> = text.split('\n').collect();
    if offset > 0 && lines.len() > 1 {
        lines.remove(0);
    }
    if lines.len() > max_lines {
        let start = lines.len() - max_lines;
        lines = lines[start..].to_vec();
    }

    if offset > 0 {
        println!("... (showing last {max_lines} lines)");
    }
    print!("{}", lines.join("\n"));
    0
}

fn cmd_sys_metrics_json() -> i32 {
    let loadavg = fs::read_to_string("/proc/loadavg").unwrap_or_default();
    let load_parts: Vec<&str> = loadavg.split_whitespace().collect();
    let load_1m = load_parts.first().copied().unwrap_or("0");
    let load_5m = load_parts.get(1).copied().unwrap_or("0");
    let load_15m = load_parts.get(2).copied().unwrap_or("0");

    let uptime_raw = fs::read_to_string("/proc/uptime").unwrap_or_default();
    let uptime_sec = uptime_raw.split_whitespace().next().unwrap_or("0");

    let meminfo = fs::read_to_string("/proc/meminfo").unwrap_or_default();
    let mut mem_total_kb: u64 = 0;
    let mut mem_avail_kb: u64 = 0;
    for line in meminfo.lines() {
        if let Some(rest) = line.strip_prefix("MemTotal:") {
            mem_total_kb = rest.split_whitespace().next().and_then(|v| v.parse().ok()).unwrap_or(0);
        } else if let Some(rest) = line.strip_prefix("MemAvailable:") {
            mem_avail_kb = rest.split_whitespace().next().and_then(|v| v.parse().ok()).unwrap_or(0);
        }
    }
    let mem_used_kb = mem_total_kb.saturating_sub(mem_avail_kb);

    let mut out = String::from("{");
    json::str_field(&mut out, "LOAD_1M", load_1m, false);
    json::str_field(&mut out, "LOAD_5M", load_5m, true);
    json::str_field(&mut out, "LOAD_15M", load_15m, true);
    json::str_field(&mut out, "UPTIME_SECONDS", uptime_sec, true);
    json::str_field(&mut out, "MEM_TOTAL_KB", &mem_total_kb.to_string(), true);
    json::str_field(&mut out, "MEM_USED_KB", &mem_used_kb.to_string(), true);
    json::str_field(&mut out, "MEM_AVAILABLE_KB", &mem_avail_kb.to_string(), true);
    out.push('}');
    println!("{out}");
    0
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn parses_wg_dump_peers() {
        let sample = "priv\tpub\t51820\toff\npeerPub1\tpsk\t1.2.3.4:51820\t10.66.66.2/32\t1700000000\t1024\t2048\t25\n";
        let m = parse_wg_dump(sample);
        let p = m.get("peerPub1").expect("peer should exist");
        assert_eq!(p.endpoint, "1.2.3.4:51820");
        assert_eq!(p.handshake, "1700000000");
        assert_eq!(p.rx, "1024");
        assert_eq!(p.tx, "2048");
    }

    #[test]
    fn conf_upsert_replaces_atomically() {
        let dir = env::temp_dir().join(format!("hestia_core_test_{}", process::id()));
        let _ = fs::create_dir_all(&dir);
        let conf = dir.join("git.conf");
        fs::write(&conf, "DOMAIN='a.com' BRANCH='main'\nDOMAIN='b.com' BRANCH='dev'\n").unwrap();

        let code = cmd_conf_upsert(&[
            conf.to_string_lossy().into_owned(),
            "DOMAIN".to_string(),
            "a.com".to_string(),
            "DOMAIN='a.com' BRANCH='prod'".to_string(),
        ]);
        assert_eq!(code, 0);
        let result = fs::read_to_string(&conf).unwrap();
        assert!(result.contains("DOMAIN='b.com' BRANCH='dev'"));
        assert!(result.contains("DOMAIN='a.com' BRANCH='prod'"));
        assert!(!result.contains("BRANCH='main'"));
        let _ = fs::remove_dir_all(&dir);
    }

    #[test]
    fn log_tail_reads_last_lines() {
        let dir = env::temp_dir().join(format!("hestia_tail_test_{}", process::id()));
        let _ = fs::create_dir_all(&dir);
        let log = dir.join("build.log");
        fs::write(&log, "line1\nline2\nline3\nline4\nline5").unwrap();

        let code = cmd_log_tail(&[
            log.to_string_lossy().into_owned(),
            "--lines".to_string(),
            "2".to_string(),
            "--expected-dir".to_string(),
            dir.to_string_lossy().into_owned(),
        ]);
        assert_eq!(code, 0);
        let _ = fs::remove_dir_all(&dir);
    }
}
