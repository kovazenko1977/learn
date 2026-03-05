export interface IBaseRepository<T> {
  findAll(): Promise<T[]>;
  findOne(id: string): Promise<T | null>;
  create(data: Partial<T>): Promise<T>;
  update(id: string, data: Partial<T>): Promise<T | null>;
  delete(id: string): Promise<boolean>;
}

export const IProductRepositoryToken = 'IProductRepository';
export interface IProductRepository extends IBaseRepository<any> {}

export const IClientRepositoryToken = 'IClientRepository';
export interface IClientRepository extends IBaseRepository<any> {}

export const IOrderRepositoryToken = 'IOrderRepository';
export interface IOrderRepository extends IBaseRepository<any> {}

export const IPriceListRepositoryToken = 'IPriceListRepository';
export interface IPriceListRepository extends IBaseRepository<any> {}
