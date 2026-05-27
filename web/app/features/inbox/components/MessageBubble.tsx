import type { Message } from "~/types/crm";

type MessageBubbleProps = {
  message: Message;
  onRetry?: () => void;
};

const originLabels: Record<Message["origin"], string> = {
  customer: "Cliente",
  ai: "IA",
  user: "Atendente",
};

export function MessageBubble({ message, onRetry }: MessageBubbleProps) {
  const isCustomer = message.origin === "customer";
  const statusIcon =
    message.origin === "user"
      ? message.client_status === "pending"
        ? { kind: "pending" as const, title: "Aguardando envio" }
        : message.client_status === "failed"
          ? { kind: "failed" as const, title: "Falha ao enviar. Clique para tentar novamente" }
          : { kind: "sent" as const, title: "Enviada" }
      : null;

  return (
    <div className={["flex", isCustomer ? "justify-start" : "justify-end"].join(" ")}>
      <div
        className={[
          "max-w-[75%] rounded-2xl px-4 py-2 text-sm",
          isCustomer
            ? "bg-surface-elevated text-foreground"
            : "bg-primary text-primary-foreground",
        ].join(" ")}
      >
        <p className="mb-1 text-[10px] uppercase tracking-wide opacity-70">
          {originLabels[message.origin]}
          {message.user ? ` · ${message.user.name}` : ""}
        </p>
        <p className="whitespace-pre-wrap">{message.content}</p>
        <div className="mt-1 flex items-center justify-between gap-3 text-[10px] opacity-70">
          <span>{new Date(message.sent_at).toLocaleString("pt-BR")}</span>
          {statusIcon ? (
            <span
              className={[
                "inline-flex items-center",
                statusIcon.kind === "failed" ? "cursor-pointer opacity-90 hover:opacity-100" : "",
              ].join(" ")}
              title={statusIcon.title}
              role={statusIcon.kind === "failed" ? "button" : undefined}
              tabIndex={statusIcon.kind === "failed" ? 0 : -1}
              onClick={() => {
                if (statusIcon.kind !== "failed") return;
                onRetry?.();
              }}
              onKeyDown={(event) => {
                if (statusIcon.kind !== "failed") return;
                if (event.key === "Enter" || event.key === " ") {
                  event.preventDefault();
                  onRetry?.();
                }
              }}
            >
              {statusIcon.kind === "pending" ? (
                <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" aria-hidden="true">
                  <path
                    d="M12 7v5l3 2"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  />
                  <path
                    d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                    stroke="currentColor"
                    strokeWidth="2"
                  />
                </svg>
              ) : statusIcon.kind === "failed" ? (
                <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" aria-hidden="true">
                  <path
                    d="M12 9v4"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                  />
                  <path
                    d="M12 17h.01"
                    stroke="currentColor"
                    strokeWidth="3"
                    strokeLinecap="round"
                  />
                  <path
                    d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinejoin="round"
                  />
                </svg>
              ) : (
                <svg viewBox="0 0 24 24" className="h-4 w-4" fill="none" aria-hidden="true">
                  <path
                    d="m20 6-11 11-5-5"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  />
                </svg>
              )}
            </span>
          ) : null}
        </div>
      </div>
    </div>
  );
}
