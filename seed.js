const bcrypt = require('bcryptjs');
const fs = require('fs').promises;
const path = require('path');

async function seed() {
    const dataDir = path.join(__dirname, 'data');

    const hashedPassword = await bcrypt.hash('admin', 10);

    const departments = [
        { id: 1, name: 'Хозяйственный', description: 'Общее обслуживание', manager_id: 1 },
        { id: 2, name: 'IT', description: 'Информационные технологии', manager_id: null }
    ];

    const workTypes = [
        { id: 1, name: 'Мебель', description: 'Ремонт и перемещение', department_id: 1 },
        { id: 2, name: 'Оргтехника', description: 'Ремонт и обслуживание', department_id: 2 }
    ];

    const users = [
        {
            id: 1,
            login: 'admin',
            password_hash: hashedPassword,
            full_name: 'Системный Администратор',
            role: 'admin',
            department_id: null,
            is_active: 1,
            created_at: new Date().toISOString()
        }
    ];

    await fs.writeFile(path.join(dataDir, 'departments.json'), JSON.stringify(departments, null, 2));
    await fs.writeFile(path.join(dataDir, 'work_types.json'), JSON.stringify(workTypes, null, 2));
    await fs.writeFile(path.join(dataDir, 'users.json'), JSON.stringify(users, null, 2));
    await fs.writeFile(path.join(dataDir, 'requests.json'), JSON.stringify([], null, 2));
    await fs.writeFile(path.join(dataDir, 'status_history.json'), JSON.stringify([], null, 2));

    console.log('Seeding complete.');
}

seed().catch(err => console.error(err));
