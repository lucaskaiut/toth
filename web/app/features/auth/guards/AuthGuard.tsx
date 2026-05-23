import { Navigate, Outlet } from "react-router";
import { AuthLoadingScreen } from "~/features/auth/components/AuthLoadingScreen";
import { useAuth } from "~/features/auth/hooks/useAuth";

export function AuthGuard() {
  const { isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return <AuthLoadingScreen />;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return <Outlet />;
}
