import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { API_BASE_URL } from '../../core/config/api.config';
import { ApiCollectionResponse, ApiResourceResponse } from '../../core/http/api.types';
import { PurchaseOrder, PurchaseOrderPayload, ReceivePurchaseOrderPayload } from './purchases.types';

@Injectable({ providedIn: 'root' })
export class PurchasesApiService {
  private readonly http = inject(HttpClient);

  async list(search = '', status = ''): Promise<PurchaseOrder[]> {
    const params = new URLSearchParams();

    if (search.trim() !== '') {
      params.set('search', search.trim());
    }

    if (status.trim() !== '') {
      params.set('status', status.trim());
    }

    const queryString = params.toString();
    const url =
      queryString === ''
        ? `${API_BASE_URL}/purchase-orders`
        : `${API_BASE_URL}/purchase-orders?${queryString}`;

    const response = await firstValueFrom(
      this.http.get<ApiCollectionResponse<PurchaseOrder>>(url),
    );

    return response.data;
  }

  async create(payload: PurchaseOrderPayload): Promise<PurchaseOrder> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<PurchaseOrder>>(`${API_BASE_URL}/purchase-orders`, payload),
    );

    return response.data;
  }

  async update(id: number, payload: PurchaseOrderPayload): Promise<PurchaseOrder> {
    const response = await firstValueFrom(
      this.http.patch<ApiResourceResponse<PurchaseOrder>>(`${API_BASE_URL}/purchase-orders/${id}`, payload),
    );

    return response.data;
  }

  async receive(id: number, payload: ReceivePurchaseOrderPayload): Promise<PurchaseOrder> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<PurchaseOrder>>(
        `${API_BASE_URL}/purchase-orders/${id}/receive`,
        payload,
      ),
    );

    return response.data;
  }

  async delete(id: number): Promise<void> {
    await firstValueFrom(this.http.delete(`${API_BASE_URL}/purchase-orders/${id}`));
  }
}
