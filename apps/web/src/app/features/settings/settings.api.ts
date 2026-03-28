import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { API_BASE_URL } from '../../core/config/api.config';
import { ApiResourceResponse } from '../../core/http/api.types';
import { BusinessSettings, UpdateBusinessSettingsPayload } from './settings.types';

@Injectable({ providedIn: 'root' })
export class SettingsApiService {
  private readonly http = inject(HttpClient);

  async getBusinessSettings(): Promise<BusinessSettings> {
    const response = await firstValueFrom(
      this.http.get<ApiResourceResponse<BusinessSettings>>(`${API_BASE_URL}/settings/business`),
    );

    return response.data;
  }

  async updateBusinessSettings(payload: UpdateBusinessSettingsPayload): Promise<BusinessSettings> {
    const response = await firstValueFrom(
      this.http.put<ApiResourceResponse<BusinessSettings>>(`${API_BASE_URL}/settings/business`, payload),
    );

    return response.data;
  }
}
