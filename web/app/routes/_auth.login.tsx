import type { Route } from "./+types/_auth.login";
import { LoginForm } from "~/features/auth/components/LoginForm";

export function meta({}: Route.MetaArgs) {
  return [
    { title: "Entrar | Toth CRM" },
    { name: "description", content: "Faça login no Toth CRM" },
  ];
}

export default function LoginPage() {
  return <LoginForm />;
}
