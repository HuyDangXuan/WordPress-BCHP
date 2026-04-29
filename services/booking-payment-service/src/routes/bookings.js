export async function handleCreateBooking(body, services) {
  return {
    statusCode: 201,
    payload: await services.bookingWorkflow.createBooking(body),
  };
}
