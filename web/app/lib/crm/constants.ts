export const PIPELINE_STAGES_QUERY_KEY = ["crm", "pipeline", "stages"] as const;
export const LEADS_QUERY_KEY = ["crm", "leads"] as const;
export const CONVERSATIONS_QUERY_KEY = ["crm", "conversations"] as const;

export function conversationMessagesQueryKey(conversationId: number) {
  return ["crm", "conversations", conversationId, "messages"] as const;
}
