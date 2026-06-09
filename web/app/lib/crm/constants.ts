export const PIPELINE_STAGES_QUERY_KEY = ["crm", "pipeline", "stages"] as const;
export const LEADS_QUERY_KEY = ["crm", "leads"] as const;
export const CONVERSATIONS_QUERY_KEY = ["crm", "conversations"] as const;
export const COMPANY_CONFIGS_QUERY_KEY = ["crm", "company", "configs"] as const;
export const KNOWLEDGE_SOURCES_QUERY_KEY = ["crm", "knowledge", "sources"] as const;
export const KNOWLEDGE_STATS_QUERY_KEY = ["crm", "knowledge", "stats"] as const;

export function conversationMessagesQueryKey(conversationId: number) {
  return ["crm", "conversations", conversationId, "messages"] as const;
}

export function inboxConversationPath(conversationId: number) {
  return `/inbox?conversation=${conversationId}`;
}
