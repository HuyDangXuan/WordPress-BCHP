export async function handleRevenueReport(searchParams, services) {
  return {
    statusCode: 200,
    payload: await services.revenueReportWorkflow.getRevenueReport({
      from: searchParams.get('from'),
      to: searchParams.get('to'),
    }),
  };
}
