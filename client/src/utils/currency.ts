export function formatEtb(amount: number): string {
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'ETB',
    maximumFractionDigits: 0,
  }).format(amount)
}
