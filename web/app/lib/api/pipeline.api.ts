import { apiRequest } from "~/lib/api/client";
import type { ApiResponse, PipelineStage } from "~/types/crm";

export const pipelineApi = {
  listStages() {
    return apiRequest<ApiResponse<PipelineStage[]>>("/pipeline/stages");
  },
};
