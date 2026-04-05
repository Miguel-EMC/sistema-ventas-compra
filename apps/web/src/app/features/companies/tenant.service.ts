import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { delay, firstValueFrom, of } from 'rxjs';
import { API_BASE_URL } from '../../core/config/api.config';
import { ApiCollectionResponse, ApiResourceResponse } from '../../core/http/api.types';
import {
  CreateTenantPayload,
  TenantCompanyRecord,
  TenantCreateResult,
  TenantProvisioning,
} from './tenant.types';

const MOCK_COMPANIES: TenantCompanyRecord[] = [
  {
    id: 'cmp_1001',
    name: 'Nova Retail Group',
    domain: 'nova-retail',
    plan: 'enterprise',
    status: 'active',
    registered_at: '2026-03-19T14:12:00.000Z',
    admin_email: 'ops@novaretail.com',
    admin_username: 'nova-retail-admin',
  },
  {
    id: 'cmp_1002',
    name: 'Casa Central Foods',
    domain: 'casa-central',
    plan: 'pro',
    status: 'active',
    registered_at: '2026-03-28T10:35:00.000Z',
    admin_email: 'admin@casacentral.ec',
    admin_username: 'casa-central-admin',
  },
  {
    id: 'cmp_1003',
    name: 'Orbita Atelier',
    domain: 'orbita-atelier',
    plan: 'basic',
    status: 'pending',
    registered_at: '2026-04-02T17:08:00.000Z',
    admin_email: 'founder@orbitaatelier.com',
    admin_username: 'orbita-atelier-admin',
  },
];

@Injectable({ providedIn: 'root' })
export class TenantService {
  private readonly http = inject(HttpClient);
  private readonly endpoint = `${API_BASE_URL}/companies`;

  private mockCompanies = [...MOCK_COMPANIES];

  async listCompanies(): Promise<TenantCompanyRecord[]> {
    try {
      const response = await firstValueFrom(
        this.http.get<ApiCollectionResponse<TenantCompanyRecord>>(this.endpoint),
      );

      this.mockCompanies = [...response.data];

      return response.data;
    } catch (error) {
      if (!this.shouldUseMock(error)) {
        throw error;
      }

      return this.simulate(this.mockCompanies.map((company) => ({ ...company })), 260);
    }
  }

  async createCompany(payload: CreateTenantPayload): Promise<TenantCreateResult> {
    const normalizedPayload = {
      name: payload.name.trim(),
      domain: payload.domain.trim().toLowerCase(),
      plan: payload.plan,
      admin_email: payload.admin_email.trim().toLowerCase(),
    };

    try {
      const response = await firstValueFrom(
        this.http.post<ApiResourceResponse<TenantCreateResult>>(this.endpoint, normalizedPayload),
      );

      this.upsertMockCompany(response.data.company);

      return response.data;
    } catch (error) {
      if (!this.shouldUseMock(error)) {
        throw error;
      }

      const company: TenantCompanyRecord = {
        id: `cmp_${Date.now()}`,
        name: normalizedPayload.name,
        domain: normalizedPayload.domain,
        plan: normalizedPayload.plan,
        status: 'pending',
        registered_at: new Date().toISOString(),
        admin_email: normalizedPayload.admin_email,
        admin_username: `${normalizedPayload.domain}-admin`,
      };
      const provisioning: TenantProvisioning = {
        admin_email: normalizedPayload.admin_email,
        admin_username: company.admin_username ?? `${normalizedPayload.domain}-admin`,
        temporary_password: this.generateTemporaryPassword(),
      };

      this.upsertMockCompany(company);

      return this.simulate(
        {
          company: { ...company },
          provisioning,
        },
        480,
      );
    }
  }

  private upsertMockCompany(company: TenantCompanyRecord): void {
    this.mockCompanies = [
      company,
      ...this.mockCompanies.filter((existingCompany) => existingCompany.id !== company.id),
    ];
  }

  private shouldUseMock(error: unknown): boolean {
    return (
      error instanceof HttpErrorResponse &&
      (error.status === 0 || error.status === 404 || error.status === 405 || error.status === 501)
    );
  }

  private async simulate<T>(value: T, latencyMs: number): Promise<T> {
    return firstValueFrom(of(value).pipe(delay(latencyMs)));
  }

  private generateTemporaryPassword(): string {
    return `Tmp${Math.random().toString(36).slice(-10)}A1`;
  }
}
