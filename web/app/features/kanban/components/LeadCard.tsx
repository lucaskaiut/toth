import { useSortable } from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { Link } from "react-router";
import { inboxConversationPath } from "~/lib/crm/constants";
import type { Lead } from "~/types/crm";

type LeadCardProps = {
  lead: Lead;
};

export function LeadCard({ lead }: LeadCardProps) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } =
    useSortable({ id: lead.id });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  return (
    <article
      ref={setNodeRef}
      style={style}
      {...attributes}
      {...listeners}
      className={[
        "cursor-grab rounded-lg bg-surface-elevated p-3 shadow-sm transition-shadow active:cursor-grabbing hover:shadow-md",
        isDragging ? "opacity-70 shadow-lg ring-2 ring-primary/30" : "",
      ].join(" ")}
    >
      <h3 className="font-medium">{lead.name}</h3>
      <p className="mt-1 text-xs text-muted">{lead.phone}</p>
      {lead.email ? <p className="mt-1 truncate text-xs text-muted">{lead.email}</p> : null}

      {lead.conversation_id ? (
        <Link
          to={inboxConversationPath(lead.conversation_id)}
          onPointerDown={(event) => event.stopPropagation()}
          onClick={(event) => event.stopPropagation()}
          className="mt-2 inline-flex text-xs font-medium text-primary hover:text-primary-hover hover:underline"
        >
          Ver conversa
        </Link>
      ) : null}
    </article>
  );
}
