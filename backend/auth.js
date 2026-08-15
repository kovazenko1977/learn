import express from 'express';
import jwt from 'jsonwebtoken';
import bcrypt from 'bcryptjs';
import storage from './storage.js';

const router = express.Router();
export const JWT_SECRET = process.env.JWT_SECRET || 'crm-super-secret-key-12345';

// Middleware to authenticate token
export function authenticateToken(req, res, next) {
  const authHeader = req.headers['authorization'];
  let token = authHeader && authHeader.split(' ')[1];
  if (!token && req.query && req.query.token) {
    token = req.query.token;
  }

  if (!token) {
    return res.status(401).json({ error: 'Access token is missing' });
  }

  jwt.verify(token, JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({ error: 'Invalid or expired token' });
    }
    req.user = user;
    next();
  });
}

// Middleware to authorize roles
export function requireRole(roles) {
  return (req, res, next) => {
    if (!req.user || !roles.includes(req.user.role)) {
      return res.status(403).json({ error: 'Access denied: insufficient permissions' });
    }
    next();
  };
}

// Login API
router.post('/login', async (req, res) => {
  const { username, password } = req.body;

  if (!username || !password) {
    return res.status(400).json({ error: 'Username and password are required' });
  }

  try {
    const user = await storage.getUserByUsername(username);
    if (!user) {
      return res.status(401).json({ error: 'Invalid username or password' });
    }

    const validPassword = await bcrypt.compare(password, user.password);
    if (!validPassword) {
      return res.status(401).json({ error: 'Invalid username or password' });
    }

    const token = jwt.sign(
      { id: user.id, username: user.username, role: user.role, fullName: user.fullName },
      JWT_SECRET,
      { expiresIn: '24h' }
    );

    res.json({
      token,
      user: {
        id: user.id,
        username: user.username,
        fullName: user.fullName,
        email: user.email,
        role: user.role
      }
    });
  } catch (error) {
    console.error('Login error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Password recovery API
router.post('/recover-password', async (req, res) => {
  const { username, email } = req.body;
  if (!username || !email) {
    return res.status(400).json({ error: 'Username and email are required' });
  }

  try {
    const user = await storage.getUserByUsername(username);
    if (!user || user.email.toLowerCase() !== email.toLowerCase()) {
      return res.status(404).json({ error: 'User with specified username and email not found' });
    }

    // Mock recovery token and response
    const recoveryToken = jwt.sign({ id: user.id, purpose: 'password-recovery' }, JWT_SECRET, { expiresIn: '15m' });
    const recoveryLink = `/reset-password?token=${recoveryToken}`;

    res.json({
      message: 'Recovery instructions generated successfully.',
      recoveryLink, // Returned for simulated demonstration
      sentTo: user.email
    });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Reset Password API (using recovery token)
router.post('/reset-password', async (req, res) => {
  const { token, newPassword } = req.body;
  if (!token || !newPassword) {
    return res.status(400).json({ error: 'Token and new password are required' });
  }

  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    if (decoded.purpose !== 'password-recovery') {
      return res.status(400).json({ error: 'Invalid recovery token' });
    }

    const salt = await bcrypt.genSalt(10);
    const hashedPassword = await bcrypt.hash(newPassword, salt);

    await storage.updateUser(decoded.id, { password: hashedPassword });
    res.json({ message: 'Password has been reset successfully.' });
  } catch (error) {
    res.status(400).json({ error: 'Invalid or expired recovery token' });
  }
});

// Get profile
router.get('/profile', authenticateToken, async (req, res) => {
  try {
    const user = await storage.getUserById(req.user.id);
    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }
    res.json({
      id: user.id,
      username: user.username,
      fullName: user.fullName,
      email: user.email,
      role: user.role
    });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// Update profile
router.put('/profile', authenticateToken, async (req, res) => {
  const { fullName, email, password } = req.body;
  const updates = {};

  if (fullName) updates.fullName = fullName;
  if (email) updates.email = email;
  if (password) {
    const salt = await bcrypt.genSalt(10);
    updates.password = await bcrypt.hash(password, salt);
  }

  try {
    const updated = await storage.updateUser(req.user.id, updates);
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

export default router;
