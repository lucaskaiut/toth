import { NavLink, Outlet } from "react-router";
import { Button } from "~/components/ui/Button";
import { useAuth } from "~/features/auth/hooks/useAuth";

const navItems = [
  { to: "/kanban", label: "Kanban" },
  { to: "/inbox", label: "Atendimento" },
];

export function AppShell() {
  const { user, logout } = useAuth();

  return (
    <div className="flex min-h-screen bg-surface text-foreground">
      <aside className="flex w-64 shrink-0 flex-col border-r border-border bg-surface-elevated p-4">
        <div className="mb-8">
          <p className="text-lg font-semibold">Toth CRM</p>
          <p className="text-sm text-muted">{user?.name}</p>
        </div>

        <nav className="flex flex-1 flex-col gap-1">
          {navItems.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              className={({ isActive }) =>
                [
                  "rounded-lg px-3 py-2 text-sm font-medium transition-colors",
                  isActive
                    ? "bg-primary text-primary-foreground"
                    : "text-muted hover:bg-surface hover:text-foreground",
                ].join(" ")
              }
            >
              {item.label}
            </NavLink>
          ))}
        </nav>

        <Button variant="ghost" onClick={() => logout()}>
          Sair
        </Button>
      </aside>

      <main className="flex min-w-0 flex-1 flex-col">
        <Outlet />
      </main>
    </div>
  );
}
