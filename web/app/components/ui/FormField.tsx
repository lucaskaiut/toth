import type { ReactNode } from "react";
import { Label } from "~/components/ui/Label";

type FormFieldProps = {
  id: string;
  label: string;
  error?: string;
  children: ReactNode;
  hint?: string;
};

export function FormField({ id, label, error, children, hint }: FormFieldProps) {
  return (
    <div className="flex flex-col gap-1.5">
      <Label htmlFor={id}>{label}</Label>
      {children}
      {error ? (
        <p id={`${id}-error`} className="text-sm text-danger" role="alert">
          {error}
        </p>
      ) : hint ? (
        <p className="text-sm text-muted">{hint}</p>
      ) : null}
    </div>
  );
}
