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
            <button onclick="saveTemplate()" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg text-sm font-semibold transition">Сохранить шаблон</button>
            <button onclick="printLabel()" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-lg text-sm font-semibold transition">Печать (PDF)</button>
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

    <script>
        let stage, layer, tr;
        const SCALE = 4; // px per mm

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
        }

        function updateSize() {
            const [w, h] = document.getElementById('label-size').value.split('x').map(Number);
            stage.width(w * SCALE);
            stage.height(h * SCALE);
        }

        async function saveTemplate() {
            const data = {
                name: 'Шаблон ' + new Date().toLocaleString(),
                elements: layer.toJSON()
            };
            await fetch('api.php?action=save_template', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            alert('Шаблон сохранен');
        }

        function printLabel() {
            window.print();
        }

        window.onload = init;
    </script>
</body>
</html>
