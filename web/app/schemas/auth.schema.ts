import { z } from "zod";

export const loginSchema = z.object({
  email: z
    .string()
    .min(1, "Informe seu e-mail")
    .email("Informe um e-mail válido"),
  password: z.string().min(1, "Informe sua senha"),
});

export const registerSchema = z
  .object({
    company_name: z
      .string()
      .min(1, "Informe o nome da empresa")
      .max(255, "Nome da empresa muito longo"),
    name: z
      .string()
      .min(1, "Informe seu nome")
      .max(255, "Nome muito longo"),
    email: z
      .string()
      .min(1, "Informe seu e-mail")
      .email("Informe um e-mail válido"),
    password: z
      .string()
      .min(8, "A senha deve ter pelo menos 8 caracteres"),
    password_confirmation: z.string().min(1, "Confirme sua senha"),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "As senhas não coincidem",
    path: ["password_confirmation"],
  });

export type LoginFormValues = z.infer<typeof loginSchema>;
export type RegisterFormValues = z.infer<typeof registerSchema>;
