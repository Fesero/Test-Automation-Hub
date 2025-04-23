<template>
  <q-page padding class="modern-page">
    <div class="row q-col-gutter-md">
      <!-- Audit Log -->
      <div class="col-12 col-md-8">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="row items-center justify-between q-mb-md">
              <div class="text-h6 text-primary">Журнал аудита</div>
              <div class="row q-gutter-sm">
                <q-btn-dropdown
                  color="primary"
                  label="Фильтры"
                  class="premium-btn"
                >
                  <q-list>
                    <q-item clickable v-close-popup>
                      <q-item-section>
                        <q-item-label>Все действия</q-item-label>
                        <q-item-label caption>Показать все записи</q-item-label>
                      </q-item-section>
                    </q-item>
                    <q-item clickable v-close-popup>
                      <q-item-section>
                        <q-item-label>Только ошибки</q-item-label>
                        <q-item-label caption>Показать только ошибки</q-item-label>
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-btn-dropdown>
                <q-btn
                  color="primary"
                  icon="download"
                  label="Экспорт"
                  class="premium-btn"
                />
              </div>
            </div>

            <q-table
              :rows="filteredLogs"
              :columns="columns"
              row-key="id"
              flat
              bordered
              :pagination="{ rowsPerPage: 10 }"
              class="modern-table"
            >
              <template v-slot:body-cell-action="props">
                <q-td :props="props">
                  <q-chip
                    :color="getActionColor(props.value)"
                    text-color="white"
                    dense
                    class="status-badge"
                  >
                    {{ props.value }}
                  </q-chip>
                </q-td>
              </template>

              <template v-slot:body-cell-details="props">
                <q-td :props="props">
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="info"
                    @click="showDetails(props.row)"
                  >
                    <q-tooltip>Подробности</q-tooltip>
                  </q-btn>
                </q-td>
              </template>
            </q-table>
          </q-card-section>
        </q-card>
      </div>

      <!-- Statistics -->
      <div class="col-12 col-md-4">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Статистика действий</div>
            <div class="chart-container">
              <canvas ref="actionChartRef"></canvas>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Активность по времени</div>
            <div class="chart-container">
              <canvas ref="timeChartRef"></canvas>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Топ пользователей</div>
            <q-list>
              <q-item v-for="user in topUsers" :key="user.id" class="user-item">
                <q-item-section avatar>
                  <q-avatar color="primary" text-color="white">
                    {{ user.name.charAt(0) }}
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ user.name }}</q-item-label>
                  <q-item-label caption>{{ user.actions }} действий</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Details Dialog -->
    <q-dialog v-model="showDetailsDialog" persistent>
      <q-card style="min-width: 500px" class="modern-card">
        <q-card-section>
          <div class="text-h6 text-primary">Подробности действия</div>
        </q-card-section>
        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-6">
              <div class="text-subtitle2 text-grey-8">Действие</div>
              <div class="text-body1">{{ selectedAction?.action }}</div>
            </div>
            <div class="col-6">
              <div class="text-subtitle2 text-grey-8">Время</div>
              <div class="text-body1">{{ selectedAction?.timestamp }}</div>
            </div>
            <div class="col-12">
              <div class="text-subtitle2 text-grey-8">Детали</div>
              <div class="text-body1">{{ selectedAction?.details }}</div>
            </div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Закрыть" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import Chart from 'chart.js/auto'

interface AuditLog {
  id: number
  action: string
  user: string
  timestamp: string
  details: string
}

interface TopUser {
  id: number
  name: string
  actions: number
}

const actionChartRef = ref<HTMLCanvasElement | null>(null)
const timeChartRef = ref<HTMLCanvasElement | null>(null)
const showDetailsDialog = ref(false)
const selectedAction = ref<AuditLog | null>(null)

const filteredLogs = ref<AuditLog[]>([
  {
    id: 1,
    action: 'Создание',
    user: 'John Doe',
    timestamp: '2024-03-25 10:30',
    details: 'Создан новый тест'
  },
  {
    id: 2,
    action: 'Изменение',
    user: 'Jane Smith',
    timestamp: '2024-03-25 09:15',
    details: 'Обновлены настройки проекта'
  }
])

const topUsers = ref<TopUser[]>([
  { id: 1, name: 'John Doe', actions: 45 },
  { id: 2, name: 'Jane Smith', actions: 32 },
  { id: 3, name: 'Mike Johnson', actions: 28 }
])

const columns = [
  {
    name: 'action',
    required: true,
    label: 'Действие',
    align: 'left' as const,
    field: 'action',
    sortable: true
  },
  {
    name: 'user',
    required: true,
    label: 'Пользователь',
    align: 'left' as const,
    field: 'user',
    sortable: true
  },
  {
    name: 'timestamp',
    required: true,
    label: 'Время',
    align: 'left' as const,
    field: 'timestamp',
    sortable: true
  },
  {
    name: 'details',
    required: true,
    label: 'Детали',
    align: 'left' as const,
    field: 'details',
    sortable: true
  },
  {
    name: 'details',
    required: true,
    label: '',
    align: 'center' as const,
    field: 'details'
  }
]

const getActionColor = (action: string) => {
  switch (action.toLowerCase()) {
    case 'создание':
      return 'positive'
    case 'изменение':
      return 'primary'
    case 'удаление':
      return 'negative'
    default:
      return 'grey'
  }
}

const showDetails = (action: AuditLog) => {
  selectedAction.value = action
  showDetailsDialog.value = true
}

onMounted(() => {
  // Initialize charts
  if (actionChartRef.value) {
    new Chart(actionChartRef.value, {
      type: 'doughnut',
      data: {
        labels: ['Создание', 'Изменение', 'Удаление'],
        datasets: [{
          data: [45, 35, 20],
          backgroundColor: ['#10b981', '#3b82f6', '#ef4444']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top'
          }
        }
      }
    })
  }

  if (timeChartRef.value) {
    new Chart(timeChartRef.value, {
      type: 'line',
      data: {
        labels: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
        datasets: [{
          label: 'Активность',
          data: [12, 19, 3, 5, 2, 3, 7],
          borderColor: '#1e3a8a',
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top'
          }
        }
      }
    })
  }
})

onUnmounted(() => {
  // Cleanup charts
  if (actionChartRef.value) {
    Chart.getChart(actionChartRef.value)?.destroy()
  }
  if (timeChartRef.value) {
    Chart.getChart(timeChartRef.value)?.destroy()
  }
})
</script>

<style scoped lang="scss">
.modern-page {
  background: $bg-gradient-light;
  min-height: 100vh;
}

.modern-card {
  border-radius: $border-radius;
  box-shadow: $card-shadow;
  transition: $transition-default;
  overflow: hidden;
  background-color: $background-white;
  border: 1px solid $border-color;
  backdrop-filter: blur(10px);
  
  &:hover {
    box-shadow: $shadow-lg;
    transform: translateY(-2px);
  }
}

.chart-container {
  position: relative;
  height: 300px;
  width: 100%;
}

.modern-table {
  .q-table {
    &__card {
      border-radius: $border-radius;
    }
    
    thead tr th {
      font-weight: 600;
      color: $text-secondary;
      background-color: $background-light;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.05em;
      padding: 16px;
    }
    
    tbody tr {
      cursor: pointer;
      transition: background-color 0.2s ease;
      
      &:hover {
        background-color: $hover-color;
      }
      
      &.selected {
        background-color: $selected-color;
      }
      
      td {
        padding: 16px;
      }
    }
  }
}

.status-badge {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 6px 12px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  box-shadow: $shadow-sm;
}

.user-item {
  border-radius: $border-radius;
  margin-bottom: 8px;
  transition: $transition-default;

  &:hover {
    background-color: $hover-color;
  }

  .q-avatar {
    font-weight: 600;
  }
}

.premium-btn {
  background: $gradient-primary;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 12px 24px;
  font-weight: 600;
  transition: all 0.3s ease;
  
  &:hover {
    transform: translateY(-1px);
    box-shadow: $neon-shadow;
  }
  
  &:active {
    transform: translateY(0);
  }
}
</style> 