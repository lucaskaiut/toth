import { apiRequest } from "~/lib/api/client";
import type { ApiResponse } from "~/types/crm";
import type {
  KnowledgeSearchResult,
  KnowledgeSource,
  KnowledgeSourceType,
  KnowledgeStats,
} from "~/types/knowledge";

export type CreateKnowledgeSourcePayload = {
  type: KnowledgeSourceType;
  title: string;
  content?: string | null;
  metadata?: Record<string, unknown> | null;
};

export type UpdateKnowledgeSourcePayload = {
  title?: string;
  content?: string | null;
  metadata?: Record<string, unknown> | null;
};

export const knowledgeApi = {
  listSources(type?: KnowledgeSourceType) {
    const query = type ? `?type=${encodeURIComponent(type)}` : "";
    return apiRequest<ApiResponse<KnowledgeSource[]>>(`/knowledge/sources${query}`);
  },

  createSource(payload: CreateKnowledgeSourcePayload) {
    return apiRequest<ApiResponse<KnowledgeSource>>("/knowledge/sources", {
      method: "POST",
      body: payload,
    });
  },

  updateSource(id: number, payload: UpdateKnowledgeSourcePayload) {
    return apiRequest<ApiResponse<KnowledgeSource>>(`/knowledge/sources/${id}`, {
      method: "PUT",
      body: payload,
    });
  },

  deleteSource(id: number) {
    return apiRequest<{ message: string }>(`/knowledge/sources/${id}`, {
      method: "DELETE",
    });
  },

  uploadDocument(file: File, title?: string) {
    const formData = new FormData();
    formData.append("file", file);
    if (title) {
      formData.append("title", title);
    }

    return apiRequest<ApiResponse<KnowledgeSource>>("/knowledge/sources/documents", {
      method: "POST",
      body: formData,
    });
  },

  reindexSource(id: number) {
    return apiRequest<ApiResponse<KnowledgeSource>>(`/knowledge/sources/${id}/reindex`, {
      method: "POST",
    });
  },

  reindexAll() {
    return apiRequest<{ message: string }>("/knowledge/reindex-all", {
      method: "POST",
    });
  },

  stats() {
    return apiRequest<ApiResponse<KnowledgeStats>>("/knowledge/stats");
  },

  search(query: string, limit?: number) {
    return apiRequest<ApiResponse<KnowledgeSearchResult[]>>("/knowledge/search", {
      method: "POST",
      body: { query, limit },
    });
  },

  context(query: string) {
    return apiRequest<ApiResponse<{ context: string }>>("/knowledge/context", {
      method: "POST",
      body: { query },
    });
  },
};
