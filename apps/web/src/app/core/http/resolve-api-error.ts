export function resolveApiError(error: unknown): string {
  if (
    typeof error === 'object' &&
    error !== null &&
    'error' in error &&
    typeof error.error === 'object' &&
    error.error !== null
  ) {
    const apiError = error.error as {
      message?: string;
      errors?: Record<string, string[]>;
    };

    const validationMessage = apiError.errors ? Object.values(apiError.errors).flat()[0] : null;

    return validationMessage ?? apiError.message ?? 'No se pudo completar la operacion.';
  }

  return 'No se pudo completar la operacion.';
}
