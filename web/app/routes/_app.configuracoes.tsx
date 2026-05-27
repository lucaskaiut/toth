import type { Route } from "./+types/_app.configuracoes";
import { CompanySettingsPage } from "~/features/settings/components/CompanySettingsPage";

export function meta({}: Route.MetaArgs) {
  return [{ title: "Configurações — Toth CRM" }];
}

export default function SettingsRoute() {
  return <CompanySettingsPage />;
}

