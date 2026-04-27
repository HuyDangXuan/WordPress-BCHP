export function handleRevenueReport(searchParams) {
  return {
    status: 'ok',
    from: searchParams.get('from') || null,
    to: searchParams.get('to') || null,
    revenue_total: 0,
    paid_bookings: 0,
  };
}
