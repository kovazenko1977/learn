import React, { useState, useEffect } from 'react';
import { useLanguage } from '../LanguageContext';
import {
  Plus,
  Search,
  Filter,
  Calendar,
  User,
  Clock,
  Download,
  Printer,
  MessageSquare,
  Paperclip,
  CheckCircle,
  XCircle,
  Maximize2,
  Minimize2,
  Settings,
  HelpCircle,
  FileImage
} from 'lucide-react';

interface Ticket {
  id: number;
  title: string;
  description: string;
  category: string;
  priority: string;
  status: 'new' | 'assigned' | 'work' | 'completed' | 'rejected';
  creator_id: string;
  assignee_id: string | null;
  created_at: string;
  updated_at: string;
  deadline: string | null;
  custom_fields: Record<string, any>;
  attachments: Array<{ filepath: string; filename: string }>;
}

interface FormField {
  id: string;
  name: string;
  label: string;
  type: 'text' | 'number' | 'select' | 'checkbox';
  category: string;
  options: string[];
  required: boolean;
  sort_order: number;
}

interface Comment {
  id: number;
  ticket_id: number;
  user_id: string;
  username: string;
  user_fullName: string;
  text: string;
  created_at: string;
  attachments: Array<{ filepath: string; filename: string }>;
}

export const Tickets: React.FC<{ currentUser: any }> = ({ currentUser }) => {
  const { t } = useLanguage();
  const [tickets, setTickets] = useState<Ticket[]>([]);
  const [filteredTickets, setFilteredTickets] = useState<Ticket[]>([]);
  const [formFields, setFormFields] = useState<FormField[]>([]);
  const [users, setUsers] = useState<any[]>([]);

  // Filters
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [priorityFilter, setPriorityFilter] = useState('all');
  const [categoryFilter, setCategoryFilter] = useState('all');
  const [assigneeFilter, setAssigneeFilter] = useState('all');

  // Categories & Priorities from system
  const [categories, setCategories] = useState<string[]>(['IT', 'Plumbing', 'Electrical', 'Hardware']);
  const [priorities, setPriorities] = useState<string[]>(['Low', 'Medium', 'High', 'Critical']);

  // Modal States
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [selectedTicket, setSelectedTicket] = useState<Ticket | null>(null);

  // View Mode: List vs Kanban
  const [viewMode, setViewMode] = useState<'list' | 'kanban'>('list');

  // New Ticket Form State
  const [newTitle, setNewTitle] = useState('');
  const [newDescription, setNewDescription] = useState('');
  const [newCategory, setNewCategory] = useState('IT');
  const [newPriority, setNewPriority] = useState('Medium');
  const [newCustomFields, setNewCustomFields] = useState<Record<string, any>>({});
  const [newAttachments, setNewAttachments] = useState<Array<{ filepath: string; filename: string }>>([]);

  // Comment Form State
  const [comments, setComments] = useState<Comment[]>([]);
  const [newCommentText, setNewCommentText] = useState('');
  const [commentAttachments, setCommentAttachments] = useState<Array<{ filepath: string; filename: string }>>([]);

  // Uploading Loading State
  const [uploading, setUploading] = useState(false);

  useEffect(() => {
    fetchTickets();
    fetchFormFields();
    fetchUsers();
    fetchSettings();
  }, []);

  useEffect(() => {
    applyFilters();
  }, [tickets, search, statusFilter, priorityFilter, categoryFilter, assigneeFilter]);

  const fetchSettings = async () => {
    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/admin/settings', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      if (res.ok) {
        const data = await res.json();
        if (data.categories) setCategories(data.categories);
        if (data.priorities) setPriorities(data.priorities);
      }
    } catch (e) {
      console.error(e);
    }
  };

  const fetchTickets = async () => {
    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/tickets', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      if (res.ok) {
        const data = await res.json();
        setTickets(data);
      }
    } catch (e) {
      console.error(e);
    }
  };

  const fetchFormFields = async () => {
    try {
      const token = localStorage.getItem('crm_token');
      // Admin form fields can be read by anyone when creating/viewing ticket
      const res = await fetch('/api/admin/form-fields', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      if (res.ok) {
        const data = await res.json();
        setFormFields(data);
      }
    } catch (e) {
      console.error(e);
    }
  };

  const fetchUsers = async () => {
    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/admin/users', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      if (res.ok) {
        const data = await res.json();
        setUsers(data);
      }
    } catch (e) {
      console.error(e);
    }
  };

  const applyFilters = () => {
    let result = [...tickets];

    if (search) {
      const s = search.toLowerCase();
      result = result.filter(t =>
        t.title.toLowerCase().includes(s) ||
        t.description.toLowerCase().includes(s)
      );
    }

    if (statusFilter !== 'all') {
      result = result.filter(t => t.status === statusFilter);
    }

    if (priorityFilter !== 'all') {
      result = result.filter(t => t.priority === priorityFilter);
    }

    if (categoryFilter !== 'all') {
      result = result.filter(t => t.category === categoryFilter);
    }

    if (assigneeFilter !== 'all') {
      result = result.filter(t => {
        if (assigneeFilter === 'unassigned') return t.assignee_id === null;
        return t.assignee_id === assigneeFilter;
      });
    }

    setFilteredTickets(result);
  };

  const handleFileUpload = async (e: React.ChangeEvent<HTMLInputElement>, isComment = false) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setUploading(true);
    const formData = new FormData();
    formData.append('file', file);

    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/tickets/upload', {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` },
        body: formData
      });

      if (res.ok) {
        const data = await res.json();
        if (isComment) {
          setCommentAttachments(prev => [...prev, data]);
        } else {
          setNewAttachments(prev => [...prev, data]);
        }
      }
    } catch (error) {
      console.error('File upload failed:', error);
    } finally {
      setUploading(false);
    }
  };

  const handleCreateTicket = async (e: React.FormEvent) => {
    e.preventDefault();

    const payload = {
      title: newTitle,
      description: newDescription,
      category: newCategory,
      priority: newPriority,
      custom_fields: newCustomFields,
      attachments: newAttachments
    };

    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/tickets', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(payload)
      });

      if (res.ok) {
        setIsCreateOpen(false);
        // Clear Form
        setNewTitle('');
        setNewDescription('');
        setNewCustomFields({});
        setNewAttachments([]);
        fetchTickets();
      }
    } catch (err) {
      console.error(err);
    }
  };

  const handleSelectTicket = async (ticket: Ticket) => {
    setSelectedTicket(ticket);
    // Fetch comments
    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch(`/api/tickets/${ticket.id}/comments`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      if (res.ok) {
        const data = await res.json();
        setComments(data);
      }
    } catch (e) {
      console.error(e);
    }
  };

  const handlePostComment = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedTicket || !newCommentText.trim()) return;

    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch(`/api/tickets/${selectedTicket.id}/comments`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          text: newCommentText,
          attachments: commentAttachments
        })
      });

      if (res.ok) {
        const created = await res.json();
        setComments(prev => [...prev, created]);
        setNewCommentText('');
        setCommentAttachments([]);
      }
    } catch (e) {
      console.error(e);
    }
  };

  // Change assignee/status from dropdowns or DnD
  const handleUpdateTicketValue = async (id: number, key: string, val: any) => {
    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch(`/api/tickets/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ [key]: val })
      });

      if (res.ok) {
        const updated = await res.json();
        // Refresh details modal if open
        if (selectedTicket && selectedTicket.id === id) {
          setSelectedTicket(updated);
        }
        fetchTickets();
      }
    } catch (e) {
      console.error(e);
    }
  };

  const handleExportCSV = () => {
    const token = localStorage.getItem('crm_token');
    window.open(`/api/tickets/export?token=${token}`, '_blank');
  };

  // --- Mass Action (Bulk Assign) State ---
  const [selectedTicketIds, setSelectedTicketIds] = useState<number[]>([]);
  const [bulkAssigneeId, setBulkAssigneeId] = useState('');

  const toggleSelectTicket = (id: number) => {
    setSelectedTicketIds(prev =>
      prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]
    );
  };

  const handleBulkAssign = async () => {
    if (!bulkAssigneeId || selectedTicketIds.length === 0) return;

    try {
      const token = localStorage.getItem('crm_token');
      for (const id of selectedTicketIds) {
        await fetch(`/api/tickets/${id}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
          },
          body: JSON.stringify({ assignee_id: bulkAssigneeId, status: 'assigned' })
        });
      }
      setSelectedTicketIds([]);
      setBulkAssigneeId('');
      fetchTickets();
    } catch (e) {
      console.error(e);
    }
  };

  // --- HTML5 Native Drag & Drop Handlers ---
  const handleDragStart = (e: React.DragEvent, ticketId: number) => {
    e.dataTransfer.setData('text/plain', String(ticketId));
    e.dataTransfer.effectAllowed = 'move';
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
  };

  const handleDropToStatus = (e: React.DragEvent, newStatus: Ticket['status']) => {
    e.preventDefault();
    const id = Number(e.dataTransfer.getData('text/plain'));
    if (!isNaN(id)) {
      handleUpdateTicketValue(id, 'status', newStatus);
    }
  };

  const handleDropToExecutor = (e: React.DragEvent, executorId: string) => {
    e.preventDefault();
    const id = Number(e.dataTransfer.getData('text/plain'));
    if (!isNaN(id)) {
      handleUpdateTicketValue(id, 'assignee_id', executorId);
      handleUpdateTicketValue(id, 'status', 'assigned');
    }
  };

  // UI labels translation
  const getStatusLabel = (status: string) => {
    switch(status) {
      case 'new': return t('new');
      case 'assigned': return t('assigned');
      case 'work': return t('work');
      case 'completed': return t('completed');
      case 'rejected': return t('rejected');
      default: return status;
    }
  };

  const getPriorityLabel = (priority: string) => {
    switch(priority.toLowerCase()) {
      case 'low': return t('low');
      case 'medium': return t('medium');
      case 'high': return t('high');
      case 'critical': return t('critical');
      default: return priority;
    }
  };

  const getStatusColor = (status: string) => {
    switch(status) {
      case 'new': return 'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-400 border-blue-200/50';
      case 'assigned': return 'bg-purple-100 text-purple-800 dark:bg-purple-950/40 dark:text-purple-400 border-purple-200/50';
      case 'work': return 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400 border-amber-200/50';
      case 'completed': return 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-400 border-green-200/50';
      default: return 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400 border-red-200/50';
    }
  };

  const getPriorityColor = (priority: string) => {
    switch(priority.toLowerCase()) {
      case 'low': return 'text-slate-500';
      case 'medium': return 'text-amber-500';
      case 'high': return 'text-orange-500 font-semibold';
      default: return 'text-red-500 font-bold';
    }
  };

  const formatDateTime = (isoStr: string) => {
    return new Date(isoStr).toLocaleString('ru-RU', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const isHeadOrAdmin = ['admin', 'head'].includes(currentUser.role);
  const isExecutor = currentUser.role === 'executor';

  const executors = users.filter(u => u.role === 'executor');

  // Filter form fields applicable to newCategory
  const currentFormFields = formFields.filter(f => f.category === newCategory);

  return (
    <div className="space-y-6">
      {/* Header and Controls */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        {/* Toggle between List and Kanban views */}
        <div className="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-xl w-fit print:hidden">
          <button
            onClick={() => setViewMode('list')}
            className={`px-4 py-2 text-xs font-semibold rounded-lg transition-all duration-150 ${
              viewMode === 'list'
                ? 'bg-white dark:bg-slate-900 shadow-sm text-slate-900 dark:text-slate-100'
                : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
            }`}
          >
            Список (List)
          </button>
          <button
            onClick={() => setViewMode('kanban')}
            className={`px-4 py-2 text-xs font-semibold rounded-lg transition-all duration-150 ${
              viewMode === 'kanban'
                ? 'bg-white dark:bg-slate-900 shadow-sm text-slate-900 dark:text-slate-100'
                : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
            }`}
          >
            Доска (Kanban / DnD)
          </button>
        </div>

        <div className="flex items-center gap-2 print:hidden">
          {/* Print / PDF button */}
          <button
            onClick={() => window.print()}
            className="flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition"
          >
            <Printer className="w-4 h-4" />
            <span>Экспорт в PDF / Печать</span>
          </button>

          {/* Export CSV button */}
          <button
            onClick={handleExportCSV}
            className="flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition"
          >
            <Download className="w-4 h-4" />
            <span>{t('exportExcel')}</span>
          </button>

          {/* New Ticket button */}
          {['admin', 'responsible', 'head'].includes(currentUser.role) && (
            <button
              onClick={() => setIsCreateOpen(true)}
              className="flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-primary-600 text-white rounded-xl shadow-lg shadow-primary-500/20 hover:bg-primary-700 transition"
            >
              <Plus className="w-4 h-4" />
              <span>{t('newTicket')}</span>
            </button>
          )}
        </div>
      </div>

      {/* Filter Toolbar */}
      <div className="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/50 dark:border-slate-800/50 shadow-sm flex flex-wrap gap-3 items-center">
        {/* Search Input */}
        <div className="relative flex-1 min-w-[200px]">
          <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="text"
            placeholder={t('search')}
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
          />
        </div>

        {/* Status Filter */}
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-800 dark:text-slate-300 focus:outline-none"
        >
          <option value="all">{t('status')}: {t('all')}</option>
          <option value="new">{t('new')}</option>
          <option value="assigned">{t('assigned')}</option>
          <option value="work">{t('work')}</option>
          <option value="completed">{t('completed')}</option>
          <option value="rejected">{t('rejected')}</option>
        </select>

        {/* Priority Filter */}
        <select
          value={priorityFilter}
          onChange={(e) => setPriorityFilter(e.target.value)}
          className="px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-800 dark:text-slate-300 focus:outline-none"
        >
          <option value="all">{t('priority')}: {t('all')}</option>
          {priorities.map(p => (
            <option key={p} value={p}>{getPriorityLabel(p)}</option>
          ))}
        </select>

        {/* Category Filter */}
        <select
          value={categoryFilter}
          onChange={(e) => setCategoryFilter(e.target.value)}
          className="px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-800 dark:text-slate-300 focus:outline-none"
        >
          <option value="all">{t('category')}: {t('all')}</option>
          {categories.map(cat => (
            <option key={cat} value={cat}>{cat}</option>
          ))}
        </select>

        {/* Assignee Filter */}
        {isHeadOrAdmin && (
          <select
            value={assigneeFilter}
            onChange={(e) => setAssigneeFilter(e.target.value)}
            className="px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-800 dark:text-slate-300 focus:outline-none"
          >
            <option value="all">{t('assignee')}: {t('all')}</option>
            <option value="unassigned">{t('unassigned')}</option>
            {executors.map(exec => (
              <option key={exec.id} value={exec.id}>{exec.fullName}</option>
            ))}
          </select>
        )}
      </div>

      {/* Bulk Action Panel for Admins & Heads */}
      {isHeadOrAdmin && selectedTicketIds.length > 0 && (
        <div className="bg-primary-50 dark:bg-primary-950/20 border border-primary-200/50 dark:border-primary-900/30 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-sm">
          <span className="text-xs font-bold text-primary-800 dark:text-primary-300">
            Выбрано заявок для массового назначения: {selectedTicketIds.length}
          </span>
          <div className="flex items-center gap-2">
            <select
              value={bulkAssigneeId}
              onChange={(e) => setBulkAssigneeId(e.target.value)}
              className="px-3 py-2 text-xs rounded-xl border border-primary-200/50 dark:border-primary-900/40 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100"
            >
              <option value="">-- Выберите исполнителя --</option>
              {executors.map(exec => (
                <option key={exec.id} value={exec.id}>{exec.fullName}</option>
              ))}
            </select>
            <button
              onClick={handleBulkAssign}
              disabled={!bulkAssigneeId}
              className="px-4 py-2 text-xs font-bold bg-primary-600 disabled:opacity-50 text-white rounded-xl hover:bg-primary-700 transition"
            >
              {t('bulkAssign')}
            </button>
            <button
              onClick={() => setSelectedTicketIds([])}
              className="px-3 py-2 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
            >
              Сбросить
            </button>
          </div>
        </div>
      )}

      {/* Main View Mode rendering */}
      {viewMode === 'list' ? (
        /* List Catalog Mode */
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/50 dark:border-slate-800/50 shadow-sm overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead>
              <tr className="border-b border-slate-100 dark:border-slate-800 text-slate-400 dark:text-slate-500 font-medium">
                {isHeadOrAdmin && <th className="p-4 w-10"></th>}
                <th className="p-4">ID</th>
                <th className="p-4">{t('title')}</th>
                <th className="p-4">{t('category')}</th>
                <th className="p-4">{t('priority')}</th>
                <th className="p-4">{t('status')}</th>
                <th className="p-4">{t('assignee')}</th>
                <th className="p-4">{t('deadline')}</th>
                <th className="p-4 text-right">{t('actions')}</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800/50">
              {filteredTickets.map(ticket => {
                const assigneeUser = users.find(u => u.id === ticket.assignee_id);
                return (
                  <tr
                    key={ticket.id}
                    className="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 text-slate-700 dark:text-slate-300 transition duration-100"
                  >
                    {isHeadOrAdmin && (
                      <td className="p-4 text-center">
                        <input
                          type="checkbox"
                          checked={selectedTicketIds.includes(ticket.id)}
                          onChange={() => toggleSelectTicket(ticket.id)}
                          className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                        />
                      </td>
                    )}
                    <td className="p-4 font-bold text-slate-900 dark:text-slate-400">#{ticket.id}</td>
                    <td className="p-4">
                      <div className="max-w-[240px] truncate font-semibold text-slate-950 dark:text-slate-100">
                        {ticket.title}
                      </div>
                      <div className="text-[10px] text-slate-400 mt-0.5">
                        {formatDateTime(ticket.created_at)}
                      </div>
                    </td>
                    <td className="p-4">
                      <span className="px-2 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-[10px] font-bold">
                        {ticket.category}
                      </span>
                    </td>
                    <td className="p-4 font-semibold">
                      <span className={getPriorityColor(ticket.priority)}>
                        {getPriorityLabel(ticket.priority)}
                      </span>
                    </td>
                    <td className="p-4">
                      <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold border ${getStatusColor(ticket.status)}`}>
                        {getStatusLabel(ticket.status)}
                      </span>
                    </td>
                    <td className="p-4">
                      {isHeadOrAdmin ? (
                        <select
                          value={ticket.assignee_id || ''}
                          onChange={(e) => handleUpdateTicketValue(ticket.id, 'assignee_id', e.target.value || null)}
                          className="px-2 py-1 rounded bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs"
                        >
                          <option value="">{t('unassigned')}</option>
                          {executors.map(exec => (
                            <option key={exec.id} value={exec.id}>{exec.fullName}</option>
                          ))}
                        </select>
                      ) : (
                        <span className="font-medium text-slate-900 dark:text-slate-100">
                          {assigneeUser ? assigneeUser.fullName : t('unassigned')}
                        </span>
                      )}
                    </td>
                    <td className="p-4 font-medium text-slate-500">
                      {ticket.deadline ? formatDateTime(ticket.deadline) : '—'}
                    </td>
                    <td className="p-4 text-right">
                      <button
                        onClick={() => handleSelectTicket(ticket)}
                        className="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 inline-flex items-center gap-1 font-bold text-primary-600 hover:text-primary-700 transition"
                      >
                        <Maximize2 className="w-3.5 h-3.5" />
                        <span>Открыть</span>
                      </button>
                    </td>
                  </tr>
                );
              })}
              {filteredTickets.length === 0 && (
                <tr>
                  <td colSpan={9} className="p-8 text-center text-slate-400 dark:text-slate-500 font-medium">
                    {t('noTickets')}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      ) : (
        /* Drag-and-Drop Kanban View Board */
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 overflow-x-auto pb-4">
          {([
            { id: 'new', label: t('new'), color: 'border-t-blue-500' },
            { id: 'assigned', label: t('assigned'), color: 'border-t-purple-500' },
            { id: 'work', label: t('work'), color: 'border-t-amber-500' },
            { id: 'completed', label: t('completed'), color: 'border-t-green-500' },
            { id: 'rejected', label: t('rejected'), color: 'border-t-red-500' }
          ] as const).map(column => {
            const columnTickets = filteredTickets.filter(t => t.status === column.id);
            return (
              <div
                key={column.id}
                onDragOver={handleDragOver}
                onDrop={(e) => handleDropToStatus(e, column.id)}
                className="bg-slate-100 dark:bg-slate-900/60 rounded-2xl p-4 border-t-4 border-slate-200 dark:border-slate-800/50 flex flex-col min-h-[450px] shadow-sm max-w-[320px]"
                style={{ borderTopColor: column.id === 'new' ? '#3b82f6' : column.id === 'assigned' ? '#a855f7' : column.id === 'work' ? '#f59e0b' : column.id === 'completed' ? '#10b981' : '#ef4444' }}
              >
                <div className="flex items-center justify-between mb-4">
                  <h5 className="font-bold text-slate-900 dark:text-slate-100 text-sm">{column.label}</h5>
                  <span className="px-2 py-0.5 rounded-full text-xs font-bold bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                    {columnTickets.length}
                  </span>
                </div>

                <div className="space-y-3 flex-1 overflow-y-auto max-h-[400px]">
                  {columnTickets.map(ticket => (
                    <div
                      key={ticket.id}
                      draggable
                      onDragStart={(e) => handleDragStart(e, ticket.id)}
                      onClick={() => handleSelectTicket(ticket)}
                      className="bg-white dark:bg-slate-800 p-3.5 rounded-xl border border-slate-200/50 dark:border-slate-700/30 shadow-sm hover:shadow-md cursor-grab active:cursor-grabbing transition duration-150 space-y-3"
                    >
                      <div className="flex justify-between items-start">
                        <span className="text-[10px] font-bold text-slate-400">#{ticket.id}</span>
                        <span className={`text-[10px] font-semibold ${getPriorityColor(ticket.priority)}`}>
                          {getPriorityLabel(ticket.priority)}
                        </span>
                      </div>
                      <h6 className="font-bold text-xs text-slate-900 dark:text-slate-100 line-clamp-2">
                        {ticket.title}
                      </h6>
                      <div className="flex items-center justify-between text-[10px] text-slate-400">
                        <span className="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 font-bold uppercase">{ticket.category}</span>
                        <span className="flex items-center gap-0.5 font-medium"><Calendar className="w-3 h-3" /> {formatDateTime(ticket.created_at)}</span>
                      </div>
                    </div>
                  ))}
                  {columnTickets.length === 0 && (
                    <div className="h-full border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-center p-4 text-center text-xs text-slate-400">
                      Перетащите сюда заявку
                    </div>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Drag onto Technician assignment card dashboard (Heads/Admins only) */}
      {isHeadOrAdmin && viewMode === 'kanban' && (
        <div className="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/50 dark:border-slate-800/50 shadow-sm mt-8">
          <h4 className="font-bold text-slate-900 dark:text-slate-50 text-sm mb-4 flex items-center gap-2">
            <User className="w-5 h-5 text-primary-500" />
            <span>Назначение перетаскиванием (Drag & Drop на исполнителей)</span>
          </h4>
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
            {executors.map(exec => (
              <div
                key={exec.id}
                onDragOver={handleDragOver}
                onDrop={(e) => handleDropToExecutor(e, exec.id)}
                className="bg-slate-50 dark:bg-slate-950/40 border-2 border-dashed border-slate-200 dark:border-slate-800 hover:border-primary-500 dark:hover:border-primary-400 rounded-xl p-4 text-center transition cursor-default group"
              >
                <div className="w-10 h-10 bg-primary-100 dark:bg-primary-950 rounded-full flex items-center justify-center mx-auto mb-2 text-primary-700 dark:text-primary-300 font-bold">
                  {exec.fullName.charAt(0)}
                </div>
                <p className="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">{exec.fullName}</p>
                <span className="inline-block mt-1 text-[10px] font-semibold text-primary-600 dark:text-primary-400">
                  {t('dragDropAssign')}
                </span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* CREATE TICKET MODAL */}
      {isCreateOpen && (
        <div className="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200/50 dark:border-slate-800/50 relative max-h-[90vh] overflow-y-auto animate-in fade-in duration-150">
            <h3 className="text-lg font-bold text-slate-900 dark:text-slate-50 mb-4">{t('newTicket')}</h3>
            <form onSubmit={handleCreateTicket} className="space-y-4">
              {/* Title */}
              <div>
                <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">{t('title')}</label>
                <input
                  type="text"
                  required
                  value={newTitle}
                  onChange={(e) => setNewTitle(e.target.value)}
                  className="w-full px-4 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-950 dark:text-slate-100 focus:outline-none"
                  placeholder="Опишите вкратце проблему..."
                />
              </div>

              {/* Description */}
              <div>
                <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">{t('description')}</label>
                <textarea
                  required
                  rows={3}
                  value={newDescription}
                  onChange={(e) => setNewDescription(e.target.value)}
                  className="w-full px-4 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-950 dark:text-slate-100 focus:outline-none"
                  placeholder="Подробное описание проблемы..."
                />
              </div>

              {/* Category and Priority Grid */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">{t('category')}</label>
                  <select
                    value={newCategory}
                    onChange={(e) => {
                      setNewCategory(e.target.value);
                      setNewCustomFields({}); // reset category fields
                    }}
                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-850"
                  >
                    {categories.map(cat => (
                      <option key={cat} value={cat}>{cat}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">{t('priority')}</label>
                  <select
                    value={newPriority}
                    onChange={(e) => setNewPriority(e.target.value)}
                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-850"
                  >
                    {priorities.map(p => (
                      <option key={p} value={p}>{getPriorityLabel(p)}</option>
                    ))}
                  </select>
                </div>
              </div>

              {/* DYNAMIC CUSTOM FIELDS BASED ON CATEGORY */}
              {currentFormFields.length > 0 && (
                <div className="p-4 bg-slate-50 dark:bg-slate-950/30 rounded-xl border border-slate-200/50 dark:border-slate-800/50 space-y-3">
                  <h5 className="font-bold text-slate-900 dark:text-slate-50 text-xs mb-2">{t('customFields')}</h5>
                  {currentFormFields.map(field => (
                    <div key={field.id} className="space-y-1">
                      <label className="block text-xs font-semibold text-slate-600 dark:text-slate-300">
                        {field.label} {field.required && <span className="text-red-500">*</span>}
                      </label>
                      {field.type === 'select' ? (
                        <select
                          required={field.required}
                          onChange={(e) => setNewCustomFields(prev => ({ ...prev, [field.name]: e.target.value }))}
                          className="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900"
                        >
                          <option value="">Выберите опцию...</option>
                          {field.options.map(opt => (
                            <option key={opt} value={opt}>{opt}</option>
                          ))}
                        </select>
                      ) : field.type === 'checkbox' ? (
                        <input
                          type="checkbox"
                          onChange={(e) => setNewCustomFields(prev => ({ ...prev, [field.name]: e.target.checked }))}
                          className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                        />
                      ) : (
                        <input
                          type={field.type === 'number' ? 'number' : 'text'}
                          required={field.required}
                          placeholder={field.label}
                          onChange={(e) => setNewCustomFields(prev => ({ ...prev, [field.name]: e.target.value }))}
                          className="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-950 dark:text-slate-50"
                        />
                      )}
                    </div>
                  ))}
                </div>
              )}

              {/* Attachments list & Upload */}
              <div className="space-y-2">
                <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider">{t('uploadFile')}</label>
                <div className="flex items-center gap-2">
                  <input
                    type="file"
                    id="file-upload"
                    className="hidden"
                    onChange={(e) => handleFileUpload(e)}
                  />
                  <label
                    htmlFor="file-upload"
                    className="px-4 py-2 border border-dashed border-slate-300 hover:border-primary-500 text-xs font-bold rounded-xl cursor-pointer flex items-center gap-1 text-slate-600 hover:text-primary-600 transition"
                  >
                    <Paperclip className="w-4 h-4" />
                    <span>Выбрать файл</span>
                  </label>
                  {uploading && <span className="text-xs text-slate-400">Загрузка...</span>}
                </div>

                {newAttachments.length > 0 && (
                  <div className="flex flex-wrap gap-2 pt-2">
                    {newAttachments.map((att, i) => (
                      <span key={i} className="px-2.5 py-1 text-xs bg-slate-100 dark:bg-slate-800 rounded-lg inline-flex items-center gap-1 border">
                        <FileImage className="w-3.5 h-3.5" />
                        <span className="max-w-[120px] truncate">{att.filename}</span>
                        <button
                          type="button"
                          onClick={() => setNewAttachments(prev => prev.filter((_, idx) => idx !== i))}
                          className="text-red-500 hover:text-red-700 ml-1 font-bold text-[10px]"
                        >
                          ✕
                        </button>
                      </span>
                    ))}
                  </div>
                )}
              </div>

              {/* Action Buttons */}
              <div className="pt-4 flex justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
                <button
                  type="button"
                  onClick={() => setIsCreateOpen(false)}
                  className="px-4 py-2 border rounded-xl text-xs hover:bg-slate-50 dark:hover:bg-slate-800"
                >
                  {t('cancel')}
                </button>
                <button
                  type="submit"
                  className="px-6 py-2 bg-primary-600 text-white font-bold rounded-xl text-xs hover:bg-primary-700 transition"
                >
                  {t('create')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* TICKET DETAILS & COMMENTS DIALOG */}
      {selectedTicket && (
        <div className="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white dark:bg-slate-900 rounded-2xl max-w-3xl w-full p-6 shadow-2xl border border-slate-200/50 dark:border-slate-800/50 relative max-h-[92vh] overflow-y-auto animate-in fade-in duration-150">
            <button
              onClick={() => setSelectedTicket(null)}
              className="absolute top-4 right-4 text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800"
            >
              ✕
            </button>

            <div className="flex flex-wrap items-center gap-2 mb-4">
              <span className="font-bold text-slate-400">#{selectedTicket.id}</span>
              <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold border ${getStatusColor(selectedTicket.status)}`}>
                {getStatusLabel(selectedTicket.status)}
              </span>
              <span className={`text-xs font-semibold ${getPriorityColor(selectedTicket.priority)}`}>
                {getPriorityLabel(selectedTicket.priority)}
              </span>
              <span className="text-xs bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded font-bold uppercase">{selectedTicket.category}</span>
            </div>

            <h3 className="text-xl font-bold text-slate-900 dark:text-slate-50 mb-4">{selectedTicket.title}</h3>
            <p className="text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-950/20 p-4 rounded-xl border border-slate-200/50 dark:border-slate-800/50 mb-6">
              {selectedTicket.description}
            </p>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 text-xs bg-slate-50 dark:bg-slate-950/10 p-4 rounded-xl">
              <div>
                <p className="text-slate-400 font-semibold mb-1">Сведения:</p>
                <p className="font-medium text-slate-700 dark:text-slate-200">Создатель: <span className="font-bold">{users.find(u => u.id === selectedTicket.creator_id)?.fullName || selectedTicket.creator_id}</span></p>
                <p className="font-medium text-slate-700 dark:text-slate-200 mt-1">Создано: <span className="font-bold">{formatDateTime(selectedTicket.created_at)}</span></p>
                <p className="font-medium text-slate-700 dark:text-slate-200 mt-1">Дедлайн SLA: <span className="font-bold text-red-600 dark:text-red-400">{selectedTicket.deadline ? formatDateTime(selectedTicket.deadline) : '—'}</span></p>
              </div>

              <div>
                <p className="text-slate-400 font-semibold mb-1">Дополнительные поля:</p>
                {Object.keys(selectedTicket.custom_fields || {}).length > 0 ? (
                  Object.entries(selectedTicket.custom_fields).map(([k, v]) => (
                    <p key={k} className="text-slate-700 dark:text-slate-200 font-medium">
                      {k}: <span className="font-bold">{String(v)}</span>
                    </p>
                  ))
                ) : (
                  <p className="text-slate-400">Нет дополнительных полей.</p>
                )}
              </div>
            </div>

            {/* Attachments Section */}
            {selectedTicket.attachments && selectedTicket.attachments.length > 0 && (
              <div className="mb-6 space-y-2 text-xs">
                <p className="text-slate-400 font-semibold">Прикрепленные файлы:</p>
                <div className="flex flex-wrap gap-2">
                  {selectedTicket.attachments.map((att, idx) => (
                    <a
                      key={idx}
                      href={att.filepath}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="px-3 py-1.5 bg-white dark:bg-slate-800 border hover:border-primary-500 rounded-xl inline-flex items-center gap-1.5 hover:text-primary-600 transition"
                    >
                      <Paperclip className="w-4 h-4 text-slate-400" />
                      <span>{att.filename}</span>
                    </a>
                  ))}
                </div>
              </div>
            )}

            {/* Operational controls for technicians & Heads */}
            <div className="pt-4 border-t border-slate-100 dark:border-slate-800/80 mb-6 flex flex-wrap gap-3">
              {/* Mark resolved by executor */}
              {isExecutor && selectedTicket.assignee_id === currentUser.id && (
                <div className="flex gap-2">
                  {selectedTicket.status !== 'completed' && (
                    <button
                      onClick={() => handleUpdateTicketValue(selectedTicket.id, 'status', 'completed')}
                      className="px-4 py-2 bg-green-600 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 hover:bg-green-700 transition"
                    >
                      <CheckCircle className="w-4 h-4" />
                      <span>Отметить выполнение</span>
                    </button>
                  )}
                  {selectedTicket.status === 'new' && (
                    <button
                      onClick={() => handleUpdateTicketValue(selectedTicket.id, 'status', 'work')}
                      className="px-4 py-2 bg-amber-500 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 hover:bg-amber-600 transition"
                    >
                      <Clock className="w-4 h-4" />
                      <span>В работу</span>
                    </button>
                  )}
                </div>
              )}

              {/* Change assignee for heads/admins */}
              {isHeadOrAdmin && (
                <div className="flex items-center gap-2">
                  <span className="text-xs text-slate-500">Назначить исполнителя:</span>
                  <select
                    value={selectedTicket.assignee_id || ''}
                    onChange={(e) => {
                      handleUpdateTicketValue(selectedTicket.id, 'assignee_id', e.target.value || null);
                      handleUpdateTicketValue(selectedTicket.id, 'status', e.target.value ? 'assigned' : 'new');
                    }}
                    className="px-2.5 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800 border text-xs text-slate-800 dark:text-slate-100"
                  >
                    <option value="">{t('unassigned')}</option>
                    {executors.map(exec => (
                      <option key={exec.id} value={exec.id}>{exec.fullName}</option>
                    ))}
                  </select>

                  <span className="text-xs text-slate-500 ml-4">Статус:</span>
                  <select
                    value={selectedTicket.status}
                    onChange={(e) => handleUpdateTicketValue(selectedTicket.id, 'status', e.target.value)}
                    className="px-2.5 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-800 border text-xs text-slate-800 dark:text-slate-100"
                  >
                    <option value="new">{t('new')}</option>
                    <option value="assigned">{t('assigned')}</option>
                    <option value="work">{t('work')}</option>
                    <option value="completed">{t('completed')}</option>
                    <option value="rejected">{t('rejected')}</option>
                  </select>
                </div>
              )}
            </div>

            {/* COMMENTS STREAM */}
            <div className="border-t border-slate-100 dark:border-slate-800 pt-4 space-y-4">
              <h4 className="font-bold text-slate-900 dark:text-slate-50 text-sm flex items-center gap-1.5">
                <MessageSquare className="w-5 h-5 text-primary-500" />
                <span>{t('comments')} ({comments.length})</span>
              </h4>

              {/* Feed List */}
              <div className="space-y-3 max-h-[250px] overflow-y-auto pr-1">
                {comments.map(c => (
                  <div key={c.id} className="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl border border-slate-200/40 text-xs">
                    <div className="flex justify-between font-bold text-slate-900 dark:text-slate-100 mb-1">
                      <span>{c.user_fullName || c.username}</span>
                      <span className="text-[10px] text-slate-400 font-normal">{formatDateTime(c.created_at)}</span>
                    </div>
                    <p className="text-slate-600 dark:text-slate-300 leading-relaxed font-medium">{c.text}</p>
                    {c.attachments && c.attachments.length > 0 && (
                      <div className="flex flex-wrap gap-1.5 mt-2">
                        {c.attachments.map((att, aIdx) => (
                          <a
                            key={aIdx}
                            href={att.filepath}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="px-2 py-1 bg-white dark:bg-slate-800 text-[10px] rounded border inline-flex items-center gap-1"
                          >
                            <Paperclip className="w-3 h-3 text-slate-400" />
                            <span>{att.filename}</span>
                          </a>
                        ))}
                      </div>
                    )}
                  </div>
                ))}
                {comments.length === 0 && (
                  <p className="text-center text-xs text-slate-400 py-3 font-medium">Нет комментариев. Оставьте первый!</p>
                )}
              </div>

              {/* Comment submission form */}
              <form onSubmit={handlePostComment} className="pt-2 space-y-2">
                <textarea
                  required
                  rows={2}
                  value={newCommentText}
                  onChange={(e) => setNewCommentText(e.target.value)}
                  placeholder={t('writeComment')}
                  className="w-full px-3 py-2 text-xs border rounded-xl bg-slate-50 dark:bg-slate-950/40 text-slate-950 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-primary-500"
                />

                <div className="flex justify-between items-center">
                  <div className="flex items-center gap-2">
                    <input
                      type="file"
                      id="comment-file"
                      className="hidden"
                      onChange={(e) => handleFileUpload(e, true)}
                    />
                    <label
                      htmlFor="comment-file"
                      className="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg cursor-pointer flex items-center text-slate-500"
                    >
                      <Paperclip className="w-4 h-4" />
                    </label>
                    {commentAttachments.map((att, i) => (
                      <span key={i} className="text-[10px] bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded truncate max-w-[80px]">
                        {att.filename}
                      </span>
                    ))}
                  </div>

                  <button
                    type="submit"
                    className="px-4 py-2 bg-primary-600 text-white font-bold rounded-xl text-xs hover:bg-primary-700 transition"
                  >
                    {t('addComment')}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
