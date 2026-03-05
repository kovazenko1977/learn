import { Controller, Post, Body, Param } from '@nestjs/common';
import { DocumentsService } from './documents.service';

@Controller('documents')
export class DocumentsController {
  constructor(private readonly documentsService: DocumentsService) {}

  @Post('invoice/:orderId')
  generateInvoice(@Param('orderId') orderId: string) {
    return this.documentsService.generateInvoice(orderId);
  }

  @Post('certificate/:productId')
  generateCertificate(@Param('productId') productId: string) {
    return this.documentsService.generateCertificate(productId);
  }
}
