import { Outlet, useLocation, useNavigate } from "react-router";
import { Button } from "~/components/ui/Button";
import { useAuth } from "~/features/auth/hooks/useAuth";

const navItems = [
  { to: "/kanban", label: "Kanban" },
  { to: "/inbox", label: "Atendimento" },
  { to: "/settings/knowledge", label: "Base de Conhecimento" },
  { to: "/settings/pipeline", label: "Pipeline" },
  { to: "/configuracoes", label: "Configurações" },
] as const;

function isNavItemActive(pathname: string, to: string): boolean {
  return pathname === to;
}

export function AppShell() {
  const { user, logout } = useAuth();
  const location = useLocation();
  const navigate = useNavigate();

  return (
    <div className="flex h-screen max-h-screen overflow-hidden bg-surface text-foreground">
      <aside className="ui-sidebar flex w-64 shrink-0 flex-col p-4">
        <div className="mb-8">
          <p className="text-lg font-semibold tracking-tight">Toth CRM</p>
          <p className="text-sm text-muted">{user?.name}</p>
        </div>

        <nav className="flex flex-1 flex-col gap-1.5">
          {navItems.map((item) => {
            const isActive = isNavItemActive(location.pathname, item.to);

            return (
              <button
                key={item.to}
                type="button"
                aria-current={isActive ? "page" : undefined}
                onClick={() => navigate(item.to)}
                className={["ui-nav-link", isActive ? "ui-nav-link-active" : ""].join(" ")}
              >
                {item.label}
              </button>
            );
          })}
        </nav>

        <Button variant="ghost" onClick={() => logout()}>
          Sair
        </Button>
      </aside>

      <main className="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
        <Outlet />
      </main>
    </div>
  );
}
