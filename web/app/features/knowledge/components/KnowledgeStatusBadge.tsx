import type { KnowledgeSourceStatus } from "~/types/knowledge";

const labels: Record<KnowledgeSourceStatus, string> = {
  pending: "Pendente",
  indexing: "Indexando",
  indexed: "Indexado",
  error: "Erro",
};

const styles: Record<KnowledgeSourceStatus, string> = {
  pending: "bg-surface-muted text-muted shadow-xs",
  indexing: "bg-primary/15 text-primary shadow-xs",
  indexed: "bg-success-subtle text-success shadow-xs",
  error: "bg-danger-subtle text-danger shadow-xs",
};

type Props = {
  status: KnowledgeSourceStatus;
};

export function KnowledgeStatusBadge({ status }: Props) {
  return (
    <span
      className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${styles[status]}`}
    >
      {labels[status]}
    </span>
  );
}
