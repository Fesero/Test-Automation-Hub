<template>
  <div>
    <q-card class="modern-card q-mb-lg chart-container">
      <div class="row no-wrap items-center q-px-lg q-pt-lg">
        <div>
          <div class="text-h6 q-mb-xs">Обзор тестов</div>
          <div class="text-grey-8">Статистика ошибок и предупреждений</div>
        </div>
        <q-space />
        <div class="modern-tabs">
          <q-btn-toggle
            v-model="chartPeriod"
            flat
            toggle-color="primary"
            :options="[
              {label: 'Неделя', value: 'week'},
              {label: 'Месяц', value: 'month'},
              {label: 'Год', value: 'year'}
            ]"
          />
        </div>
      </div>
      <q-card-section>
        <TestsOverviewChart :tests="tests" />
      </q-card-section>
    </q-card>

    <q-card class="modern-card modern-table">
      <div class="row no-wrap items-center q-px-lg q-pt-lg">
        <div>
          <div class="text-h6 q-mb-xs">Результаты тестов</div>
          <div class="text-grey-8">Найдено тестов: {{ tests.length }}</div>
        </div>
        <q-space />
        <q-input
          dense
          outlined
          placeholder="Поиск тестов..."
          class="q-ml-md"
          style="width: 250px"
          v-model="searchTerm"
        >
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </div>
      
      <q-card-section>
        <q-table
          :rows="filteredTests"
          :columns="columns"
          row-key="id"
          :loading="loading"
          @row-click="onRowClick"
          selection="single"
          v-model:selected="selected"
          @update:selected="onSelectedUpdate"
          :pagination="{ rowsPerPage: 10 }"
          class="tests-table"
          :filter="searchTerm"
          :filter-method="filterTests"
          flat
          bordered
        >
          <template v-slot:header="props">
            <q-tr :props="props">
              <q-th auto-width>
                <q-checkbox v-model="props.selected" />
              </q-th>
              <q-th
                v-for="col in (props.cols as TableColumn[]).filter((col) => col.name !== 'selection')"
                :key="col.name"
                :props="props"
                class="text-weight-bold"
              >
                {{ col.label }}
              </q-th>
            </q-tr>
          </template>

          <template v-slot:body="props">
            <q-tr :props="props">
              <q-td auto-width>
                <q-checkbox v-model="props.selected" />
              </q-td>
              <q-td v-for="col in (props.cols as TableColumn[]).filter((col) => col.name !== 'selection')" :key="col.name" :props="props">
                <template v-if="col.name === 'type'">
                  <div class="q-py-xs">
                    <q-badge :color="typeColor(props.row.type)" text-color="white">
                      {{ props.row.type }}
                    </q-badge>
                  </div>
                </template>
                <template v-else-if="col.name === 'status'">
                  <div class="status-badge" :class="`status-badge--${props.row[col.field]}`">
                    {{ props.row[col.field] }}
                  </div>
                </template>
                <template v-else-if="col.name === 'project_name'">
                  <q-chip size="sm" color="grey-3" text-color="grey-8" outline>
                    {{ props.row[col.field] || 'N/A' }}
                  </q-chip>
                </template>
                <template v-else-if="col.name === 'errors'">
                  <div class="text-center">
                    <q-chip 
                      :color="props.row[col.field] > 0 ? 'negative' : 'grey-4'" 
                      :text-color="props.row[col.field] > 0 ? 'white' : 'grey-8'"
                      dense
                      size="sm"
                      outline
                    >
                      {{ props.row[col.field] }}
                    </q-chip>
                  </div>
                </template>
                <template v-else-if="col.name === 'warnings'">
                  <div class="text-center">
                    <q-chip 
                      :color="props.row[col.field] > 0 ? 'warning' : 'grey-4'" 
                      :text-color="props.row[col.field] > 0 ? 'white' : 'grey-8'"
                      dense
                      size="sm"
                      outline
                    >
                      {{ props.row[col.field] }}
                    </q-chip>
                  </div>
                </template>
                <template v-else-if="col.name === 'execution_time'">
                  <div class="text-right">
                    <q-badge outline :color="props.row[col.field] > 10 ? 'negative' : 'positive'" class="q-pa-xs">
                      {{ props.row[col.field] ? parseFloat(props.row[col.field]).toFixed(2) + 's' : '-' }}
                    </q-badge>
                  </div>
                </template>
                <template v-else-if="col.name === 'created_at'">
                  {{ new Date(props.row[col.field]).toLocaleString('en-US', { 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  }) }}
                </template>
                <template v-else>
                  {{ props.row[col.field] }}
                </template>
              </q-td>
            </q-tr>
          </template>
        </q-table>
      </q-card-section>
    </q-card>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { TestResult } from 'src/types/test.type'
import type { QTableColumn } from 'quasar'
import TestsOverviewChart from './TestsOverviewChart.vue'

interface TableColumn extends QTableColumn {
  name: string
  label: string
  field: string
  align?: 'left' | 'right' | 'center'
  sortable?: boolean
}

const props = defineProps<{
  tests: TestResult[]
  loading: boolean
}>()

const emit = defineEmits<{
  'row-click': [event: Event, row: TestResult]
  'update:selected': [value: TestResult[]]
}>()

const selected = ref<TestResult[]>([])
const searchTerm = ref('')
const chartPeriod = ref('week')

const formattedTests = computed(() => 
  props.tests.map(test => {
    let errors = 0
    let warnings = 0

    if (test.result?.files) {
      Object.values(test.result.files).forEach(file => {
        errors += (file as { errors?: number }).errors || 0
        warnings += (file as { warnings?: number }).warnings || 0
      })
    }

    return {
      ...test,
      errors,
      warnings
    }
  })
)

const filteredTests = computed(() => formattedTests.value)

const filterTests = (
  rows: readonly TestResult[],
  terms: string
) => {
  if (!terms) return rows
  
  const lowTerms = terms.toLowerCase()
  
  return rows.filter(row => 
    (row.name && row.name.toLowerCase().includes(lowTerms)) ||
    (row.project_name && row.project_name.toLowerCase().includes(lowTerms)) ||
    (row.type && row.type.toLowerCase().includes(lowTerms)) ||
    (row.status && row.status.toLowerCase().includes(lowTerms))
  )
}

const columns: TableColumn[] = [
  { name: 'selection', label: '', field: 'selection', align: 'center', sortable: false },
  { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true },
  { name: 'name', label: 'Название', field: 'name', align: 'left', sortable: true },
  { name: 'project_name', label: 'Проект', field: 'project_name', align: 'left', sortable: true },
  { name: 'type', label: 'Тип', field: 'type', align: 'left', sortable: true },
  { name: 'status', label: 'Статус', field: 'status', align: 'left', sortable: true },
  { name: 'errors', label: 'Ошибки', field: 'errors', align: 'center', sortable: true },
  { name: 'warnings', label: 'Предупреждения', field: 'warnings', align: 'center', sortable: true },
  { name: 'execution_time', label: 'Время', field: 'execution_time', align: 'right', sortable: true },
  { name: 'created_at', label: 'Дата', field: 'created_at', align: 'left', sortable: true }
]

const typeColor = (type: string) => {
  switch(type) {
    case 'sniffer': return 'blue'
    case 'static_analysis': return 'purple'
    case 'load': return 'deep-orange'
    case 'functional': return 'teal'
    default: return 'grey'
  }
}

const onRowClick = (evt: Event, row: TestResult) => {
  selected.value = [row]
  emit('row-click', evt, row)
}

const onSelectedUpdate = (newSelected: readonly TestResult[]) => {
  selected.value = [...newSelected]
  if (newSelected.length > 0 && newSelected[0]) {
    emit('row-click', new Event('click'), newSelected[0])
  }
}
</script>

<style scoped lang="scss">
.chart-container {
  min-height: 400px;
}

.tests-table {
  thead tr th {
    font-weight: 600;
    color: #5f6368;
    background-color: #f8f9fa;
  }

  tbody tr {
    cursor: pointer;
    transition: background-color 0.2s ease;

    &:hover {
      background-color: rgba(0, 0, 0, 0.03);
    }

    &.selected {
      background-color: rgba(var(--q-primary), 0.08);
    }
  }

  :deep(.q-table__middle) {
    .q-table__grid-content {
      padding-left: 0;
    }
  }

  :deep(.q-checkbox) {
    margin: 0;
    padding: 0;
  }
}
</style> 