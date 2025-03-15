export interface Message {
    message: string
    source: string
    severity: number
    fixable: boolean
    type: 'ERROR' | 'WARNING'
    line: number
    column: number
}
export interface FileInfo {
    errors: number
    warnings: number
    messages: Message[]
}

export interface TestResult {
    id: number,
    created_at: string,
    updated_at: string,
    name: string,
    description: string | null,
    status: 'pending' | 'running' | 'completed' | 'failed',
    execution_time: string | null,
    result: {
        totals: {
            errors: number
            warnings: number
            fixable: number
        }
        files: Record<string, FileInfo>
    }
    type: string
}