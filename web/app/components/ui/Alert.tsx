type AlertProps = {
  variant?: "error" | "success";
  children: React.ReactNode;
};

const variants = {
  error: "bg-danger-subtle text-danger ring-1 ring-danger/30",
  success: "bg-success-subtle text-success ring-1 ring-success/30",
};

export function Alert({ variant = "error", children }: AlertProps) {
  return (
    <div
      role="alert"
      className={`rounded-lg px-3 py-2.5 text-sm ring-1 ${variants[variant]}`}
    >
      {children}
    </div>
  );
}
