const express = require('express');
const router = express.Router();
const path = require('path');
const bcrypt = require('bcryptjs');
const JsonStorage = require('../storage/JsonStorage');
const auth = require('../middleware/auth');

const storage = new JsonStorage(path.join(__dirname, '../data'));

// Users CRUD
router.get('/users', auth('admin'), async (req, res) => {
    const users = await storage.readCollection('users');
    res.json(users.map(({ password_hash, ...u }) => u));
});

router.post('/users', auth('admin'), async (req, res) => {
    const { login, password, full_name, role, department_id } = req.body;
    const password_hash = await bcrypt.hash(password, 10);
    const newUser = await storage.insert('users', {
        login,
        password_hash,
        full_name,
        role,
        department_id: department_id ? parseInt(department_id) : null,
        is_active: 1,
        created_at: new Date().toISOString()
    });
    const { password_hash: ph, ...userWithoutPassword } = newUser;
    res.status(201).json(userWithoutPassword);
});

// Departments CRUD
router.get('/departments', auth(['admin', 'manager', 'user']), async (req, res) => {
    const departments = await storage.readCollection('departments');
    res.json(departments);
});

router.post('/departments', auth('admin'), async (req, res) => {
    const newDept = await storage.insert('departments', req.body);
    res.status(201).json(newDept);
});

// Work Types CRUD
router.get('/worktypes', auth(['admin', 'user']), async (req, res) => {
    const workTypes = await storage.readCollection('work_types');
    res.json(workTypes);
});

router.post('/worktypes', auth('admin'), async (req, res) => {
    const newWorkType = await storage.insert('work_types', req.body);
    res.status(201).json(newWorkType);
});

module.exports = router;
