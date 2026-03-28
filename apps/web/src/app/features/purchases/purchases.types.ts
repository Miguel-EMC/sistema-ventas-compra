import { AuthUser } from '../../core/auth/auth.types';
import { BusinessPartner } from '../partners/partners.types';
import { Product } from '../products/products.types';

export type PurchaseOrderStatus = 'ordered' | 'received' | 'cancelled';

export interface PurchaseOrderItem {
  id: number;
  product_id: number | null;
  name_snapshot: string;
  sku_snapshot: string | null;
  quantity_ordered: number;
  quantity_received: number;
  unit_cost: number;
  line_total: number;
  notes: string | null;
  product?: Product | null;
}

export interface PurchaseOrder {
  id: number;
  public_id: string | null;
  status: PurchaseOrderStatus;
  reference: string | null;
  subtotal: number;
  tax_total: number;
  grand_total: number;
  notes: string | null;
  ordered_at: string | null;
  received_at: string | null;
  items_count: number;
  supplier?: BusinessPartner | null;
  created_by?: AuthUser | null;
  received_by?: AuthUser | null;
  items: PurchaseOrderItem[];
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
