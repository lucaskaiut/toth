import { parseApiError, ApiError } from "~/lib/api/errors";
import { tokenStorage } from "~/lib/auth/token.storage";
import type { ApiErrorResponse } from "~/types/auth";

const API_URL = import.meta.env.VITE_API_URL ?? "/api";

type UnauthorizedHandler = () => void;

let unauthorizedHandler: UnauthorizedHandler | null = null;

export function setUnauthorizedHandler(handler: UnauthorizedHandler | null) {
  unauthorizedHandler = handler;
}

type RequestOptions = Omit<RequestInit, "body"> & {
  body?: unknown;
  skipAuth?: boolean;
  skipUnauthorizedHandler?: boolean;
};

export async function apiRequest<T>(
  path: string,
  options: RequestOptions = {},
): Promise<T> {
  const {
    body,
    skipAuth = false,
    skipUnauthorizedHandler = false,
    headers,
    ...init
  } = options;

  const requestHeaders = new Headers(headers);
  requestHeaders.set("Accept", "application/json");

  if (body !== undefined) {
    requestHeaders.set("Content-Type", "application/json");
  }

  if (!skipAuth) {
    const token = tokenStorage.get();
    if (token) {
      requestHeaders.set("Authorization", `Bearer ${token}`);
    }
  }

  const response = await fetch(`${API_URL}${path}`, {
    ...init,
    headers: requestHeaders,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  if (response.status === 401 && !skipUnauthorizedHandler) {
    unauthorizedHandler?.();
    throw await parseApiError(response);
  }

  if (!response.ok) {
    throw await parseApiError(response);
  }

  if (response.status === 204) {
    return undefined as T;
  }

  return (await response.json()) as T;
}

export function getValidationErrors(error: unknown): Record<string, string> {
  if (!(error instanceof ApiError) || !error.payload?.errors) {
    return {};
  }

  return Object.fromEntries(
    Object.entries(error.payload.errors).map(([key, messages]) => [
      key,
      messages[0] ?? "",
    ]),
  );
}

export { API_URL };
