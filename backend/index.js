import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

import storage from './storage.js';
import authRouter from './auth.js';
import ticketsRouter from './tickets.js';
import adminRouter from './admin.js';
import analyticsRouter from './analytics.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.PORT || 5000;

// Set up middle wares
// We configure Content Security Policy (CSP) in helmet to allow resources like Tailwind, styles, and file downloads properly.
app.use(helmet({
  contentSecurityPolicy: false, // Turn off CSP restriction to ease local execution/preview
  crossOriginResourcePolicy: false
}));
app.use(cors());
app.use(express.json());

// Serve static uploaded files
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));

// Serve compiled frontend assets
const frontendPublicPath = path.join(__dirname, 'public');
if (!fs.existsSync(frontendPublicPath)) {
  fs.mkdirSync(frontendPublicPath, { recursive: true });
}
app.use(express.static(frontendPublicPath));

// API Routers
app.use('/api/auth', authRouter);
app.use('/api/tickets', ticketsRouter);
app.use('/api/admin', adminRouter);
app.use('/api/analytics', analyticsRouter);

// Deep linking fallback for React SPA
app.get('*', (req, res, next) => {
  if (req.path.startsWith('/api') || req.path.startsWith('/uploads')) {
    return next();
  }
  const indexPath = path.join(frontendPublicPath, 'index.html');
  if (fs.existsSync(indexPath)) {
    res.sendFile(indexPath);
  } else {
    res.status(200).send(`
      <!DOCTYPE html>
      <html>
        <head><title>CRM Server</title></head>
        <body style="font-family: sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; background: #f3f4f6; color: #1f2937;">
          <h2>CRM Server is running successfully</h2>
          <p>Frontend is currently not built. Run <code>npm run build</code> in the root directory to bundle and serve the frontend SPA.</p>
        </body>
      </html>
    `);
  }
});

// Global Error Handler
app.use((err, req, res, next) => {
  console.error('Unhandled server error:', err);
  res.status(500).json({ error: 'Internal server error' });
});

// Start Server
async function startServer() {
  await storage.init();
  app.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 CRM Server running on http://0.0.0.0:${PORT}`);
  });
}

startServer().catch(err => {
  console.error('Failed to start CRM server:', err);
});
export default app;
