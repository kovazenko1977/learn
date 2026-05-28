<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>LabelPro Professional</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/konva@9.2.1/konva.min.js"></script>
    <style>
        .sidebar { width: 300px; }
        .canvas-area { background: #f1f5f9; position: relative; }
        #konva-holder { background: white; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); margin: auto; }
        .tool-btn { @apply flex flex-col items-center justify-center p-3 border rounded-lg hover:bg-indigo-50 transition; }

        @media print {
            body * { visibility: hidden; }
            .canvas-area, .canvas-area * { visibility: visible; }
            .canvas-area { position: absolute; left: 0; top: 0; padding: 0; margin: 0; background: white; }
            #konva-holder { box-shadow: none; border: none; }
            aside, header, .absolute { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col overflow-hidden">
    <!-- Header -->
    <header class="bg-indigo-800 text-white p-4 flex justify-between items-center shadow-md">
        <div class="flex items-center space-x-2">
            <span class="text-2xl">🏷️</span>
            <h1 class="text-xl font-bold tracking-tight">LabelPro Professional</h1>
        </div>
        <div class="flex space-x-2">
            <button onclick="showTemplates()" class="bg-indigo-700 hover:bg-indigo-600 px-4 py-2 rounded-lg text-sm font-semibold transition">Шаблоны ГОСТ</button>
            <button onclick="undo()" title="Назад" class="bg-indigo-700 hover:bg-indigo-600 p-2 rounded-lg text-sm">↩️</button>
            <button onclick="redo()" title="Вперед" class="bg-indigo-700 hover:bg-indigo-600 p-2 rounded-lg text-sm">↪️</button>
            <button onclick="saveTemplate()" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg text-sm font-semibold transition">Сохранить шаблон</button>
            <button onclick="printLabel()" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-lg text-sm font-semibold transition">Печать</button>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden">
        <!-- Sidebar Tools -->
        <aside class="sidebar bg-white border-r flex flex-col p-4 overflow-y-auto">
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Инструменты</h2>
            <div class="grid grid-cols-2 gap-3 mb-8">
                <button onclick="addText()" class="tool-btn">
                    <span class="text-sm font-medium">Текст</span>
                </button>
                <button onclick="addBarcode()" class="tool-btn">
                    <span class="text-sm font-medium">Штрихкод</span>
                </button>
                <button onclick="addRect()" class="tool-btn">
                    <span class="text-sm font-medium">Рамка</span>
                </button>
                <button onclick="addQR()" class="tool-btn">
                    <span class="text-sm font-medium">QR-код</span>
                </button>
            </div>

            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Знаки ГОСТ 14192-96</h2>
            <div class="grid grid-cols-3 gap-2 mb-8">
                <button onclick="addSign('fragile')" class="p-2 border rounded hover:bg-orange-50 text-xl" title="Хрупкое">🍷</button>
                <button onclick="addSign('keep_dry')" class="p-2 border rounded hover:bg-orange-50 text-xl" title="Беречь от влаги">☂️</button>
                <button onclick="addSign('up')" class="p-2 border rounded hover:bg-orange-50 text-xl" title="Верх">↑↑</button>
            </div>

            <div class="mt-auto pt-4 border-t">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Свойства</h2>
                <div id="properties-panel" class="space-y-4">
                    <p class="text-sm text-gray-400 italic">Элемент не выбран</p>
                </div>
                <div class="mt-6 border-t pt-4">
                    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Выравнивание</h2>
                    <div class="flex space-x-2">
                        <button onclick="align('left')" class="flex-1 py-2 border rounded hover:bg-gray-50 font-bold text-xs">L</button>
                        <button onclick="align('center')" class="flex-1 py-2 border rounded hover:bg-gray-50 font-bold text-xs">C</button>
                        <button onclick="align('right')" class="flex-1 py-2 border rounded hover:bg-gray-50 font-bold text-xs">R</button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Canvas -->
        <main class="flex-1 canvas-area flex items-center justify-center p-12 overflow-auto">
            <div class="absolute top-4 left-4 z-10 flex space-x-4 items-center bg-white/80 backdrop-blur p-2 rounded-lg shadow-sm border">
                <label class="text-xs font-bold text-gray-500 uppercase">Размер:</label>
                <select id="label-size" onchange="updateSize()" class="text-sm border-none bg-transparent focus:ring-0 cursor-pointer">
                    <option value="58x40">58 x 40 мм</option>
                    <option value="58x60">58 x 60 мм</option>
                    <option value="100x150">100 x 150 мм</option>
                </select>
            </div>
            <div id="konva-holder"></div>
        </main>
    </div>

    <!-- Templates Modal -->
    <div id="modal-templates" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-8">
        <div class="bg-white rounded-xl w-full max-w-4xl max-h-full flex flex-col shadow-2xl">
            <div class="p-6 border-b flex justify-between items-center bg-gray-50 rounded-t-xl">
                <h2 class="text-xl font-bold">Библиотека шаблонов</h2>
                <button onclick="closeTemplates()" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
            </div>
            <div id="templates-grid" class="flex-1 overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Templates here -->
            </div>
        </div>
    </div>

    <script>
        let stage, layer, tr;
        const SCALE = 4; // px per mm
        let history = [];
        let historyStep = -1;

        function saveHistory() {
            history = history.slice(0, historyStep + 1);
            history.push(layer.toJSON());
            historyStep++;
        }

        function undo() {
            if (historyStep > 0) {
                historyStep--;
                layer.destroyChildren();
                Konva.Node.create(history[historyStep], layer);
                layer.add(tr);
                layer.draw();
            }
        }

        function redo() {
            if (historyStep < history.length - 1) {
                historyStep++;
                layer.destroyChildren();
                Konva.Node.create(history[historyStep], layer);
                layer.add(tr);
                layer.draw();
            }
        }

        function init() {
            const holder = document.getElementById('konva-holder');
            stage = new Konva.Stage({
                container: 'konva-holder',
                width: 58 * SCALE,
                height: 40 * SCALE
            });

            layer = new Konva.Layer();
            stage.add(layer);

            tr = new Konva.Transformer({
                rotateEnabled: true,
                enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right']
            });
            layer.add(tr);

            stage.on('click tap', (e) => {
                if (e.target === stage) {
                    select(null);
                    return;
                }
                select(e.target);
            });
        }

        function select(node) {
            if (node) {
                tr.nodes([node]);
                renderProperties(node);
            } else {
                tr.nodes([]);
                document.getElementById('properties-panel').innerHTML = '<p class="text-sm text-gray-400 italic">Элемент не выбран</p>';
            }
            layer.draw();
        }

        function renderProperties(node) {
            const isText = node.className === 'Text';
            let html = `
                <div class="space-y-3">
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase">Данные</label>
                        <textarea class="w-full border rounded p-2 text-sm mt-1" oninput="updateNode('text', this.value)">${node.text ? node.text() : ''}</textarea>
                    </div>
                    ${isText ? `
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase">Размер шрифта</label>
                        <input type="number" class="w-full border rounded p-2 text-sm mt-1" value="${node.fontSize()}" oninput="updateNode('fontSize', parseInt(this.value))">
                    </div>
                    ` : ''}
                    <button onclick="deleteNode()" class="w-full bg-red-50 text-red-600 py-2 rounded text-xs font-bold border border-red-100 hover:bg-red-100 transition">Удалить</button>
                </div>
            `;
            document.getElementById('properties-panel').innerHTML = html;
        }

        function updateNode(prop, val) {
            const nodes = tr.nodes();
            if (nodes.length) {
                nodes[0][prop](val);
                layer.draw();
                saveHistory();
            }
        }

        function deleteNode() {
            const nodes = tr.nodes();
            if (nodes.length) {
                nodes[0].destroy();
                select(null);
            }
        }

        function addText() {
            const text = new Konva.Text({
                x: 20, y: 20,
                text: '{PRODUCT}',
                fontSize: 18,
                fontFamily: 'Arial',
                draggable: true
            });
            layer.add(text);
            select(text);
            saveHistory();
        }

        function addRect() {
            const rect = new Konva.Rect({
                x: 50, y: 50,
                width: 100, height: 100,
                stroke: 'black',
                strokeWidth: 2,
                draggable: true
            });
            layer.add(rect);
            select(rect);
            saveHistory();
        }

        function addQR() {
            const group = new Konva.Group({ draggable: true, x: 50, y: 50 });
            const rect = new Konva.Rect({ width: 60, height: 60, fill: 'white', stroke: 'black' });
            const text = new Konva.Text({ text: 'QR', fontSize: 10, padding: 25 });
            group.add(rect).add(text);
            layer.add(group);
            select(group);
            saveHistory();
        }

        function addBarcode() {
            // Simplified placeholder for barcode
            const group = new Konva.Group({ draggable: true, x: 20, y: 60 });
            const rect = new Konva.Rect({
                width: 120, height: 40,
                fill: '#f8fafc',
                stroke: 'black',
                strokeWidth: 1
            });
            const text = new Konva.Text({
                text: '||||| BARCODE',
                fontSize: 12,
                padding: 10
            });
            group.add(rect).add(text);
            layer.add(group);
            select(group);
        }

        function addSign(type) {
            const text = new Konva.Text({
                x: 40, y: 40,
                text: type === 'fragile' ? '🍷' : (type === 'keep_dry' ? '☂️' : '↑↑'),
                fontSize: 48,
                draggable: true
            });
            layer.add(text);
            select(text);
            saveHistory();
        }

        function align(dir) {
            const node = tr.nodes()[0];
            if (!node) return;
            if (dir === 'left') node.x(0);
            if (dir === 'center') node.x((stage.width() - node.width() * node.scaleX()) / 2);
            if (dir === 'right') node.x(stage.width() - node.width() * node.scaleX());
            layer.draw();
            saveHistory();
        }

        function updateSize() {
            const [w, h] = document.getElementById('label-size').value.split('x').map(Number);
            stage.width(w * SCALE);
            stage.height(h * SCALE);
        }

        async function saveTemplate() {
            // Filter out transformer from being saved
            const children = layer.getChildren().filter(node => node.className !== 'Transformer');
            const elementsJson = children.map(c => c.toJSON());

            const data = {
                name: 'Шаблон ' + new Date().toLocaleString(),
                width: stage.width() / SCALE,
                height: stage.height() / SCALE,
                elements: JSON.stringify(elementsJson)
            };
            await fetch('api.php?action=save_template', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            alert('Шаблон сохранен');
        }

        async function showTemplates() {
            document.getElementById('modal-templates').classList.remove('hidden');
            const res = await fetch('api.php?action=get_templates');
            const templates = await res.json();
            const grid = document.getElementById('templates-grid');
            grid.innerHTML = templates.map(t => `
                <div class="border rounded-xl p-4 hover:shadow-lg transition cursor-pointer bg-gray-50 group" onclick="loadTemplate('${t.id}')">
                    <div class="aspect-[3/4] bg-white mb-3 rounded border flex items-center justify-center text-gray-300 group-hover:border-indigo-400">
                        <span class="text-4xl">📄</span>
                    </div>
                    <h3 class="font-bold text-gray-800">${t.name}</h3>
                    <p class="text-xs text-gray-400 mt-1">${t.width} x ${t.height} мм</p>
                </div>
            `).join('');
        }

        function closeTemplates() {
            document.getElementById('modal-templates').classList.add('hidden');
        }

        async function loadTemplate(id) {
            const res = await fetch('api.php?action=get_templates');
            const templates = await res.json();
            const t = templates.find(tpl => tpl.id == id);
            if (!t) return;

            stage.width(t.width * SCALE);
            stage.height(t.height * SCALE);
            document.getElementById('label-size').value = `${t.width}x${t.height}`;

            layer.destroyChildren();

            const elements = JSON.parse(t.elements);
            elements.forEach(elJson => {
                const node = Konva.Node.create(elJson);
                layer.add(node);
            });

            // Re-add transformer
            tr = new Konva.Transformer({
                rotateEnabled: true,
                enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right']
            });
            layer.add(tr);

            closeTemplates();
            layer.draw();
            saveHistory();
        }

        function printLabel() {
            window.print();
        }

        window.onload = init;
    </script>
</body>
</html>
