import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { API_BASE_URL } from '../../core/config/api.config';
import { ApiCollectionResponse, ApiResourceResponse } from '../../core/http/api.types';
import {
  Product,
  ProductCategory,
  ProductCategoryPayload,
  ProductPayload,
  ProductStockAdjustmentPayload,
} from './products.types';

@Injectable({ providedIn: 'root' })
export class ProductsApiService {
  private readonly http = inject(HttpClient);

  async listProducts(search = ''): Promise<Product[]> {
    const params = new URLSearchParams();

    if (search.trim() !== '') {
      params.set('search', search.trim());
    }

    const queryString = params.toString();
    const url = queryString === '' ? `${API_BASE_URL}/products` : `${API_BASE_URL}/products?${queryString}`;

    const response = await firstValueFrom(
      this.http.get<ApiCollectionResponse<Product>>(url),
    );

    return response.data;
  }

  async listCategories(): Promise<ProductCategory[]> {
    const response = await firstValueFrom(
      this.http.get<ApiCollectionResponse<ProductCategory>>(`${API_BASE_URL}/product-categories`),
    );

    return response.data;
  }

  async createCategory(payload: ProductCategoryPayload): Promise<ProductCategory> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<ProductCategory>>(`${API_BASE_URL}/product-categories`, payload),
    );

    return response.data;
  }

  async updateCategory(id: number, payload: ProductCategoryPayload): Promise<ProductCategory> {
    const response = await firstValueFrom(
      this.http.patch<ApiResourceResponse<ProductCategory>>(
        `${API_BASE_URL}/product-categories/${id}`,
        payload,
      ),
    );

    return response.data;
  }

  async deleteCategory(id: number): Promise<void> {
    await firstValueFrom(this.http.delete(`${API_BASE_URL}/product-categories/${id}`));
  }

  async createProduct(payload: ProductPayload): Promise<Product> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<Product>>(`${API_BASE_URL}/products`, payload),
    );

    return response.data;
  }

  async updateProduct(id: number, payload: ProductPayload): Promise<Product> {
    const response = await firstValueFrom(
      this.http.patch<ApiResourceResponse<Product>>(`${API_BASE_URL}/products/${id}`, payload),
    );

    return response.data;
  }

  async deleteProduct(id: number): Promise<void> {
    await firstValueFrom(this.http.delete(`${API_BASE_URL}/products/${id}`));
  }

  async adjustStock(id: number, payload: ProductStockAdjustmentPayload): Promise<Product> {
    const response = await firstValueFrom(
      this.http.post<ApiResourceResponse<Product>>(
        `${API_BASE_URL}/products/${id}/stock-adjustments`,
        payload,
      ),
    );

    return response.data;
  }
}
