/**
 * Format cents (sen) to Rupiah display string.
 * All monetary values in the backend are stored in cents (sen).
 *
 * @param {number|null|undefined} cents - Nilai dalam sen
 * @returns {string} Formatted Rupiah string, e.g. "Rp 15.000"
 */
export function formatRupiah(cents) {
  if (cents === null || cents === undefined || Number.isNaN(Number(cents))) {
    return 'Rp 0'
  }
  const rupiah = Math.round(Math.abs(cents) / 100)
  return 'Rp ' + rupiah.toLocaleString('id-ID')
}

/**
 * Format cents to plain number string without "Rp" prefix.
 * Useful inside InputNumber or raw display contexts.
 *
 * @param {number|null|undefined} cents
 * @returns {string} e.g. "15.000"
 */
export function formatCents(cents) {
  if (cents === null || cents === undefined || Number.isNaN(Number(cents))) {
    return '0'
  }
  return Math.round(Math.abs(cents) / 100).toLocaleString('id-ID')
}
