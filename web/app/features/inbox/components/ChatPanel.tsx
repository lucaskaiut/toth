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

type SendPayload = {
  content: string;
  replaceId?: number;
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
    mutationFn: ({ content }: SendPayload) =>
      conversationsApi.sendMessage(conversation!.id, content),
    onMutate: async ({ content, replaceId }: SendPayload) => {
      const queryKey = conversationMessagesQueryKey(conversation!.id);

      await queryClient.cancelQueries({ queryKey });

      const optimisticId = -Date.now();
      const now = new Date().toISOString();

      queryClient.setQueryData(
        queryKey,
        (current: { data: import("~/types/crm").Message[] } | undefined) => {
          const optimisticMessage: import("~/types/crm").Message = {
            id: replaceId ?? optimisticId,
            conversation_id: conversation!.id,
            origin: "user",
            content,
            sent_at: now,
            user: null,
            client_status: "pending",
          };

          if (!current) {
            return { data: [optimisticMessage] };
          }

          if (replaceId) {
            return {
              data: current.data.map((item) =>
                item.id === replaceId ? optimisticMessage : item,
              ),
            };
          }

          return { data: [...current.data, optimisticMessage] };
        },
      );

      setDraft("");

      return { optimisticId: replaceId ?? optimisticId };
    },
    onSuccess: (response) => {
      queryClient.setQueryData(
        conversationMessagesQueryKey(conversation!.id),
        (current: { data: import("~/types/crm").Message[] } | undefined) => {
          const serverMessage = { ...response.data, client_status: "sent" as const };

          if (!current) {
            return { data: [serverMessage] };
          }

          const replaced = current.data.map((item) =>
            item.client_status === "pending" &&
            item.origin === "user" &&
            item.content === serverMessage.content
              ? serverMessage
              : item,
          );

          const exists = replaced.some((item) => item.id === serverMessage.id);
          return exists ? { data: replaced } : { data: [...replaced, serverMessage] };
        },
      );

      void queryClient.invalidateQueries({ queryKey: CONVERSATIONS_QUERY_KEY });
    },
    onError: (_error, _payload, context) => {
      if (!context?.optimisticId) {
        return;
      }

      queryClient.setQueryData(
        conversationMessagesQueryKey(conversation!.id),
        (current: { data: import("~/types/crm").Message[] } | undefined) => {
          if (!current) {
            return current;
          }

          return {
            data: current.data.map((item) =>
              item.id === context.optimisticId
                ? { ...item, client_status: "failed" }
                : item,
            ),
          };
        },
      );
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
    <div className="flex min-h-0 min-w-0 flex-1 flex-col">
      <header className="ui-page-header">
        <h2 className="text-lg font-semibold">{conversation.lead?.name}</h2>
        <p className="text-sm text-muted">{conversation.lead?.phone}</p>
        {conversation.summary ? (
          <p className="mt-2 text-sm text-muted">{conversation.summary}</p>
        ) : null}
        <ConversationAttendanceControls conversation={conversation} />
      </header>

      <div className="min-h-0 flex-1 space-y-3 overflow-y-auto px-6 py-4">
        {messagesQuery.isLoading ? (
          <p className="text-sm text-muted">Carregando mensagens...</p>
        ) : (
          messages.map((message) => (
            <MessageBubble
              key={message.id}
              message={message}
              onRetry={() => {
                if (message.origin !== "user") return;
                if (message.client_status !== "failed") return;

                sendMutation.mutate({ content: message.content, replaceId: message.id });
              }}
            />
          ))
        )}
        <div ref={bottomRef} />
      </div>

      <form
        className="flex gap-2 bg-surface-elevated px-6 py-4 shadow-[0_-4px_16px_rgba(11,18,32,0.06)]"
        onSubmit={(event) => {
          event.preventDefault();

          if (!draft.trim()) {
            return;
          }

          sendMutation.mutate({ content: draft.trim() });
        }}
      >
        <Input
          value={draft}
          onChange={(event) => setDraft(event.target.value)}
          placeholder="Digite sua mensagem..."
          className="flex-1"
        />
        <Button type="submit">
          Enviar
        </Button>
      </form>
    </div>
  );
}
