import { apiRequest } from "~/lib/api/client";
import type { ApiResponse, CompanyConfig } from "~/types/crm";

export type CompanyConfigType = "string" | "int" | "bool" | "json";

export type UpdateCompanyConfigsPayload = {
  configs: Array<{
    key: string;
    value: unknown;
    type?: CompanyConfigType;
  }>;
};

export const companyConfigsApi = {
  list() {
    return apiRequest<ApiResponse<CompanyConfig[]>>("/company-configs");
  },

  update(payload: UpdateCompanyConfigsPayload) {
    return apiRequest<ApiResponse<CompanyConfig[]>>("/company-configs", {
      method: "PUT",
      body: payload,
    });
  },
};
