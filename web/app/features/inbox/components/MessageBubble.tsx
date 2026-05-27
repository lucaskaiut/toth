import type { Message } from "~/types/crm";

type MessageBubbleProps = {
  message: Message;
};

const originLabels: Record<Message["origin"], string> = {
  customer: "Cliente",
  ai: "IA",
  user: "Atendente",
};

export function MessageBubble({ message }: MessageBubbleProps) {
  const isCustomer = message.origin === "customer";

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
        <p className="mt-1 text-[10px] opacity-70">
          {new Date(message.sent_at).toLocaleString("pt-BR")}
        </p>
      </div>
    </div>
  );
}
