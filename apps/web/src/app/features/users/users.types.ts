import { AuthUser, Role } from '../../core/auth/auth.types';

export type UserRecord = AuthUser;
export type UserRole = Role;

export interface SaveUserPayload {
  name: string;
  username: string;
  display_name: string | null;
  email: string | null;
  role_id: number;
  is_active: boolean;
  password?: string;
  password_confirmation?: string;
}
