import { Injectable, OnModuleInit, OnModuleDestroy } from '@nestjs/common';
import * as fs from 'fs/promises';
import { constants } from 'fs';
import * as path from 'path';
import { IBaseRepository } from '../common/interfaces/repository.interface';

@Injectable()
export class JsonDataProviderService<T extends { id: string }> implements IBaseRepository<T> {
  private readonly dataPath: string;

  constructor(private readonly collectionName: string) {
    this.dataPath = path.join(process.cwd(), 'data', `${this.collectionName}.json`);
  }

  private async ensureDirectoryExistence() {
    const dir = path.dirname(this.dataPath);
    try {
      await fs.access(dir);
    } catch {
      await fs.mkdir(dir, { recursive: true });
    }
    try {
      await fs.access(this.dataPath);
    } catch {
      await fs.writeFile(this.dataPath, JSON.stringify([]));
    }
  }

  private async readData(): Promise<T[]> {
    await this.ensureDirectoryExistence();
    const rawData = await fs.readFile(this.dataPath, 'utf8');
    return JSON.parse(rawData);
  }

  private async writeData(data: T[]): Promise<void> {
    await this.ensureDirectoryExistence();
    await fs.writeFile(this.dataPath, JSON.stringify(data, null, 2));
  }

  async findAll(): Promise<T[]> {
    return this.readData();
  }

  async findOne(id: string): Promise<T | null> {
    const data = await this.readData();
    return data.find((item) => item.id === id) || null;
  }

  async create(item: Partial<T>): Promise<T> {
    const data = await this.readData();
    const newItem = {
      ...item,
      id: Math.random().toString(36).substr(2, 9) + Date.now().toString(36)
    } as T;
    data.push(newItem);
    await this.writeData(data);
    return newItem;
  }

  async update(id: string, item: Partial<T>): Promise<T | null> {
    const data = await this.readData();
    const index = data.findIndex((i) => i.id === id);
    if (index === -1) return null;
    data[index] = { ...data[index], ...item };
    await this.writeData(data);
    return data[index];
  }

  async delete(id: string): Promise<boolean> {
    const data = await this.readData();
    const initialLength = data.length;
    const filteredData = data.filter((i) => i.id !== id);
    if (filteredData.length === initialLength) return false;
    await this.writeData(filteredData);
    return true;
  }
}
