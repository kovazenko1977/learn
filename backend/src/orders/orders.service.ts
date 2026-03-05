import { Injectable, Inject } from '@nestjs/common';
import { CreateOrderDto } from './dto/create-order.dto';
import { UpdateOrderDto } from './dto/update-order.dto';
import { type IOrderRepository, IOrderRepositoryToken } from '../common/interfaces/repository.interface';

@Injectable()
export class OrdersService {
  constructor(
    @Inject(IOrderRepositoryToken)
    private readonly orderRepository: IOrderRepository,
  ) {}

  create(createOrderDto: CreateOrderDto) {
    return this.orderRepository.create({
      ...createOrderDto,
      status: 'new',
      createdAt: new Date().toISOString(),
    } as any);
  }

  findAll() {
    return this.orderRepository.findAll();
  }

  findOne(id: string) {
    return this.orderRepository.findOne(id);
  }

  update(id: string, updateOrderDto: UpdateOrderDto) {
    return this.orderRepository.update(id, updateOrderDto);
  }

  remove(id: string) {
    return this.orderRepository.delete(id);
  }
}
