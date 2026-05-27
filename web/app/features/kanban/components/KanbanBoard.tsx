import {
  DndContext,
  DragOverlay,
  PointerSensor,
  closestCorners,
  useSensor,
  useSensors,
  type DragEndEvent,
  type DragStartEvent,
} from "@dnd-kit/core";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useMemo, useState } from "react";
import { leadsApi } from "~/lib/api/leads.api";
import { pipelineApi } from "~/lib/api/pipeline.api";
import { LEADS_QUERY_KEY, PIPELINE_STAGES_QUERY_KEY } from "~/lib/crm/constants";
import { KanbanColumn } from "~/features/kanban/components/KanbanColumn";
import { LeadCard } from "~/features/kanban/components/LeadCard";
import type { Lead } from "~/types/crm";

export function KanbanBoard() {
  const queryClient = useQueryClient();
  const [activeLead, setActiveLead] = useState<Lead | null>(null);

  const stagesQuery = useQuery({
    queryKey: PIPELINE_STAGES_QUERY_KEY,
    queryFn: () => pipelineApi.listStages(),
  });

  const leadsQuery = useQuery({
    queryKey: LEADS_QUERY_KEY,
    queryFn: () => leadsApi.list(),
  });

  const moveMutation = useMutation({
    mutationFn: ({ leadId, stageId }: { leadId: number; stageId: number }) =>
      leadsApi.moveStage(leadId, stageId),
    onMutate: async ({ leadId, stageId }) => {
      await queryClient.cancelQueries({ queryKey: LEADS_QUERY_KEY });

      const previous = queryClient.getQueryData<{ data: Lead[] }>(LEADS_QUERY_KEY);

      queryClient.setQueryData<{ data: Lead[] }>(LEADS_QUERY_KEY, (current) => {
        if (!current) {
          return current;
        }

        return {
          data: current.data.map((lead) =>
            lead.id === leadId ? { ...lead, pipeline_stage_id: stageId } : lead,
          ),
        };
      });

      return { previous };
    },
    onError: (_error, _variables, context) => {
      if (context?.previous) {
        queryClient.setQueryData(LEADS_QUERY_KEY, context.previous);
      }
    },
    onSettled: () => {
      void queryClient.invalidateQueries({ queryKey: LEADS_QUERY_KEY });
    },
  });

  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 6 } }));

  const stages = stagesQuery.data?.data ?? [];
  const leads = leadsQuery.data?.data ?? [];

  const leadsByStage = useMemo(() => {
    const map = new Map<number, Lead[]>();

    for (const stage of stages) {
      map.set(stage.id, []);
    }

    for (const lead of leads) {
      const bucket = map.get(lead.pipeline_stage_id) ?? [];
      bucket.push(lead);
      map.set(lead.pipeline_stage_id, bucket);
    }

    return map;
  }, [leads, stages]);

  const handleDragStart = (event: DragStartEvent) => {
    const lead = leads.find((item) => item.id === event.active.id);
    setActiveLead(lead ?? null);
  };

  const handleDragEnd = (event: DragEndEvent) => {
    setActiveLead(null);

    const leadId = Number(event.active.id);
    const overId = event.over?.id;

    if (!overId) {
      return;
    }

    const targetStageId = stages.some((stage) => stage.id === overId)
      ? Number(overId)
      : leads.find((lead) => lead.id === overId)?.pipeline_stage_id;

    if (!targetStageId) {
      return;
    }

    const lead = leads.find((item) => item.id === leadId);

    if (!lead || lead.pipeline_stage_id === targetStageId) {
      return;
    }

    moveMutation.mutate({ leadId, stageId: targetStageId });
  };

  if (stagesQuery.isLoading || leadsQuery.isLoading) {
    return <p className="p-6 text-muted">Carregando Kanban...</p>;
  }

  return (
    <DndContext
      sensors={sensors}
      collisionDetection={closestCorners}
      onDragStart={handleDragStart}
      onDragEnd={handleDragEnd}
    >
      <div className="flex gap-4 overflow-x-auto p-6">
        {stages.map((stage) => (
          <KanbanColumn
            key={stage.id}
            stage={stage}
            leads={leadsByStage.get(stage.id) ?? []}
          />
        ))}
      </div>

      <DragOverlay>
        {activeLead ? (
          <div className="w-72">
            <LeadCard lead={activeLead} />
          </div>
        ) : null}
      </DragOverlay>
    </DndContext>
  );
}
