export function formatDate(value: string | null | undefined): string {
  if (!value) {
    return "—";
  }

  // SQLite stores "YYYY-MM-DD HH:MM:SS" in UTC; make it parseable everywhere.
  const parsed = new Date(value.replace(" ", "T") + "Z");

  if (Number.isNaN(parsed.getTime())) {
    return value;
  }

  return parsed.toLocaleString(undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  });
}