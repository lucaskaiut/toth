import { Button } from "~/components/ui/Button";
import { useAuth } from "~/features/auth/hooks/useAuth";

export function AppHeader() {
  const { user, logout } = useAuth();

  return (
    <header className="border-b border-border bg-surface-elevated">
      <div className="mx-auto flex h-14 max-w-5xl items-center justify-between px-4">
        <div className="flex items-center gap-2">
          <span className="text-sm font-semibold tracking-tight text-foreground">
            Toth CRM
          </span>
        </div>

        <div className="flex items-center gap-3">
          {user ? (
            <span className="hidden text-sm text-muted sm:inline">
              {user.name}
            </span>
          ) : null}
          <Button variant="secondary" onClick={logout}>
            Sair
          </Button>
        </div>
      </div>
    </header>
  );
}
