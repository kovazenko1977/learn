import express from 'express';
import multer from 'multer';
import path from 'path';
import storage from './storage.js';
import { authenticateToken, requireRole } from './auth.js';

const router = express.Router();

// Multer Config
const diskStorage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, 'uploads/');
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1e9);
    cb(null, uniqueSuffix + path.extname(file.originalname));
  }
});

const upload = multer({ storage: diskStorage });

// Helper to calculate SLA deadline
async function calculateDeadline(category, priority, createdAtStr) {
  try {
    const settings = await storage.getSettings();
    const slaHours = settings.sla && settings.sla[priority] ? Number(settings.sla[priority]) : 24;
    const date = new Date(createdAtStr);
    date.setHours(date.getHours() + slaHours);
    return date.toISOString();
  } catch (err) {
    const date = new Date(createdAtStr);
    date.setHours(date.getHours() + 24); // default 24h fallback
    return date.toISOString();
  }
}

// Upload file endpoint
router.post('/upload', authenticateToken, upload.single('file'), (req, res) => {
  if (!req.file) {
    return res.status(400).json({ error: 'No file uploaded' });
  }
  res.json({
    filepath: `/uploads/${req.file.filename}`,
    filename: req.file.originalname
  });
});

// Export Tickets to CSV (Excel compatible with BOM for Cyrillic support)
router.get('/export', authenticateToken, async (req, res) => {
  try {
    const tickets = await storage.getTickets();
    const users = await storage.getUsers();
    const userMap = new Map(users.map(u => [u.id, u.fullName]));

    let csvContent = '\uFEFF'; // UTF-8 BOM
    csvContent += 'ID;Название;Описание;Категория;Приоритет;Статус;Создатель;Исполнитель;Создано;Обновлено;Дедлайн (SLA)\n';

    for (const t of tickets) {
      const creatorName = userMap.get(t.creator_id) || t.creator_id;
      const assigneeName = t.assignee_id ? (userMap.get(t.assignee_id) || t.assignee_id) : 'Не назначен';

      const row = [
        t.id,
        `"${t.title.replace(/"/g, '""')}"`,
        `"${t.description.replace(/"/g, '""')}"`,
        t.category,
        t.priority,
        t.status,
        `"${creatorName.replace(/"/g, '""')}"`,
        `"${assigneeName.replace(/"/g, '""')}"`,
        t.created_at,
        t.updated_at,
        t.deadline || ''
      ];
      csvContent += row.join(';') + '\n';
    }

    res.setHeader('Content-Type', 'text/csv; charset=utf-8');
    res.setHeader('Content-Disposition', 'attachment; filename="tickets_export.csv"');
    res.status(200).send(csvContent);
  } catch (error) {
    console.error('Export error:', error);
    res.status(500).json({ error: 'Failed to export tickets' });
  }
});

// GET /api/tickets - List & Filter tickets
router.get('/', authenticateToken, async (req, res) => {
  try {
    let tickets = await storage.getTickets();
    const { status, priority, category, assignee_id, creator_id, search } = req.query;

    // Filters
    if (status) {
      tickets = tickets.filter(t => t.status === status);
    }
    if (priority) {
      tickets = tickets.filter(t => t.priority === priority);
    }
    if (category) {
      tickets = tickets.filter(t => t.category === category);
    }
    if (assignee_id) {
      tickets = tickets.filter(t => t.assignee_id === assignee_id);
    }
    if (creator_id) {
      tickets = tickets.filter(t => t.creator_id === creator_id);
    }
    if (search) {
      const s = search.toLowerCase();
      tickets = tickets.filter(t =>
        t.title.toLowerCase().includes(s) ||
        t.description.toLowerCase().includes(s)
      );
    }

    // Role restrictions
    // 1. Executor (Technician) should see only assigned tickets, or tickets he created.
    // Wait, the specification says: "Исполнитель: Просмотр назначенных заявок."
    // "Ответственный сотрудник: Создание заявок, Просмотр статуса своих заявок."
    // Let's filter depending on role to match the specs precisely!
    if (req.user.role === 'executor') {
      tickets = tickets.filter(t => t.assignee_id === req.user.id);
    } else if (req.user.role === 'responsible') {
      tickets = tickets.filter(t => t.creator_id === req.user.id);
    }
    // Admin & Head can see all tickets. Perfect!

    res.json(tickets);
  } catch (error) {
    console.error('Get tickets error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// GET /api/tickets/:id - Get ticket details
router.get('/:id', authenticateToken, async (req, res) => {
  try {
    const ticket = await storage.getTicketById(req.params.id);
    if (!ticket) {
      return res.status(404).json({ error: 'Ticket not found' });
    }

    // Role access control check
    if (req.user.role === 'executor' && ticket.assignee_id !== req.user.id) {
      return res.status(403).json({ error: 'Access denied to this ticket' });
    }
    if (req.user.role === 'responsible' && ticket.creator_id !== req.user.id) {
      return res.status(403).json({ error: 'Access denied to this ticket' });
    }

    res.json(ticket);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// POST /api/tickets - Create ticket
router.post('/', authenticateToken, async (req, res) => {
  const { title, description, category, priority, custom_fields, attachments } = req.body;

  if (!title || !description || !category || !priority) {
    return res.status(400).json({ error: 'Missing required fields' });
  }

  try {
    const createdAt = new Date().toISOString();
    const deadline = await calculateDeadline(category, priority, createdAt);

    const ticket = {
      title,
      description,
      category,
      priority,
      status: 'new', // default status
      creator_id: req.user.id,
      assignee_id: null,
      created_at: createdAt,
      updated_at: createdAt,
      deadline,
      custom_fields: custom_fields || {},
      attachments: attachments || []
    };

    const created = await storage.createTicket(ticket);
    res.status(201).json(created);
  } catch (error) {
    console.error('Create ticket error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// PUT /api/tickets/:id - Update ticket
router.put('/:id', authenticateToken, async (req, res) => {
  const ticketId = req.params.id;
  try {
    const ticket = await storage.getTicketById(ticketId);
    if (!ticket) {
      return res.status(404).json({ error: 'Ticket not found' });
    }

    // Authorization checks
    // 1. Responsible employee can only edit their own tickets before they are assigned/completed.
    if (req.user.role === 'responsible' && ticket.creator_id !== req.user.id) {
      return res.status(403).json({ error: 'Access denied' });
    }
    // 2. Executor can only update the status or add comment (handled in executor endpoint, but here we can restrict what they update)
    if (req.user.role === 'executor') {
      // executor can only update status to complete or in-progress
      const allowedKeys = ['status', 'updated_at'];
      const keys = Object.keys(req.body);
      const isAllowed = keys.every(k => allowedKeys.includes(k));
      if (!isAllowed) {
        return res.status(403).json({ error: 'Executors can only update status' });
      }
    }

    const updates = { ...req.body, updated_at: new Date().toISOString() };

    // If status changes to completed, we can track that
    if (updates.status === 'completed') {
      updates.completed_at = new Date().toISOString();
    }

    // If priority or category changed, recalculate deadline
    if (updates.priority || updates.category) {
      const finalPriority = updates.priority || ticket.priority;
      const finalCategory = updates.category || ticket.category;
      updates.deadline = await calculateDeadline(finalCategory, finalPriority, ticket.created_at);
    }

    const updated = await storage.updateTicket(ticketId, updates);
    res.json(updated);
  } catch (error) {
    console.error('Update ticket error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// DELETE /api/tickets/:id - Delete ticket
router.delete('/:id', authenticateToken, requireRole(['admin']), async (req, res) => {
  try {
    const success = await storage.deleteTicket(req.params.id);
    if (success) {
      res.json({ message: 'Ticket deleted successfully' });
    } else {
      res.status(404).json({ error: 'Ticket not found' });
    }
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// --- Comments for Tickets ---

// GET /api/tickets/:id/comments
router.get('/:id/comments', authenticateToken, async (req, res) => {
  try {
    const comments = await storage.getComments(req.params.id);
    res.json(comments);
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

// POST /api/tickets/:id/comments
router.post('/:id/comments', authenticateToken, async (req, res) => {
  const { text, attachments } = req.body;
  if (!text) {
    return res.status(400).json({ error: 'Comment text is required' });
  }

  try {
    const comment = {
      ticket_id: Number(req.params.id),
      user_id: req.user.id,
      username: req.user.username,
      user_fullName: req.user.fullName,
      text,
      created_at: new Date().toISOString(),
      attachments: attachments || []
    };

    const created = await storage.createComment(comment);
    res.status(201).json(created);
  } catch (error) {
    console.error('Create comment error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

export default router;
