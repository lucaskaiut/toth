export type AuthUser = {
  id: number;
  name: string;
  email: string;
};

export type AuthSession = {
  token: string;
  user: AuthUser;
};

export type AuthStatus = "idle" | "loading" | "authenticated" | "unauthenticated";

export type ApiResponse<T> = {
  data: T;
};

export type ApiErrorResponse = {
  message?: string;
  errors?: Record<string, string[]>;
};
