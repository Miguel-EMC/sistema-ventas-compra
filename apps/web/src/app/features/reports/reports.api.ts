import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { API_BASE_URL } from '../../core/config/api.config';
import { ApiResourceResponse } from '../../core/http/api.types';
import { ReportsOverview, ReportsOverviewFilters } from './reports.types';

@Injectable({ providedIn: 'root' })
export class ReportsApiService {
  private readonly http = inject(HttpClient);

  async getOverview(filters: ReportsOverviewFilters = {}): Promise<ReportsOverview> {
    const response = await firstValueFrom(
      this.http.get<ApiResourceResponse<ReportsOverview>>(this.buildReportsUrl('overview', filters)),
    );

    return response.data;
  }

  async downloadReceivablesPdf(filters: ReportsOverviewFilters = {}): Promise<Blob> {
    return await firstValueFrom(
      this.http.get(this.buildReportsUrl('receivables/pdf', filters), {
        responseType: 'blob',
      }),
    );
  }

  async downloadProfitabilityPdf(filters: ReportsOverviewFilters = {}): Promise<Blob> {
    return await firstValueFrom(
      this.http.get(this.buildReportsUrl('profitability/pdf', filters), {
        responseType: 'blob',
      }),
    );
  }

  async downloadSalesPdf(filters: ReportsOverviewFilters = {}): Promise<Blob> {
    return await firstValueFrom(
      this.http.get(this.buildReportsUrl('sales/pdf', filters), {
        responseType: 'blob',
      }),
    );
  }

  async downloadSalesCsv(filters: ReportsOverviewFilters = {}): Promise<Blob> {
    return await firstValueFrom(
      this.http.get(this.buildReportsUrl('sales/csv', filters), {
        responseType: 'blob',
      }),
    );
  }

  async downloadProductsCsv(filters: ReportsOverviewFilters = {}): Promise<Blob> {
    return await firstValueFrom(
      this.http.get(this.buildReportsUrl('products/csv', filters), {
        responseType: 'blob',
      }),
    );
  }

  private buildReportsUrl(path: string, filters: ReportsOverviewFilters): string {
    const params = new URLSearchParams();

    if (filters.date_from?.trim()) {
      params.set('date_from', filters.date_from.trim());
    }

    if (filters.date_to?.trim()) {
      params.set('date_to', filters.date_to.trim());
    }

    if (filters.customer_id) {
      params.set('customer_id', String(filters.customer_id));
    }

    const queryString = params.toString();
    const baseUrl = `${API_BASE_URL}/reports/${path}`;

    return queryString === '' ? baseUrl : `${baseUrl}?${queryString}`;
  }
}
