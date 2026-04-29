const fs = require('fs').promises;
const path = require('path');
const { v4: uuidv4 } = require('uuid');

class JsonStorage {
    constructor(dataDir) {
        this.dataDir = dataDir;
    }

    async readCollection(collection) {
        const filePath = path.join(this.dataDir, `${collection}.json`);
        try {
            const data = await fs.readFile(filePath, 'utf8');
            return JSON.parse(data);
        } catch (error) {
            if (error.code === 'ENOENT') return [];
            throw error;
        }
    }

    async writeCollection(collection, data) {
        const filePath = path.join(this.dataDir, `${collection}.json`);
        await fs.writeFile(filePath, JSON.stringify(data, null, 2), 'utf8');
    }

    async find(collection, query) {
        const items = await this.readCollection(collection);
        return items.filter(item => {
            return Object.keys(query).every(key => item[key] === query[key]);
        });
    }

    async findOne(collection, query) {
        const items = await this.readCollection(collection);
        return items.find(item => {
            return Object.keys(query).every(key => item[key] === query[key]);
        });
    }

    async insert(collection, item) {
        const items = await this.readCollection(collection);
        const newItem = { id: Date.now(), ...item };
        items.push(newItem);
        await this.writeCollection(collection, items);
        return newItem;
    }

    async update(collection, id, updates) {
        const items = await this.readCollection(collection);
        const index = items.findIndex(item => item.id === id);
        if (index === -1) return null;
        items[index] = { ...items[index], ...updates };
        await this.writeCollection(collection, items);
        return items[index];
    }

    async delete(collection, id) {
        const items = await this.readCollection(collection);
        const newItems = items.filter(item => item.id !== id);
        await this.writeCollection(collection, newItems);
    }
}

module.exports = JsonStorage;
