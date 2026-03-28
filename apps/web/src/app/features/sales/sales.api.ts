import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { API_BASE_URL } from '../../core/config/api.config';
import { ApiCollectionResponse, ApiResourceResponse } from '../../core/http/api.types';
import {
  CheckoutSalePayload,
  SaleDraft,
  SaleDraftItemPayload,
  SaleRecord,
  UpdateSaleDraftItemPayload,
  UpdateSaleDraftPayload,
} from './sales.types';

@Injectable({ providedIn: 'root' })
export class SalesApiService {
  private readonly http = inject(HttpClient);

  async getDraft(): Promise<SaleDraft> {
    const response = await firstValueFrom(
      this.http.get<ApiResourceResponse<SaleDraft>>(`${API_BASE_URL}/sales/draft`),
    );

    return response.data;
  }

  async updateDraft(payload: UpdateSaleDraftPayload): Promise<SaleDraft> {
    const response = await firstValueFrom(
      this.http.patch<ApiResourceResponse<SaleDraft>>(`${API_BASE_URL}/sales/draft`, payload),
    );

    return response.data;
  }

  async addDraftItem(payload: SaleDraftItemPayload): Promise<SaleDraft> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<SaleDraft>>(`${API_BASE_URL}/sales/draft/items`, payload),
    );

    return response.data;
  }

  async updateDraftItem(id: number, payload: UpdateSaleDraftItemPayload): Promise<SaleDraft> {
    const response = await firstValueFrom(
      this.http.patch<ApiResourceResponse<SaleDraft>>(
        `${API_BASE_URL}/sales/draft/items/${id}`,
        payload,
      ),
    );

    return response.data;
  }

  async removeDraftItem(id: number): Promise<SaleDraft> {
    const response = await firstValueFrom(
      this.http.delete<ApiResourceResponse<SaleDraft>>(`${API_BASE_URL}/sales/draft/items/${id}`),
    );

    return response.data;
  }

  async listSales(): Promise<SaleRecord[]> {
    const response = await firstValueFrom(
      this.http.get<ApiCollectionResponse<SaleRecord>>(`${API_BASE_URL}/sales`),
    );

    return response.data;
  }

  async checkout(payload: CheckoutSalePayload): Promise<SaleRecord> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<SaleRecord>>(`${API_BASE_URL}/sales`, payload),
    );

    return response.data;
  }
}
