const express = require('express');
const router = express.Router();
const path = require('path');
const JsonStorage = require('../storage/JsonStorage');
const auth = require('../middleware/auth');

const storage = new JsonStorage(path.join(__dirname, '../data'));

router.get('/summary', auth(['manager', 'admin']), async (req, res) => {
    const requests = await storage.readCollection('requests');
    const summary = requests.reduce((acc, req) => {
        acc[req.status] = (acc[req.status] || 0) + 1;
        acc.total = (acc.total || 0) + 1;
        return acc;
    }, {});
    res.json(summary);
});

router.get('/executors', auth(['manager', 'admin']), async (req, res) => {
    const requests = await storage.readCollection('requests');
    const users = await storage.readCollection('users');
    const executors = users.filter(u => u.role === 'executor');

    const stats = executors.map(exec => {
        const count = requests.filter(r => r.assigned_to === exec.id && r.status !== 'closed').length;
        return {
            id: exec.id,
            full_name: exec.full_name,
            active_requests: count
        };
    });

    res.json(stats);
});

module.exports = router;
