type RuntimeEnv = {
  API_ORIGIN?: string;
};

const runtimeEnv = (globalThis as typeof globalThis & { __env?: RuntimeEnv }).__env ?? {};

export const API_ORIGIN = runtimeEnv.API_ORIGIN ?? 'http://localhost:8001';
export const API_BASE_URL = `${API_ORIGIN}/api/v1`;
export const ACCESS_TOKEN_STORAGE_KEY = 'ventaspos_api_token';
