<template>
  <q-page padding class="modern-page">
    <div class="row q-col-gutter-md">
      <!-- Project List -->
      <div class="col-12">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="row items-center justify-between q-mb-md">
              <div class="text-h6 text-primary">Проекты</div>
              <q-btn
                color="primary"
                icon="add"
                label="Новый проект"
                class="premium-btn"
                @click="showNewProjectDialog = true"
              />
            </div>

            <q-table
              :rows="projects"
              :columns="columns"
              row-key="id"
              flat
              bordered
              :pagination="{ rowsPerPage: 10 }"
              class="modern-table"
            >
              <template v-slot:body-cell-status="props">
                <q-td :props="props">
                  <q-chip
                    :color="getStatusColor(props.value)"
                    text-color="white"
                    dense
                    class="status-badge"
                  >
                    {{ props.value }}
                  </q-chip>
                </q-td>
              </template>

              <template v-slot:body-cell-progress="props">
                <q-td :props="props">
                  <q-linear-progress
                    :value="props.value / 100"
                    :color="getProgressColor(props.value)"
                    class="q-mt-sm"
                    rounded
                    size="8px"
                  />
                </q-td>
              </template>

              <template v-slot:body-cell-actions="props">
                <q-td :props="props">
                  <q-btn-group flat>
                    <q-btn
                      flat
                      round
                      color="primary"
                      icon="edit"
                      @click="editProject(props.row)"
                    >
                      <q-tooltip>Редактировать</q-tooltip>
                    </q-btn>
                    <q-btn
                      flat
                      round
                      color="negative"
                      icon="delete"
                      @click="deleteProject(props.row)"
                    >
                      <q-tooltip>Удалить</q-tooltip>
                    </q-btn>
                  </q-btn-group>
                </q-td>
              </template>
            </q-table>
          </q-card-section>
        </q-card>
      </div>

      <!-- Project Statistics -->
      <div class="col-12 col-md-4">
        <q-card flat bordered class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Статистика проектов</div>
            <div class="row q-col-gutter-md">
              <div class="col-6">
                <div class="text-center">
                  <div class="text-h4 text-primary">{{ stats.totalProjects }}</div>
                  <div class="text-caption text-grey-8">Всего проектов</div>
                </div>
              </div>
              <div class="col-6">
                <div class="text-center">
                  <div class="text-h4 text-positive">{{ stats.activeProjects }}</div>
                  <div class="text-caption text-grey-8">Активных</div>
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 q-mb-md">Последние изменения</div>
            <q-list>
              <q-item v-for="change in recentChanges" :key="change.id">
                <q-item-section>
                  <q-item-label>{{ change.project }}</q-item-label>
                  <q-item-label caption>{{ change.description }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-item-label caption>{{ change.date }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 q-mb-md">Распределение по статусам</div>
            <div class="chart-container">
              <canvas ref="statusChartRef"></canvas>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- New Project Dialog -->
    <q-dialog v-model="showNewProjectDialog" persistent>
      <q-card style="min-width: 350px">
        <q-card-section>
          <div class="text-h6">Новый проект</div>
        </q-card-section>

        <q-card-section>
          <q-form @submit="onSubmit" class="q-gutter-md">
            <q-input
              v-model="newProject.name"
              label="Название проекта"
              :rules="[val => !!val || 'Обязательное поле']"
              outlined
              dense
            />

            <q-input
              v-model="newProject.description"
              label="Описание"
              type="textarea"
              outlined
              dense
            />

            <q-select
              v-model="newProject.status"
              :options="['Активный', 'В разработке', 'На паузе', 'Завершен']"
              label="Статус"
              outlined
              dense
            />

            <q-input
              v-model="newProject.deadline"
              label="Дедлайн"
              type="date"
              outlined
              dense
            />
          </q-form>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Отмена" color="primary" v-close-popup />
          <q-btn flat label="Создать" color="primary" @click="onSubmit" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import {
  Chart,
  CategoryScale,
  LinearScale,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  DoughnutController
} from 'chart.js'

defineOptions({
  name: 'ProjectsPage'
})

Chart.register(
  CategoryScale,
  LinearScale,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  DoughnutController
)

const statusChartRef = ref<HTMLCanvasElement | null>(null)
let statusChart: Chart | null = null

const columns = [
  { name: 'name', label: 'Название', field: 'name', align: 'left' as const },
  { name: 'description', label: 'Описание', field: 'description', align: 'left' as const },
  { name: 'status', label: 'Статус', field: 'status', align: 'left' as const },
  { name: 'progress', label: 'Прогресс', field: 'progress', align: 'left' as const },
  { name: 'deadline', label: 'Дедлайн', field: 'deadline', align: 'left' as const },
  { name: 'actions', label: 'Действия', field: 'actions', align: 'center' as const }
]

const projects = ref([
  {
    id: 1,
    name: 'NexStep',
    description: 'Основной проект',
    status: 'Активный',
    progress: 75,
    deadline: '2024-06-30'
  },
  {
    id: 2,
    name: 'Backend API',
    description: 'API сервер',
    status: 'В разработке',
    progress: 45,
    deadline: '2024-05-15'
  },
  {
    id: 3,
    name: 'Frontend App',
    description: 'Веб-приложение',
    status: 'На паузе',
    progress: 30,
    deadline: '2024-07-20'
  }
])

const stats = ref({
  totalProjects: 3,
  activeProjects: 1
})

const recentChanges = ref([
  {
    id: 1,
    project: 'NexStep',
    description: 'Обновлены тесты',
    date: '2024-03-25'
  },
  {
    id: 2,
    project: 'Backend API',
    description: 'Добавлены новые эндпоинты',
    date: '2024-03-24'
  },
  {
    id: 3,
    project: 'Frontend App',
    description: 'Исправлены ошибки',
    date: '2024-03-23'
  }
])

const showNewProjectDialog = ref(false)
const newProject = ref({
  name: '',
  description: '',
  status: 'Активный',
  deadline: ''
})

const getStatusColor = (status: string) => {
  switch (status) {
    case 'Активный':
      return 'positive'
    case 'В разработке':
      return 'primary'
    case 'На паузе':
      return 'warning'
    case 'Завершен':
      return 'grey'
    default:
      return 'grey'
  }
}

const getProgressColor = (value: number) => {
  if (value >= 80) return 'positive'
  if (value >= 40) return 'warning'
  return 'negative'
}

const createStatusChart = () => {
  if (!statusChartRef.value) return

  statusChart?.destroy()
  const statusCounts = {
    'Активный': projects.value.filter(p => p.status === 'Активный').length,
    'В разработке': projects.value.filter(p => p.status === 'В разработке').length,
    'На паузе': projects.value.filter(p => p.status === 'На паузе').length,
    'Завершен': projects.value.filter(p => p.status === 'Завершен').length
  }

  statusChart = new Chart(statusChartRef.value, {
    type: 'doughnut',
    data: {
      labels: Object.keys(statusCounts),
      datasets: [{
        data: Object.values(statusCounts),
        backgroundColor: ['#4CAF50', '#2196F3', '#FFC107', '#9E9E9E']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom' as const
        }
      }
    }
  })
}

interface Project {
  id: number
  name: string
  description: string
  status: string
  deadline: string
  progress: number
}

const editProject = (project: Project) => {
  console.log('Editing project:', project)
  // TODO: Implement project editing
}

const deleteProject = (project: Project) => {
  console.log('Deleting project:', project)
  // TODO: Implement project deletion
}

const onSubmit = () => {
  // TODO: Implement project creation
  showNewProjectDialog.value = false
}

onMounted(createStatusChart)
onUnmounted(() => {
  statusChart?.destroy()
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

.chart-container {
  height: 200px;
  position: relative;
}
</style> 