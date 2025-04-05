export const statusColor = (status: string | undefined | null): string => {
  if (!status) return 'grey';
  switch (status.toLowerCase()) {
    case 'completed':
    case 'passed':
      return 'positive';
    case 'failed':
    case 'error':
      return 'negative';
    case 'running':
    case 'pending':
      return 'warning';
    default:
      return 'grey';
  }
}; 