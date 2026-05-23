import { forwardRef } from "react";
import type { InputHTMLAttributes } from "react";

type InputProps = InputHTMLAttributes<HTMLInputElement> & {
  hasError?: boolean;
};

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ className = "", hasError = false, ...props }, ref) => {
    return (
      <input
        ref={ref}
        className={`h-10 w-full rounded-lg bg-surface-elevated px-3 text-sm text-foreground ring-1 transition-colors placeholder:text-muted/70 focus:outline-none focus:ring-2 focus:ring-primary/25 disabled:cursor-not-allowed disabled:opacity-60 ${
          hasError ? "ring-danger" : "ring-border"
        } ${className}`}
        {...props}
      />
    );
  },
);

Input.displayName = "Input";
