export interface ReportsRange {
  date_from: string;
  date_to: string;
}

export interface ReportsSummary {
  sales_total: number;
  sales_count: number;
  average_ticket: number;
  cash_sales_total: number;
  customers_total: number;
  open_cash_sessions: number;
  low_stock_products: number;
}

export interface ReportPaymentMethod {
  method: string;
  count: number;
  total: number;
}

export interface ReportSalesByDay {
  day: string;
  sales_count: number;
  total: number;
}

export interface ReportTopProduct {
  name: string;
  quantity: number;
  total: number;
}

export interface ReportCashSession {
  id: number;
  status: string;
  register_name: string | null;
  opened_by_name: string | null;
  opened_at: string | null;
  closed_at: string | null;
  sales_count: number;
  sales_total: number;
  cash_balance: number;
}

export interface ReportsOverview {
  range: ReportsRange;
  summary: ReportsSummary;
  payment_methods: ReportPaymentMethod[];
  sales_by_day: ReportSalesByDay[];
  top_products: ReportTopProduct[];
  cash_sessions: ReportCashSession[];
}

export interface ReportsOverviewFilters {
  date_from?: string | null;
  date_to?: string | null;
}
