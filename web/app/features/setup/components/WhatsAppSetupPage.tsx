import { useQuery } from "@tanstack/react-query";
import { useEffect } from "react";
import { useNavigate } from "react-router";
import { Alert } from "~/components/ui/Alert";
import { Button } from "~/components/ui/Button";
import { whatsappSetupApi } from "~/lib/api/whatsapp-setup.api";
import { useAuthStore } from "~/stores/auth.store";
import type { AuthCompany } from "~/types/auth";

const POLL_INTERVAL_MS = 5000;

export function WhatsAppSetupPage() {
  const navigate = useNavigate();
  const user = useAuthStore((state) => state.user);
  const setUser = useAuthStore((state) => state.setUser);

  const connectQuery = useQuery({
    queryKey: ["whatsapp", "connect"],
    queryFn: () => whatsappSetupApi.connect(),
  });

  const statusQuery = useQuery({
    queryKey: ["whatsapp", "connection-state"],
    queryFn: () => whatsappSetupApi.connectionState(),
    refetchInterval: POLL_INTERVAL_MS,
    enabled: connectQuery.isSuccess,
  });

  useEffect(() => {
    if (!statusQuery.data?.data.connected) {
      return;
    }

    if (user) {
      const company: AuthCompany = {
        ...(user.company ?? {
          id: user.company_id,
          name: "",
          whatsapp: null,
        }),
        status: "active",
      };

      setUser({ ...user, company });
    }

    navigate("/kanban", { replace: true });
  }, [navigate, setUser, statusQuery.data?.data.connected, user]);

  const connectData = connectQuery.data?.data;
  const qrBase64 = connectData?.base64;
  const pairingCode = connectData?.pairing_code;
  const state = statusQuery.data?.data.state;
  const isConnected = statusQuery.data?.data.connected;

  const errorMessage =
    connectQuery.error instanceof Error
      ? connectQuery.error.message
      : statusQuery.error instanceof Error
        ? statusQuery.error.message
        : null;

  return (
    <div className="flex flex-col gap-6 rounded-xl border border-border bg-panel p-6 shadow-sm">
      <div className="flex flex-col gap-2">
        <h1 className="text-xl font-semibold text-foreground">
          Conectar WhatsApp
        </h1>
        <p className="text-sm text-muted">
          Sua instância já foi criada. Escaneie o QR Code no aplicativo WhatsApp
          para concluir a ativação do CRM.
        </p>
        {connectData?.instance_name ? (
          <p className="text-xs text-muted">
            Instância:{" "}
            <span className="font-mono">{connectData.instance_name}</span>
          </p>
        ) : null}
      </div>

      {errorMessage ? <Alert>{errorMessage}</Alert> : null}

      {connectQuery.isPending ? (
        <p className="text-sm text-muted">Gerando QR Code...</p>
      ) : null}

      {qrBase64 ? (
        <div className="flex justify-center">
          <img
            src={
              qrBase64.startsWith("data:")
                ? qrBase64
                : `data:image/png;base64,${qrBase64}`
            }
            alt="QR Code WhatsApp"
            className="h-64 w-64 rounded-lg border border-border bg-white p-2"
          />
        </div>
      ) : null}

      {pairingCode ? (
        <p className="text-center text-sm text-foreground">
          Código de pareamento:{" "}
          <span className="font-mono font-semibold">{pairingCode}</span>
        </p>
      ) : null}

      <div className="flex flex-col gap-2 rounded-lg bg-surface px-4 py-3 text-sm">
        <p className="font-medium text-foreground">Status da conexão</p>
        <p className="text-muted">
          {isConnected
            ? "Conectado. Redirecionando..."
            : state
              ? `Estado: ${state}`
              : "Aguardando leitura do QR Code..."}
        </p>
      </div>

      <Button
        type="button"
        variant="secondary"
        onClick={() => connectQuery.refetch()}
        isLoading={connectQuery.isFetching}
      >
        Atualizar QR Code
      </Button>
    </div>
  );
}
