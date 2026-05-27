import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useCallback } from "react";
import { useNavigate, useSearchParams } from "react-router";
import { authApi } from "~/lib/api/auth.api";
import { AUTH_ME_QUERY_KEY } from "~/lib/auth/constants";
import type { LoginFormValues, RegisterFormValues } from "~/schemas/auth.schema";
import { resolvePostAuthPath } from "~/lib/company/status";
import { toApiPhone } from "~/lib/phone/mask";
import { tokenStorage } from "~/lib/auth/token.storage";
import {
  selectIsAuthenticated,
  selectIsAuthLoading,
  useAuthStore,
} from "~/stores/auth.store";

export function useAuth() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const queryClient = useQueryClient();
  const user = useAuthStore((state) => state.user);
  const status = useAuthStore((state) => state.status);
  const storeAuthenticated = useAuthStore(selectIsAuthenticated);
  const hasToken = Boolean(tokenStorage.get());
  const isAuthenticated = storeAuthenticated && hasToken;
  const isLoading = useAuthStore(selectIsAuthLoading);
  const setAuthenticated = useAuthStore((state) => state.setAuthenticated);
  const setUnauthenticated = useAuthStore((state) => state.setUnauthenticated);

  const loginMutation = useMutation({
    mutationFn: (values: LoginFormValues) =>
      authApi.login({
        channel: "internal",
        data: {
          email: values.email,
          password: values.password,
        },
      }),
    onSuccess: (response) => {
      const session = response.data;
      setAuthenticated(session.user, session.token);
      queryClient.setQueryData(AUTH_ME_QUERY_KEY, session.user);

      const redirectTo =
        searchParams.get("redirectTo") ||
        resolvePostAuthPath(session.user.company);
      navigate(redirectTo, { replace: true });
    },
  });

  const registerMutation = useMutation({
    mutationFn: (values: RegisterFormValues) =>
      authApi.register({
        channel: "internal",
        data: {
          company_name: values.company_name,
          whatsapp: toApiPhone(values.whatsapp),
          name: values.name,
          email: values.email,
          password: values.password,
        },
      }),
    onSuccess: (response) => {
      const session = response.data;
      setAuthenticated(session.user, session.token);
      queryClient.setQueryData(AUTH_ME_QUERY_KEY, session.user);
      navigate(resolvePostAuthPath(session.user.company), { replace: true });
    },
  });

  const logout = useCallback(() => {
    setUnauthenticated();
    queryClient.removeQueries({ queryKey: AUTH_ME_QUERY_KEY });
    queryClient.clear();
    navigate("/login", { replace: true });
  }, [navigate, queryClient, setUnauthenticated]);

  return {
    user,
    status,
    isAuthenticated,
    isLoading,
    login: loginMutation.mutateAsync,
    register: registerMutation.mutateAsync,
    isLoggingIn: loginMutation.isPending,
    isRegistering: registerMutation.isPending,
    logout,
  };
}
