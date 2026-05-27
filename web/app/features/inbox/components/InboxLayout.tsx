import { useQuery } from "@tanstack/react-query";
import { useMemo, useState } from "react";
import { ChatPanel } from "~/features/inbox/components/ChatPanel";
import { ConversationList } from "~/features/inbox/components/ConversationList";
import { conversationsApi } from "~/lib/api/conversations.api";
import { CONVERSATIONS_QUERY_KEY } from "~/lib/crm/constants";
import type { Conversation } from "~/types/crm";

export function InboxLayout() {
  const [selectedId, setSelectedId] = useState<number | null>(null);

  const conversationsQuery = useQuery({
    queryKey: CONVERSATIONS_QUERY_KEY,
    queryFn: () => conversationsApi.list(),
  });

  const conversations = conversationsQuery.data?.data ?? [];

  const selectedConversation = useMemo(() => {
    if (selectedId === null) {
      return conversations[0] ?? null;
    }

    return conversations.find((conversation) => conversation.id === selectedId) ?? null;
  }, [conversations, selectedId]);

  if (conversationsQuery.isLoading) {
    return <p className="p-6 text-muted">Carregando conversas...</p>;
  }

  return (
    <div className="flex min-h-0 flex-1">
      <ConversationList
        conversations={conversations}
        selectedId={selectedConversation?.id ?? null}
        onSelect={(conversation: Conversation) => setSelectedId(conversation.id)}
      />
      <ChatPanel conversation={selectedConversation} />
    </div>
  );
}
