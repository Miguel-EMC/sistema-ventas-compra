import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { API_BASE_URL } from '../../core/config/api.config';
import { ApiCollectionResponse, ApiResourceResponse } from '../../core/http/api.types';
import { BusinessPartner, BusinessPartnerPayload } from '../partners/partners.types';

@Injectable({ providedIn: 'root' })
export class CustomersApiService {
  private readonly http = inject(HttpClient);

  async list(search = ''): Promise<BusinessPartner[]> {
    const params = new URLSearchParams();

    if (search.trim() !== '') {
      params.set('search', search.trim());
    }

    const queryString = params.toString();
    const url = queryString === '' ? `${API_BASE_URL}/customers` : `${API_BASE_URL}/customers?${queryString}`;

    const response = await firstValueFrom(
      this.http.get<ApiCollectionResponse<BusinessPartner>>(url),
    );

    return response.data;
  }

  async create(payload: BusinessPartnerPayload): Promise<BusinessPartner> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<BusinessPartner>>(`${API_BASE_URL}/customers`, payload),
    );

    return response.data;
  }

  async update(id: number, payload: BusinessPartnerPayload): Promise<BusinessPartner> {
    const response = await firstValueFrom(
      this.http.patch<ApiResourceResponse<BusinessPartner>>(
        `${API_BASE_URL}/customers/${id}`,
        payload,
      ),
    );

    return response.data;
  }

  async delete(id: number): Promise<void> {
    await firstValueFrom(this.http.delete(`${API_BASE_URL}/customers/${id}`));
  }
}
