import type { Route } from "./+types/_app.inbox";
import { InboxLayout } from "~/features/inbox/components/InboxLayout";

export function meta({}: Route.MetaArgs) {
  return [{ title: "Atendimento — Toth CRM" }];
}

export default function InboxPage() {
  return (
    <div className="flex min-h-0 flex-1 flex-col">
      <header className="ui-page-header">
        <h1 className="text-xl font-semibold">Atendimento</h1>
        <p className="text-sm text-muted">Acompanhe e responda conversas em tempo real.</p>
      </header>
      <InboxLayout />
    </div>
  );
}
