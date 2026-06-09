type AlertProps = {
  variant?: "error" | "success";
  children: React.ReactNode;
};

const variants = {
  error: "bg-danger-subtle text-danger shadow-sm",
  success: "bg-success-subtle text-success shadow-sm",
};

export function Alert({ variant = "error", children }: AlertProps) {
  return (
    <div
      role="alert"
      className={`rounded-lg px-3 py-2.5 text-sm ${variants[variant]}`}
    >
      {children}
    </div>
  );
}
