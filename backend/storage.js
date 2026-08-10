import fs from 'fs';
import path from 'path';
import bcrypt from 'bcryptjs';
import mysql from 'mysql2/promise';

const DATA_DIR = path.resolve('data');
const UPLOADS_DIR = path.resolve('uploads');

// Ensure directories exist
if (!fs.existsSync(DATA_DIR)) {
  fs.mkdirSync(DATA_DIR, { recursive: true });
}
if (!fs.existsSync(UPLOADS_DIR)) {
  fs.mkdirSync(UPLOADS_DIR, { recursive: true });
}

class Storage {
  constructor() {
    this.mode = 'JSON'; // JSON or MySQL
    this.mysqlConfig = null;
    this.pool = null;

    // Load default settings if JSON exists, otherwise set defaults
    this.loadConfig();
  }

  loadConfig() {
    try {
      const settingsFile = path.join(DATA_DIR, 'settings.json');
      if (fs.existsSync(settingsFile)) {
        const settings = JSON.parse(fs.readFileSync(settingsFile, 'utf8'));
        this.mode = settings.storageMode || 'JSON';
        if (settings.mysqlConfig) {
          this.mysqlConfig = settings.mysqlConfig;
        }
      }
    } catch (e) {
      console.error('Error loading storage config:', e);
    }
  }

  async init() {
    this.loadConfig();

    // Seed JSON tables if they don't exist
    this.seedJSON();

    if (this.mode === 'MySQL' && this.mysqlConfig) {
      try {
        await this.connectMySQLAndMigrate();
        console.log('Successfully connected to MySQL database and verified schemas.');
      } catch (err) {
        console.error('Failed to connect to MySQL on init, falling back to JSON mode:', err.message);
        this.mode = 'JSON';
      }
    }
  }

  seedJSON() {
    const files = {
      'users.json': [],
      'tickets.json': [],
      'comments.json': [],
      'form_fields.json': [],
      'settings.json': { storageMode: 'JSON', categories: ['IT', 'Plumbing', 'Electrical', 'Hardware'], priorities: ['Low', 'Medium', 'High', 'Critical'], sla: { Low: 48, Medium: 24, High: 8, Critical: 2 } }
    };

    for (const [filename, defaultData] of Object.entries(files)) {
      const filepath = path.join(DATA_DIR, filename);
      if (!fs.existsSync(filepath)) {
        fs.writeFileSync(filepath, JSON.stringify(defaultData, null, 2), 'utf8');
      }
    }

    // Seed default users if users.json is empty
    const usersPath = path.join(DATA_DIR, 'users.json');
    let users = [];
    try {
      users = JSON.parse(fs.readFileSync(usersPath, 'utf8'));
    } catch (e) {
      users = [];
    }

    if (users.length === 0) {
      const salt = bcrypt.genSaltSync(10);
      users = [
        {
          id: '1',
          username: 'admin',
          password: bcrypt.hashSync('admin123', salt),
          fullName: 'Администратор Системы',
          email: 'admin@crm.local',
          role: 'admin',
          created_at: new Date().toISOString()
        },
        {
          id: '2',
          username: 'responsible',
          password: bcrypt.hashSync('resp123', salt),
          fullName: 'Иван Иванов (Ответственный)',
          email: 'resp@crm.local',
          role: 'responsible',
          created_at: new Date().toISOString()
        },
        {
          id: '3',
          username: 'head',
          password: bcrypt.hashSync('head123', salt),
          fullName: 'Петр Петров (Начальник)',
          email: 'head@crm.local',
          role: 'head',
          created_at: new Date().toISOString()
        },
        {
          id: '4',
          username: 'executor',
          password: bcrypt.hashSync('exec123', salt),
          fullName: 'Сидор Сидоров (Исполнитель)',
          email: 'exec@crm.local',
          role: 'executor',
          created_at: new Date().toISOString()
        }
      ];
      fs.writeFileSync(usersPath, JSON.stringify(users, null, 2), 'utf8');
    }
  }

  async connectMySQLAndMigrate() {
    if (!this.mysqlConfig) throw new Error('No MySQL configuration provided');

    // Create connection pool
    this.pool = mysql.createPool({
      host: this.mysqlConfig.host || 'localhost',
      port: Number(this.mysqlConfig.port) || 3306,
      user: this.mysqlConfig.user,
      password: this.mysqlConfig.password,
      database: this.mysqlConfig.database,
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0
    });

    // Run table creation queries
    const connection = await this.pool.getConnection();
    try {
      // 1. users
      await connection.query(`
        CREATE TABLE IF NOT EXISTS users (
          id VARCHAR(50) PRIMARY KEY,
          username VARCHAR(50) UNIQUE NOT NULL,
          password VARCHAR(255) NOT NULL,
          fullName VARCHAR(100) NOT NULL,
          email VARCHAR(100) NOT NULL,
          role VARCHAR(20) NOT NULL,
          created_at VARCHAR(50) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
      `);

      // 2. tickets
      await connection.query(`
        CREATE TABLE IF NOT EXISTS tickets (
          id INT AUTO_INCREMENT PRIMARY KEY,
          title VARCHAR(255) NOT NULL,
          description TEXT NOT NULL,
          category VARCHAR(100) NOT NULL,
          priority VARCHAR(20) NOT NULL,
          status VARCHAR(20) NOT NULL,
          creator_id VARCHAR(50) NOT NULL,
          assignee_id VARCHAR(50) NULL,
          created_at VARCHAR(50) NOT NULL,
          updated_at VARCHAR(50) NOT NULL,
          deadline VARCHAR(50) NULL,
          custom_fields TEXT NULL,
          attachments TEXT NULL,
          completed_at VARCHAR(50) NULL,
          FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
      `);

      // 3. comments
      await connection.query(`
        CREATE TABLE IF NOT EXISTS comments (
          id INT AUTO_INCREMENT PRIMARY KEY,
          ticket_id INT NOT NULL,
          user_id VARCHAR(50) NOT NULL,
          text TEXT NOT NULL,
          created_at VARCHAR(50) NOT NULL,
          attachments TEXT NULL,
          FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
      `);

      // 4. form_fields
      await connection.query(`
        CREATE TABLE IF NOT EXISTS form_fields (
          id VARCHAR(50) PRIMARY KEY,
          name VARCHAR(50) NOT NULL,
          label VARCHAR(100) NOT NULL,
          type VARCHAR(20) NOT NULL,
          category VARCHAR(100) NOT NULL,
          options TEXT NULL,
          required BOOLEAN DEFAULT FALSE,
          sort_order INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
      `);

      // 5. settings
      await connection.query(`
        CREATE TABLE IF NOT EXISTS settings (
          id VARCHAR(50) PRIMARY KEY,
          val TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
      `);

      // Seed admin into MySQL users if table empty
      const [rows] = await connection.query('SELECT COUNT(*) as count FROM users');
      if (rows[0].count === 0) {
        const salt = bcrypt.genSaltSync(10);
        const usersSeed = [
          ['1', 'admin', bcrypt.hashSync('admin123', salt), 'Администратор Системы', 'admin@crm.local', 'admin', new Date().toISOString()],
          ['2', 'responsible', bcrypt.hashSync('resp123', salt), 'Иван Иванов (Ответственный)', 'resp@crm.local', 'responsible', new Date().toISOString()],
          ['3', 'head', bcrypt.hashSync('head123', salt), 'Петр Петров (Начальник)', 'head@crm.local', 'head', new Date().toISOString()],
          ['4', 'executor', bcrypt.hashSync('exec123', salt), 'Сидор Сидоров (Исполнитель)', 'exec@crm.local', 'executor', new Date().toISOString()]
        ];
        for (const u of usersSeed) {
          await connection.query('INSERT INTO users (id, username, password, fullName, email, role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)', u);
        }
      }

      // Seed settings into MySQL settings if empty
      const [setRows] = await connection.query('SELECT COUNT(*) as count FROM settings WHERE id = "app_config"');
      if (setRows[0].count === 0) {
        const defaultConfig = {
          storageMode: 'MySQL',
          categories: ['IT', 'Plumbing', 'Electrical', 'Hardware'],
          priorities: ['Low', 'Medium', 'High', 'Critical'],
          sla: { Low: 48, Medium: 24, High: 8, Critical: 2 }
        };
        await connection.query('INSERT INTO settings (id, val) VALUES (?, ?)', ['app_config', JSON.stringify(defaultConfig)]);
      }

    } finally {
      connection.release();
    }
  }

  // --- Helper JSON Reader/Writer ---
  readJSON(file) {
    const filepath = path.join(DATA_DIR, file);
    try {
      return JSON.parse(fs.readFileSync(filepath, 'utf8'));
    } catch (e) {
      return [];
    }
  }

  writeJSON(file, data) {
    const filepath = path.join(DATA_DIR, file);
    fs.writeFileSync(filepath, JSON.stringify(data, null, 2), 'utf8');
  }

  // --- General Storage Settings Management ---
  async getSettings() {
    if (this.mode === 'MySQL' && this.pool) {
      try {
        const [rows] = await this.pool.query('SELECT val FROM settings WHERE id = "app_config"');
        if (rows.length > 0) {
          return JSON.parse(rows[0].val);
        }
      } catch (err) {
        console.error('MySQL getSettings error, fallback to JSON:', err);
      }
    }
    const settingsPath = path.join(DATA_DIR, 'settings.json');
    if (fs.existsSync(settingsPath)) {
      return JSON.parse(fs.readFileSync(settingsPath, 'utf8'));
    }
    return {};
  }

  async saveSettings(settings) {
    // Write JSON settings first (so we always have them synchronized as backup / core configuration)
    const settingsPath = path.join(DATA_DIR, 'settings.json');
    fs.writeFileSync(settingsPath, JSON.stringify(settings, null, 2), 'utf8');

    if (this.mode === 'MySQL' && this.pool) {
      try {
        await this.pool.query('INSERT INTO settings (id, val) VALUES ("app_config", ?) ON DUPLICATE KEY UPDATE val = ?', [JSON.stringify(settings), JSON.stringify(settings)]);
      } catch (err) {
        console.error('MySQL saveSettings error:', err);
      }
    }
  }

  async setStorageMode(newMode, mysqlConfig = null) {
    const currentSettings = await this.getSettings();
    currentSettings.storageMode = newMode;
    if (mysqlConfig) {
      currentSettings.mysqlConfig = mysqlConfig;
    }

    if (newMode === 'MySQL') {
      const oldMode = this.mode;
      const oldPool = this.pool;
      try {
        this.mysqlConfig = mysqlConfig || currentSettings.mysqlConfig;
        this.mode = 'MySQL';
        await this.connectMySQLAndMigrate();
        await this.saveSettings(currentSettings);
        console.log('Switched storage mode to MySQL successfully.');
      } catch (err) {
        this.mode = oldMode;
        this.pool = oldPool;
        throw new Error('MySQL Connection failed: ' + err.message);
      }
    } else {
      this.mode = 'JSON';
      if (this.pool) {
        await this.pool.end();
        this.pool = null;
      }
      await this.saveSettings(currentSettings);
      console.log('Switched storage mode to JSON successfully.');
    }
  }

  // --- Users Table Functions ---
  async getUsers() {
    if (this.mode === 'MySQL' && this.pool) {
      const [rows] = await this.pool.query('SELECT * FROM users');
      return rows;
    }
    return this.readJSON('users.json');
  }

  async getUserById(id) {
    if (this.mode === 'MySQL' && this.pool) {
      const [rows] = await this.pool.query('SELECT * FROM users WHERE id = ?', [id]);
      return rows[0] || null;
    }
    const users = this.readJSON('users.json');
    return users.find(u => u.id === String(id)) || null;
  }

  async getUserByUsername(username) {
    if (this.mode === 'MySQL' && this.pool) {
      const [rows] = await this.pool.query('SELECT * FROM users WHERE username = ?', [username]);
      return rows[0] || null;
    }
    const users = this.readJSON('users.json');
    return users.find(u => u.username.toLowerCase() === username.toLowerCase()) || null;
  }

  async createUser(user) {
    if (this.mode === 'MySQL' && this.pool) {
      await this.pool.query(
        'INSERT INTO users (id, username, password, fullName, email, role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [user.id, user.username, user.password, user.fullName, user.email, user.role, user.created_at]
      );
      return user;
    }
    const users = this.readJSON('users.json');
    users.push(user);
    this.writeJSON('users.json', users);
    return user;
  }

  async updateUser(id, data) {
    if (this.mode === 'MySQL' && this.pool) {
      const allowedUserFields = ['username', 'password', 'fullName', 'email', 'role', 'created_at'];
      const fields = [];
      const values = [];
      for (const [key, val] of Object.entries(data)) {
        if (allowedUserFields.includes(key)) {
          fields.push(`\`${key}\` = ?`);
          values.push(val);
        }
      }
      if (fields.length === 0) return this.getUserById(id);
      values.push(id);
      await this.pool.query(`UPDATE users SET ${fields.join(', ')} WHERE id = ?`, values);
      return this.getUserById(id);
    }
    const users = this.readJSON('users.json');
    const idx = users.findIndex(u => u.id === String(id));
    if (idx !== -1) {
      users[idx] = { ...users[idx], ...data };
      this.writeJSON('users.json', users);
      return users[idx];
    }
    return null;
  }

  async deleteUser(id) {
    if (this.mode === 'MySQL' && this.pool) {
      await this.pool.query('DELETE FROM users WHERE id = ?', [id]);
      return true;
    }
    const users = this.readJSON('users.json');
    const filtered = users.filter(u => u.id !== String(id));
    this.writeJSON('users.json', filtered);
    return true;
  }

  // --- Tickets Table Functions ---
  async getTickets() {
    if (this.mode === 'MySQL' && this.pool) {
      const [rows] = await this.pool.query('SELECT * FROM tickets');
      return rows.map(r => ({
        ...r,
        custom_fields: r.custom_fields ? JSON.parse(r.custom_fields) : {},
        attachments: r.attachments ? JSON.parse(r.attachments) : []
      }));
    }
    const tickets = this.readJSON('tickets.json');
    return tickets;
  }

  async getTicketById(id) {
    if (this.mode === 'MySQL' && this.pool) {
      const [rows] = await this.pool.query('SELECT * FROM tickets WHERE id = ?', [id]);
      if (rows.length === 0) return null;
      const r = rows[0];
      return {
        ...r,
        custom_fields: r.custom_fields ? JSON.parse(r.custom_fields) : {},
        attachments: r.attachments ? JSON.parse(r.attachments) : []
      };
    }
    const tickets = this.readJSON('tickets.json');
    return tickets.find(t => t.id === Number(id)) || null;
  }

  async createTicket(ticket) {
    if (this.mode === 'MySQL' && this.pool) {
      const [res] = await this.pool.query(
        `INSERT INTO tickets (title, description, category, priority, status, creator_id, assignee_id, created_at, updated_at, deadline, custom_fields, attachments)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        [
          ticket.title,
          ticket.description,
          ticket.category,
          ticket.priority,
          ticket.status,
          ticket.creator_id,
          ticket.assignee_id || null,
          ticket.created_at,
          ticket.updated_at,
          ticket.deadline || null,
          JSON.stringify(ticket.custom_fields || {}),
          JSON.stringify(ticket.attachments || [])
        ]
      );
      return { ...ticket, id: res.insertId };
    }
    const tickets = this.readJSON('tickets.json');
    const newId = tickets.length > 0 ? Math.max(...tickets.map(t => t.id)) + 1 : 1;
    const newTicket = { ...ticket, id: newId };
    tickets.push(newTicket);
    this.writeJSON('tickets.json', tickets);
    return newTicket;
  }

  async updateTicket(id, data) {
    if (this.mode === 'MySQL' && this.pool) {
      const allowedTicketFields = [
        'title', 'description', 'category', 'priority', 'status',
        'creator_id', 'assignee_id', 'created_at', 'updated_at',
        'deadline', 'custom_fields', 'attachments', 'completed_at'
      ];
      const fields = [];
      const values = [];
      for (const [key, val] of Object.entries(data)) {
        if (allowedTicketFields.includes(key)) {
          fields.push(`\`${key}\` = ?`);
          if (key === 'custom_fields' || key === 'attachments') {
            values.push(JSON.stringify(val));
          } else {
            values.push(val);
          }
        }
      }
      if (fields.length === 0) return this.getTicketById(id);
      values.push(id);
      await this.pool.query(`UPDATE tickets SET ${fields.join(', ')} WHERE id = ?`, values);
      return this.getTicketById(id);
    }
    const tickets = this.readJSON('tickets.json');
    const idx = tickets.findIndex(t => t.id === Number(id));
    if (idx !== -1) {
      tickets[idx] = { ...tickets[idx], ...data };
      this.writeJSON('tickets.json', tickets);
      return tickets[idx];
    }
    return null;
  }

  async deleteTicket(id) {
    if (this.mode === 'MySQL' && this.pool) {
      await this.pool.query('DELETE FROM tickets WHERE id = ?', [id]);
      return true;
    }
    const tickets = this.readJSON('tickets.json');
    const filtered = tickets.filter(t => t.id !== Number(id));
    this.writeJSON('tickets.json', filtered);
    return true;
  }

  // --- Comments Table Functions ---
  async getComments(ticketId = null) {
    if (this.mode === 'MySQL' && this.pool) {
      let query = 'SELECT c.*, u.username, u.fullName AS user_fullName FROM comments c JOIN users u ON c.user_id = u.id';
      const params = [];
      if (ticketId) {
        query += ' WHERE c.ticket_id = ?';
        params.push(ticketId);
      }
      const [rows] = await this.pool.query(query, params);
      return rows.map(r => ({
        ...r,
        attachments: r.attachments ? JSON.parse(r.attachments) : []
      }));
    }
    const comments = this.readJSON('comments.json');
    if (ticketId) {
      return comments.filter(c => c.ticket_id === Number(ticketId));
    }
    return comments;
  }

  async createComment(comment) {
    if (this.mode === 'MySQL' && this.pool) {
      const [res] = await this.pool.query(
        'INSERT INTO comments (ticket_id, user_id, text, created_at, attachments) VALUES (?, ?, ?, ?, ?)',
        [
          comment.ticket_id,
          comment.user_id,
          comment.text,
          comment.created_at,
          JSON.stringify(comment.attachments || [])
        ]
      );
      return { ...comment, id: res.insertId };
    }
    const comments = this.readJSON('comments.json');
    const newId = comments.length > 0 ? Math.max(...comments.map(c => c.id)) + 1 : 1;
    const newComment = { ...comment, id: newId };
    comments.push(newComment);
    this.writeJSON('comments.json', comments);
    return newComment;
  }

  // --- Form Fields Table Functions ---
  async getFormFields() {
    if (this.mode === 'MySQL' && this.pool) {
      const [rows] = await this.pool.query('SELECT * FROM form_fields ORDER BY sort_order ASC');
      return rows.map(r => ({
        ...r,
        required: Boolean(r.required),
        options: r.options ? r.options.split(',') : []
      }));
    }
    const fields = this.readJSON('form_fields.json');
    return fields.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
  }

  async saveFormField(field) {
    if (this.mode === 'MySQL' && this.pool) {
      await this.pool.query(
        `INSERT INTO form_fields (id, name, label, type, category, options, required, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE name=?, label=?, type=?, category=?, options=?, required=?, sort_order=?`,
        [
          field.id, field.name, field.label, field.type, field.category, (field.options || []).join(','), field.required ? 1 : 0, field.sort_order || 0,
          field.name, field.label, field.type, field.category, (field.options || []).join(','), field.required ? 1 : 0, field.sort_order || 0
        ]
      );
      return field;
    }
    const fields = this.readJSON('form_fields.json');
    const idx = fields.findIndex(f => f.id === field.id);
    if (idx !== -1) {
      fields[idx] = field;
    } else {
      fields.push(field);
    }
    this.writeJSON('form_fields.json', fields);
    return field;
  }

  async deleteFormField(id) {
    if (this.mode === 'MySQL' && this.pool) {
      await this.pool.query('DELETE FROM form_fields WHERE id = ?', [id]);
      return true;
    }
    const fields = this.readJSON('form_fields.json');
    const filtered = fields.filter(f => f.id !== id);
    this.writeJSON('form_fields.json', filtered);
    return true;
  }
}

const storageInstance = new Storage();
export default storageInstance;
