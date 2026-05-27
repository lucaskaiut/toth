import type { Route } from "./+types/_app.kanban";
import { KanbanBoard } from "~/features/kanban/components/KanbanBoard";

export function meta({}: Route.MetaArgs) {
  return [{ title: "Kanban — Toth CRM" }];
}

export default function KanbanPage() {
  return (
    <div className="flex min-h-0 flex-1 flex-col">
      <header className="border-b border-border px-6 py-4">
        <h1 className="text-xl font-semibold">Kanban</h1>
        <p className="text-sm text-muted">Gerencie seus leads por estágio do funil.</p>
      </header>
      <KanbanBoard />
    </div>
  );
}
