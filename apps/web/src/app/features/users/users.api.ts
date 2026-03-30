import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { API_BASE_URL } from '../../core/config/api.config';
import { ApiCollectionResponse, ApiResourceResponse } from '../../core/http/api.types';
import { SaveUserPayload, UserRecord, UserRole } from './users.types';

@Injectable({ providedIn: 'root' })
export class UsersApiService {
  private readonly http = inject(HttpClient);

  async list(search = ''): Promise<UserRecord[]> {
    const params = new URLSearchParams({
      per_page: '100',
    });

    if (search.trim() !== '') {
      params.set('search', search.trim());
    }

    const response = await firstValueFrom(
      this.http.get<ApiCollectionResponse<UserRecord>>(`${API_BASE_URL}/users?${params.toString()}`),
    );

    return response.data;
  }

  async listRoles(): Promise<UserRole[]> {
    const response = await firstValueFrom(
      this.http.get<ApiCollectionResponse<UserRole>>(`${API_BASE_URL}/roles`),
    );

    return response.data;
  }

  async create(payload: SaveUserPayload): Promise<UserRecord> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<UserRecord>>(`${API_BASE_URL}/users`, payload),
    );

    return response.data;
  }

  async update(id: number, payload: SaveUserPayload): Promise<UserRecord> {
    const response = await firstValueFrom(
      this.http.patch<ApiResourceResponse<UserRecord>>(`${API_BASE_URL}/users/${id}`, payload),
    );

    return response.data;
  }

  async delete(id: number): Promise<void> {
    await firstValueFrom(this.http.delete(`${API_BASE_URL}/users/${id}`));
  }
}
