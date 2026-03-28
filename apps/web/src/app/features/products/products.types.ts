export interface ProductCategory {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  is_active: boolean;
}

export interface Product {
  id: number;
  public_id: string | null;
  sku: string | null;
  barcode: string | null;
  name: string;
  description: string | null;
  sale_price: number;
  cost_price: number;
  tax_rate: number;
  unit: string;
  track_stock: boolean;
  minimum_stock: number;
  current_stock: number;
  is_active: boolean;
  category?: ProductCategory | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface ProductCategoryPayload {
  name: string;
  description: string | null;
  is_active: boolean;
}

export interface ProductPayload {
  name: string;
  description: string | null;
  sku: string | null;
  barcode: string | null;
  category_id: number | null;
  sale_price: number;
  cost_price: number;
  tax_rate: number;
  unit: string;
  track_stock: boolean;
  minimum_stock: number;
  is_active: boolean;
  initial_stock?: number;
}

export interface ProductStockAdjustmentPayload {
  quantity: number;
  reason: string | null;
  notes: string | null;
}
