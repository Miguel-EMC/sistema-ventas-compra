import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { API_BASE_URL } from '../../core/config/api.config';
import { ApiResourceResponse } from '../../core/http/api.types';
import { DashboardSummary } from './dashboard.types';

@Injectable({ providedIn: 'root' })
export class DashboardApiService {
  private readonly http = inject(HttpClient);

  async getSummary(): Promise<DashboardSummary> {
    const response = await firstValueFrom(
      this.http.get<ApiResourceResponse<DashboardSummary>>(`${API_BASE_URL}/dashboard/summary`),
    );

    return response.data;
  }
}
