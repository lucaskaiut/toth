import type { AuthCompany, CompanyStatus } from "~/types/auth";

export function isCompanyActive(company?: AuthCompany | null): boolean {
  return company?.status === "active";
}

export function requiresWhatsappSetup(company?: AuthCompany | null): boolean {
  return company?.status === "pending_whatsapp_connection";
}

export function resolvePostAuthPath(company?: AuthCompany | null): string {
  if (requiresWhatsappSetup(company)) {
    return "/setup/whatsapp";
  }

  return "/kanban";
}

export type { CompanyStatus };
