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
  change_total: number;
  notes: string | null;
  sold_at: string | null;
  items_count: number;
  payment_methods: string[];
  customer?: SalesCustomerSummary | null;
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
