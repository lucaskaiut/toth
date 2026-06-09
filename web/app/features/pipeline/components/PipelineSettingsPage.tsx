import {
  DndContext,
  PointerSensor,
  closestCenter,
  useSensor,
  useSensors,
  type DragEndEvent,
} from "@dnd-kit/core";
import {
  SortableContext,
  arrayMove,
  useSortable,
  verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useMemo, useState } from "react";
import { Alert } from "~/components/ui/Alert";
import { Button } from "~/components/ui/Button";
import { FormField } from "~/components/ui/FormField";
import { Input } from "~/components/ui/Input";
import { Spinner } from "~/components/ui/Spinner";
import { pipelineApi } from "~/lib/api/pipeline.api";
import { LEADS_QUERY_KEY, PIPELINE_STAGES_QUERY_KEY } from "~/lib/crm/constants";
import type { PipelineStage } from "~/types/crm";

type StageFormState = {
  name: string;
  description: string;
  ai_instruction: string;
};

const emptyForm: StageFormState = {
  name: "",
  description: "",
  ai_instruction: "",
};

function slugPreview(name: string): string {
  return name
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "_")
    .replace(/^_|_$/g, "");
}

function textareaClass(hasError = false) {
  return [hasError ? "ui-textarea ui-field-error" : "ui-textarea"].join(" ");
}

function SortableStageRow({
  stage,
  onEdit,
  onDelete,
  isDeleting,
}: {
  stage: PipelineStage;
  onEdit: (stage: PipelineStage) => void;
  onDelete: (stage: PipelineStage) => void;
  isDeleting: boolean;
}) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: stage.id,
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={[
        "flex items-start gap-3 rounded-xl bg-surface-elevated p-4 shadow-md",
        isDragging ? "opacity-70 shadow-lg" : "",
      ].join(" ")}
    >
      <button
        type="button"
        className="mt-1 cursor-grab touch-none text-muted hover:text-foreground active:cursor-grabbing"
        aria-label={`Reordenar ${stage.name}`}
        {...attributes}
        {...listeners}
      >
        ⠿
      </button>

      <div className="min-w-0 flex-1">
        <div className="flex flex-wrap items-center gap-2">
          <h3 className="font-medium">{stage.name}</h3>
          <span className="ui-chip font-mono normal-case">
            {stage.slug}
          </span>
        </div>
        <p className="mt-1 text-sm text-muted">{stage.description}</p>
        {stage.ai_instruction ? (
          <p className="mt-2 text-sm">
            <span className="font-medium text-foreground">IA: </span>
            <span className="text-muted">{stage.ai_instruction}</span>
          </p>
        ) : null}
      </div>

      <div className="flex shrink-0 gap-2">
        <Button variant="ghost" className="h-8 px-3 text-xs" onClick={() => onEdit(stage)}>
          Editar
        </Button>
        <Button
          variant="ghost"
          className="h-8 px-3 text-xs"
          disabled={isDeleting}
          onClick={() => onDelete(stage)}
        >
          Excluir
        </Button>
      </div>
    </div>
  );
}

export function PipelineSettingsPage() {
  const queryClient = useQueryClient();
  const [form, setForm] = useState<StageFormState>(emptyForm);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const stagesQuery = useQuery({
    queryKey: PIPELINE_STAGES_QUERY_KEY,
    queryFn: () => pipelineApi.listStages(),
  });

  const stages = stagesQuery.data?.data ?? [];
  const stageIds = useMemo(() => stages.map((stage) => stage.id), [stages]);

  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 6 } }));

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: PIPELINE_STAGES_QUERY_KEY });
    void queryClient.invalidateQueries({ queryKey: LEADS_QUERY_KEY });
  };

  const showSuccess = (message: string) => {
    setSuccessMessage(message);
    setErrorMessage(null);
    window.setTimeout(() => setSuccessMessage(null), 2800);
  };

  const showError = (message: string) => {
    setErrorMessage(message);
    setSuccessMessage(null);
  };

  const createMutation = useMutation({
    mutationFn: () =>
      pipelineApi.create({
        name: form.name.trim(),
        description: form.description.trim(),
        ai_instruction: form.ai_instruction.trim() || null,
      }),
    onSuccess: () => {
      invalidate();
      setForm(emptyForm);
      setIsFormOpen(false);
      showSuccess("Estágio criado com sucesso.");
    },
    onError: (error: Error) => showError(error.message),
  });

  const updateMutation = useMutation({
    mutationFn: () => {
      if (editingId === null) {
        throw new Error("Estágio não selecionado.");
      }

      return pipelineApi.update(editingId, {
        name: form.name.trim(),
        description: form.description.trim(),
        ai_instruction: form.ai_instruction.trim() || null,
      });
    },
    onSuccess: () => {
      invalidate();
      setForm(emptyForm);
      setEditingId(null);
      setIsFormOpen(false);
      showSuccess("Estágio atualizado com sucesso.");
    },
    onError: (error: Error) => showError(error.message),
  });

  const deleteMutation = useMutation({
    mutationFn: (stageId: number) => pipelineApi.delete(stageId),
    onSuccess: () => {
      invalidate();
      showSuccess("Estágio excluído com sucesso.");
    },
    onError: (error: Error) => showError(error.message),
  });

  const reorderMutation = useMutation({
    mutationFn: (orderedIds: number[]) => pipelineApi.reorder(orderedIds),
    onMutate: async (orderedIds) => {
      await queryClient.cancelQueries({ queryKey: PIPELINE_STAGES_QUERY_KEY });
      const previous = queryClient.getQueryData<{ data: PipelineStage[] }>(PIPELINE_STAGES_QUERY_KEY);

      queryClient.setQueryData<{ data: PipelineStage[] }>(PIPELINE_STAGES_QUERY_KEY, (current) => {
        if (!current) {
          return current;
        }

        const map = new Map(current.data.map((stage) => [stage.id, stage]));

        return {
          data: orderedIds
            .map((id, index) => {
              const stage = map.get(id);
              return stage ? { ...stage, position: index } : null;
            })
            .filter((stage): stage is PipelineStage => stage !== null),
        };
      });

      return { previous };
    },
    onError: (error: Error, _variables, context) => {
      if (context?.previous) {
        queryClient.setQueryData(PIPELINE_STAGES_QUERY_KEY, context.previous);
      }
      showError(error.message);
    },
    onSettled: () => {
      void queryClient.invalidateQueries({ queryKey: PIPELINE_STAGES_QUERY_KEY });
      void queryClient.invalidateQueries({ queryKey: LEADS_QUERY_KEY });
    },
  });

  const openCreateForm = () => {
    setEditingId(null);
    setForm(emptyForm);
    setIsFormOpen(true);
  };

  const openEditForm = (stage: PipelineStage) => {
    setEditingId(stage.id);
    setForm({
      name: stage.name,
      description: stage.description,
      ai_instruction: stage.ai_instruction ?? "",
    });
    setIsFormOpen(true);
  };

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();

    if (!form.name.trim() || !form.description.trim()) {
      showError("Nome e descrição são obrigatórios.");
      return;
    }

    if (editingId === null) {
      createMutation.mutate();
      return;
    }

    updateMutation.mutate();
  };

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;

    if (!over || active.id === over.id) {
      return;
    }

    const oldIndex = stages.findIndex((stage) => stage.id === active.id);
    const newIndex = stages.findIndex((stage) => stage.id === over.id);

    if (oldIndex < 0 || newIndex < 0) {
      return;
    }

    const reordered = arrayMove(stages, oldIndex, newIndex);
    reorderMutation.mutate(reordered.map((stage) => stage.id));
  };

  const handleDelete = (stage: PipelineStage) => {
    const confirmed = window.confirm(
      `Excluir o estágio "${stage.name}"? Só é possível excluir estágios sem leads vinculados.`,
    );

    if (confirmed) {
      deleteMutation.mutate(stage.id);
    }
  };

  const isSaving = createMutation.isPending || updateMutation.isPending;
  const previewSlug = editingId
    ? stages.find((stage) => stage.id === editingId)?.slug
    : slugPreview(form.name) || "stage";

  if (stagesQuery.isLoading) {
    return (
      <div className="flex flex-1 items-center justify-center">
        <Spinner />
      </div>
    );
  }

  return (
    <div className="flex min-h-0 flex-1 flex-col overflow-y-auto">
      <header className="ui-page-header">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-xl font-semibold">Pipeline Comercial</h1>
            <p className="text-sm text-muted">
              Configure os estágios do funil. A IA usa a descrição e as instruções para decidir
              movimentações automaticamente.
            </p>
          </div>
          <Button onClick={openCreateForm}>Novo estágio</Button>
        </div>
      </header>

      <div className="space-y-4 px-6 py-4">
        {successMessage ? <Alert variant="success">{successMessage}</Alert> : null}
        {errorMessage ? <Alert variant="error">{errorMessage}</Alert> : null}

        {isFormOpen ? (
          <form
            onSubmit={handleSubmit}
            className="ui-panel space-y-4 p-4"
          >
            <h2 className="font-medium">{editingId ? "Editar estágio" : "Novo estágio"}</h2>

            <FormField id="stage-name" label="Nome">
              <Input
                id="stage-name"
                value={form.name}
                onChange={(event) => setForm((current) => ({ ...current, name: event.target.value }))}
                placeholder="Ex.: Proposta enviada"
              />
            </FormField>

            <p className="text-sm text-muted">
              Slug {editingId ? "(imutável)" : "(gerado automaticamente)"}:{" "}
              <code className="rounded bg-surface px-1.5 py-0.5 font-mono text-xs">{previewSlug}</code>
            </p>

            <FormField id="stage-description" label="Descrição">
              <textarea
                id="stage-description"
                rows={3}
                className={textareaClass()}
                value={form.description}
                onChange={(event) =>
                  setForm((current) => ({ ...current, description: event.target.value }))
                }
                placeholder="Descreva o significado deste estágio no funil."
              />
            </FormField>

            <FormField
              id="stage-ai-instruction"
              label="Instrução para IA"
              hint="Opcional. Critério explícito para o LLM decidir quando usar este estágio."
            >
              <textarea
                id="stage-ai-instruction"
                rows={3}
                className={textareaClass()}
                value={form.ai_instruction}
                onChange={(event) =>
                  setForm((current) => ({ ...current, ai_instruction: event.target.value }))
                }
                placeholder="Ex.: Use quando o cliente solicitar orçamento ou condições comerciais."
              />
            </FormField>

            <div className="flex gap-2">
              <Button type="submit" disabled={isSaving}>
                {isSaving ? "Salvando..." : editingId ? "Salvar alterações" : "Criar estágio"}
              </Button>
              <Button
                type="button"
                variant="ghost"
                onClick={() => {
                  setIsFormOpen(false);
                  setEditingId(null);
                  setForm(emptyForm);
                }}
              >
                Cancelar
              </Button>
            </div>
          </form>
        ) : null}

        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
          <SortableContext items={stageIds} strategy={verticalListSortingStrategy}>
            <div className="space-y-3">
              {stages.map((stage) => (
                <SortableStageRow
                  key={stage.id}
                  stage={stage}
                  onEdit={openEditForm}
                  onDelete={handleDelete}
                  isDeleting={deleteMutation.isPending}
                />
              ))}
            </div>
          </SortableContext>
        </DndContext>

        {stages.length === 0 ? (
          <p className="text-sm text-muted">Nenhum estágio cadastrado.</p>
        ) : null}
      </div>
    </div>
  );
}
