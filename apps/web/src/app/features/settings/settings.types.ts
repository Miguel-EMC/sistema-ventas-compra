export interface CompanyProfileMetadata {
  billing_owner_name?: string | null;
  billing_address_reference?: string | null;
  [key: string]: unknown;
}

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
  metadata: CompanyProfileMetadata | null;
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

export interface TaxResolution {
  id: number;
  name: string;
  authorization_number: string;
  series: string | null;
  invoice_number_start: number;
  invoice_number_end: number;
  next_invoice_number: number;
  remaining_invoices: number;
  starts_at: string | null;
  ends_at: string | null;
  technical_key: string | null;
  legend: string | null;
  is_active: boolean;
  created_at: string | null;
  updated_at: string | null;
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
  active_tax_resolution: TaxResolution | null;
  tax_resolutions: TaxResolution[];
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
    metadata: CompanyProfileMetadata | null;
  };
  system_settings: BusinessSystemSettings;
  active_tax_resolution: {
    id?: number;
    name: string;
    authorization_number: string;
    series: string | null;
    invoice_number_start: number;
    invoice_number_end: number;
    next_invoice_number: number;
    starts_at: string;
    ends_at: string | null;
    technical_key: string | null;
    legend: string | null;
  } | null;
}
