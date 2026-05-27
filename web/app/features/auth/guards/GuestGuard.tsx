import { Navigate, Outlet, useSearchParams } from "react-router";
import { AuthLoadingScreen } from "~/features/auth/components/AuthLoadingScreen";
import { useAuth } from "~/features/auth/hooks/useAuth";
import { resolvePostAuthPath } from "~/lib/company/status";
import { tokenStorage } from "~/lib/auth/token.storage";
import { useAuthStore } from "~/stores/auth.store";

export function GuestGuard() {
  const [searchParams] = useSearchParams();
  const { isAuthenticated, isLoading } = useAuth();
  const user = useAuthStore((state) => state.user);
  const hasToken = Boolean(tokenStorage.get());

  if (isLoading) {
    return <AuthLoadingScreen />;
  }

  if (isAuthenticated && hasToken) {
    const redirectTo =
      searchParams.get("redirectTo") || resolvePostAuthPath(user?.company);
    return <Navigate to={redirectTo} replace />;
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-surface px-4 py-12">
      <Outlet />
    </div>
  );
}
