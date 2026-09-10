import express from 'express';
import bcrypt from 'bcryptjs';
import storage from './storage.js';
import { authenticateToken, requireRole } from './auth.js';

const router = express.Router();

// Require authentication for all routes
router.use(authenticateToken);

// --- User Management ---

// GET /api/admin/users (Accessible to all authenticated users for assignee/creator lookups)
router.get('/users', async (req, res) => {
  try {
    const users = await storage.getUsers();
    // Do not return password hashes
    const sanitized = users.map(u => ({
      id: u.id,
      username: u.username,
      fullName: u.fullName,
      email: u.email,
      role: u.role,
      created_at: u.created_at
    }));
    res.json(sanitized);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// POST /api/admin/users
router.post('/users', requireRole(['admin']), async (req, res) => {
  const { username, password, fullName, email, role } = req.body;

  if (!username || !password || !fullName || !email || !role) {
    return res.status(400).json({ error: 'All fields are required' });
  }

  try {
    const existing = await storage.getUserByUsername(username);
    if (existing) {
      return res.status(400).json({ error: 'Username is already taken' });
    }

    const salt = await bcrypt.genSalt(10);
    const hashedPassword = await bcrypt.hash(password, salt);

    const newUser = {
      id: Date.now().toString(),
      username,
      password: hashedPassword,
      fullName,
      email,
      role,
      created_at: new Date().toISOString()
    };

    await storage.createUser(newUser);
    res.status(201).json({
      id: newUser.id,
      username: newUser.username,
      fullName: newUser.fullName,
      email: newUser.email,
      role: newUser.role,
      created_at: newUser.created_at
    });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// PUT /api/admin/users/:id
router.put('/users/:id', requireRole(['admin']), async (req, res) => {
  const { fullName, email, role, password } = req.body;
  const updates = {};

  if (fullName) updates.fullName = fullName;
  if (email) updates.email = email;
  if (role) updates.role = role;
  if (password) {
    const salt = await bcrypt.genSalt(10);
    updates.password = await bcrypt.hash(password, salt);
  }

  try {
    const updated = await storage.updateUser(req.params.id, updates);
    if (!updated) {
      return res.status(404).json({ error: 'User not found' });
    }
    res.json({
      id: updated.id,
      username: updated.username,
      fullName: updated.fullName,
      email: updated.email,
      role: updated.role
    });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// DELETE /api/admin/users/:id
router.delete('/users/:id', requireRole(['admin']), async (req, res) => {
  if (req.params.id === req.user.id) {
    return res.status(400).json({ error: 'Cannot delete your own admin account' });
  }
  try {
    await storage.deleteUser(req.params.id);
    res.json({ message: 'User deleted successfully' });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// --- Dynamic Form Builder ---

// GET /api/admin/form-fields
router.get('/form-fields', async (req, res) => {
  try {
    const fields = await storage.getFormFields();
    res.json(fields);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// POST /api/admin/form-fields
router.post('/form-fields', requireRole(['admin']), async (req, res) => {
  const { id, name, label, type, category, options, required, sort_order } = req.body;

  if (!name || !label || !type || !category) {
    return res.status(400).json({ error: 'Missing required field attributes' });
  }

  try {
    const field = {
      id: id || name.toLowerCase().replace(/[^a-z0-9]/g, '_') + '_' + Date.now(),
      name,
      label,
      type,
      category,
      options: options || [],
      required: Boolean(required),
      sort_order: Number(sort_order) || 0
    };

    const saved = await storage.saveFormField(field);
    res.status(200).json(saved);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// DELETE /api/admin/form-fields/:id
router.delete('/form-fields/:id', requireRole(['admin']), async (req, res) => {
  try {
    await storage.deleteFormField(req.params.id);
    res.json({ message: 'Form field deleted successfully' });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// --- Settings Management & SLA Configuration ---

// GET /api/admin/settings
router.get('/settings', async (req, res) => {
  try {
    const settings = await storage.getSettings();
    res.json(settings);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// PUT /api/admin/settings
router.put('/settings', requireRole(['admin']), async (req, res) => {
  const { categories, priorities, sla } = req.body;
  try {
    const currentSettings = await storage.getSettings();
    if (categories) currentSettings.categories = categories;
    if (priorities) currentSettings.priorities = priorities;
    if (sla) currentSettings.sla = sla;

    await storage.saveSettings(currentSettings);
    res.json(currentSettings);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// POST /api/admin/settings/storage-mode - Switch Database
router.post('/settings/storage-mode', requireRole(['admin']), async (req, res) => {
  const { mode, mysqlConfig } = req.body;

  if (!mode || !['JSON', 'MySQL'].includes(mode)) {
    return res.status(400).json({ error: 'Invalid storage mode' });
  }

  if (mode === 'MySQL' && (!mysqlConfig || !mysqlConfig.host || !mysqlConfig.database || !mysqlConfig.user)) {
    return res.status(400).json({ error: 'MySQL configuration is incomplete' });
  }

  try {
    await storage.setStorageMode(mode, mysqlConfig);
    res.json({
      message: `Successfully switched storage mode to ${mode}. Tables generated.`,
      currentMode: mode
    });
  } catch (error) {
    console.error('Storage switch error:', error);
    res.status(500).json({ error: 'Failed to switch storage mode: ' + error.message });
  }
});

export default router;
