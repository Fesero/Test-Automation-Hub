export interface Message {
    message: string
    source: string
    severity: 'error' | 'warning'
    fixable: boolean
    line: number
    column: number
}

export interface FileResult {
    errors: number
    warnings: number
    messages: Message[]
}

export interface TestResultTotals {
    errors: number
    warnings: number
    fixable: number
    checked_files: number
    total_files: number
}

export interface TestResult {
    id: number
    name: string
    type: 'sniffer' | 'static_analysis'
    status: 'pending' | 'running' | 'completed' | 'failed'
    execution_time: number
    result: {
        totals: TestResultTotals
        files: Record<string, FileResult>
    }
}