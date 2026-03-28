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
    const params = new URLSearchParams();

    if (filters.date_from?.trim()) {
      params.set('date_from', filters.date_from.trim());
    }

    if (filters.date_to?.trim()) {
      params.set('date_to', filters.date_to.trim());
    }

    const queryString = params.toString();
    const url =
      queryString === ''
        ? `${API_BASE_URL}/reports/overview`
        : `${API_BASE_URL}/reports/overview?${queryString}`;

    const response = await firstValueFrom(
      this.http.get<ApiResourceResponse<ReportsOverview>>(url),
    );

    return response.data;
  }
}
