export interface Project {
  id: number;
  name: string;
  url?: string | null; // Optional URL
  api_token?: string | null; // Optional API token (usually not sent to frontend)
  created_at: string;
  updated_at: string;
  // Add optional fields for stats (to be added to backend API)
  tests_count?: number;
  last_test_status?: string | null;
}

// Interface for the raw message from phpcs/phpstan output
export interface TestResultMessage {
  message: string;
  source?: string;
  severity?: number;
  type: string; // Simplify union type
  line: number;
  column?: number;
  fixable?: boolean;
}

// Interface for the parsed metrics JSON in TestResult
export interface TestResultMetrics {
  file_path: string;
  errors: number;
  warnings: number;
}

// Correct TestResult interface
export interface TestResult {
  id: number; // Keep numeric ID
  test_id: number;
  status: string; // Simplify status union, backend validates specific values
  output: string | null;
  metrics: string | null;
  report_path?: string | null;
  created_at: string;
  updated_at: string;

  parsedOutput?: TestResultMessage[] | null;
  parsedMetrics?: TestResultMetrics | null;
}

export interface Test {
  id: number;
  name: string;
  description?: string | null;
  status: string; // Simplify status union
  execution_time?: number | null;
  type: string; // Simplify type union
  project_id: number;
  created_at: string;
  updated_at: string;

  project: Project;
  results: TestResult[];
}

// Remove commented out old structures as well