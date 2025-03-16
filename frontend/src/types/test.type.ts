export interface Message {
    type: 'ERROR' | 'WARNING' | 'INFO'
    message: string
    line?: number
    column?: number
    source?: string
    fixable?: boolean
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