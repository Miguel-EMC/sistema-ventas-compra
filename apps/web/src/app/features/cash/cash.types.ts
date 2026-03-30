export interface CashRegister {
  id: number;
  public_id: string | null;
  name: string;
  code: string | null;
  location: string | null;
  is_active: boolean;
}

export interface CashSessionUserSummary {
  id: number;
  name: string;
}

export interface CashSession {
  id: number;
  public_id: string | null;
  status: string;
  opening_amount: number;
  closing_amount: number | null;
  notes: string | null;
  opened_at: string | null;
  closed_at: string | null;
  sales_total: number;
  sales_count: number;
  cash_income_total: number;
  cash_out_total: number;
  cash_balance: number;
  register?: CashRegister | null;
  opened_by?: CashSessionUserSummary | null;
  closed_by?: CashSessionUserSummary | null;
}

export type CashMovementType = 'income' | 'expense';

export interface CashMovementUserSummary {
  id: number;
  name: string;
  username: string;
}

export interface CashMovementSessionSummary {
  id: number;
  status: string;
  register_name: string | null;
  opened_at: string | null;
}

export interface CashMovement {
  id: number;
  public_id: string | null;
  type: CashMovementType;
  category: string;
  amount: number;
  notes: string | null;
  occurred_at: string | null;
  can_manage: boolean;
  cash_session?: CashMovementSessionSummary | null;
  user?: CashMovementUserSummary | null;
}

export interface OpenCashSessionPayload {
  cash_register_id: number;
  opening_amount: number;
  notes: string | null;
}

export interface CloseCashSessionPayload {
  closing_amount: number;
  notes: string | null;
}

export interface CashMovementPayload {
  type: CashMovementType;
  category: string;
  amount: number;
  notes: string | null;
  occurred_at: string | null;
}
