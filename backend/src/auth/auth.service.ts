import { Injectable } from '@nestjs/common';
import { JwtService } from '@nestjs/jwt';

@Injectable()
export class AuthService {
  constructor(private readonly jwtService: JwtService) {}

  async validateUser(username: string, pass: string): Promise<any> {
    // In a real app, you would verify the password against a hashed value in the database
    if (username === 'admin' && pass === 'admin123') {
      return { id: '1', username: 'admin', role: 'admin' };
    }
    if (username === 'client' && pass === 'client123') {
      return { id: '2', username: 'client', role: 'client' };
    }
    return null;
  }

  async login(user: any) {
    const payload = { username: user.username, sub: user.id, role: user.role };
    return {
      access_token: this.jwtService.sign(payload),
      user: {
        id: user.id,
        username: user.username,
        role: user.role
      }
    };
  }
}
