import { useQueryClient } from "@tanstack/react-query";
import { useEffect } from "react";
import {
  CONVERSATIONS_QUERY_KEY,
  LEADS_QUERY_KEY,
  conversationMessagesQueryKey,
} from "~/lib/crm/constants";
import { disconnectEcho, getEcho } from "~/lib/realtime/echo";
import { useAuthStore } from "~/stores/auth.store";
import type { Conversation, Lead, Message } from "~/types/crm";

export function RealtimeProvider({ children }: { children: React.ReactNode }) {
  const companyId = useAuthStore((state) => state.user?.company_id);
  const status = useAuthStore((state) => state.status);
  const queryClient = useQueryClient();

  useEffect(() => {
    if (status !== "authenticated" || !companyId) {
      disconnectEcho();
      return;
    }

    const echo = getEcho();

    if (!echo) {
      return;
    }

    const channel = echo.private(`company.${companyId}`);

    channel.listen(".message.created", (payload: { message: Message }) => {
      queryClient.setQueryData(
        conversationMessagesQueryKey(payload.message.conversation_id),
        (current: { data: Message[] } | undefined) => {
          if (!current) {
            return current;
          }

          const exists = current.data.some((item) => item.id === payload.message.id);

          if (exists) {
            return current;
          }

          return {
            data: [...current.data, payload.message],
          };
        },
      );

      void queryClient.invalidateQueries({ queryKey: CONVERSATIONS_QUERY_KEY });
    });

    channel.listen(".lead.stage_changed", (payload: { lead: Lead }) => {
      queryClient.setQueryData(LEADS_QUERY_KEY, (current: { data: Lead[] } | undefined) => {
        if (!current) {
          return current;
        }

        return {
          data: current.data.map((lead) =>
            lead.id === payload.lead.id
              ? {
                  ...lead,
                  pipeline_stage_id: payload.lead.pipeline_stage_id,
                  pipeline_stage: payload.lead.pipeline_stage,
                }
              : lead,
          ),
        };
      });
    });

    channel.listen(
      ".conversation.updated",
      (payload: { conversation: Conversation }) => {
        queryClient.setQueryData(
          CONVERSATIONS_QUERY_KEY,
          (current: { data: Conversation[] } | undefined) => {
            if (!current) {
              return current;
            }

            return {
              data: current.data.map((conversation) =>
                conversation.id === payload.conversation.id
                  ? { ...conversation, ...payload.conversation }
                  : conversation,
              ),
            };
          },
        );
      },
    );

    return () => {
      channel.stopListening(".message.created");
      channel.stopListening(".lead.stage_changed");
      channel.stopListening(".conversation.updated");
      echo.leave(`company.${companyId}`);
    };
  }, [companyId, queryClient, status]);

  return children;
}
