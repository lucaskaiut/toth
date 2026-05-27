import { AuthGuard } from "~/features/auth/guards/AuthGuard";
import { CompanyActiveGuard } from "~/features/auth/guards/CompanyActiveGuard";
import { AppShell } from "~/features/layout/components/AppShell";

export default function AppLayout() {
  return (
    <AuthGuard>
      <CompanyActiveGuard />
    </AuthGuard>
  );
}
