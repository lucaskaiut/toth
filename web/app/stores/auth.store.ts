import { create } from "zustand";
import { resolveInitialAuthStatus } from "~/lib/auth/bootstrap";
import { tokenStorage } from "~/lib/auth/token.storage";
import type { AuthStatus, AuthUser } from "~/types/auth";

type AuthState = {
  user: AuthUser | null;
  status: AuthStatus;
  setLoading: () => void;
  setAuthenticated: (user: AuthUser, token: string) => void;
  setUnauthenticated: () => void;
  setUser: (user: AuthUser) => void;
};

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  status: resolveInitialAuthStatus(),

  setLoading: () => set({ status: "loading" }),

  setAuthenticated: (user, token) => {
    tokenStorage.set(token);
    set({ user, status: "authenticated" });
  },

  setUnauthenticated: () => {
    tokenStorage.clear();
    set({ user: null, status: "unauthenticated" });
  },

  setUser: (user) => set({ user }),
}));

export const selectIsAuthenticated = (state: AuthState) =>
  state.status === "authenticated";

export const selectIsAuthLoading = (state: AuthState) => state.status === "loading";
