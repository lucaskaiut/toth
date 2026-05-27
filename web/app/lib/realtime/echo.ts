import Echo from "laravel-echo";
import Pusher from "pusher-js";
import { API_URL } from "~/lib/api/client";
import { tokenStorage } from "~/lib/auth/token.storage";

let echoInstance: Echo<"pusher"> | null = null;

export function getEcho(): Echo<"pusher"> | null {
  if (typeof window === "undefined") {
    return null;
  }

  const key = import.meta.env.VITE_REVERB_APP_KEY;

  if (!key) {
    return null;
  }

  if (echoInstance) {
    return echoInstance;
  }

  window.Pusher = Pusher;

  const scheme = import.meta.env.VITE_REVERB_SCHEME ?? "http";
  const host = import.meta.env.VITE_REVERB_HOST ?? "localhost";
  const port = Number(import.meta.env.VITE_REVERB_PORT ?? 8081);

  echoInstance = new Echo({
    broadcaster: "pusher",
    key,
    // pusher-js exige cluster mesmo quando usamos wsHost custom.
    // Reverb não usa cluster, então usamos um valor dummy.
    cluster: "mt1",
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS: scheme === "https",
    enabledTransports: ["ws", "wss"],
    disableStats: true,
    authEndpoint: `${API_URL}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${tokenStorage.get() ?? ""}`,
        Accept: "application/json",
      },
    },
  });

  return echoInstance;
}

export function disconnectEcho() {
  echoInstance?.disconnect();
  echoInstance = null;
}

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}
