export interface ReportsRange {
  date_from: string;
  date_to: string;
  customer_id: number | null;
}

export interface ReportsSummary {
  sales_total: number;
  sales_count: number;
  average_ticket: number;
  cash_sales_total: number;
  customers_total: number;
  open_cash_sessions: number;
  low_stock_products: number;
  receivables_total: number;
  receivables_sales_count: number;
  customers_with_receivables: number;
}

export interface ReportsProfitability {
  refund_total: number;
  net_sales_total: number;
  cost_total: number;
  gross_margin_total: number;
  operational_income_total: number;
  operational_expenses_total: number;
  net_utility_total: number;
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

export interface ReportSalesByMonth {
  month: string;
  sales_count: number;
  total: number;
}

export interface ReportSalesDocument {
  id: number;
  public_id: string | null;
  customer_name: string;
  document_type: string | null;
  invoice_number: string | null;
  document_reference: string;
  items_count: number;
  quantity_total: number;
  payment_methods: string[];
  grand_total: number;
  paid_total: number;
  returned_total: number;
  net_total: number;
  balance_due: number;
  payment_status: string;
  sold_at: string | null;
}

export interface ReportTopProduct {
  name: string;
  quantity: number;
  total: number;
}

export interface ReportProductSale {
  name: string;
  sku: string | null;
  sales_count: number;
  quantity: number;
  average_unit_price: number;
  total: number;
  last_sold_at: string | null;
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

export interface ReportReceivableCustomer {
  customer_id: number | null;
  name: string;
  document_number: string | null;
  sales_count: number;
  net_receivable_total: number;
  paid_total: number;
  balance_due: number;
  last_sale_at: string | null;
}

export interface ReportReceivableSale {
  id: number;
  public_id: string | null;
  customer_id: number | null;
  customer_name: string;
  customer_document_number: string | null;
  document_type: string | null;
  invoice_number: string | null;
  items_count: number;
  payment_methods: string[];
  grand_total: number;
  paid_total: number;
  returned_total: number;
  net_receivable_total: number;
  balance_due: number;
  payment_status: string;
  sold_at: string | null;
}

export interface ReportsReceivables {
  customer: {
    id: number;
    name: string;
    document_number: string | null;
  } | null;
  balance_due_total: number;
  sales_count: number;
  customers_count: number;
  customers: ReportReceivableCustomer[];
  sales: ReportReceivableSale[];
}

export interface ReportExpenseCategory {
  category: string;
  count: number;
  total: number;
}

export interface ReportOperationalMovement {
  id: number;
  type: string;
  category: string;
  amount: number;
  notes: string | null;
  user_name: string | null;
  register_name: string | null;
  occurred_at: string | null;
}

export interface ReportsOverview {
  range: ReportsRange;
  summary: ReportsSummary;
  profitability: ReportsProfitability;
  payment_methods: ReportPaymentMethod[];
  sales_by_day: ReportSalesByDay[];
  sales_by_month: ReportSalesByMonth[];
  sales_last_six_months: ReportSalesByMonth[];
  sales_documents: ReportSalesDocument[];
  top_products: ReportTopProduct[];
  product_sales: ReportProductSale[];
  cash_sessions: ReportCashSession[];
  receivables: ReportsReceivables;
  expense_categories: ReportExpenseCategory[];
  operational_movements: ReportOperationalMovement[];
}

export interface ReportsOverviewFilters {
  date_from?: string | null;
  date_to?: string | null;
  customer_id?: number | null;
}
