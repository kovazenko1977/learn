import { Module, Global } from '@nestjs/common';
import { JsonDataProviderService } from './json-data-provider.service';
import {
  IProductRepositoryToken,
  IClientRepositoryToken,
  IOrderRepositoryToken,
  IPriceListRepositoryToken,
} from '../common/interfaces/repository.interface';

@Global()
@Module({
  providers: [
    {
      provide: IProductRepositoryToken,
      useValue: new JsonDataProviderService('products'),
    },
    {
      provide: IClientRepositoryToken,
      useValue: new JsonDataProviderService('clients'),
    },
    {
      provide: IOrderRepositoryToken,
      useValue: new JsonDataProviderService('orders'),
    },
    {
      provide: IPriceListRepositoryToken,
      useValue: new JsonDataProviderService('price-lists'),
    },
  ],
  exports: [
    IProductRepositoryToken,
    IClientRepositoryToken,
    IOrderRepositoryToken,
    IPriceListRepositoryToken,
  ],
})
export class DatabaseModule {}
