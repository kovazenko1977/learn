// Pure JavaScript QR Code Generator (QRCode.js inline lightweight bundle)
// Author: Kazuhiko Arase (MIT License) - modified for standalone inline embedding
var QRCode;

(function () {
	function QR8bitByte(data) {
		this.mode = 4;
		this.data = data;
	}
	QR8bitByte.prototype = {
		getLength: function () { return this.data.length; },
		write: function (buffer) {
			for (var i = 0; i < this.data.length; i++) {
				buffer.put(this.data.charCodeAt(i), 8);
			}
		}
	};

	function QRCodeModel(typeNumber, errorCorrectLevel) {
		this.typeNumber = typeNumber;
		this.errorCorrectLevel = errorCorrectLevel;
		this.modules = null;
		this.moduleCount = 0;
		this.dataCache = null;
		this.dataList = [];
	}

	QRCodeModel.prototype = {
		addData: function (data) {
			var newData = new QR8bitByte(data);
			this.dataList.push(newData);
			this.dataCache = null;
		},
		isDark: function (row, col) {
			if (row < 0 || this.moduleCount <= row || col < 0 || this.moduleCount <= col) {
				throw new Error(row + "," + col);
			}
			return this.modules[row][col];
		},
		getModuleCount: function () { return this.moduleCount; },
		make: function () {
			this.makeImpl(false, this.getBestMaskPattern());
		},
		makeImpl: function (test, maskPattern) {
			this.moduleCount = this.typeNumber * 4 + 17;
			this.modules = new Array(this.moduleCount);
			for (var row = 0; row < this.moduleCount; row++) {
				this.modules[row] = new Array(this.moduleCount);
				for (var col = 0; col < this.moduleCount; col++) {
					this.modules[row][col] = null;
				}
			}
			this.setupPositionProbePattern(0, 0);
			this.setupPositionProbePattern(this.moduleCount - 7, 0);
			this.setupPositionProbePattern(0, this.moduleCount - 7);
			this.setupTimingPattern();
			this.setupTypeInfo(test, maskPattern);
			if (this.typeNumber >= 7) {
				this.setupTypeNumber(test);
			}
			if (this.dataCache == null) {
				this.dataCache = QRCodeModel.createData(this.typeNumber, this.errorCorrectLevel, this.dataList);
			}
			this.mapData(this.dataCache, maskPattern);
		},
		setupPositionProbePattern: function (row, col) {
			for (var r = -1; r <= 7; r++) {
				if (row + r <= -1 || this.moduleCount <= row + r) continue;
				for (var c = -1; c <= 7; c++) {
					if (col + c <= -1 || this.moduleCount <= col + c) continue;
					if ((0 <= r && r <= 6 && (c == 0 || c == 6)) || (0 <= c && c <= 6 && (r == 0 || r == 6)) || (2 <= r && r <= 4 && 2 <= c && c <= 4)) {
						this.modules[row + r][col + c] = true;
					} else {
						this.modules[row + r][col + c] = false;
					}
				}
			}
		},
		getBestMaskPattern: function () {
			var minLostPoint = 0;
			var pattern = 0;
			for (var i = 0; i < 8; i++) {
				this.makeImpl(true, i);
				var lostPoint = QRUtil.getLostPoint(this);
				if (i == 0 || minLostPoint > lostPoint) {
					minLostPoint = lostPoint;
					pattern = i;
				}
			}
			return pattern;
		},
		setupTimingPattern: function () {
			for (var r = 8; r < this.moduleCount - 8; r++) {
				if (this.modules[r][6] != null) continue;
				this.modules[r][6] = (r % 2 == 0);
			}
			for (var c = 8; c < this.moduleCount - 8; c++) {
				if (this.modules[6][c] != null) continue;
				this.modules[6][c] = (c % 2 == 0);
			}
		},
		setupTypeInfo: function (test, maskPattern) {
			var data = (this.errorCorrectLevel << 3) | maskPattern;
			var bits = QRUtil.getBCHTypeInfo(data);
			for (var i = 0; i < 15; i++) {
				var mod = (!test && ((bits >> i) & 1) == 1);
				if (i < 6) {
					this.modules[i][8] = mod;
				} else if (i < 9) {
					this.modules[i + 1][8] = mod;
				} else {
					this.modules[this.moduleCount - 15 + i][8] = mod;
				}
			}
			for (var i = 0; i < 15; i++) {
				var mod = (!test && ((bits >> i) & 1) == 1);
				if (i < 8) {
					this.modules[8][this.moduleCount - i - 1] = mod;
				} else if (i < 9) {
					this.modules[8][15 - i - 1 + 1] = mod;
				} else {
					this.modules[8][15 - i - 1] = mod;
				}
			}
			this.modules[this.moduleCount - 8][8] = (!test);
		},
		setupTypeNumber: function (test) {
			var bits = QRUtil.getBCHTypeNumber(this.typeNumber);
			for (var i = 0; i < 18; i++) {
				var mod = (!test && ((bits >> i) & 1) == 1);
				this.modules[Math.floor(i / 3)][i % 3 + this.moduleCount - 8 - 3] = mod;
			}
			for (var i = 0; i < 18; i++) {
				var mod = (!test && ((bits >> i) & 1) == 1);
				this.modules[i % 3 + this.moduleCount - 8 - 3][Math.floor(i / 3)] = mod;
			}
		},
		mapData: function (data, maskPattern) {
			var inc = -1;
			var row = this.moduleCount - 1;
			var bitIndex = 7;
			var byteIndex = 0;

			for (var col = this.moduleCount - 1; col > 0; col -= 2) {
				if (col == 6) col--;
				while (true) {
					for (var c = 0; c < 2; c++) {
						if (this.modules[row][col - c] == null) {
							var dark = false;
							if (byteIndex < data.length) {
								dark = (((data[byteIndex] >>> bitIndex) & 1) == 1);
							}
							var mask = QRUtil.getMask(maskPattern, row, col - c);
							if (mask) {
								dark = !dark;
							}
							this.modules[row][col - c] = dark;
							bitIndex--;
							if (bitIndex == -1) {
								byteIndex++;
								bitIndex = 7;
							}
						}
					}
					row += inc;
					if (row < 0 || this.moduleCount <= row) {
						row -= inc;
						inc = -inc;
						break;
					}
				}
			}
		}
	};

	QRCodeModel.PAD0 = 0xEC;
	QRCodeModel.PAD1 = 0x11;

	QRCodeModel.createData = function (typeNumber, errorCorrectLevel, dataList) {
		var rsBlocks = QRRSBlock.getRSBlocks(typeNumber, errorCorrectLevel);
		var buffer = new QRBitBuffer();
		for (var i = 0; i < dataList.length; i++) {
			var data = dataList[i];
			buffer.put(data.mode, 4);
			buffer.put(data.getLength(), QRUtil.getLengthInBits(data.mode, typeNumber));
			data.write(buffer);
		}
		var totalDataCount = 0;
		for (var i = 0; i < rsBlocks.length; i++) {
			totalDataCount += rsBlocks[i].dataCount;
		}
		if (buffer.getLengthInBits() > totalDataCount * 8) {
			throw new Error("code length overflow. (" + buffer.getLengthInBits() + ">" + totalDataCount * 8 + ")");
		}
		if (buffer.getLengthInBits() + 4 <= totalDataCount * 8) {
			buffer.put(0, 4);
		}
		while (buffer.getLengthInBits() % 8 != 0) {
			buffer.putBit(false);
		}
		while (true) {
			if (buffer.getLengthInBits() >= totalDataCount * 8) break;
			buffer.put(QRCodeModel.PAD0, 8);
			if (buffer.getLengthInBits() >= totalDataCount * 8) break;
			buffer.put(QRCodeModel.PAD1, 8);
		}
		return QRCodeModel.createBytes(buffer, rsBlocks);
	};

	QRCodeModel.createBytes = function (buffer, rsBlocks) {
		var offset = 0;
		var maxDcCount = 0;
		var maxEcCount = 0;
		var dcdata = new Array(rsBlocks.length);
		var ecdata = new Array(rsBlocks.length);
		for (var r = 0; r < rsBlocks.length; r++) {
			var dcCount = rsBlocks[r].dataCount;
			var ecCount = rsBlocks[r].totalCount - dcCount;
			maxDcCount = Math.max(maxDcCount, dcCount);
			maxEcCount = Math.max(maxEcCount, ecCount);
			dcdata[r] = new Array(dcCount);
			for (var i = 0; i < dcdata[r].length; i++) {
				dcdata[r][i] = 0xff & buffer.buffer[i + offset];
			}
			offset += dcCount;
			var rsPoly = QRUtil.getErrorCorrectPolynomial(ecCount);
			var rawPoly = new QRPolynomial(dcdata[r], rsPoly.getLength() - 1);
			var modPoly = rawPoly.mod(rsPoly);
			ecdata[r] = new Array(rsPoly.getLength() - 1);
			for (var i = 0; i < ecdata[r].length; i++) {
				var modIndex = i + modPoly.getLength() - ecdata[r].length;
				ecdata[r][i] = (modIndex >= 0) ? modPoly.get(modIndex) : 0;
			}
		}
		var totalCodeCount = 0;
		for (var i = 0; i < rsBlocks.length; i++) {
			totalCodeCount += rsBlocks[i].totalCount;
		}
		var data = new Array(totalCodeCount);
		var index = 0;
		for (var i = 0; i < maxDcCount; i++) {
			for (var r = 0; r < rsBlocks.length; r++) {
				if (i < dcdata[r].length) {
					data[index++] = dcdata[r][i];
				}
			}
		}
		for (var i = 0; i < maxEcCount; i++) {
			for (var r = 0; r < rsBlocks.length; r++) {
				if (i < ecdata[r].length) {
					data[index++] = ecdata[r][i];
				}
			}
		}
		return data;
	};

	// Helper classes
	function QRPolynomial(num, shift) {
		if (num.length == undefined) throw new Error(num.length + "/" + shift);
		var offset = 0;
		while (offset < num.length && num[offset] == 0) offset++;
		this.num = new Array(num.length - offset + shift);
		for (var i = 0; i < num.length - offset; i++) this.num[i] = num[i + offset];
	}
	QRPolynomial.prototype = {
		get: function (index) { return this.num[index]; },
		getLength: function () { return this.num.length; },
		mod: function (e) {
			if (this.getLength() - e.getLength() < 0) return this;
			var ratio = QRMath.glog(this.get(0)) - QRMath.glog(e.get(0));
			var num = new Array(this.getLength());
			for (var i = 0; i < this.getLength(); i++) num[i] = this.get(i);
			for (var i = 0; i < e.getLength(); i++) num[i] ^= QRMath.gexp(e.get(i) + ratio);
			return new QRPolynomial(num, 0).mod(e);
		}
	};

	function QRRSBlock(totalCount, dataCount) {
		this.totalCount = totalCount;
		this.dataCount = dataCount;
	}
	QRRSBlock.RS_BLOCK_TABLE = [
		[1, 26, 19], [1, 26, 16], [1, 26, 13], [1, 26, 9],
		[1, 44, 34], [1, 44, 28], [1, 44, 22], [1, 44, 16],
		[1, 70, 55], [1, 70, 44], [2, 35, 17], [2, 35, 13],
		[1, 100, 80], [2, 50, 32], [2, 50, 24], [4, 25, 9],
		[1, 134, 108], [2, 67, 43], [2, 33, 15, 2, 34, 16], [2, 33, 11, 2, 34, 12]
	];
	QRRSBlock.getRSBlocks = function (typeNumber, errorCorrectLevel) {
		var rsBlock = QRRSBlock.getRsBlockTable(typeNumber, errorCorrectLevel);
		if (rsBlock == undefined) throw new Error("bad rs block @ typeNumber:" + typeNumber);
		var length = rsBlock.length / 3;
		var list = [];
		for (var i = 0; i < length; i++) {
			var count = rsBlock[i * 3 + 0];
			var totalCount = rsBlock[i * 3 + 1];
			var dataCount = rsBlock[i * 3 + 2];
			for (var j = 0; j < count; j++) list.push(new QRRSBlock(totalCount, dataCount));
		}
		return list;
	};
	QRRSBlock.getRsBlockTable = function (typeNumber, errorCorrectLevel) {
		switch (errorCorrectLevel) {
			case 1: return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 0];
			case 0: return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 1];
			case 3: return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 2];
			case 2: return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 3];
			default: return undefined;
		}
	};

	function QRBitBuffer() {
		this.buffer = [];
		this.length = 0;
	}
	QRBitBuffer.prototype = {
		get: function (index) {
			var bufIndex = Math.floor(index / 8);
			return ((this.buffer[bufIndex] >>> (7 - index % 8)) & 1) == 1;
		},
		put: function (num, length) {
			for (var i = 0; i < length; i++) this.putBit(((num >>> (length - i - 1)) & 1) == 1);
		},
		getLengthInBits: function () { return this.length; },
		putBit: function (bit) {
			var bufIndex = Math.floor(this.length / 8);
			if (this.buffer.length <= bufIndex) this.buffer.push(0);
			if (bit) this.buffer[bufIndex] |= (0x80 >>> (this.length % 8));
			this.length++;
		}
	};

	var QRMath = {
		glog: function (n) {
			if (n < 1) throw new Error("glog(" + n + ")");
			return QRMath.LOG_TABLE[n];
		},
		gexp: function (n) {
			while (n < 0) n += 255;
			while (n >= 256) n -= 255;
			return QRMath.EXP_TABLE[n];
		},
		EXP_TABLE: new Array(256),
		LOG_TABLE: new Array(256)
	};
	for (var i = 0; i < 8; i++) QRMath.EXP_TABLE[i] = 1 << i;
	for (var i = 8; i < 256; i++) QRMath.EXP_TABLE[i] = QRMath.EXP_TABLE[i - 4] ^ QRMath.EXP_TABLE[i - 5] ^ QRMath.EXP_TABLE[i - 6] ^ QRMath.EXP_TABLE[i - 8];
	for (var i = 0; i < 255; i++) QRMath.LOG_TABLE[QRMath.EXP_TABLE[i]] = i;

	var QRUtil = {
		getBCHTypeInfo: function (data) {
			var d = data << 10;
			while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(1335) >= 0) d ^= (1335 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(1335)));
			return ((data << 10) | d) ^ 21522;
		},
		getBCHTypeNumber: function (data) {
			var d = data << 12;
			while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(7973) >= 0) d ^= (7973 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(7973)));
			return (data << 12) | d;
		},
		getBCHDigit: function (data) {
			var digit = 0;
			while (data != 0) { digit++; data >>>= 1; }
			return digit;
		},
		getErrorCorrectPolynomial: function (errorCorrectLength) {
			var a = new QRPolynomial([1], 0);
			for (var i = 0; i < errorCorrectLength; i++) a = a.mod(new QRPolynomial([1, QRMath.gexp(i)], 0));
			return a;
		},
		getLengthInBits: function (mode, type) {
			if (1 <= type && type < 10) return 8;
			return 16;
		},
		getLostPoint: function (qrCode) {
			var moduleCount = qrCode.getModuleCount();
			var lostPoint = 0;
			for (var row = 0; row < moduleCount; row++) {
				for (var col = 0; col < moduleCount; col++) {
					var sameCount = 0;
					var dark = qrCode.isDark(row, col);
					for (var r = -1; r <= 1; r++) {
						if (row + r < 0 || moduleCount <= row + r) continue;
						for (var c = -1; c <= 1; c++) {
							if (col + c < 0 || moduleCount <= col + c) continue;
							if (r == 0 && c == 0) continue;
							if (dark == qrCode.isDark(row + r, col + c)) sameCount++;
						}
					}
					if (sameCount > 5) lostPoint += (3 + sameCount - 5);
				}
			}
			return lostPoint;
		},
		getMask: function (maskPattern, row, col) {
			switch (maskPattern) {
				case 0: return (row + col) % 2 == 0;
				case 1: return row % 2 == 0;
				case 2: return col % 3 == 0;
				case 3: return (row + col) % 3 == 0;
				case 4: return (Math.floor(row / 2) + Math.floor(col / 3)) % 2 == 0;
				case 5: return (row * col) % 2 + (row * col) % 3 == 0;
				case 6: return ((row * col) % 2 + (row * col) % 3) % 2 == 0;
				case 7: return ((row + col) % 2 + (row * col) % 3) % 2 == 0;
				default: throw new Error("bad maskPattern:" + maskPattern);
			}
		}
	};

	QRCode = function (text, level) {
		var type = 4;
		if (text.length > 50) type = 6;
		if (text.length > 100) type = 10;
		var qr = new QRCodeModel(type, level || 1);
		qr.addData(text);
		qr.make();
		return qr;
	};
})();

// Function to render HTML5 Canvas or SVG QR code
function generateQRCode(elementId, text, size = 180) {
  const container = document.getElementById(elementId);
  if (!container) return;
  container.innerHTML = '';

  try {
    const qr = QRCode(text, 1);
    const count = qr.getModuleCount();
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');

    const tileW = size / count;
    const tileH = size / count;

    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, size, size);

    ctx.fillStyle = '#0F172A';
    for (let r = 0; r < count; r++) {
      for (let c = 0; c < count; c++) {
        if (qr.isDark(r, c)) {
          ctx.fillRect(Math.round(c * tileW), Math.round(r * tileH), Math.ceil(tileW), Math.ceil(tileH));
        }
      }
    }

    canvas.style.borderRadius = '8px';
    canvas.style.border = '1px solid #cbd5e1';
    container.appendChild(canvas);
  } catch (err) {
    console.error('Local QR render error, using fallback:', err);
    const img = document.createElement('img');
    img.src = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(text)}`;
    img.style.width = size + 'px';
    img.style.height = size + 'px';
    container.appendChild(img);
  }
}

// Global App Utilities
const App = {
  currentUser: null,
  isAdmin: false,

  async fetch(action, params = {}, options = {}) {
    let url = `api/index.php?action=${action}`;
    const fetchOpts = { ...options };

    if (options.method === 'POST') {
      if (options.body instanceof FormData) {
        url += `&` + new URLSearchParams(params).toString();
      } else {
        fetchOpts.headers = {
          'Content-Type': 'application/json',
          ...(options.headers || {})
        };
        fetchOpts.body = JSON.stringify({ ...params, ...(options.body || {}) });
      }
    } else {
      const q = new URLSearchParams(params).toString();
      if (q) url += `&${q}`;
    }

    try {
      const res = await fetch(url, fetchOpts);
      return await res.json();
    } catch (e) {
      console.error('API Error:', e);
      return { success: false, error: 'Ошибка сети или сервера' };
    }
  },

  async checkAuth() {
    const res = await this.fetch('auth_current');
    if (res.success) {
      this.currentUser = res.user;
      this.isAdmin = res.is_admin;
      this.updateNav();
    }
  },

  updateNav() {
    const navLinks = document.getElementById('navLinks');
    if (!navLinks) return;

    let html = `
      <li><a href="index.php" class="nav-link ${window.location.pathname.endsWith('index.php') || window.location.pathname === '/' ? 'active' : ''}">🔍 Поиск</a></li>
    `;

    if (this.currentUser) {
      html += `
        <li><a href="user.php" class="nav-link ${window.location.pathname.endsWith('user.php') ? 'active' : ''}">👤 Личный кабинет (${this.currentUser.phone})</a></li>
        <li><button onclick="App.openCreateModal()" class="btn btn-accent btn-sm">➕ Создать страницу</button></li>
        <li><a href="#" onclick="App.logoutUser(event)" class="nav-link">Выход</a></li>
      `;
    } else {
      html += `
        <li><a href="user.php" class="nav-link">🔑 Вход / Регистрация</a></li>
        <li><button onclick="App.openCreateModal()" class="btn btn-accent btn-sm">➕ Создать страницу</button></li>
      `;
    }

    if (this.isAdmin) {
      html += `
        <li><a href="admin.php" class="nav-link ${window.location.pathname.endsWith('admin.php') ? 'active' : ''}" style="color:#fbbf24; font-weight:bold;">⚙️ Админ-панель</a></li>
      `;
    } else {
      html += `
        <li><a href="admin.php" class="nav-link">Вход Администратора</a></li>
      `;
    }

    navLinks.innerHTML = html;
  },

  async logoutUser(e) {
    if (e) e.preventDefault();
    await this.fetch('auth_logout');
    this.currentUser = null;
    window.location.href = 'index.php';
  },

  openCreateModal() {
    if (!this.currentUser) {
      alert('Пожалуйста, авторизуйтесь или зарегистрируйтесь по номеру телефона для создания страницы.');
      window.location.href = 'user.php?redirect=create';
      return;
    }
    const modal = document.getElementById('createPageModal');
    if (modal) {
      modal.classList.add('active');
    }
  },

  closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('active');
    }
  }
};

document.addEventListener('DOMContentLoaded', () => {
  App.checkAuth();
});
