import type { Route } from "./+types/_app.settings.pipeline";
import { PipelineSettingsPage } from "~/features/pipeline/components/PipelineSettingsPage";

export function meta({}: Route.MetaArgs) {
  return [{ title: "Pipeline Comercial — Toth CRM" }];
}

export default function PipelineSettingsRoute() {
  return <PipelineSettingsPage />;
}
