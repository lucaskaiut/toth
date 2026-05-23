import { GuestGuard } from "~/features/auth/guards/GuestGuard";

export default function AuthLayout() {
  return <GuestGuard />;
}
