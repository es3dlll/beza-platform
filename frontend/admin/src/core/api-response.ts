export interface ApiResponse<T = unknown> {
  success: boolean;
  message: string;
  data: T | null;
  errors: unknown;
  timestamp: string;
  request_id: string | null;
}
