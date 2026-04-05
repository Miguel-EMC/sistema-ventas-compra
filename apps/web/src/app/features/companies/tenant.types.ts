export type TenantPlan = 'basic' | 'pro' | 'enterprise';

export type TenantStatus = 'active' | 'pending' | 'suspended';

export interface TenantCompanyRecord {
  id: string;
  name: string;
  domain: string;
  plan: TenantPlan;
  status: TenantStatus;
  registered_at: string;
  admin_email: string | null;
  admin_username: string | null;
}

export interface CreateTenantPayload {
  name: string;
  domain: string;
  plan: TenantPlan;
  admin_email: string;
}

export interface TenantProvisioning {
  admin_email: string;
  admin_username: string;
  temporary_password: string;
}

export interface TenantCreateResult {
  company: TenantCompanyRecord;
  provisioning: TenantProvisioning;
}

export const TENANT_PLAN_OPTIONS: Array<{ value: TenantPlan; label: string }> = [
  { value: 'basic', label: 'Basico' },
  { value: 'pro', label: 'Pro' },
  { value: 'enterprise', label: 'Enterprise' },
];
