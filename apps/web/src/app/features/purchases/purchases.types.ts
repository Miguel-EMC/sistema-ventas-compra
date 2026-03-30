import { AuthUser } from '../../core/auth/auth.types';
import { BusinessPartner } from '../partners/partners.types';
import { Product } from '../products/products.types';

export type PurchaseOrderStatus = 'ordered' | 'received' | 'cancelled';
export type PurchasePaymentMethod = 'cash' | 'transfer' | 'card' | 'check';
export type PurchasePaymentStatus = 'pending' | 'partial' | 'paid' | 'credit';

export interface PurchaseOrderItem {
  id: number;
  product_id: number | null;
  name_snapshot: string;
  sku_snapshot: string | null;
  quantity_ordered: number;
  quantity_received: number;
  returned_quantity: number;
  remaining_returnable_quantity: number;
  unit_cost: number;
  line_total: number;
  notes: string | null;
  product?: Product | null;
}

export interface PurchaseReturnItem {
  id: number;
  purchase_order_item_id: number;
  product_id: number | null;
  name_snapshot: string;
  sku_snapshot: string | null;
  quantity: number;
  unit_cost: number;
  line_total: number;
  reason: string | null;
}

export interface PurchaseReturn {
  id: number;
  public_id: string | null;
  status: string;
  subtotal: number;
  tax_total: number;
  return_total: number;
  reason: string;
  notes: string | null;
  returned_at: string | null;
  returned_by?: AuthUser | null;
  items: PurchaseReturnItem[];
}

export interface PurchaseOrderPaymentUserSummary {
  id: number;
  name: string;
  username: string;
}

export interface PurchaseOrderPaymentCashSessionSummary {
  id: number;
  register_name: string | null;
}

export interface PurchaseOrderPayment {
  id: number;
  public_id: string | null;
  method: PurchasePaymentMethod;
  amount: number;
  reference: string | null;
  notes: string | null;
  paid_at: string | null;
  paid_by?: PurchaseOrderPaymentUserSummary | null;
  cash_session?: PurchaseOrderPaymentCashSessionSummary | null;
}

export interface PurchaseOrder {
  id: number;
  public_id: string | null;
  status: PurchaseOrderStatus;
  reference: string | null;
  subtotal: number;
  tax_total: number;
  grand_total: number;
  paid_total: number;
  net_payable_total: number;
  balance_due: number;
  payment_status: PurchasePaymentStatus;
  notes: string | null;
  cancellation_reason: string | null;
  ordered_at: string | null;
  received_at: string | null;
  cancelled_at: string | null;
  items_count: number;
  returns_count: number;
  payments_count: number;
  returned_total: number;
  supplier?: BusinessPartner | null;
  created_by?: AuthUser | null;
  received_by?: AuthUser | null;
  cancelled_by?: AuthUser | null;
  items: PurchaseOrderItem[];
  payments: PurchaseOrderPayment[];
  returns: PurchaseReturn[];
}

export interface PurchaseOrderPayload {
  supplier_id: number;
  reference: string | null;
  ordered_at: string | null;
  notes: string | null;
  items: Array<{
    product_id: number;
    quantity_ordered: number;
    unit_cost: number;
    notes: string | null;
  }>;
}

export interface ReceivePurchaseOrderPayload {
  received_at: string | null;
  notes: string | null;
}

export interface CancelPurchaseOrderPayload {
  cancellation_reason: string;
}

export interface CreatePurchaseReturnPayload {
  returned_at: string | null;
  reason: string;
  notes: string | null;
  items: Array<{
    purchase_order_item_id: number;
    quantity: number;
    reason: string | null;
  }>;
}

export interface CreatePurchaseOrderPaymentPayload {
  method: PurchasePaymentMethod;
  amount: number;
  reference: string | null;
  notes: string | null;
  paid_at: string | null;
}
