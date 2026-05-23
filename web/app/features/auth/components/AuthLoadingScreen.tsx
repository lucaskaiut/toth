import { Spinner } from "~/components/ui/Spinner";

export function AuthLoadingScreen() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-surface">
      <div className="flex flex-col items-center gap-3">
        <Spinner size="lg" />
        <p className="text-sm text-muted">Carregando...</p>
      </div>
    </div>
  );
}
