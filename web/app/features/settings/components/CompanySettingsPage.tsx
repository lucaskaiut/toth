import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect, useMemo, useState } from "react";
import { Alert } from "~/components/ui/Alert";
import { Button } from "~/components/ui/Button";
import { FormField } from "~/components/ui/FormField";
import { Input } from "~/components/ui/Input";
import { Spinner } from "~/components/ui/Spinner";
import { companyConfigsApi, type CompanyConfigType } from "~/lib/api/company-configs.api";
import { COMPANY_CONFIGS_QUERY_KEY } from "~/lib/crm/constants";
import type { CompanyConfig } from "~/types/crm";

type ConfigRow = {
  key: string;
  type: CompanyConfigType;
  value: string;
};

const typeOptions: CompanyConfigType[] = ["string", "int", "bool", "json"];

function normalizeRows(configs: CompanyConfig[]): ConfigRow[] {
  return configs
    .map((cfg) => ({
      key: cfg.key,
      type: (cfg.type as CompanyConfigType) ?? "string",
      value: cfg.value ?? "",
    }))
    .sort((a, b) => a.key.localeCompare(b.key));
}

function upsertRow(rows: ConfigRow[], key: string, patch: Partial<ConfigRow>): ConfigRow[] {
  const index = rows.findIndex((r) => r.key === key);
  if (index < 0) {
    const nextRow: ConfigRow = {
      key,
      type: patch.type ?? "string",
      value: patch.value ?? "",
    };
    return [...rows, nextRow].sort((a, b) => a.key.localeCompare(b.key));
  }
  const next = [...rows];
  next[index] = { ...next[index], ...patch };
  return next;
}

function serializeValue(type: CompanyConfigType, raw: string): unknown {
  if (raw === "") return null;

  switch (type) {
    case "int": {
      const n = Number(raw);
      return Number.isFinite(n) ? Math.trunc(n) : raw;
    }
    case "bool": {
      const v = raw.trim().toLowerCase();
      if (["1", "true", "yes", "sim", "on"].includes(v)) return true;
      if (["0", "false", "no", "não", "nao", "off"].includes(v)) return false;
      return raw;
    }
    case "json": {
      try {
        return JSON.parse(raw);
      } catch {
        return raw;
      }
    }
    default:
      return raw;
  }
}

export function CompanySettingsPage() {
  const queryClient = useQueryClient();
  const [rows, setRows] = useState<ConfigRow[]>([]);
  const [newKey, setNewKey] = useState("");
  const [newType, setNewType] = useState<CompanyConfigType>("string");
  const [newValue, setNewValue] = useState("");
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const query = useQuery({
    queryKey: COMPANY_CONFIGS_QUERY_KEY,
    queryFn: () => companyConfigsApi.list(),
  });

  useEffect(() => {
    if (!query.data?.data) return;
    setRows(normalizeRows(query.data.data));
  }, [query.data?.data]);

  const aiApiKey = useMemo(() => rows.find((r) => r.key === "ai.api_key")?.value ?? "", [rows]);
  const aiModel = useMemo(() => rows.find((r) => r.key === "ai.model")?.value ?? "", [rows]);
  const aiSystemPrompt = useMemo(
    () => rows.find((r) => r.key === "ai.system_prompt")?.value ?? "",
    [rows],
  );

  const saveMutation = useMutation({
    mutationFn: async () => {
      const payload = {
        configs: rows
          .filter((r) => r.key.trim() !== "")
          .map((r) => ({
            key: r.key.trim(),
            type: r.type,
            value: serializeValue(r.type, r.value),
          })),
      };

      return await companyConfigsApi.update(payload);
    },
    onSuccess: (response) => {
      queryClient.setQueryData(COMPANY_CONFIGS_QUERY_KEY, response);
      setRows(normalizeRows(response.data));
      setSuccessMessage("Configurações salvas com sucesso.");
      setErrorMessage(null);
      window.setTimeout(() => setSuccessMessage(null), 2500);
    },
    onError: (error) => {
      setSuccessMessage(null);
      setErrorMessage(error instanceof Error ? error.message : "Não foi possível salvar as configurações.");
    },
  });

  if (query.isLoading) {
    return (
      <div className="flex flex-1 items-center justify-center">
        <Spinner />
      </div>
    );
  }

  return (
    <div className="flex min-h-0 flex-1 flex-col">
      <header className="border-b border-border px-6 py-4">
        <h1 className="text-xl font-semibold">Configurações</h1>
        <p className="text-sm text-muted">
          Configure integrações e parâmetros da sua empresa.
        </p>
      </header>

      <div className="min-h-0 flex-1 overflow-y-auto p-6">
        <div className="mx-auto flex w-full max-w-3xl flex-col gap-6">
          {successMessage ? <Alert variant="success">{successMessage}</Alert> : null}
          {errorMessage ? <Alert variant="error">{errorMessage}</Alert> : null}

          <section className="rounded-xl border border-border bg-panel p-5">
            <div className="mb-4">
              <h2 className="text-base font-semibold">IA</h2>
              <p className="text-sm text-muted">
                Configurações usadas para respostas automáticas.
              </p>
            </div>

            <div className="flex flex-col gap-4">
              <FormField
                id="ai.api_key"
                label="API Key"
                hint="Chave da IA (OpenAI-compatible)."
              >
                <Input
                  id="ai.api_key"
                  type="password"
                  value={aiApiKey}
                  onChange={(e) =>
                    setRows((current) =>
                      upsertRow(current, "ai.api_key", {
                        key: "ai.api_key",
                        type: "string",
                        value: e.target.value,
                      }),
                    )
                  }
                />
              </FormField>

              <FormField id="ai.model" label="Modelo" hint="Ex.: gpt-4o-mini">
                <Input
                  id="ai.model"
                  value={aiModel}
                  onChange={(e) =>
                    setRows((current) =>
                      upsertRow(current, "ai.model", {
                        key: "ai.model",
                        type: "string",
                        value: e.target.value,
                      }),
                    )
                  }
                />
              </FormField>

              <FormField
                id="ai.system_prompt"
                label="System prompt"
                hint="Instruções gerais para a IA (tom, regras, objetivos)."
              >
                <textarea
                  id="ai.system_prompt"
                  className="min-h-32 w-full resize-y rounded-lg bg-surface-elevated px-3 py-2 text-sm text-foreground ring-1 ring-border transition-colors placeholder:text-muted/70 focus:outline-none focus:ring-2 focus:ring-primary/25"
                  value={aiSystemPrompt}
                  onChange={(e) =>
                    setRows((current) =>
                      upsertRow(current, "ai.system_prompt", {
                        key: "ai.system_prompt",
                        type: "string",
                        value: e.target.value,
                      }),
                    )
                  }
                />
              </FormField>
            </div>
          </section>

          <section className="rounded-xl border border-border bg-panel p-5">
            <div className="mb-4 flex items-start justify-between gap-4">
              <div>
                <h2 className="text-base font-semibold">Avançado</h2>
                <p className="text-sm text-muted">
                  Edite chaves livres. Use com cuidado.
                </p>
              </div>
              <Button
                type="button"
                variant="secondary"
                onClick={() => {
                  setRows((current) => [...current, { key: "", type: "string", value: "" }]);
                }}
              >
                Adicionar
              </Button>
            </div>

            <div className="flex flex-col gap-3">
              {rows.length === 0 ? (
                <p className="text-sm text-muted">Nenhuma configuração cadastrada.</p>
              ) : (
                rows.map((row, index) => (
                  <div
                    key={`${row.key}:${index}`}
                    className="grid grid-cols-12 gap-2"
                  >
                    <div className="col-span-5">
                      <Input
                        value={row.key}
                        onChange={(e) =>
                          setRows((current) => {
                            const next = [...current];
                            next[index] = { ...next[index], key: e.target.value };
                            return next;
                          })
                        }
                        placeholder="chave.exemplo"
                      />
                    </div>

                    <div className="col-span-2">
                      <select
                        value={row.type}
                        onChange={(e) =>
                          setRows((current) => {
                            const next = [...current];
                            next[index] = {
                              ...next[index],
                              type: e.target.value as CompanyConfigType,
                            };
                            return next;
                          })
                        }
                        className="h-10 w-full rounded-lg bg-surface-elevated px-2 text-sm text-foreground ring-1 ring-border focus:outline-none focus:ring-2 focus:ring-primary/25"
                      >
                        {typeOptions.map((t) => (
                          <option key={t} value={t}>
                            {t}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div className="col-span-4">
                      <Input
                        value={row.value}
                        onChange={(e) =>
                          setRows((current) => {
                            const next = [...current];
                            next[index] = { ...next[index], value: e.target.value };
                            return next;
                          })
                        }
                        placeholder="valor"
                      />
                    </div>

                    <div className="col-span-1 flex justify-end">
                      <button
                        type="button"
                        className="h-10 rounded-lg px-2 text-sm text-muted hover:bg-surface-elevated hover:text-foreground"
                        title="Remover"
                        onClick={() =>
                          setRows((current) => current.filter((_, i) => i !== index))
                        }
                      >
                        ×
                      </button>
                    </div>
                  </div>
                ))
              )}

              <div className="mt-3 grid grid-cols-12 gap-2 border-t border-border pt-3">
                <div className="col-span-5">
                  <Input
                    value={newKey}
                    onChange={(e) => setNewKey(e.target.value)}
                    placeholder="nova.chave"
                  />
                </div>
                <div className="col-span-2">
                  <select
                    value={newType}
                    onChange={(e) => setNewType(e.target.value as CompanyConfigType)}
                    className="h-10 w-full rounded-lg bg-surface-elevated px-2 text-sm text-foreground ring-1 ring-border focus:outline-none focus:ring-2 focus:ring-primary/25"
                  >
                    {typeOptions.map((t) => (
                      <option key={t} value={t}>
                        {t}
                      </option>
                    ))}
                  </select>
                </div>
                <div className="col-span-4">
                  <Input
                    value={newValue}
                    onChange={(e) => setNewValue(e.target.value)}
                    placeholder="valor"
                  />
                </div>
                <div className="col-span-1 flex justify-end">
                  <button
                    type="button"
                    className="h-10 rounded-lg px-2 text-sm text-muted hover:bg-surface-elevated hover:text-foreground"
                    title="Adicionar"
                    onClick={() => {
                      if (!newKey.trim()) return;
                      setRows((current) => [
                        ...current,
                        { key: newKey.trim(), type: newType, value: newValue },
                      ]);
                      setNewKey("");
                      setNewType("string");
                      setNewValue("");
                    }}
                  >
                    +
                  </button>
                </div>
              </div>
            </div>
          </section>

          <div className="flex items-center justify-end gap-2">
            <Button
              type="button"
              variant="secondary"
              onClick={() => void query.refetch()}
              disabled={saveMutation.isPending}
            >
              Recarregar
            </Button>
            <Button
              type="button"
              onClick={() => {
                setSuccessMessage(null);
                setErrorMessage(null);
                saveMutation.mutate();
              }}
              isLoading={saveMutation.isPending}
            >
              Salvar
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}

