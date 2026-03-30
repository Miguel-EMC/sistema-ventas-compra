export interface SalesCustomerSummary {
  id: number;
  name: string;
  document_number: string | null;
}

export interface SalesProductSummary {
  id: number;
  name: string;
  unit: string;
  track_stock: boolean;
  current_stock: number;
}

export interface SaleDraftItem {
  id: number;
  product_id: number | null;
  name_snapshot: string;
  unit_price: number;
  quantity: number;
  line_total: number;
  product?: SalesProductSummary | null;
}

export interface SaleDraft {
  id: number;
  public_id: string | null;
  status: string;
  channel: string;
  notes: string | null;
  customer?: SalesCustomerSummary | null;
  cash_session?: {
    id: number;
    status: string;
    opened_at: string | null;
    register_name: string | null;
  } | null;
  items: SaleDraftItem[];
  subtotal: number;
  tax_total: number;
  grand_total: number;
  total_items: number;
  updated_at: string | null;
}

export interface SaleRecord {
  id: number;
  public_id: string | null;
  status: string;
  document_type: string | null;
  subtotal: number;
  tax_total: number;
  grand_total: number;
  paid_total: number;
  net_receivable_total: number;
  balance_due: number;
  payment_status: string;
  change_total: number;
  notes: string | null;
  cancellation_reason?: string | null;
  sold_at: string | null;
  cancelled_at?: string | null;
  items_count: number;
  returns_count: number;
  payments_count: number;
  returned_total: number;
  payment_methods: string[];
  cancelled_by?: {
    id: number;
    name: string;
  } | null;
  customer?: SalesCustomerSummary | null;
  cash_session?: {
    id: number;
    register_name: string | null;
  } | null;
  invoice?: InvoiceRecord | null;
  payments?: SalePaymentRecord[];
}

export interface InvoiceItemRecord {
  id: number;
  description: string;
  sku_snapshot: string | null;
  quantity: number;
  unit_price: number;
  line_subtotal: number;
  line_tax: number;
  line_total: number;
}

export interface InvoiceRecord {
  id: number;
  public_id: string | null;
  status: string;
  sequence_number: number;
  invoice_number: string;
  authorization_number: string | null;
  company_name: string;
  company_tax_id: string | null;
  customer_name: string;
  customer_document_type: string | null;
  customer_document_number: string | null;
  subtotal: number;
  tax_total: number;
  grand_total: number;
  currency_code: string;
  footer: string | null;
  legend: string | null;
  issued_at: string | null;
  tax_resolution?: {
    id: number;
    name: string;
    series: string | null;
    next_invoice_number: number;
    remaining_invoices: number;
  } | null;
  items: InvoiceItemRecord[];
}

export interface SaleItemRecord {
  id: number;
  product_id: number | null;
  name_snapshot: string;
  sku_snapshot: string | null;
  unit_price: number;
  unit_cost: number | null;
  quantity: number;
  returned_quantity: number;
  remaining_quantity: number;
  line_subtotal: number;
  line_tax: number;
  line_total: number;
  product?: SalesProductSummary | null;
}

export interface SaleReturnItemRecord {
  id: number;
  sale_item_id: number;
  product_id: number | null;
  name_snapshot: string;
  sku_snapshot: string | null;
  quantity: number;
  unit_price: number;
  unit_cost: number | null;
  line_subtotal: number;
  line_tax: number;
  line_total: number;
  reason: string | null;
}

export interface CreditNoteItemRecord {
  id: number;
  description: string;
  sku_snapshot: string | null;
  quantity: number;
  unit_price: number;
  line_subtotal: number;
  line_tax: number;
  line_total: number;
  reason: string | null;
}

export interface CreditNoteRecord {
  id: number;
  public_id: string | null;
  sale_return_id: number;
  sale_id: number;
  invoice_id: number;
  status: string;
  sequence_number: number;
  credit_note_number: string;
  invoice_number_reference: string;
  authorization_number: string | null;
  company_name: string;
  company_tax_id: string | null;
  customer_name: string;
  customer_document_type: string | null;
  customer_document_number: string | null;
  reason: string;
  subtotal: number;
  tax_total: number;
  grand_total: number;
  currency_code: string;
  footer: string | null;
  legend: string | null;
  issued_at: string | null;
  issued_by?: {
    id: number;
    name: string;
    username: string;
  } | null;
  tax_resolution?: {
    id: number;
    name: string;
    series: string | null;
    next_invoice_number: number;
    remaining_invoices: number;
  } | null;
  items: CreditNoteItemRecord[];
}

export interface SaleReturnRecord {
  id: number;
  public_id: string | null;
  status: string;
  refund_method: string;
  subtotal: number;
  tax_total: number;
  refund_total: number;
  reason: string;
  notes: string | null;
  returned_at: string | null;
  returned_by?: {
    id: number;
    name: string;
    username: string;
  } | null;
  cash_session?: {
    id: number;
    register_name: string | null;
  } | null;
  credit_note?: CreditNoteRecord | null;
  items: SaleReturnItemRecord[];
}

export interface SalePaymentRecord {
  id: number;
  public_id: string | null;
  method: string;
  amount: number;
  reference: string | null;
  notes: string | null;
  paid_at: string | null;
  paid_by?: {
    id: number;
    name: string;
    username: string;
  } | null;
  cash_session?: {
    id: number;
    register_name: string | null;
  } | null;
}

export interface SaleDetail extends SaleRecord {
  payments: SalePaymentRecord[];
  items: SaleItemRecord[];
  returns: SaleReturnRecord[];
}

export interface UpdateSaleDraftPayload {
  customer_id: number | null;
  notes: string | null;
}

export interface SaleDraftItemPayload {
  product_id: number;
  quantity: number;
}

export interface UpdateSaleDraftItemPayload {
  quantity: number;
}

export interface CheckoutSalePayload {
  payment_method: string;
  amount_paid: number;
  reference: string | null;
  document_type: string | null;
  notes: string | null;
}

export interface CancelSalePayload {
  cancellation_reason: string;
}

export interface CreateSalePaymentPayload {
  method: string;
  amount: number;
  reference: string | null;
  notes: string | null;
}

export interface CreateSaleReturnPayload {
  refund_method: string;
  reason: string;
  notes: string | null;
  items: Array<{
    sale_item_id: number;
    quantity: number;
    reason: string | null;
  }>;
}
