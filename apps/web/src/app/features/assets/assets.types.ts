export interface AssetCategory {
  id: number;
  name: string;
  slug: string;
  description: string | null;
}

export interface Asset {
  id: number;
  public_id: string | null;
  code: string | null;
  name: string;
  description: string | null;
  quantity: number;
  acquisition_cost: number | null;
  acquired_at: string | null;
  status: string;
  category?: AssetCategory | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface AssetCategoryPayload {
  name: string;
  description: string | null;
}

export interface AssetPayload {
  code: string | null;
  category_id: number | null;
  name: string;
  description: string | null;
  quantity: number;
  acquisition_cost: number | null;
  acquired_at: string | null;
  status: string;
}
