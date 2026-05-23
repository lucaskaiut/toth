import type { ReactNode } from "react";
import { Link } from "react-router";

type AuthFormCardProps = {
  title: string;
  description: string;
  children: ReactNode;
  footer: {
    text: string;
    linkText: string;
    linkTo: string;
  };
};

export function AuthFormCard({
  title,
  description,
  children,
  footer,
}: AuthFormCardProps) {
  return (
    <div className="w-full max-w-md">
      <div className="mb-8">
        <p className="text-sm font-medium tracking-wide text-primary uppercase">
          Toth CRM
        </p>
        <h1 className="mt-2 text-2xl font-semibold tracking-tight text-foreground">
          {title}
        </h1>
        <p className="mt-2 text-sm text-muted">{description}</p>
      </div>

      <div className="rounded-xl bg-surface-elevated p-6 ring-1 ring-border">
        {children}
      </div>

      <p className="mt-6 text-center text-sm text-muted">
        {footer.text}{" "}
        <Link
          to={footer.linkTo}
          className="font-medium text-primary hover:text-primary-hover"
        >
          {footer.linkText}
        </Link>
      </p>
    </div>
  );
}
