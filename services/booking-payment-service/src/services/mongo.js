export function describeMongoDependency(env) {
  const database = env.MONGO_URI.split('/').pop() || 'hv_travel';

  return {
    status: 'configured',
    database,
  };
}
