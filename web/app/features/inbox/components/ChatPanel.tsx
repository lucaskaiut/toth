import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect, useRef, useState } from "react";
import { Button } from "~/components/ui/Button";
import { Input } from "~/components/ui/Input";
import { ConversationAttendanceControls } from "~/features/inbox/components/ConversationAttendanceControls";
import { MessageBubble } from "~/features/inbox/components/MessageBubble";
import { conversationsApi } from "~/lib/api/conversations.api";
import {
  CONVERSATIONS_QUERY_KEY,
  conversationMessagesQueryKey,
} from "~/lib/crm/constants";
import type { Conversation } from "~/types/crm";

type ChatPanelProps = {
  conversation: Conversation | null;
};

export function ChatPanel({ conversation }: ChatPanelProps) {
  const [draft, setDraft] = useState("");
  const bottomRef = useRef<HTMLDivElement>(null);
  const queryClient = useQueryClient();

  const messagesQuery = useQuery({
    queryKey: conversation
      ? conversationMessagesQueryKey(conversation.id)
      : ["crm", "conversations", "empty", "messages"],
    queryFn: () => conversationsApi.messages(conversation!.id),
    enabled: Boolean(conversation),
  });

  const sendMutation = useMutation({
    mutationFn: (content: string) =>
      conversationsApi.sendMessage(conversation!.id, content),
    onSuccess: (response) => {
      queryClient.setQueryData(
        conversationMessagesQueryKey(conversation!.id),
        (current: { data: import("~/types/crm").Message[] } | undefined) => {
          if (!current) {
            return { data: [response.data] };
          }

          return { data: [...current.data, response.data] };
        },
      );

      void queryClient.invalidateQueries({ queryKey: CONVERSATIONS_QUERY_KEY });
      setDraft("");
    },
  });

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messagesQuery.data?.data.length, conversation?.id]);

  if (!conversation) {
    return (
      <div className="flex flex-1 items-center justify-center text-muted">
        Selecione uma conversa para visualizar o histórico.
      </div>
    );
  }

  const messages = messagesQuery.data?.data ?? [];

  return (
    <div className="flex min-w-0 flex-1 flex-col">
      <header className="border-b border-border px-6 py-4">
        <h2 className="text-lg font-semibold">{conversation.lead?.name}</h2>
        <p className="text-sm text-muted">{conversation.lead?.phone}</p>
        {conversation.summary ? (
          <p className="mt-2 text-sm text-muted">{conversation.summary}</p>
        ) : null}
        <ConversationAttendanceControls conversation={conversation} />
      </header>

      <div className="flex-1 space-y-3 overflow-y-auto px-6 py-4">
        {messagesQuery.isLoading ? (
          <p className="text-sm text-muted">Carregando mensagens...</p>
        ) : (
          messages.map((message) => <MessageBubble key={message.id} message={message} />)
        )}
        <div ref={bottomRef} />
      </div>

      <form
        className="flex gap-2 border-t border-border px-6 py-4"
        onSubmit={(event) => {
          event.preventDefault();

          if (!draft.trim()) {
            return;
          }

          sendMutation.mutate(draft.trim());
        }}
      >
        <Input
          value={draft}
          onChange={(event) => setDraft(event.target.value)}
          placeholder="Digite sua mensagem..."
          className="flex-1"
        />
        <Button type="submit" isLoading={sendMutation.isPending}>
          Enviar
        </Button>
      </form>
    </div>
  );
}
