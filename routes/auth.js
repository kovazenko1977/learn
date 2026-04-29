const express = require('express');
const router = express.Router();
const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');
const path = require('path');
const JsonStorage = require('../storage/JsonStorage');
const auth = require('../middleware/auth');

const SECRET = process.env.JWT_SECRET || 'your-secret-key';
const storage = new JsonStorage(path.join(__dirname, '../data'));

router.post('/login', async (req, res) => {
    const { login, password } = req.body;
    const user = await storage.findOne('users', { login, is_active: 1 });

    if (!user || !(await bcrypt.compare(password, user.password_hash))) {
        return res.status(401).json({ message: 'Invalid credentials' });
    }

    const token = jwt.sign(
        { id: user.id, role: user.role, department_id: user.department_id },
        SECRET,
        { expiresIn: '24h' }
    );

    const { password_hash, ...userWithoutPassword } = user;
    res.json({ token, user: userWithoutPassword });
});

router.get('/me', auth(), async (req, res) => {
    const user = await storage.findOne('users', { id: req.user.id });
    if (!user) return res.status(404).json({ message: 'User not found' });

    const { password_hash, ...userWithoutPassword } = user;
    res.json(userWithoutPassword);
});

module.exports = router;
