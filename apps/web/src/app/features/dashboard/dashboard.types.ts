export interface DashboardSummaryMetrics {
  sales_today_total: number;
  sales_today_count: number;
  sales_month_total: number;
  sales_month_count: number;
  open_cash_sessions: number;
  low_stock_products: number;
  active_customers: number;
}

export interface DashboardRecentSale {
  id: number;
  public_id: string | null;
  customer_name: string | null;
  register_name: string | null;
  grand_total: number;
  items_count: number;
  payment_methods: string[];
  sold_at: string | null;
}

export interface DashboardLowStockProduct {
  id: number;
  name: string;
  current_stock: number;
  minimum_stock: number;
  unit: string;
}

export interface DashboardSummary {
  summary: DashboardSummaryMetrics;
  recent_sales: DashboardRecentSale[];
  low_stock_products: DashboardLowStockProduct[];
}
