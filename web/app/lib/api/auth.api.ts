import { apiRequest } from "~/lib/api/client";
import type { ApiResponse, AuthSession, AuthUser } from "~/types/auth";

export type ChannelPayload<T> = {
  channel: "internal";
  data: T;
};

export type LoginData = {
  email: string;
  password: string;
};

export type RegisterData = {
  company_name: string;
  name: string;
  email: string;
  password: string;
};

export const authApi = {
  login(payload: ChannelPayload<LoginData>) {
    return apiRequest<ApiResponse<AuthSession>>("/login", {
      method: "POST",
      body: payload,
      skipAuth: true,
      skipUnauthorizedHandler: true,
    });
  },

  register(payload: ChannelPayload<RegisterData>) {
    return apiRequest<ApiResponse<AuthSession>>("/register", {
      method: "POST",
      body: payload,
      skipAuth: true,
      skipUnauthorizedHandler: true,
    });
  },

  me() {
    return apiRequest<ApiResponse<AuthUser>>("/me");
  },
};
