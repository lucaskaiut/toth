import { apiRequest } from "~/lib/api/client";
import type { ApiResponse, PipelineStage } from "~/types/crm";

export type CreatePipelineStagePayload = {
  name: string;
  description: string;
  ai_instruction?: string | null;
};

export type UpdatePipelineStagePayload = Partial<CreatePipelineStagePayload>;

export const pipelineApi = {
  listStages() {
    return apiRequest<ApiResponse<PipelineStage[]>>("/pipeline/stages");
  },

  create(payload: CreatePipelineStagePayload) {
    return apiRequest<ApiResponse<PipelineStage>>("/pipeline/stages", {
      method: "POST",
      body: payload,
    });
  },

  update(id: number, payload: UpdatePipelineStagePayload) {
    return apiRequest<ApiResponse<PipelineStage>>(`/pipeline/stages/${id}`, {
      method: "PUT",
      body: payload,
    });
  },

  delete(id: number) {
    return apiRequest<{ message: string }>(`/pipeline/stages/${id}`, {
      method: "DELETE",
    });
  },

  reorder(stageIds: number[]) {
    return apiRequest<ApiResponse<PipelineStage[]>>("/pipeline/stages/reorder", {
      method: "PATCH",
      body: { stages: stageIds },
    });
  },
};
