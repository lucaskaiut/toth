import { useQuery } from "@tanstack/react-query";
import { useEffect, useMemo, useState } from "react";
import { useSearchParams } from "react-router";
import { ChatPanel } from "~/features/inbox/components/ChatPanel";
import { ConversationList } from "~/features/inbox/components/ConversationList";
import { conversationsApi } from "~/lib/api/conversations.api";
import { CONVERSATIONS_QUERY_KEY } from "~/lib/crm/constants";
import type { Conversation } from "~/types/crm";

function parseConversationId(raw: string | null): number | null {
  if (!raw) {
    return null;
  }

  const id = Number(raw);
  return Number.isInteger(id) && id > 0 ? id : null;
}

export function InboxLayout() {
  const [searchParams, setSearchParams] = useSearchParams();
  const conversationFromUrl = parseConversationId(searchParams.get("conversation"));
  const [selectedId, setSelectedId] = useState<number | null>(conversationFromUrl);

  const conversationsQuery = useQuery({
    queryKey: CONVERSATIONS_QUERY_KEY,
    queryFn: () => conversationsApi.list(),
  });

  const conversations = conversationsQuery.data?.data ?? [];

  useEffect(() => {
    if (conversationFromUrl === null) {
      return;
    }

    if (conversations.some((conversation) => conversation.id === conversationFromUrl)) {
      setSelectedId(conversationFromUrl);
    }
  }, [conversationFromUrl, conversations]);

  const selectedConversation = useMemo(() => {
    if (selectedId !== null) {
      return conversations.find((conversation) => conversation.id === selectedId) ?? null;
    }

    return conversations[0] ?? null;
  }, [conversations, selectedId]);

  const handleSelect = (conversation: Conversation) => {
    setSelectedId(conversation.id);
    setSearchParams({ conversation: String(conversation.id) }, { replace: true });
  };

  if (conversationsQuery.isLoading) {
    return <p className="p-6 text-muted">Carregando conversas...</p>;
  }

  return (
    <div className="flex min-h-0 flex-1">
      <ConversationList
        conversations={conversations}
        selectedId={selectedConversation?.id ?? null}
        onSelect={handleSelect}
      />
      <ChatPanel conversation={selectedConversation} />
    </div>
  );
}
