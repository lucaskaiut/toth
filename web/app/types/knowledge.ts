export type KnowledgeSourceType =
  | "company"
  | "faq"
  | "product"
  | "policy"
  | "script"
  | "document"
  | "free_context";

export type KnowledgeSourceStatus = "pending" | "indexing" | "indexed" | "error";

export type KnowledgeSource = {
  id: number;
  company_id: number;
  type: KnowledgeSourceType;
  title: string;
  content: string | null;
  metadata: Record<string, unknown> | null;
  status: KnowledgeSourceStatus;
  indexed_at: string | null;
  index_error: string | null;
  created_at: string;
  updated_at: string;
};

export type KnowledgeStats = {
  sources_total: number;
  chunks_total: number;
  vectors_total: number;
  last_indexed_at: string | null;
  by_status: Record<string, number>;
};

export type KnowledgeSearchResult = {
  chunk_id: number;
  source_id: number;
  source_type: KnowledgeSourceType;
  source_title: string;
  chunk_index: number;
  content: string;
  score: number;
  source_metadata: Record<string, unknown> | null;
  chunk_metadata: Record<string, unknown> | null;
};
