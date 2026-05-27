import { Navigate, Outlet } from "react-router";
import { useAuthStore } from "~/stores/auth.store";
import { isCompanyActive } from "~/lib/company/status";

export function WhatsappSetupGuard() {
  const user = useAuthStore((state) => state.user);

  if (isCompanyActive(user?.company)) {
    return <Navigate to="/kanban" replace />;
  }

  return <Outlet />;
}
