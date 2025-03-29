<template>
  <q-page padding class="modern-page">
    <div class="row q-col-gutter-md">
      <!-- Performance Metrics -->
      <div class="col-12 col-md-6">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Метрики производительности</div>
            <div class="chart-container">
              <canvas ref="performanceChartRef"></canvas>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Test Coverage -->
      <div class="col-12 col-md-6">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Покрытие тестами</div>
            <div class="chart-container">
              <canvas ref="coverageChartRef"></canvas>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Error Trends -->
      <div class="col-12">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Тренды ошибок</div>
            <div class="chart-container">
              <canvas ref="errorTrendsChartRef"></canvas>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Project Health -->
      <div class="col-12">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Здоровье проектов</div>
            <q-table
              :rows="projectHealth"
              :columns="healthColumns"
              row-key="name"
              flat
              bordered
              :pagination="{ rowsPerPage: 5 }"
              class="modern-table"
            >
              <template v-slot:body-cell-health="props">
                <q-td :props="props">
                  <q-linear-progress
                    :value="props.value / 100"
                    :color="getHealthColor(props.value)"
                    class="q-mt-sm"
                    rounded
                    size="8px"
                  />
                </q-td>
              </template>
            </q-table>
          </q-card-section>
        </q-card>
      </div>

      <!-- Team Performance -->
      <div class="col-12">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Производительность команды</div>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-4" v-for="metric in teamMetrics" :key="metric.label">
                <div class="metric-card">
                  <div class="metric-value">{{ metric.value }}</div>
                  <div class="metric-label">{{ metric.label }}</div>
                  <div class="metric-trend" :class="metric.trend">
                    <q-icon :name="metric.trend === 'up' ? 'trending_up' : 'trending_down'" />
                    {{ metric.change }}
                  </div>
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import Chart from 'chart.js/auto'

const performanceChartRef = ref<HTMLCanvasElement | null>(null)
const coverageChartRef = ref<HTMLCanvasElement | null>(null)
const errorTrendsChartRef = ref<HTMLCanvasElement | null>(null)

const projectHealth = ref([
  { name: 'Frontend', health: 85 },
  { name: 'Backend', health: 92 },
  { name: 'API', health: 78 },
  { name: 'Mobile', health: 95 }
])

const healthColumns = [
  {
    name: 'name',
    required: true,
    label: 'Проект',
    align: 'left' as const,
    field: 'name',
    sortable: true
  },
  {
    name: 'health',
    required: true,
    label: 'Здоровье',
    align: 'center' as const,
    field: 'health',
    sortable: true
  }
]

const teamMetrics = ref([
  {
    label: 'Среднее время выполнения',
    value: '2.5 мин',
    trend: 'up',
    change: '+0.3 мин'
  },
  {
    label: 'Успешность тестов',
    value: '94%',
    trend: 'up',
    change: '+2%'
  },
  {
    label: 'Покрытие кода',
    value: '87%',
    trend: 'down',
    change: '-1%'
  }
])

const getHealthColor = (value: number) => {
  if (value >= 90) return 'positive'
  if (value >= 80) return 'primary'
  if (value >= 70) return 'warning'
  return 'negative'
}

onMounted(() => {
  // Initialize charts
  if (performanceChartRef.value) {
    new Chart(performanceChartRef.value, {
      type: 'line',
      data: {
        labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
        datasets: [{
          label: 'Время выполнения',
          data: [65, 59, 80, 81, 56, 55],
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

  if (coverageChartRef.value) {
    new Chart(coverageChartRef.value, {
      type: 'doughnut',
      data: {
        labels: ['Покрыто', 'Непокрыто'],
        datasets: [{
          data: [75, 25],
          backgroundColor: ['#10b981', '#ef4444']
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

  if (errorTrendsChartRef.value) {
    new Chart(errorTrendsChartRef.value, {
      type: 'bar',
      data: {
        labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
        datasets: [{
          label: 'Количество ошибок',
          data: [12, 19, 3, 5, 2, 3],
          backgroundColor: '#ef4444'
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
  if (performanceChartRef.value) {
    Chart.getChart(performanceChartRef.value)?.destroy()
  }
  if (coverageChartRef.value) {
    Chart.getChart(coverageChartRef.value)?.destroy()
  }
  if (errorTrendsChartRef.value) {
    Chart.getChart(errorTrendsChartRef.value)?.destroy()
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

.metric-card {
  background: $glass-background;
  backdrop-filter: blur(10px);
  border-radius: $border-radius;
  padding: 24px;
  text-align: center;
  border: 1px solid $glass-border;
  box-shadow: $glass-shadow;
  transition: $transition-default;

  &:hover {
    transform: translateY(-2px);
    box-shadow: $shadow-lg;
  }

  .metric-value {
    font-size: 2rem;
    font-weight: 700;
    color: $text-primary;
    margin-bottom: 8px;
  }

  .metric-label {
    color: $text-secondary;
    font-size: 0.875rem;
    margin-bottom: 8px;
  }

  .metric-trend {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    font-size: 0.875rem;
    font-weight: 500;

    &.up {
      color: $positive;
    }

    &.down {
      color: $negative;
    }

    .q-icon {
      font-size: 1.1em;
    }
  }
}
</style> 