export interface BusinessPartner {
  id: number;
  public_id: string | null;
  document_type: string | null;
  document_number: string | null;
  name: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  is_active: boolean;
  created_at: string | null;
  updated_at: string | null;
}

export interface BusinessPartnerPayload {
  document_type: string | null;
  document_number: string | null;
  name: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  is_active: boolean;
}

export interface DocumentTypeOption {
  value: string;
  label: string;
}

export const DOCUMENT_TYPE_OPTIONS: DocumentTypeOption[] = [
  { value: 'cedula', label: 'Cedula' },
  { value: 'ruc', label: 'RUC' },
  { value: 'pasaporte', label: 'Pasaporte' },
  { value: 'otro', label: 'Otro' },
];
