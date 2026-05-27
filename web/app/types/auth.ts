export type CompanyStatus =
  | "pending_whatsapp_connection"
  | "active";

export type AuthCompany = {
  id: number;
  name: string;
  status: CompanyStatus;
  whatsapp: string | null;
};

export type AuthUser = {
  id: number;
  name: string;
  email: string;
  company_id: number;
  company?: AuthCompany;
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
  code?: string;
  company_status?: CompanyStatus;
};
