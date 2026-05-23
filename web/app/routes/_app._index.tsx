import type { Route } from "./+types/_app._index";

export function meta({}: Route.MetaArgs) {
  return [{ title: "Toth CRM" }];
}

export default function HomePage() {
  return null;
}
