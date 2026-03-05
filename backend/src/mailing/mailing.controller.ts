import { Controller, Post, Body } from '@nestjs/common';
import { MailingService } from './mailing.service';

@Controller('mailing')
export class MailingController {
  constructor(private readonly mailingService: MailingService) {}

  @Post('send-email')
  sendEmail(@Body() body: { to: string; subject: string; content: string }) {
    return this.mailingService.sendEmail(body.to, body.subject, body.content);
  }

  @Post('send-price-list')
  sendPriceList(@Body() body: { clientId: string; priceListId: string }) {
    return this.mailingService.sendPriceList(body.clientId, body.priceListId);
  }
}
