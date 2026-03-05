import { Injectable, Inject } from '@nestjs/common';
import { CreatePriceListDto } from './dto/create-price-list.dto';
import { UpdatePriceListDto } from './dto/update-price-list.dto';
import { type IPriceListRepository, IPriceListRepositoryToken, type IProductRepository, IProductRepositoryToken } from '../common/interfaces/repository.interface';

@Injectable()
export class PriceListsService {
  constructor(
    @Inject(IPriceListRepositoryToken)
    private readonly priceListRepository: IPriceListRepository,
    @Inject(IProductRepositoryToken)
    private readonly productRepository: IProductRepository,
  ) {}

  create(createPriceListDto: CreatePriceListDto) {
    return this.priceListRepository.create(createPriceListDto);
  }

  findAll() {
    return this.priceListRepository.findAll();
  }

  findOne(id: string) {
    return this.priceListRepository.findOne(id);
  }

  async generate(id: string) {
    const priceList = await this.priceListRepository.findOne(id);
    if (!priceList) return null;

    const products = await this.productRepository.findAll();
    const items = products.map(p => ({
      name: p.name,
      sku: p.sku,
      price: p.price * (1 - (priceList.discount || 0) / 100)
    }));

    return {
      title: priceList.name,
      items
    };
  }

  update(id: string, updatePriceListDto: UpdatePriceListDto) {
    return this.priceListRepository.update(id, updatePriceListDto);
  }

  remove(id: string) {
    return this.priceListRepository.delete(id);
  }
}
