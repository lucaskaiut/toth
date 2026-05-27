import { useSortable } from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
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
        "cursor-grab rounded-lg border border-border bg-surface-elevated p-3 shadow-sm active:cursor-grabbing",
        isDragging ? "opacity-60 ring-2 ring-primary" : "",
      ].join(" ")}
    >
      <h3 className="font-medium">{lead.name}</h3>
      <p className="mt-1 text-xs text-muted">{lead.phone}</p>
      {lead.email ? <p className="mt-1 truncate text-xs text-muted">{lead.email}</p> : null}
    </article>
  );
}
