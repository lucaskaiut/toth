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
        className={`ui-field ${hasError ? "ui-field-error" : ""} ${className}`}
        {...props}
      />
    );
  },
);

Input.displayName = "Input";
