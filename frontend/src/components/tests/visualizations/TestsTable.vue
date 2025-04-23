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
        <TestsOverviewChart :tests="tests" :key="chartKey" />
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
          :rows="tests"
          :columns="columns"
          row-key="id"
          :loading="loading"
          @row-click="onRowClick"
          selection="single"
          v-model:selected="selected"
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
            <q-tr :props="props" @click="onRowClick($event, props.row)">
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
                <template v-else-if="col.name === 'projectName'">
                  <q-chip size="sm" color="grey-3" text-color="grey-8" outline>
                    {{ props.row.project?.name || 'N/A' }}
                  </q-chip>
                </template>
                <template v-else-if="col.name === 'errors'">
                  <div class="text-center">
                    <q-chip
                      :color="calculateErrors(props.row) > 0 ? 'negative' : 'grey-4'"
                      :text-color="calculateErrors(props.row) > 0 ? 'white' : 'grey-8'"
                      dense size="sm" outline
                    >
                      {{ calculateErrors(props.row) }}
                    </q-chip>
                  </div>
                </template>
                <template v-else-if="col.name === 'warnings'">
                  <div class="text-center">
                    <q-chip
                      :color="calculateWarnings(props.row) > 0 ? 'warning' : 'grey-4'"
                      :text-color="calculateWarnings(props.row) > 0 ? 'white' : 'grey-8'"
                      dense size="sm" outline
                    >
                      {{ calculateWarnings(props.row) }}
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
                  {{ formatDate(props.row[col.field]) }}
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
import { ref, watch } from 'vue'
import type { Test } from 'src/types/test.type'
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
  tests: Test[]
  loading: boolean
}>()

const emit = defineEmits<{
  'row-click': [event: Event, row: Test]
}>()

const selected = ref<Test[]>([])
const searchTerm = ref('')
const chartPeriod = ref('week')
const chartKey = ref(0)

const filterTests = (
  rows: readonly Test[],
  terms: string
): readonly Test[] => {
  if (!terms) return rows
  
  const lowTerms = terms.toLowerCase()
  
  return rows.filter(row => 
    (row.name && row.name.toLowerCase().includes(lowTerms)) ||
    (row.project?.name && row.project.name.toLowerCase().includes(lowTerms)) ||
    (row.type && row.type.toLowerCase().includes(lowTerms)) ||
    (row.status && row.status.toLowerCase().includes(lowTerms))
  )
}

const calculateErrors = (test: Test): number => {
  return test.results?.reduce((sum, r) => sum + (r.parsedMetrics?.errors ?? 0), 0) ?? 0
}

const calculateWarnings = (test: Test): number => {
  return test.results?.reduce((sum, r) => sum + (r.parsedMetrics?.warnings ?? 0), 0) ?? 0
}

const columns: TableColumn[] = [
  { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true },
  { name: 'name', label: 'Название', field: 'name', align: 'left', sortable: true },
  { name: 'projectName', label: 'Проект', field: 'project', align: 'left', sortable: true },
  { name: 'type', label: 'Тип', field: 'type', align: 'left', sortable: true },
  { name: 'status', label: 'Статус', field: 'status', align: 'left', sortable: true },
  { name: 'errors', label: 'Ошибки', field: 'id', align: 'center', sortable: false },
  { name: 'warnings', label: 'Предупреждения', field: 'id', align: 'center', sortable: false },
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

const formatDate = (dateString: string): string => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('ru-RU', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const onRowClick = (evt: Event, row: Test) => {
  selected.value = [row]
  emit('row-click', evt, row)
}

watch(() => props.tests, () => {
    chartKey.value++;
}, { deep: true });
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