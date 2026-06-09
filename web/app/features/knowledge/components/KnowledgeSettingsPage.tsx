import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect, useMemo, useState } from "react";
import { Alert } from "~/components/ui/Alert";
import { Button } from "~/components/ui/Button";
import { FormField } from "~/components/ui/FormField";
import { Input } from "~/components/ui/Input";
import { Spinner } from "~/components/ui/Spinner";
import { KnowledgeStatusBadge } from "~/features/knowledge/components/KnowledgeStatusBadge";
import { knowledgeApi } from "~/lib/api/knowledge.api";
import { KNOWLEDGE_SOURCES_QUERY_KEY, KNOWLEDGE_STATS_QUERY_KEY } from "~/lib/crm/constants";
import type { KnowledgeSource, KnowledgeSourceType } from "~/types/knowledge";

const tabs = [
  { id: "company", label: "Empresa" },
  { id: "products", label: "Produtos" },
  { id: "faq", label: "FAQ" },
  { id: "policies", label: "Políticas" },
  { id: "scripts", label: "Scripts" },
  { id: "documents", label: "Documentos" },
  { id: "free", label: "Contexto livre" },
  { id: "indexing", label: "Indexação" },
] as const;

type TabId = (typeof tabs)[number]["id"];

const SAVE_TOAST = "Conteúdo salvo e enviado para indexação";

function useFeedback() {
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const showSuccess = (message: string = SAVE_TOAST) => {
    setSuccessMessage(message);
    setErrorMessage(null);
    window.setTimeout(() => setSuccessMessage(null), 2800);
  };

  const showError = (message: string) => {
    setErrorMessage(message);
    setSuccessMessage(null);
  };

  return { successMessage, errorMessage, showSuccess, showError };
}

function textareaClass(hasError = false) {
  return [hasError ? "ui-textarea ui-field-error" : "ui-textarea"].join(" ");
}

export function KnowledgeSettingsPage() {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState<TabId>("company");
  const [isReindexing, setIsReindexing] = useState(false);
  const feedback = useFeedback();

  const sourcesQuery = useQuery({
    queryKey: KNOWLEDGE_SOURCES_QUERY_KEY,
    queryFn: () => knowledgeApi.listSources(),
    refetchInterval: isReindexing || activeTab === "indexing" ? 3000 : false,
  });

  const statsQuery = useQuery({
    queryKey: KNOWLEDGE_STATS_QUERY_KEY,
    queryFn: () => knowledgeApi.stats(),
    refetchInterval: isReindexing || activeTab === "indexing" ? 3000 : false,
    retry: 1,
  });

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: KNOWLEDGE_SOURCES_QUERY_KEY });
    void queryClient.invalidateQueries({ queryKey: KNOWLEDGE_STATS_QUERY_KEY });
  };

  const sources = sourcesQuery.data?.data ?? [];

  const byType = useMemo(() => {
    const map: Partial<Record<KnowledgeSourceType, KnowledgeSource[]>> = {};
    for (const source of sources) {
      const list = map[source.type] ?? [];
      list.push(source);
      map[source.type] = list;
    }
    return map;
  }, [sources]);

  if (sourcesQuery.isLoading) {
    return (
      <div className="flex flex-1 items-center justify-center">
        <Spinner />
      </div>
    );
  }

  return (
    <div className="flex min-h-0 flex-1 flex-col">
      <header className="ui-page-header">
        <h1 className="text-xl font-semibold">Base de Conhecimento</h1>
        <p className="text-sm text-muted">
          Alimente a IA com informações da sua empresa. O conteúdo é indexado automaticamente.
        </p>
      </header>

      <div className="px-6 py-2 shadow-sm">
        <nav className="flex gap-1 overflow-x-auto py-2">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              type="button"
              onClick={() => setActiveTab(tab.id)}
              className={[
                "shrink-0 rounded-lg px-3 py-2 text-sm font-medium transition-colors",
                activeTab === tab.id
                  ? "bg-primary text-primary-foreground"
                  : "text-muted hover:bg-surface-muted hover:text-foreground",
              ].join(" ")}
            >
              {tab.label}
            </button>
          ))}
        </nav>
      </div>

      <div className="flex-1 overflow-y-auto p-6">
        {feedback.successMessage && (
          <div className="mb-4">
            <Alert variant="success">{feedback.successMessage}</Alert>
          </div>
        )}
        {feedback.errorMessage && (
          <div className="mb-4">
            <Alert variant="error">{feedback.errorMessage}</Alert>
          </div>
        )}

        {activeTab === "company" && (
          <CompanyTab
            source={byType.company?.[0]}
            onSaved={feedback.showSuccess}
            onError={feedback.showError}
            invalidate={invalidate}
          />
        )}
        {activeTab === "products" && (
          <CrudTab
            type="product"
            title="Produtos e Serviços"
            items={byType.product ?? []}
            onSaved={feedback.showSuccess}
            onError={feedback.showError}
            invalidate={invalidate}
          />
        )}
        {activeTab === "faq" && (
          <CrudTab
            type="faq"
            title="FAQ"
            items={byType.faq ?? []}
            onSaved={feedback.showSuccess}
            onError={feedback.showError}
            invalidate={invalidate}
          />
        )}
        {activeTab === "policies" && (
          <MarkdownTab
            type="policy"
            title="Políticas internas"
            items={byType.policy ?? []}
            onSaved={feedback.showSuccess}
            onError={feedback.showError}
            invalidate={invalidate}
          />
        )}
        {activeTab === "scripts" && (
          <MarkdownTab
            type="script"
            title="Scripts comerciais"
            items={byType.script ?? []}
            onSaved={feedback.showSuccess}
            onError={feedback.showError}
            invalidate={invalidate}
          />
        )}
        {activeTab === "documents" && (
          <DocumentsTab
            items={byType.document ?? []}
            onSaved={feedback.showSuccess}
            onError={feedback.showError}
            invalidate={invalidate}
          />
        )}
        {activeTab === "free" && (
          <FreeContextTab
            source={byType.free_context?.[0]}
            onSaved={feedback.showSuccess}
            onError={feedback.showError}
            invalidate={invalidate}
          />
        )}
        {activeTab === "indexing" && (
          <IndexingTab
            stats={statsQuery.data?.data}
            isLoading={statsQuery.isLoading}
            isReindexing={isReindexing}
            statsError={statsQuery.isError ? "Não foi possível carregar as estatísticas." : null}
            onSaved={feedback.showSuccess}
            onError={feedback.showError}
            invalidate={invalidate}
            onReindexStart={() => {
              setIsReindexing(true);
              window.setTimeout(() => setIsReindexing(false), 120_000);
            }}
          />
        )}
      </div>
    </div>
  );
}

type TabCallbacks = {
  onSaved: () => void;
  onError: (message: string) => void;
  invalidate: () => void;
};

function CompanyTab({
  source,
  onSaved,
  onError,
  invalidate,
}: TabCallbacks & { source?: KnowledgeSource }) {
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [segment, setSegment] = useState("");
  const [website, setWebsite] = useState("");

  useEffect(() => {
    const meta = (source?.metadata ?? {}) as Record<string, string>;
    setName(meta.name ?? source?.title ?? "");
    setDescription(source?.content ?? "");
    setSegment(meta.segment ?? "");
    setWebsite(meta.website ?? "");
  }, [source]);

  const saveMutation = useMutation({
    mutationFn: async () => {
      const content = [
        name && `Nome: ${name}`,
        segment && `Segmento: ${segment}`,
        website && `Site: ${website}`,
        description && `\n${description}`,
      ]
        .filter(Boolean)
        .join("\n");

      const payload = {
        type: "company" as const,
        title: name || "Informações da empresa",
        content,
        metadata: { name, segment, website },
      };

      if (source) {
        return knowledgeApi.updateSource(source.id, payload);
      }
      return knowledgeApi.createSource(payload);
    },
    onSuccess: () => {
      invalidate();
      onSaved();
    },
    onError: (error: Error) => onError(error.message),
  });

  return (
    <section className="mx-auto max-w-3xl space-y-4">
      <h2 className="text-lg font-semibold">Informações da empresa</h2>
      {source && (
        <div className="flex items-center gap-2">
          <KnowledgeStatusBadge status={source.status} />
        </div>
      )}
      <FormField id="company-name" label="Nome da empresa">
        <Input id="company-name" value={name} onChange={(e) => setName(e.target.value)} />
      </FormField>
      <FormField id="company-segment" label="Segmento">
        <Input id="company-segment" value={segment} onChange={(e) => setSegment(e.target.value)} />
      </FormField>
      <FormField id="company-website" label="Website">
        <Input id="company-website" value={website} onChange={(e) => setWebsite(e.target.value)} />
      </FormField>
      <FormField id="company-description" label="Descrição">
        <textarea
          className={textareaClass()}
          rows={8}
          value={description}
          onChange={(e) => setDescription(e.target.value)}
        />
      </FormField>
      <Button isLoading={saveMutation.isPending} onClick={() => saveMutation.mutate()}>
        Salvar
      </Button>
    </section>
  );
}

function CrudTab({
  type,
  title,
  items,
  onSaved,
  onError,
  invalidate,
}: TabCallbacks & {
  type: "product" | "faq";
  title: string;
  items: KnowledgeSource[];
}) {
  const [editingId, setEditingId] = useState<number | null>(null);
  const [formTitle, setFormTitle] = useState("");
  const [formContent, setFormContent] = useState("");

  const resetForm = () => {
    setEditingId(null);
    setFormTitle("");
    setFormContent("");
  };

  const saveMutation = useMutation({
    mutationFn: async () => {
      const payload = { type, title: formTitle, content: formContent };
      if (editingId) {
        return knowledgeApi.updateSource(editingId, payload);
      }
      return knowledgeApi.createSource(payload);
    },
    onSuccess: () => {
      resetForm();
      invalidate();
      onSaved();
    },
    onError: (error: Error) => onError(error.message),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => knowledgeApi.deleteSource(id),
    onSuccess: () => {
      invalidate();
      onSaved();
    },
    onError: (error: Error) => onError(error.message),
  });

  return (
    <section className="mx-auto max-w-3xl space-y-6">
      <h2 className="text-lg font-semibold">{title}</h2>

      <div className="space-y-3 rounded-xl bg-surface-elevated shadow-md p-4">
        <FormField id={`${type}-title`} label="Título">
          <Input id={`${type}-title`} value={formTitle} onChange={(e) => setFormTitle(e.target.value)} />
        </FormField>
        <FormField id={`${type}-content`} label="Conteúdo">
          <textarea
            id={`${type}-content`}
            className={textareaClass()}
            rows={6}
            value={formContent}
            onChange={(e) => setFormContent(e.target.value)}
          />
        </FormField>
        <div className="flex gap-2">
          <Button
            isLoading={saveMutation.isPending}
            onClick={() => saveMutation.mutate()}
            disabled={!formTitle.trim()}
          >
            {editingId ? "Atualizar" : "Adicionar"}
          </Button>
          {editingId && (
            <Button variant="ghost" onClick={resetForm}>
              Cancelar
            </Button>
          )}
        </div>
      </div>

      <ul className="space-y-3">
        {items.map((item) => (
          <li
            key={item.id}
            className="rounded-xl bg-surface-elevated shadow-md p-4"
          >
            <div className="mb-2 flex items-center justify-between gap-2">
              <h3 className="font-medium">{item.title}</h3>
              <KnowledgeStatusBadge status={item.status} />
            </div>
            <p className="line-clamp-3 text-sm text-muted">{item.content}</p>
            <div className="mt-3 flex gap-2">
              <Button
                variant="secondary"
                onClick={() => {
                  setEditingId(item.id);
                  setFormTitle(item.title);
                  setFormContent(item.content ?? "");
                }}
              >
                Editar
              </Button>
              <Button
                variant="ghost"
                onClick={() => deleteMutation.mutate(item.id)}
                isLoading={deleteMutation.isPending}
              >
                Excluir
              </Button>
            </div>
          </li>
        ))}
      </ul>
    </section>
  );
}

function MarkdownTab({
  type,
  title,
  items,
  onSaved,
  onError,
  invalidate,
}: TabCallbacks & {
  type: "policy" | "script";
  title: string;
  items: KnowledgeSource[];
}) {
  const [editingId, setEditingId] = useState<number | null>(null);
  const [formTitle, setFormTitle] = useState("");
  const [formContent, setFormContent] = useState("");

  const saveMutation = useMutation({
    mutationFn: async () => {
      const payload = { type, title: formTitle, content: formContent };
      if (editingId) {
        return knowledgeApi.updateSource(editingId, payload);
      }
      return knowledgeApi.createSource(payload);
    },
    onSuccess: () => {
      setEditingId(null);
      setFormTitle("");
      setFormContent("");
      invalidate();
      onSaved();
    },
    onError: (error: Error) => onError(error.message),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => knowledgeApi.deleteSource(id),
    onSuccess: () => {
      invalidate();
      onSaved();
    },
    onError: (error: Error) => onError(error.message),
  });

  return (
    <section className="mx-auto max-w-3xl space-y-6">
      <h2 className="text-lg font-semibold">{title}</h2>
      <p className="text-sm text-muted">Suporte a Markdown no conteúdo.</p>

      <div className="space-y-3 rounded-xl bg-surface-elevated shadow-md p-4">
        <FormField id={`${type}-md-title`} label="Título">
          <Input id={`${type}-md-title`} value={formTitle} onChange={(e) => setFormTitle(e.target.value)} />
        </FormField>
        <FormField id={`${type}-md-content`} label="Conteúdo (Markdown)">
          <textarea
            id={`${type}-md-content`}
            className={textareaClass()}
            rows={12}
            value={formContent}
            onChange={(e) => setFormContent(e.target.value)}
            placeholder="# Título&#10;&#10;Conteúdo em **markdown**..."
          />
        </FormField>
        <Button
          isLoading={saveMutation.isPending}
          onClick={() => saveMutation.mutate()}
          disabled={!formTitle.trim()}
        >
          {editingId ? "Atualizar" : "Adicionar"}
        </Button>
      </div>

      <ul className="space-y-3">
        {items.map((item) => (
          <li key={item.id} className="rounded-xl bg-surface-elevated p-4 shadow-sm">
            <div className="mb-2 flex items-center justify-between">
              <h3 className="font-medium">{item.title}</h3>
              <KnowledgeStatusBadge status={item.status} />
            </div>
            <pre className="max-h-40 overflow-auto rounded-lg bg-surface-muted p-3 text-xs">
              {item.content}
            </pre>
            <div className="mt-3 flex gap-2">
              <Button
                variant="secondary"
                onClick={() => {
                  setEditingId(item.id);
                  setFormTitle(item.title);
                  setFormContent(item.content ?? "");
                }}
              >
                Editar
              </Button>
              <Button variant="ghost" onClick={() => deleteMutation.mutate(item.id)}>
                Excluir
              </Button>
            </div>
          </li>
        ))}
      </ul>
    </section>
  );
}

function DocumentsTab({
  items,
  onSaved,
  onError,
  invalidate,
}: TabCallbacks & { items: KnowledgeSource[] }) {
  const [dragOver, setDragOver] = useState(false);

  const uploadMutation = useMutation({
    mutationFn: (file: File) => knowledgeApi.uploadDocument(file),
    onSuccess: () => {
      invalidate();
      onSaved();
    },
    onError: (error: Error) => onError(error.message),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => knowledgeApi.deleteSource(id),
    onSuccess: () => {
      invalidate();
      onSaved();
    },
    onError: (error: Error) => onError(error.message),
  });

  const handleFiles = (files: FileList | null) => {
    if (!files?.length) return;
    uploadMutation.mutate(files[0]);
  };

  return (
    <section className="mx-auto max-w-3xl space-y-6">
      <h2 className="text-lg font-semibold">Documentos</h2>

      <div
        onDragOver={(e) => {
          e.preventDefault();
          setDragOver(true);
        }}
        onDragLeave={() => setDragOver(false)}
        onDrop={(e) => {
          e.preventDefault();
          setDragOver(false);
          handleFiles(e.dataTransfer.files);
        }}
        className={[
          "flex flex-col items-center justify-center rounded-xl p-10 text-center transition-all",
          dragOver ? "bg-primary/8 shadow-lg ring-2 ring-primary/25" : "bg-surface-muted shadow-inner",
        ].join(" ")}
      >
        <p className="text-sm text-muted">Arraste arquivos ou clique para enviar</p>
        <p className="mt-1 text-xs text-muted">TXT, MD, PDF, DOC, DOCX</p>
        <label className="mt-4 cursor-pointer">
          <input
            type="file"
            className="hidden"
            accept=".txt,.md,.pdf,.doc,.docx"
            onChange={(e) => handleFiles(e.target.files)}
          />
          <Button variant="secondary" isLoading={uploadMutation.isPending} type="button">
            Selecionar arquivo
          </Button>
        </label>
      </div>

      <ul className="space-y-3">
        {items.map((item) => {
          const meta = item.metadata as Record<string, string> | null;
          return (
            <li
              key={item.id}
              className="flex items-center justify-between rounded-xl bg-surface-elevated p-4 shadow-sm"
            >
              <div>
                <p className="font-medium">{meta?.original_name ?? item.title}</p>
                <KnowledgeStatusBadge status={item.status} />
              </div>
              <Button variant="ghost" onClick={() => deleteMutation.mutate(item.id)}>
                Remover
              </Button>
            </li>
          );
        })}
      </ul>
    </section>
  );
}

function FreeContextTab({
  source,
  onSaved,
  onError,
  invalidate,
}: TabCallbacks & { source?: KnowledgeSource }) {
  const [content, setContent] = useState(source?.content ?? "");

  const saveMutation = useMutation({
    mutationFn: async () => {
      const payload = {
        type: "free_context" as const,
        title: "Contexto livre",
        content,
      };
      if (source) {
        return knowledgeApi.updateSource(source.id, payload);
      }
      return knowledgeApi.createSource(payload);
    },
    onSuccess: () => {
      invalidate();
      onSaved();
    },
    onError: (error: Error) => onError(error.message),
  });

  return (
    <section className="mx-auto max-w-3xl space-y-4">
      <h2 className="text-lg font-semibold">Contexto livre</h2>
      {source && <KnowledgeStatusBadge status={source.status} />}
      <textarea
        className={textareaClass()}
        rows={14}
        value={content}
        onChange={(e) => setContent(e.target.value)}
        placeholder="Informações adicionais para a IA..."
      />
      <Button isLoading={saveMutation.isPending} onClick={() => saveMutation.mutate()}>
        Salvar
      </Button>
    </section>
  );
}

function IndexingTab({
  stats,
  isLoading,
  isReindexing,
  statsError,
  onSaved,
  onError,
  invalidate,
  onReindexStart,
}: TabCallbacks & {
  stats?: {
    sources_total: number;
    chunks_total: number;
    vectors_total: number;
    last_indexed_at: string | null;
    by_status: Record<string, number>;
  };
  isLoading: boolean;
  isReindexing: boolean;
  statsError: string | null;
  onReindexStart: () => void;
}) {
  const reindexMutation = useMutation({
    mutationFn: () => knowledgeApi.reindexAll(),
    onSuccess: () => {
      onReindexStart();
      invalidate();
      onSaved();
    },
    onError: (error: Error) => onError(error.message),
  });

  if (isLoading) {
    return <Spinner />;
  }

  return (
    <section className="mx-auto max-w-3xl space-y-6">
      <h2 className="text-lg font-semibold">Indexação</h2>

      {statsError && (
        <Alert variant="error">{statsError}</Alert>
      )}

      {isReindexing && (
        <p className="text-sm text-muted">
          Reindexação em andamento… os números atualizam automaticamente a cada poucos segundos.
        </p>
      )}

      <div className="grid gap-4 sm:grid-cols-2">
        <StatCard label="Total de fontes" value={stats?.sources_total ?? 0} />
        <StatCard label="Total de chunks" value={stats?.chunks_total ?? 0} />
        <StatCard label="Vetores indexados" value={stats?.vectors_total ?? 0} />
        <StatCard
          label="Última indexação"
          value={
            stats?.last_indexed_at
              ? new Date(stats.last_indexed_at).toLocaleString("pt-BR")
              : "—"
          }
        />
      </div>

      {stats?.by_status && (
        <div className="rounded-xl bg-surface-elevated p-4 shadow-sm">
          <h3 className="mb-3 text-sm font-medium">Por status</h3>
          <ul className="space-y-2 text-sm">
            {Object.entries(stats.by_status).map(([status, count]) => (
              <li key={status} className="flex justify-between">
                <span className="capitalize text-muted">{status}</span>
                <span>{count}</span>
              </li>
            ))}
          </ul>
        </div>
      )}

      <Button
        variant="secondary"
        isLoading={reindexMutation.isPending}
        onClick={() => reindexMutation.mutate()}
      >
        Reindexar tudo
      </Button>
    </section>
  );
}

function StatCard({ label, value }: { label: string; value: string | number }) {
  return (
    <div className="rounded-xl bg-surface-elevated shadow-md p-4">
      <p className="text-sm text-muted">{label}</p>
      <p className="mt-1 text-2xl font-semibold">{value}</p>
    </div>
  );
}
