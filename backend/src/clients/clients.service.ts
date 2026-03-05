import { Injectable, Inject } from '@nestjs/common';
import { CreateClientDto } from './dto/create-client.dto';
import { UpdateClientDto } from './dto/update-client.dto';
import { type IClientRepository, IClientRepositoryToken } from '../common/interfaces/repository.interface';

@Injectable()
export class ClientsService {
  constructor(
    @Inject(IClientRepositoryToken)
    private readonly clientRepository: IClientRepository,
  ) {}

  create(createClientDto: CreateClientDto) {
    return this.clientRepository.create(createClientDto);
  }

  findAll() {
    return this.clientRepository.findAll();
  }

  findOne(id: string) {
    return this.clientRepository.findOne(id);
  }

  update(id: string, updateClientDto: UpdateClientDto) {
    return this.clientRepository.update(id, updateClientDto);
  }

  remove(id: string) {
    return this.clientRepository.delete(id);
  }
}
