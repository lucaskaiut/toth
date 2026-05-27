import { apiRequest } from "~/lib/api/client";
import type { ApiResponse, Lead } from "~/types/crm";

export const leadsApi = {
  list() {
    return apiRequest<ApiResponse<Lead[]>>("/leads");
  },

  update(id: number, data: Partial<Pick<Lead, "name" | "email" | "company_name" | "notes">>) {
    return apiRequest<ApiResponse<Lead>>(`/leads/${id}`, {
      method: "PUT",
      body: data,
    });
  },

  moveStage(id: number, pipelineStageId: number) {
    return apiRequest<ApiResponse<Lead>>(`/leads/${id}/stage`, {
      method: "PATCH",
      body: { pipeline_stage_id: pipelineStageId },
    });
  },
};
