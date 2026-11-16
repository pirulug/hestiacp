/**
 * Self-contained QR Code SVG generator (ISO/IEC 18004, Byte mode, ECC-L, Versions 1-20).
 * Lazy-loaded on demand for WireGuard client configurations. No external requests.
 */
(function (global) {
	"use strict";

	// Total codewords and EC codewords per block + block structure for ECC-L, Versions 1..20
	// Format: [totalDataCodewords, ecPerBlock, g1Blocks, g1Data, g2Blocks, g2Data]
	var VERSION_TABLE = [
		null,
		[19, 7, 1, 19, 0, 0],     // 1
		[34, 10, 1, 34, 0, 0],    // 2
		[55, 15, 1, 55, 0, 0],    // 3
		[80, 20, 1, 80, 0, 0],    // 4
		[108, 26, 1, 108, 0, 0],  // 5
		[136, 18, 2, 68, 0, 0],   // 6
		[156, 20, 2, 78, 0, 0],   // 7
		[194, 24, 2, 97, 0, 0],   // 8
		[232, 30, 2, 116, 0, 0],  // 9
		[274, 18, 2, 68, 2, 69],  // 10
		[324, 20, 4, 81, 0, 0],   // 11
		[370, 24, 2, 92, 2, 93],  // 12
		[428, 26, 4, 107, 0, 0],  // 13
		[461, 30, 3, 115, 1, 116],// 14
		[523, 22, 5, 87, 1, 88],  // 15
		[589, 24, 5, 98, 1, 99],  // 16
		[647, 28, 1, 107, 5, 108],// 17
		[721, 30, 5, 120, 1, 121],// 18
		[795, 28, 3, 113, 4, 114],// 19
		[861, 28, 3, 107, 5, 108] // 20
	];

	var ALIGNMENT_POS = [
		null, [], [6, 18], [6, 22], [6, 26], [6, 30], [6, 34],
		[6, 22, 38], [6, 24, 42], [6, 26, 46], [6, 28, 50],
		[6, 30, 54], [6, 32, 58], [6, 34, 62], [6, 26, 46, 66],
		[6, 26, 48, 70], [6, 26, 50, 74], [6, 30, 54, 78],
		[6, 30, 56, 82], [6, 30, 58, 86], [6, 34, 62, 90]
	];

	// GF(256) log/exp tables with primitive polynomial 0x11d
	var GF_EXP = new Uint8Array(512);
	var GF_LOG = new Uint8Array(256);
	(function initGF() {
		var x = 1;
		for (var i = 0; i < 255; i++) {
			GF_EXP[i] = x;
			GF_LOG[x] = i;
			x <<= 1;
			if (x & 0x100) x ^= 0x11d;
		}
		for (var j = 255; j < 512; j++) {
			GF_EXP[j] = GF_EXP[j - 255];
		}
	})();

	function gfMul(a, b) {
		if (a === 0 || b === 0) return 0;
		return GF_EXP[GF_LOG[a] + GF_LOG[b]];
	}

	function rsGeneratorPoly(ecLen) {
		var poly = [1];
		for (var i = 0; i < ecLen; i++) {
			var next = new Array(poly.length + 1).fill(0);
			var factor = GF_EXP[i];
			for (var j = 0; j < poly.length; j++) {
				next[j] ^= poly[j];
				next[j + 1] ^= gfMul(poly[j], factor);
			}
			poly = next;
		}
		return poly;
	}

	function rsComputeEC(data, ecLen) {
		var gen = rsGeneratorPoly(ecLen);
		var buf = new Uint8Array(data.length + ecLen);
		buf.set(data);
		for (var i = 0; i < data.length; i++) {
			var coef = buf[i];
			if (coef !== 0) {
				for (var j = 0; j < gen.length; j++) {
					buf[i + j] ^= gfMul(gen[j], coef);
				}
			}
		}
		return buf.slice(data.length);
	}

	function encodeUtf8(str) {
		if (typeof TextEncoder !== "undefined") {
			return Array.from(new TextEncoder().encode(str));
		}
		var utf8 = [];
		for (var i = 0; i < str.length; i++) {
			var c = str.charCodeAt(i);
			if (c < 128) utf8.push(c);
			else if (c < 2048) utf8.push((c >> 6) | 192, (c & 63) | 128);
			else utf8.push((c >> 12) | 224, ((c >> 6) & 63) | 128, (c & 63) | 128);
		}
		return utf8;
	}

	function selectVersion(byteLen) {
		for (var v = 1; v < VERSION_TABLE.length; v++) {
			var capBits = VERSION_TABLE[v][0] * 8;
			var countBits = v < 10 ? 8 : 16;
			var neededBits = 4 + countBits + byteLen * 8;
			if (neededBits <= capBits) return v;
		}
		throw new Error("Configuration text exceeds maximum QR capacity (Version 20)");
	}

	function buildCodewords(bytes, version) {
		var spec = VERSION_TABLE[version];
		var totalData = spec[0];
		var bits = [];

		function pushBits(val, len) {
			for (var i = len - 1; i >= 0; i--) {
				bits.push((val >> i) & 1);
			}
		}

		// Byte mode indicator (0100) + character count
		pushBits(4, 4);
		pushBits(bytes.length, version < 10 ? 8 : 16);
		for (var i = 0; i < bytes.length; i++) {
			pushBits(bytes[i], 8);
		}

		var capBits = totalData * 8;
		var termLen = Math.min(4, capBits - bits.length);
		pushBits(0, termLen);
		while (bits.length % 8 !== 0) bits.push(0);

		var data = [];
		for (var b = 0; b < bits.length; b += 8) {
			var byteVal = 0;
			for (var k = 0; k < 8; k++) byteVal = (byteVal << 1) | bits[b + k];
			data.push(byteVal);
		}

		var padBytes = [0xec, 0x11];
		var padIdx = 0;
		while (data.length < totalData) {
			data.push(padBytes[padIdx % 2]);
			padIdx++;
		}

		// Split into blocks and compute Reed-Solomon EC
		var ecLen = spec[1];
		var blocks = [];
		var ecBlocks = [];
		var offset = 0;

		for (var g1 = 0; g1 < spec[2]; g1++) {
			var blk1 = data.slice(offset, offset + spec[3]);
			offset += spec[3];
			blocks.push(blk1);
			ecBlocks.push(rsComputeEC(blk1, ecLen));
		}
		for (var g2 = 0; g2 < spec[4]; g2++) {
			var blk2 = data.slice(offset, offset + spec[5]);
			offset += spec[5];
			blocks.push(blk2);
			ecBlocks.push(rsComputeEC(blk2, ecLen));
		}

		var interleaved = [];
		var maxDataLen = Math.max(spec[3], spec[5] || 0);
		for (var col = 0; col < maxDataLen; col++) {
			for (var r = 0; r < blocks.length; r++) {
				if (col < blocks[r].length) interleaved.push(blocks[r][col]);
			}
		}
		for (var ecol = 0; ecol < ecLen; ecol++) {
			for (var er = 0; er < ecBlocks.length; er++) {
				interleaved.push(ecBlocks[er][ecol]);
			}
		}
		return interleaved;
	}

	function createMatrix(version) {
		var size = version * 4 + 17;
		var modules = Array.from({ length: size }, function () { return new Int8Array(size); });
		var isFunc = Array.from({ length: size }, function () { return new Uint8Array(size); });

		function setFunc(r, c, val) {
			modules[r][c] = val ? 1 : 0;
			isFunc[r][c] = 1;
		}

		function drawFinder(top, left) {
			for (var r = -1; r <= 7; r++) {
				for (var c = -1; c <= 7; c++) {
					var rr = top + r;
					var cc = left + c;
					if (rr < 0 || rr >= size || cc < 0 || cc >= size) continue;
					var inBox = r >= 0 && r <= 6 && c >= 0 && c <= 6;
					var onBorder = r === 0 || r === 6 || c === 0 || c === 6;
					var inCenter = r >= 2 && r <= 4 && c >= 2 && c <= 4;
					setFunc(rr, cc, inBox && (onBorder || inCenter));
				}
			}
		}

		function drawAlignment(cr, cc) {
			for (var r = -2; r <= 2; r++) {
				for (var c = -2; c <= 2; c++) {
					var dark = Math.abs(r) === 2 || Math.abs(c) === 2 || (r === 0 && c === 0);
					setFunc(cr + r, cc + c, dark);
				}
			}
		}

		drawFinder(0, 0);
		drawFinder(0, size - 7);
		drawFinder(size - 7, 0);

		// Timing patterns
		for (var i = 8; i < size - 8; i++) {
			setFunc(6, i, i % 2 === 0);
			setFunc(i, 6, i % 2 === 0);
		}

		// Alignment patterns
		var align = ALIGNMENT_POS[version];
		for (var a = 0; a < align.length; a++) {
			for (var b = 0; b < align.length; b++) {
				var r = align[a];
				var c = align[b];
				if ((r < 9 && c < 9) || (r < 9 && c > size - 10) || (r > size - 10 && c < 9)) {
					continue;
				}
				drawAlignment(r, c);
			}
		}

		// Dark module & reserve format info
		setFunc(size - 8, 8, true);
		for (var f = 0; f < 9; f++) {
			if (f !== 6) {
				isFunc[8][f] = 1;
				isFunc[f][8] = 1;
			}
		}
		for (var f2 = 0; f2 < 8; f2++) {
			isFunc[8][size - 1 - f2] = 1;
			isFunc[size - 1 - f2][8] = 1;
		}

		// Reserve version info for v >= 7
		if (version >= 7) {
			var vInfo = version << 12;
			var rem = vInfo;
			for (var bit = 17; bit >= 12; bit--) {
				if ((rem >> bit) & 1) rem ^= 0x1f25 << (bit - 12);
			}
			vInfo |= rem;
			for (var vi = 0; vi < 18; vi++) {
				var vbit = ((vInfo >> vi) & 1) === 1;
				var vr = Math.floor(vi / 3);
				var vc = size - 11 + (vi % 3);
				setFunc(vr, vc, vbit);
				setFunc(vc, vr, vbit);
			}
		}

		return { size: size, modules: modules, isFunc: isFunc };
	}

	function placeData(matrix, codewords) {
		var size = matrix.size;
		var bitIdx = 0;
		var totalBits = codewords.length * 8;
		var dir = -1;

		for (var col = size - 1; col > 0; col -= 2) {
			if (col === 6) col--;
			for (var i = 0; i < size; i++) {
				var row = dir === -1 ? size - 1 - i : i;
				for (var cOffset = 0; cOffset < 2; cOffset++) {
					var c = col - cOffset;
					if (matrix.isFunc[row][c]) continue;
					var bit = 0;
					if (bitIdx < totalBits) {
						bit = (codewords[bitIdx >> 3] >> (7 - (bitIdx & 7))) & 1;
						bitIdx++;
					}
					matrix.modules[row][c] = bit;
				}
			}
			dir = -dir;
		}
	}

	function applyMaskAndFormat(matrix, maskId) {
		var size = matrix.size;
		var m = matrix.modules.map(function (row) { return new Int8Array(row); });

		for (var r = 0; r < size; r++) {
			for (var c = 0; c < size; c++) {
				if (matrix.isFunc[r][c]) continue;
				var invert = false;
				switch (maskId) {
					case 0: invert = (r + c) % 2 === 0; break;
					case 1: invert = r % 2 === 0; break;
					case 2: invert = c % 3 === 0; break;
					case 3: invert = (r + c) % 3 === 0; break;
					case 4: invert = (Math.floor(r / 2) + Math.floor(c / 3)) % 2 === 0; break;
					case 5: invert = ((r * c) % 2 + (r * c) % 3) === 0; break;
					case 6: invert = (((r * c) % 2 + (r * c) % 3) % 2) === 0; break;
					case 7: invert = (((r + c) % 2 + (r * c) % 3) % 2) === 0; break;
				}
				if (invert) m[r][c] ^= 1;
			}
		}

		// Format bits for ECC-L (01) + maskId
		var fmtData = (1 << 3) | maskId;
		var rem = fmtData << 10;
		for (var bit = 14; bit >= 10; bit--) {
			if ((rem >> bit) & 1) rem ^= 0x537 << (bit - 10);
		}
		var fmt = ((fmtData << 10) | rem) ^ 0x5412;

		for (var i = 0; i < 15; i++) {
			// i = 0 is LSB (bit 0), i = 14 is MSB (bit 14)
			var fbit = (fmt >> i) & 1;

			// Copy 1 (top-left):
			// bits 0..5 -> col 8, rows 0..5
			// bit 6     -> col 8, row 7
			// bit 7     -> col 8, row 8
			// bit 8     -> row 8, col 7
			// bits 9..14 -> row 8, cols 5..0
			if (i < 6) m[i][8] = fbit;
			else if (i === 6) m[7][8] = fbit;
			else if (i === 7) m[8][8] = fbit;
			else if (i === 8) m[8][7] = fbit;
			else m[8][14 - i] = fbit;

			// Copy 2 (top-right + bottom-left):
			// bits 0..7  -> row 8, cols (size-1)..(size-8)
			// bits 8..14 -> col 8, rows (size-7)..(size-1)
			if (i < 8) m[8][size - 1 - i] = fbit;
			else m[size - 15 + i][8] = fbit;
		}
		return m;
	}

	function scoreMatrix(m) {
		var size = m.length;
		var penalty = 0;

		for (var r = 0; r < size; r++) {
			var runColor = m[r][0];
			var runLen = 1;
			for (var c = 1; c < size; c++) {
				if (m[r][c] === runColor) {
					runLen++;
					if (runLen === 5) penalty += 3;
					else if (runLen > 5) penalty += 1;
				} else {
					runColor = m[r][c];
					runLen = 1;
				}
			}
		}

		for (var c2 = 0; c2 < size; c2++) {
			var runColor2 = m[0][c2];
			var runLen2 = 1;
			for (var r2 = 1; r2 < size; r2++) {
				if (m[r2][c2] === runColor2) {
					runLen2++;
					if (runLen2 === 5) penalty += 3;
					else if (runLen2 > 5) penalty += 1;
				} else {
					runColor2 = m[r2][c2];
					runLen2 = 1;
				}
			}
		}

		for (var r3 = 0; r3 < size - 1; r3++) {
			for (var c3 = 0; c3 < size - 1; c3++) {
				var v = m[r3][c3];
				if (v === m[r3][c3 + 1] && v === m[r3 + 1][c3] && v === m[r3 + 1][c3 + 1]) {
					penalty += 3;
				}
			}
		}
		return penalty;
	}

	function renderSvg(text, sizePx) {
		var bytes = encodeUtf8(text);
		var version = selectVersion(bytes.length);
		var codewords = buildCodewords(bytes, version);
		var baseMatrix = createMatrix(version);
		placeData(baseMatrix, codewords);

		var bestMatrix = null;
		var bestScore = Infinity;
		for (var mask = 0; mask < 8; mask++) {
			var candidate = applyMaskAndFormat(baseMatrix, mask);
			var s = scoreMatrix(candidate);
			if (s < bestScore) {
				bestScore = s;
				bestMatrix = candidate;
			}
		}

		var n = bestMatrix.length;
		var quiet = 4;
		var dim = n + quiet * 2;
		var px = sizePx || 240;
		var pathParts = [];

		for (var r = 0; r < n; r++) {
			for (var c = 0; c < n; c++) {
				if (bestMatrix[r][c] === 1) {
					pathParts.push("M" + (c + quiet) + "," + (r + quiet) + "h1v1h-1z");
				}
			}
		}

		return (
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + dim + " " + dim + '" ' +
			'width="' + px + '" height="' + px + '" shape-rendering="crispEdges" role="img" aria-label="WireGuard QR Code">' +
			'<rect width="100%" height="100%" fill="#ffffff"/>' +
			'<path d="' + pathParts.join("") + '" fill="#000000"/>' +
			"</svg>"
		);
	}

	global.HestiaVpnQr = {
		renderSvg: renderSvg
	};
})(window);
