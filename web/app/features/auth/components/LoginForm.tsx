import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { Alert } from "~/components/ui/Alert";
import { Button } from "~/components/ui/Button";
import { FormField } from "~/components/ui/FormField";
import { Input } from "~/components/ui/Input";
import { AuthFormCard } from "~/components/forms/AuthFormCard";
import { useAuth } from "~/features/auth/hooks/useAuth";
import { ApiError } from "~/lib/api/errors";
import { loginSchema, type LoginFormValues } from "~/schemas/auth.schema";
import { useState } from "react";

export function LoginForm() {
  const { login, isLoggingIn } = useAuth();
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: "",
      password: "",
    },
  });

  const isLoading = isLoggingIn || isSubmitting;

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null);

    try {
      await login(values);
    } catch (error) {
      if (error instanceof ApiError) {
        setFormError(error.message);
        return;
      }

      setFormError("Não foi possível entrar. Tente novamente.");
    }
  });

  return (
    <AuthFormCard
      title="Entrar na sua conta"
      description="Acesse o painel com seu e-mail e senha."
      footer={{
        text: "Ainda não tem conta?",
        linkText: "Criar cadastro",
        linkTo: "/cadastro",
      }}
    >
      <form onSubmit={onSubmit} className="flex flex-col gap-4" noValidate>
        {formError ? <Alert>{formError}</Alert> : null}

        <FormField id="email" label="E-mail" error={errors.email?.message}>
          <Input
            id="email"
            type="email"
            autoComplete="email"
            hasError={Boolean(errors.email)}
            aria-invalid={Boolean(errors.email)}
            aria-describedby={errors.email ? "email-error" : undefined}
            {...register("email")}
          />
        </FormField>

        <FormField id="password" label="Senha" error={errors.password?.message}>
          <Input
            id="password"
            type="password"
            autoComplete="current-password"
            hasError={Boolean(errors.password)}
            aria-invalid={Boolean(errors.password)}
            aria-describedby={errors.password ? "password-error" : undefined}
            {...register("password")}
          />
        </FormField>

        <Button
          type="submit"
          className="mt-2 w-full"
          isLoading={isLoading}
          disabled={isLoading}
        >
          Entrar
        </Button>
      </form>
    </AuthFormCard>
  );
}
