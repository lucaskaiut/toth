import { apiRequest } from "~/lib/api/client";
import type { ApiResponse } from "~/types/auth";

export type WhatsAppConnectData = {
  instance_name: string;
  pairing_code: string | null;
  code: string | null;
  base64: string | null;
};

export type WhatsAppConnectionStateData = {
  instance_name: string;
  state: string | null;
  connected: boolean;
  company_status: string;
};

export const whatsappSetupApi = {
  connect() {
    return apiRequest<ApiResponse<WhatsAppConnectData>>(
      "/company/whatsapp/connect",
    );
  },

  connectionState() {
    return apiRequest<ApiResponse<WhatsAppConnectionStateData>>(
      "/company/whatsapp/connection-state",
    );
  },
};
