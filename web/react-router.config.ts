import type { Config } from "@react-router/dev/config";

export default {
  // SPA: auth usa localStorage; SSR quebrava redirect e deixava tela em branco.
  ssr: false,
} satisfies Config;
