export interface Role {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  is_system: boolean;
}

export interface AuthUser {
  id: number;
  public_id: string | null;
  name: string;
  username: string;
  display_name: string | null;
  email: string | null;
  is_active: boolean;
  last_login_at: string | null;
  created_at: string | null;
  updated_at: string | null;
  role?: Role | null;
}

export interface LoginResponse {
  data: {
    user: AuthUser;
  };
  meta: {
    token: string;
    token_type: string;
  };
}

export interface MeResponse {
  data: AuthUser;
}
