export function handleCreateBooking(body) {
  return {
    booking_code: `BK-${body.wordpress_order_id ?? 'DEMO'}`,
    wordpress_order_id: body.wordpress_order_id ?? null,
    payment_status: body.payment_status || 'pending',
    status: 'accepted',
  };
}
