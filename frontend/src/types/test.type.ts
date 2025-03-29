export interface Message {
    severity: 'error' | 'warning'
    line: number
    column?: number
    message: string
    source?: string
    fixable?: boolean
}

export interface FileResult {
    errors?: number
    warnings?: number
    messages?: Message[]
}

export interface TestResultTotals {
    errors: number
    warnings: number
    fixable: number
    checked_files: number
    total_files: number
}

export interface TestResult {
    id: string
    name: string
    project_name?: string
    type: string
    status: 'completed' | 'failed' | 'running' | 'pending'
    execution_time?: number
    created_at: string
    result?: {
        totals?: TestResultTotals
        files?: Record<string, FileResult>
    }
}