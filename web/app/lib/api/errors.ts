import type { ApiErrorResponse } from "~/types/auth";

export class ApiError extends Error {
  readonly status: number;
  readonly payload?: ApiErrorResponse;

  constructor(status: number, message: string, payload?: ApiErrorResponse) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.payload = payload;
  }
}

export async function parseApiError(response: Response): Promise<ApiError> {
  let payload: ApiErrorResponse | undefined;

  try {
    payload = (await response.json()) as ApiErrorResponse;
  } catch {
    payload = undefined;
  }

  const message =
    payload?.message ??
    (response.status === 401
      ? "Sessão expirada. Faça login novamente."
      : "Não foi possível concluir a solicitação.");

  return new ApiError(response.status, message, payload);
}
