import { tokenStorage } from "~/lib/auth/token.storage";
import type { AuthStatus } from "~/types/auth";

export function resolveInitialAuthStatus(): AuthStatus {
  return tokenStorage.get() ? "loading" : "unauthenticated";
}
