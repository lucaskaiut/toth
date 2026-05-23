import type { Route } from "./+types/_auth.cadastro";
import { RegisterForm } from "~/features/auth/components/RegisterForm";

export function meta({}: Route.MetaArgs) {
  return [
    { title: "Cadastro | Toth CRM" },
    { name: "description", content: "Crie sua conta no Toth CRM" },
  ];
}

export default function RegisterPage() {
  return <RegisterForm />;
}
