const express = require('express');
const router = express.Router();
const path = require('path');
const JsonStorage = require('../storage/JsonStorage');
const auth = require('../middleware/auth');

const storage = new JsonStorage(path.join(__dirname, '../data'));

const generateRequestNumber = () => {
    const date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    const random = Math.floor(1000 + Math.random() * 9000);
    return `ХОП-${date}-${random}`;
};

// Create Request
router.post('/', auth(), async (req, res) => {
    const { work_type_id, priority, location, description } = req.body;

    const workType = await storage.findOne('work_types', { id: parseInt(work_type_id) });
    if (!workType) return res.status(400).json({ message: 'Invalid work type' });

    const newRequest = {
        number: generateRequestNumber(),
        requester_id: req.user.id,
        work_type_id: parseInt(work_type_id),
        department_id: workType.department_id,
        assigned_to: null,
        priority: priority || 'normal',
        location,
        description,
        status: 'new',
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
    };

    const savedRequest = await storage.insert('requests', newRequest);

    // Log history
    await storage.insert('status_history', {
        request_id: savedRequest.id,
        status: 'new',
        changed_by: req.user.id,
        changed_at: new Date().toISOString(),
        comment: 'Заявка создана'
    });

    res.status(201).json(savedRequest);
});

// My Requests
router.get('/my', auth(), async (req, res) => {
    const requests = await storage.find('requests', { requester_id: req.user.id });
    res.json(requests);
});

// Department Requests (for manager/executor)
router.get('/department', auth(['manager', 'executor', 'admin']), async (req, res) => {
    let requests;
    if (req.user.role === 'admin') {
        requests = await storage.readCollection('requests');
    } else {
        requests = await storage.find('requests', { department_id: req.user.department_id });
    }
    res.json(requests);
});

// All Requests (admin)
router.get('/all', auth('admin'), async (req, res) => {
    const requests = await storage.readCollection('requests');
    res.json(requests);
});

// Request Details
router.get('/:id', auth(), async (req, res) => {
    const request = await storage.findOne('requests', { id: parseInt(req.params.id) });
    if (!request) return res.status(404).json({ message: 'Request not found' });

    // Check access
    if (req.user.role !== 'admin' &&
        request.requester_id !== req.user.id &&
        request.department_id !== req.user.department_id) {
        return res.status(403).json({ message: 'Access denied' });
    }

    res.json(request);
});

// Request History
router.get('/:id/history', auth(), async (req, res) => {
    const history = await storage.find('status_history', { request_id: parseInt(req.params.id) });
    res.json(history);
});

// Change Status
router.patch('/:id/status', auth(['executor', 'manager', 'admin']), async (req, res) => {
    const { status, comment } = req.body;
    const requestId = parseInt(req.params.id);
    const request = await storage.findOne('requests', { id: requestId });

    if (!request) return res.status(404).json({ message: 'Request not found' });

    // Additional role-based checks could be here

    const updated = await storage.update('requests', requestId, {
        status,
        updated_at: new Date().toISOString(),
        closed_at: (status === 'closed' || status === 'completed') ? new Date().toISOString() : request.closed_at
    });

    await storage.insert('status_history', {
        request_id: requestId,
        status,
        changed_by: req.user.id,
        changed_at: new Date().toISOString(),
        comment
    });

    res.json(updated);
});

// Assign Executor
router.patch('/:id/assign', auth(['manager', 'admin']), async (req, res) => {
    const { executor_id } = req.body;
    const requestId = parseInt(req.params.id);

    const updated = await storage.update('requests', requestId, {
        assigned_to: parseInt(executor_id),
        status: 'assigned',
        updated_at: new Date().toISOString()
    });

    await storage.insert('status_history', {
        request_id: requestId,
        status: 'assigned',
        changed_by: req.user.id,
        changed_at: new Date().toISOString(),
        comment: `Назначен исполнитель (ID: ${executor_id})`
    });

    res.json(updated);
});

// Take to work (executor)
router.patch('/:id/take', auth(['executor', 'admin']), async (req, res) => {
    const requestId = parseInt(req.params.id);

    const updated = await storage.update('requests', requestId, {
        status: 'in_progress',
        assigned_to: req.user.role === 'executor' ? req.user.id : undefined,
        updated_at: new Date().toISOString()
    });

    await storage.insert('status_history', {
        request_id: requestId,
        status: 'in_progress',
        changed_by: req.user.id,
        changed_at: new Date().toISOString(),
        comment: 'Взято в работу'
    });

    res.json(updated);
});

// Confirm (requester)
router.patch('/:id/confirm', auth(), async (req, res) => {
    const requestId = parseInt(req.params.id);
    const request = await storage.findOne('requests', { id: requestId });

    if (request.requester_id !== req.user.id && req.user.role !== 'admin') {
        return res.status(403).json({ message: 'Only requester can confirm' });
    }

    const updated = await storage.update('requests', requestId, {
        status: 'closed',
        updated_at: new Date().toISOString(),
        closed_at: new Date().toISOString()
    });

    await storage.insert('status_history', {
        request_id: requestId,
        status: 'closed',
        changed_by: req.user.id,
        changed_at: new Date().toISOString(),
        comment: 'Заявка подтверждена и закрыта'
    });

    res.json(updated);
});

module.exports = router;
