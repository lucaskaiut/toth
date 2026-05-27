import { Navigate } from "react-router";
import { AppShell } from "~/features/layout/components/AppShell";
import { useAuthStore } from "~/stores/auth.store";
import { requiresWhatsappSetup } from "~/lib/company/status";

export function CompanyActiveGuard() {
  const user = useAuthStore((state) => state.user);

  if (requiresWhatsappSetup(user?.company)) {
    return <Navigate to="/setup/whatsapp" replace />;
  }

  return <AppShell />;
}
