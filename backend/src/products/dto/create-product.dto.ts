import { IsString, IsNumber, IsOptional } from 'class-validator';

export class CreateProductDto {
  @IsString()
  name: string;

  @IsString()
  sku: string;

  @IsString()
  batch: string;

  @IsNumber()
  quantity: number;

  @IsString()
  expirationDate: string;

  @IsNumber()
  price: number;
}
