const { app, BrowserWindow, ipcMain } = require('electron');
const path = require('path');

function createWindow() {
  const win = new BrowserWindow({
    width: 1200,
    height: 800,
    webPreferences: {
      nodeIntegration: true,
      contextIsolation: false,
    },
    title: "LabelPro - Профессиональная печать этикеток"
  });

  // In development, load from Vite dev server
  const startUrl = process.env.ELECTRON_START_URL || `file://${path.join(__dirname, '../frontend/dist/index.html')}`;
  win.loadURL(startUrl);
}

app.whenReady().then(createWindow);

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit();
});

// IPC handler for printing
ipcMain.on('print-label', (event, pdfPath) => {
  // Logic to send to thermal printer using node-printer or system dialog
  console.log('Printing label from:', pdfPath);
});
