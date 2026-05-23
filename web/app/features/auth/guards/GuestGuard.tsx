import { Navigate, Outlet } from "react-router";
import { AuthLoadingScreen } from "~/features/auth/components/AuthLoadingScreen";
import { useAuth } from "~/features/auth/hooks/useAuth";

export function GuestGuard() {
  const { isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return <AuthLoadingScreen />;
  }

  if (isAuthenticated) {
    return <Navigate to="/" replace />;
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-surface px-4 py-12">
      <Outlet />
    </div>
  );
}
