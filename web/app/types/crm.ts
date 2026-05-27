export type ApiResponse<T> = { data: T };

export type PipelineStage = {
  id: number;
  name: string;
  slug: string;
  position: number;
};

export type Lead = {
  id: number;
  name: string;
  phone: string;
  email: string | null;
  company_name: string | null;
  notes: string | null;
  pipeline_stage_id: number;
  pipeline_stage?: PipelineStage;
  conversation_id?: number | null;
  created_at?: string;
  updated_at?: string;
};

export type ConversationAttendanceStatus =
  | "ai_enabled"
  | "handoff_to_human"
  | "waiting_human"
  | "closed";

export type Conversation = {
  id: number;
  lead_id: number;
  summary: string | null;
  attendance_status: ConversationAttendanceStatus;
  attendance_status_label?: string;
  lead?: Lead;
  updated_at?: string;
  created_at?: string;
};

export type MessageOrigin = "customer" | "ai" | "user";

export type Message = {
  id: number;
  conversation_id: number;
  origin: MessageOrigin;
  content: string;
  sent_at: string;
  user?: { id: number; name: string } | null;
};

export type CompanyConfig = {
  key: string;
  value: string | null;
  type: string;
};
