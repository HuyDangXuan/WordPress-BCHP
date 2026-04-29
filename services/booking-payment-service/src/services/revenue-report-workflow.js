function normalizeDateStart(input) {
  return input ? new Date(`${input}T00:00:00.000Z`) : null;
}

function normalizeDateEnd(input) {
  return input ? new Date(`${input}T23:59:59.999Z`) : null;
}

export function createRevenueReportWorkflow({ store }) {
  return {
    async getRevenueReport({ from, to }) {
      const fromDate = normalizeDateStart(from);
      const toDate = normalizeDateEnd(to);
      const bookings = await store.listBookings();

      const filteredBookings = bookings.filter((booking) => {
        const createdAt = new Date(booking.created_at);

        if (fromDate && createdAt < fromDate) {
          return false;
        }

        if (toDate && createdAt > toDate) {
          return false;
        }

        return true;
      });

      const paidBookings = filteredBookings.filter(
        (booking) => String(booking.payment_status).toLowerCase() === 'paid',
      );

      return {
        status: 'ok',
        from: from || null,
        to: to || null,
        revenue_total: paidBookings.reduce((sum, booking) => sum + Number(booking.amount || 0), 0),
        paid_bookings: paidBookings.length,
        total_bookings: filteredBookings.length,
      };
    },
  };
}
