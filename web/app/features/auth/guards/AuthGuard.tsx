import { Navigate, Outlet, useLocation } from "react-router";
import { AuthLoadingScreen } from "~/features/auth/components/AuthLoadingScreen";
import { useAuth } from "~/features/auth/hooks/useAuth";
import { buildLoginPath } from "~/lib/auth/login-redirect";
import { tokenStorage } from "~/lib/auth/token.storage";

export function AuthGuard({ children }: { children?: React.ReactNode }) {
  const location = useLocation();
  const { isAuthenticated, isLoading } = useAuth();
  const hasToken = Boolean(tokenStorage.get());

  if (isLoading) {
    return <AuthLoadingScreen />;
  }

  if (!isAuthenticated || !hasToken) {
    return (
      <Navigate
        to={buildLoginPath(location.pathname, location.search)}
        replace
      />
    );
  }

  return children ? <>{children}</> : <Outlet />;
}
