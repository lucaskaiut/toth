export function buildLoginPath(pathname: string, search = ""): string {
  const redirectTo = `${pathname}${search}`;

  if (redirectTo === "/" || redirectTo.startsWith("/login")) {
    return "/login";
  }

  return `/login?redirectTo=${encodeURIComponent(redirectTo)}`;
}
