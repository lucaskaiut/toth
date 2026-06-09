import { useDroppable } from "@dnd-kit/core";
import { SortableContext, verticalListSortingStrategy } from "@dnd-kit/sortable";
import { LeadCard } from "~/features/kanban/components/LeadCard";
import type { Lead, PipelineStage } from "~/types/crm";

type KanbanColumnProps = {
  stage: PipelineStage;
  leads: Lead[];
};

export function KanbanColumn({ stage, leads }: KanbanColumnProps) {
  const { setNodeRef, isOver } = useDroppable({ id: stage.id });

  return (
    <section
      ref={setNodeRef}
      className={[
        "flex min-h-[70vh] w-72 shrink-0 flex-col rounded-xl bg-surface-inset p-3 shadow-md",
        isOver ? "shadow-lg ring-2 ring-primary/30" : "",
      ].join(" ")}
    >
      <header className="mb-3 flex items-center justify-between">
        <h2 className="font-semibold">{stage.name}</h2>
        <span className="ui-chip">{leads.length}</span>
      </header>

      <SortableContext items={leads.map((lead) => lead.id)} strategy={verticalListSortingStrategy}>
        <div className="flex flex-1 flex-col gap-2 overflow-y-auto">
          {leads.map((lead) => (
            <LeadCard key={lead.id} lead={lead} />
          ))}
        </div>
      </SortableContext>
    </section>
  );
}
