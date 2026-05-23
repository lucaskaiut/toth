import { useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect } from "react";
import { useNavigate } from "react-router";
import { authApi } from "~/lib/api/auth.api";
import { setUnauthorizedHandler } from "~/lib/api/client";
import { tokenStorage } from "~/lib/auth/token.storage";
import { AUTH_ME_QUERY_KEY } from "~/lib/auth/constants";
import { useAuthStore } from "~/stores/auth.store";

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const status = useAuthStore((state) => state.status);
  const setLoading = useAuthStore((state) => state.setLoading);
  const setUnauthenticated = useAuthStore((state) => state.setUnauthenticated);
  const setUser = useAuthStore((state) => state.setUser);

  const hasToken = Boolean(tokenStorage.get());

  const meQuery = useQuery({
    queryKey: AUTH_ME_QUERY_KEY,
    queryFn: async () => {
      const response = await authApi.me();
      return response.data;
    },
    enabled: hasToken && status !== "unauthenticated",
    retry: false,
  });

  useEffect(() => {
    if (!hasToken) {
      setUnauthenticated();
      return;
    }

    if (status === "idle") {
      setLoading();
    }
  }, [hasToken, setLoading, setUnauthenticated, status]);

  useEffect(() => {
    if (!hasToken) return;

    if (meQuery.isSuccess) {
      setUser(meQuery.data);
      useAuthStore.setState({ status: "authenticated" });
      return;
    }

    if (meQuery.isError) {
      setUnauthenticated();
      queryClient.removeQueries({ queryKey: AUTH_ME_QUERY_KEY });
    }
  }, [
    hasToken,
    meQuery.data,
    meQuery.isError,
    meQuery.isSuccess,
    queryClient,
    setUnauthenticated,
    setUser,
  ]);

  useEffect(() => {
    setUnauthorizedHandler(() => {
      setUnauthenticated();
      queryClient.removeQueries({ queryKey: AUTH_ME_QUERY_KEY });
      navigate("/login", { replace: true });
    });

    return () => setUnauthorizedHandler(null);
  }, [navigate, queryClient, setUnauthenticated]);

  return <>{children}</>;
}
