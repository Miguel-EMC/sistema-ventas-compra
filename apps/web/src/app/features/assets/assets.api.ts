import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { API_BASE_URL } from '../../core/config/api.config';
import { ApiCollectionResponse, ApiResourceResponse } from '../../core/http/api.types';
import { Asset, AssetCategory, AssetCategoryPayload, AssetPayload } from './assets.types';

@Injectable({ providedIn: 'root' })
export class AssetsApiService {
  private readonly http = inject(HttpClient);

  async listAssets(search = ''): Promise<Asset[]> {
    const response = await firstValueFrom(
      this.http.get<ApiCollectionResponse<Asset>>(this.buildSearchUrl(`${API_BASE_URL}/assets`, search)),
    );

    return response.data;
  }

  async listCategories(): Promise<AssetCategory[]> {
    const response = await firstValueFrom(
      this.http.get<ApiCollectionResponse<AssetCategory>>(`${API_BASE_URL}/asset-categories`),
    );

    return response.data;
  }

  async createCategory(payload: AssetCategoryPayload): Promise<AssetCategory> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<AssetCategory>>(`${API_BASE_URL}/asset-categories`, payload),
    );

    return response.data;
  }

  async updateCategory(id: number, payload: AssetCategoryPayload): Promise<AssetCategory> {
    const response = await firstValueFrom(
      this.http.patch<ApiResourceResponse<AssetCategory>>(
        `${API_BASE_URL}/asset-categories/${id}`,
        payload,
      ),
    );

    return response.data;
  }

  async deleteCategory(id: number): Promise<void> {
    await firstValueFrom(this.http.delete(`${API_BASE_URL}/asset-categories/${id}`));
  }

  async createAsset(payload: AssetPayload): Promise<Asset> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<Asset>>(`${API_BASE_URL}/assets`, payload),
    );

    return response.data;
  }

  async updateAsset(id: number, payload: AssetPayload): Promise<Asset> {
    const response = await firstValueFrom(
      this.http.patch<ApiResourceResponse<Asset>>(`${API_BASE_URL}/assets/${id}`, payload),
    );

    return response.data;
  }

  async deleteAsset(id: number): Promise<void> {
    await firstValueFrom(this.http.delete(`${API_BASE_URL}/assets/${id}`));
  }

  async downloadCatalogPdf(search = ''): Promise<Blob> {
    return await firstValueFrom(
      this.http.get(this.buildSearchUrl(`${API_BASE_URL}/reports/catalog/assets/pdf`, search), {
        responseType: 'blob',
      }),
    );
  }

  async downloadCatalogCsv(search = ''): Promise<Blob> {
    return await firstValueFrom(
      this.http.get(this.buildSearchUrl(`${API_BASE_URL}/reports/catalog/assets/csv`, search), {
        responseType: 'blob',
      }),
    );
  }

  private buildSearchUrl(baseUrl: string, search = ''): string {
    const params = new URLSearchParams();

    if (search.trim() !== '') {
      params.set('search', search.trim());
    }

    const queryString = params.toString();

    return queryString === '' ? baseUrl : `${baseUrl}?${queryString}`;
  }
}
