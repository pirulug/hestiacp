use std::collections::HashMap;

/// Parse a Hestia single-line `KEY='VALUE'` record into a key-value map.
pub fn parse_record(line: &str) -> HashMap<String, String> {
    let mut map = HashMap::new();
    let bytes = line.as_bytes();
    let len = bytes.len();
    let mut i = 0;

    while i < len {
        while i < len && bytes[i].is_ascii_whitespace() {
            i += 1;
        }
        if i >= len {
            break;
        }
        let key_start = i;
        while i < len && bytes[i] != b'=' && !bytes[i].is_ascii_whitespace() {
            i += 1;
        }
        if i >= len || bytes[i] != b'=' {
            while i < len && !bytes[i].is_ascii_whitespace() {
                i += 1;
            }
            continue;
        }
        let key = &line[key_start..i];
        i += 1; // skip '='

        let val = if i < len && bytes[i] == b'\'' {
            i += 1;
            let val_start = i;
            while i < len && bytes[i] != b'\'' {
                i += 1;
            }
            let v = &line[val_start..i];
            if i < len && bytes[i] == b'\'' {
                i += 1;
            }
            v.to_string()
        } else {
            let val_start = i;
            while i < len && !bytes[i].is_ascii_whitespace() {
                i += 1;
            }
            line[val_start..i].to_string()
        };

        map.insert(key.to_string(), val);
    }

    map
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn parses_hestia_kv_line() {
        let line = "ID='1' CLIENT='win-pc' IP='10.66.66.2' COMMENT='My Laptop' MTU=''";
        let m = parse_record(line);
        assert_eq!(m.get("ID").map(String::as_str), Some("1"));
        assert_eq!(m.get("CLIENT").map(String::as_str), Some("win-pc"));
        assert_eq!(m.get("COMMENT").map(String::as_str), Some("My Laptop"));
        assert_eq!(m.get("MTU").map(String::as_str), Some(""));
    }
}
