import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Button } from "~/components/ui/Button";
import { conversationsApi } from "~/lib/api/conversations.api";
import { CONVERSATIONS_QUERY_KEY } from "~/lib/crm/constants";
import type { Conversation, ConversationAttendanceStatus } from "~/types/crm";

type ConversationAttendanceControlsProps = {
  conversation: Conversation;
};

const statusOptions: {
  value: ConversationAttendanceStatus;
  label: string;
}[] = [
  { value: "ai_enabled", label: "Ativar IA" },
  { value: "handoff_to_human", label: "Humano assume" },
  { value: "waiting_human", label: "Aguardando humano" },
  { value: "closed", label: "Encerrar" },
];

export function ConversationAttendanceControls({
  conversation,
}: ConversationAttendanceControlsProps) {
  const queryClient = useQueryClient();

  const mutation = useMutation({
    mutationFn: (status: ConversationAttendanceStatus) =>
      conversationsApi.updateAttendanceStatus(conversation.id, status),
    onSuccess: (response) => {
      queryClient.setQueryData(CONVERSATIONS_QUERY_KEY, (current: { data: Conversation[] } | undefined) => {
        if (!current) {
          return current;
        }

        return {
          data: current.data.map((item) =>
            item.id === conversation.id ? { ...item, ...response.data } : item,
          ),
        };
      });
    },
  });

  return (
    <div className="mt-3 flex flex-wrap items-center gap-2">
      <span className="ui-chip">
        {conversation.attendance_status_label ?? conversation.attendance_status}
      </span>

      {statusOptions.map((option) => (
        <Button
          key={option.value}
          type="button"
          variant={conversation.attendance_status === option.value ? "primary" : "ghost"}
          className="h-8 px-2 text-xs"
          disabled={mutation.isPending || conversation.attendance_status === option.value}
          onClick={() => mutation.mutate(option.value)}
        >
          {option.label}
        </Button>
      ))}
    </div>
  );
}
