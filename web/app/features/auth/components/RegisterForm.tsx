import { zodResolver } from "@hookform/resolvers/zod";
import { useState } from "react";
import { useForm, Controller } from "react-hook-form";
import { maskBrazilPhone } from "~/lib/phone/mask";
import { Alert } from "~/components/ui/Alert";
import { Button } from "~/components/ui/Button";
import { FormField } from "~/components/ui/FormField";
import { Input } from "~/components/ui/Input";
import { AuthFormCard } from "~/components/forms/AuthFormCard";
import { useAuth } from "~/features/auth/hooks/useAuth";
import { ApiError } from "~/lib/api/errors";
import {
  registerSchema,
  type RegisterFormValues,
} from "~/schemas/auth.schema";

export function RegisterForm() {
  const { register: registerUser, isRegistering } = useAuth();
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    control,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<RegisterFormValues>({
    resolver: zodResolver(registerSchema),
    defaultValues: {
      company_name: "",
      whatsapp: "",
      name: "",
      email: "",
      password: "",
      password_confirmation: "",
    },
  });

  const isLoading = isRegistering || isSubmitting;

  const onSubmit = handleSubmit(async (values) => {
    setFormError(null);

    try {
      await registerUser(values);
    } catch (error) {
      if (error instanceof ApiError) {
        setFormError(error.message);
        return;
      }

      setFormError("Não foi possível concluir o cadastro. Tente novamente.");
    }
  });

  return (
    <AuthFormCard
      title="Criar sua conta"
      description="Cadastre sua empresa e comece a usar o Toth CRM."
      footer={{
        text: "Já tem conta?",
        linkText: "Fazer login",
        linkTo: "/login",
      }}
    >
      <form onSubmit={onSubmit} className="flex flex-col gap-4" noValidate>
        {formError ? <Alert>{formError}</Alert> : null}

        <FormField
          id="company_name"
          label="Nome da empresa"
          error={errors.company_name?.message}
        >
          <Input
            id="company_name"
            autoComplete="organization"
            hasError={Boolean(errors.company_name)}
            {...register("company_name")}
          />
        </FormField>

        <FormField
          id="whatsapp"
          label="WhatsApp da empresa"
          error={errors.whatsapp?.message}
          hint="Número principal com DDD"
        >
          <Controller
            name="whatsapp"
            control={control}
            render={({ field }) => (
              <Input
                id="whatsapp"
                inputMode="tel"
                autoComplete="tel"
                placeholder="(11) 99999-9999"
                hasError={Boolean(errors.whatsapp)}
                value={field.value}
                onChange={(event) =>
                  field.onChange(maskBrazilPhone(event.target.value))
                }
              />
            )}
          />
        </FormField>

        <FormField id="name" label="Seu nome" error={errors.name?.message}>
          <Input
            id="name"
            autoComplete="name"
            hasError={Boolean(errors.name)}
            {...register("name")}
          />
        </FormField>

        <FormField id="email" label="E-mail" error={errors.email?.message}>
          <Input
            id="email"
            type="email"
            autoComplete="email"
            hasError={Boolean(errors.email)}
            {...register("email")}
          />
        </FormField>

        <FormField
          id="password"
          label="Senha"
          error={errors.password?.message}
          hint="Mínimo de 8 caracteres"
        >
          <Input
            id="password"
            type="password"
            autoComplete="new-password"
            hasError={Boolean(errors.password)}
            {...register("password")}
          />
        </FormField>

        <FormField
          id="password_confirmation"
          label="Confirmar senha"
          error={errors.password_confirmation?.message}
        >
          <Input
            id="password_confirmation"
            type="password"
            autoComplete="new-password"
            hasError={Boolean(errors.password_confirmation)}
            {...register("password_confirmation")}
          />
        </FormField>

        <Button
          type="submit"
          className="mt-2 w-full"
          isLoading={isLoading}
          disabled={isLoading}
        >
          Criar conta
        </Button>
      </form>
    </AuthFormCard>
  );
}
