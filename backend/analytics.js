import express from 'express';
import storage from './storage.js';
import { authenticateToken } from './auth.js';

const router = express.Router();

router.get('/dashboard', authenticateToken, async (req, res) => {
  try {
    const tickets = await storage.getTickets();
    const users = await storage.getUsers();

    // 1. Total count by status
    const statuses = {
      new: 0,
      assigned: 0,
      work: 0,
      completed: 0,
      rejected: 0
    };

    // 2. Average resolution time
    let resolvedCount = 0;
    let totalResolutionTimeMs = 0;

    // 3. SLA status
    let overdueCount = 0;
    let onTimeCount = 0;

    const now = new Date();

    tickets.forEach(t => {
      // Status count
      if (statuses[t.status] !== undefined) {
        statuses[t.status]++;
      } else {
        statuses[t.status] = 1;
      }

      // Average Resolution Time Calculation
      // If status is completed and there's a completion/update date
      if (t.status === 'completed') {
        const start = new Date(t.created_at);
        const end = t.completed_at ? new Date(t.completed_at) : new Date(t.updated_at);
        const diffMs = end - start;
        if (diffMs > 0) {
          totalResolutionTimeMs += diffMs;
          resolvedCount++;
        }
      }

      // SLA Tracking
      if (t.deadline) {
        const deadlineDate = new Date(t.deadline);
        if (t.status === 'completed') {
          const end = t.completed_at ? new Date(t.completed_at) : new Date(t.updated_at);
          if (end > deadlineDate) {
            overdueCount++;
          } else {
            onTimeCount++;
          }
        } else {
          // ticket is still active, is it overdue?
          if (now > deadlineDate) {
            overdueCount++;
          } else {
            onTimeCount++;
          }
        }
      }
    });

    const averageResolutionTimeHours = resolvedCount > 0
      ? Number((totalResolutionTimeMs / (1000 * 60 * 60) / resolvedCount).toFixed(1))
      : 0;

    // 4. Employee Workload (technicians/executors)
    const executors = users.filter(u => u.role === 'executor');
    const workload = executors.map(exec => {
      const assignedTickets = tickets.filter(t => t.assignee_id === exec.id);
      const activeTickets = assignedTickets.filter(t => ['assigned', 'work'].includes(t.status));
      const completedTickets = assignedTickets.filter(t => t.status === 'completed');

      return {
        id: exec.id,
        fullName: exec.fullName,
        username: exec.username,
        activeCount: activeTickets.length,
        completedCount: completedTickets.length,
        totalAssigned: assignedTickets.length
      };
    });

    res.json({
      statusCounts: statuses,
      totalTickets: tickets.length,
      averageResolutionTimeHours,
      slaStatus: {
        overdue: overdueCount,
        onTime: onTimeCount
      },
      workload
    });
  } catch (error) {
    console.error('Analytics dashboard error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

export default router;
