import type { Conversation } from "~/types/crm";

type ConversationListProps = {
  conversations: Conversation[];
  selectedId: number | null;
  onSelect: (conversation: Conversation) => void;
};

export function ConversationList({
  conversations,
  selectedId,
  onSelect,
}: ConversationListProps) {
  return (
    <aside className="flex w-80 shrink-0 flex-col border-r border-border bg-surface-elevated">
      <div className="border-b border-border px-4 py-4">
        <h2 className="font-semibold">Conversas</h2>
        <p className="text-sm text-muted">{conversations.length} ativas</p>
      </div>

      <div className="flex-1 overflow-y-auto">
        {conversations.map((conversation) => {
          const isActive = conversation.id === selectedId;

          return (
            <button
              key={conversation.id}
              type="button"
              onClick={() => onSelect(conversation)}
              className={[
                "w-full border-b border-border px-4 py-3 text-left transition-colors",
                isActive ? "bg-primary/10" : "hover:bg-surface",
              ].join(" ")}
            >
              <p className="font-medium">{conversation.lead?.name}</p>
              <p className="text-xs text-muted">{conversation.lead?.phone}</p>
              <p className="mt-1 text-[10px] uppercase tracking-wide text-muted">
                {conversation.attendance_status_label ?? conversation.attendance_status}
              </p>
              {conversation.summary ? (
                <p className="mt-1 line-clamp-2 text-xs text-muted">{conversation.summary}</p>
              ) : null}
            </button>
          );
        })}
      </div>
    </aside>
  );
}
