<template>
  <div>
    <q-card class="chart-card q-mb-lg" flat bordered>
      <q-card-section class="bg-primary text-white">
        <div class="text-h6">Обзор тестов</div>
        <div class="text-subtitle2">Статистика ошибок и предупреждений</div>
      </q-card-section>
      <q-card-section>
        <TestsOverviewChart :tests="tests" />
      </q-card-section>
    </q-card>

    <q-card flat bordered class="data-table">
      <q-table
        :rows="formattedTests"
        :columns="columns"
        row-key="id"
        :loading="loading"
        @row-click="(evt, row) => emit('row-click', evt, row)"
        selection="single"
        v-model:selected="selected"
        :pagination="{ rowsPerPage: 10 }"
        class="tests-table"
      >
        <template v-slot:body-cell-type="props">
          <q-td :props="props">
            <q-badge :color="typeColor(props.value)">
              {{ props.value }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-status="props">
          <q-td :props="props">
            <q-badge :color="statusColor(props.value)">
              {{ props.value }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-errors="props">
          <q-td :props="props">
            <q-chip 
              :color="props.row.type === 'static_analysis' ? 'deep-purple' : 'red'" 
              text-color="white" 
              dense
            >
              {{ props.value }}
            </q-chip>
          </q-td>
        </template>

        <template v-slot:body-cell-execution_time="props">
          <q-td :props="props">
            {{ props.value ? parseFloat(props.value).toFixed(2) + 's' : '-' }}
          </q-td>
        </template>
      </q-table>
    </q-card>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { TestResult } from 'src/types/test.type'
import type { QTableColumn } from 'quasar'
import TestsOverviewChart from './TestsOverviewChart.vue'

const props = defineProps<{
  tests: TestResult[]
  loading: boolean
}>()

const emit = defineEmits<{
  'row-click': [event: Event, row: TestResult]
  'update:selected': [value: TestResult[]]
}>()

const selected = ref<TestResult[]>([])

const formattedTests = computed(() => 
  props.tests.map(test => {
    let errors = 0
    let warnings = 0

    if (test.result?.files) {
      Object.values(test.result.files).forEach(file => {
        errors += file.errors || 0
        warnings += file.warnings || 0
      })
    }

    return {
      ...test,
      errors,
      warnings
    }
  })
)

const columns: QTableColumn[] = [
  { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true },
  { name: 'name', label: 'Название', field: 'name', align: 'left', sortable: true },
  { name: 'type', label: 'Тип', field: 'type', align: 'left', sortable: true },
  { name: 'status', label: 'Статус', field: 'status', align: 'left', sortable: true },
  { name: 'errors', label: 'Ошибки', field: 'errors', align: 'center', sortable: true },
  { name: 'warnings', label: 'Предупреждения', field: 'warnings', align: 'center', sortable: true },
  { name: 'execution_time', label: 'Время', field: 'execution_time', align: 'right', sortable: true }
]

const statusColor = (status: string) => {
  switch(status) {
    case 'completed': return 'green'
    case 'failed': return 'red'
    default: return 'orange'
  }
}

const typeColor = (type: string) => {
  switch(type) {
    case 'sniffer': return 'blue'
    case 'static_analysis': return 'purple'
    default: return 'grey'
  }
}
</script>

<style scoped lang="scss">
.chart-card, .data-table {
  border-radius: 12px;
  transition: all 0.3s ease;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);

  &:hover {
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  }

  .q-card-section {
    &.bg-primary {
      border-top-left-radius: 12px;
      border-top-right-radius: 12px;
    }
  }
}

.chart-container {
  height: 400px;
  padding: 16px;
}

.tests-table {
  .q-table__card {
    border-radius: 12px;
  }

  thead tr th {
    background: #f5f5f5;
    font-weight: 600;
  }

  tbody tr {
    cursor: pointer;
    transition: background-color 0.3s ease;

    &:hover {
      background-color: rgba(var(--q-primary), 0.05);
    }
  }
}
</style> 