import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { API_BASE_URL } from '../../core/config/api.config';
import { ApiCollectionResponse, ApiResourceResponse } from '../../core/http/api.types';
import {
  CashMovement,
  CashMovementPayload,
  CashRegister,
  CashSession,
  CloseCashSessionPayload,
  OpenCashSessionPayload,
} from './cash.types';

interface NullableCashSessionResponse {
  data: CashSession | null;
}

@Injectable({ providedIn: 'root' })
export class CashApiService {
  private readonly http = inject(HttpClient);

  async listRegisters(): Promise<CashRegister[]> {
    const response = await firstValueFrom(
      this.http.get<ApiCollectionResponse<CashRegister>>(`${API_BASE_URL}/cash/registers`),
    );

    return response.data;
  }

  async listSessions(): Promise<CashSession[]> {
    const response = await firstValueFrom(
      this.http.get<ApiCollectionResponse<CashSession>>(`${API_BASE_URL}/cash/sessions`),
    );

    return response.data;
  }

  async listMovements(): Promise<CashMovement[]> {
    const response = await firstValueFrom(
      this.http.get<ApiCollectionResponse<CashMovement>>(`${API_BASE_URL}/cash/movements`),
    );

    return response.data;
  }

  async getCurrentSession(): Promise<CashSession | null> {
    const response = await firstValueFrom(
      this.http.get<NullableCashSessionResponse>(`${API_BASE_URL}/cash/sessions/current`),
    );

    return response.data;
  }

  async openSession(payload: OpenCashSessionPayload): Promise<CashSession> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<CashSession>>(`${API_BASE_URL}/cash/sessions`, payload),
    );

    return response.data;
  }

  async closeSession(id: number, payload: CloseCashSessionPayload): Promise<CashSession> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<CashSession>>(
        `${API_BASE_URL}/cash/sessions/${id}/close`,
        payload,
      ),
    );

    return response.data;
  }

  async createMovement(payload: CashMovementPayload): Promise<CashMovement> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<CashMovement>>(`${API_BASE_URL}/cash/movements`, payload),
    );

    return response.data;
  }

  async updateMovement(id: number, payload: CashMovementPayload): Promise<CashMovement> {
    const response = await firstValueFrom(
      this.http.patch<ApiResourceResponse<CashMovement>>(`${API_BASE_URL}/cash/movements/${id}`, payload),
    );

    return response.data;
  }

  async deleteMovement(id: number): Promise<void> {
    await firstValueFrom(this.http.delete(`${API_BASE_URL}/cash/movements/${id}`));
  }
}
