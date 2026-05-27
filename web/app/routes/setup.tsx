import { Outlet } from "react-router";
import { AuthGuard } from "~/features/auth/guards/AuthGuard";
import { WhatsappSetupGuard } from "~/features/auth/guards/WhatsappSetupGuard";

export default function SetupLayout() {
  return (
    <AuthGuard>
      <div className="flex min-h-screen items-center justify-center bg-surface px-4 py-12">
        <div className="w-full max-w-lg">
          <WhatsappSetupGuard />
        </div>
      </div>
    </AuthGuard>
  );
}
