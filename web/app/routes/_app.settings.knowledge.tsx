import type { Route } from "./+types/_app.settings.knowledge";
import { KnowledgeSettingsPage } from "~/features/knowledge/components/KnowledgeSettingsPage";

export function meta({}: Route.MetaArgs) {
  return [{ title: "Base de Conhecimento — Toth CRM" }];
}

export default function KnowledgeSettingsRoute() {
  return <KnowledgeSettingsPage />;
}
