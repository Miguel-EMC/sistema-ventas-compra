export interface CompanyProfile {
  id: number;
  legal_name: string;
  trade_name: string | null;
  tax_id: string | null;
  email: string | null;
  phone: string | null;
  website: string | null;
  address_line: string | null;
  city: string | null;
  region: string | null;
  country_code: string | null;
  is_primary: boolean;
  metadata: Record<string, unknown> | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface Currency {
  id: number;
  name: string;
  code: string;
  symbol: string;
  decimals: number;
  is_default: boolean;
}

export interface Locale {
  id: number;
  name: string;
  code: string;
  is_default: boolean;
}

export interface BusinessSystemSettings {
  currency_code: string;
  locale_code: string;
  timezone: string;
  tax_included_prices: boolean;
  allow_negative_stock: boolean;
  default_document_type: 'ticket' | 'factura' | 'nota';
  invoice_footer: string | null;
}

export interface BusinessSettings {
  company_profile: CompanyProfile;
  currency: Currency | null;
  locale: Locale | null;
  currencies: Currency[];
  locales: Locale[];
  system_settings: BusinessSystemSettings;
}

export interface UpdateBusinessSettingsPayload {
  company_profile: {
    legal_name: string;
    trade_name: string | null;
    tax_id: string | null;
    email: string | null;
    phone: string | null;
    website: string | null;
    address_line: string | null;
    city: string | null;
    region: string | null;
    country_code: string | null;
  };
  system_settings: BusinessSystemSettings;
}
