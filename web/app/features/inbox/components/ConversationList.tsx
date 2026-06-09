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
    <aside className="ui-sidebar flex w-80 shrink-0 flex-col">
      <div className="px-4 py-4 shadow-sm">
        <h2 className="font-semibold">Conversas</h2>
        <p className="text-sm text-muted">{conversations.length} ativas</p>
      </div>

      <div className="flex-1 space-y-2 overflow-y-auto p-3">
        {conversations.map((conversation) => {
          const isActive = conversation.id === selectedId;

          return (
            <button
              key={conversation.id}
              type="button"
              onClick={() => onSelect(conversation)}
              className={[
                "w-full rounded-xl px-4 py-3 text-left transition-all",
                isActive
                  ? "bg-primary/12 text-foreground shadow-md"
                  : "bg-surface-elevated text-foreground shadow-sm hover:shadow-md",
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
