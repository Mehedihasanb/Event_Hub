const eurFmt = new Intl.NumberFormat(undefined, {
  style: 'currency',
  currency: 'EUR',
});

/** @param {number} cents */
export function formatEurosFromCents(cents) {
  return eurFmt.format((Number(cents) || 0) / 100);
}

/** @param {number} centsPerTicket @param {number} qty */
export function lineTotalFromCents(centsPerTicket, qty) {
  return formatEurosFromCents((Number(centsPerTicket) || 0) * (Number(qty) || 0));
}

/** @param {number} euros */
export function eurosToCents(euros) {
  return Math.round((Number(euros) || 0) * 100);
}

/** @param {number} cents */
export function centsToEurosInput(cents) {
  return (Number(cents) || 0) / 100;
}
