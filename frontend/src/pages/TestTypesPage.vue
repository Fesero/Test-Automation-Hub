<template>
  <q-page padding class="modern-page">
    <div class="row q-col-gutter-md">
      <!-- Общая статистика -->
      <div class="col-12">
        <q-card class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Общая статистика тестов</div>
            <div class="row q-col-gutter-md">
              <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                  <div class="text-h6">{{ totalTests }}</div>
                  <div class="text-subtitle2">Всего тестов</div>
                  <q-linear-progress
                    :value="totalTests / 1000"
                    color="primary"
                    class="q-mt-sm"
                  />
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                  <div class="text-h6">{{ coverage }}%</div>
                  <div class="text-subtitle2">Покрытие кода</div>
                  <q-linear-progress
                    :value="coverage / 100"
                    color="positive"
                    class="q-mt-sm"
                  />
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                  <div class="text-h6">{{ successRate }}%</div>
                  <div class="text-subtitle2">Успешных тестов</div>
                  <q-linear-progress
                    :value="successRate / 100"
                    color="positive"
                    class="q-mt-sm"
                  />
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="metric-card">
                  <div class="text-h6">{{ avgDuration }}s</div>
                  <div class="text-subtitle2">Среднее время</div>
                  <q-linear-progress
                    :value="avgDuration / 60"
                    color="warning"
                    class="q-mt-sm"
                  />
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Графики -->
      <div class="col-md-6">
        <q-card class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Распределение тестов</div>
            <canvas ref="testDistributionChart"></canvas>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-md-6">
        <q-card class="modern-card">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">Тренд покрытия</div>
            <canvas ref="coverageTrendChart"></canvas>
          </q-card-section>
        </q-card>
      </div>

      <!-- Типы тестов -->
      <div class="col-12">
        <div class="text-h5 text-primary q-mb-md">Типы тестов</div>
        <div class="row q-col-gutter-md">
          <!-- Unit Tests -->
          <div class="col-md-6 col-lg-4">
            <q-card class="modern-card test-type-card">
          <q-card-section>
                <div class="text-h6">Unit Tests</div>
                <div class="text-subtitle2">Модульные тесты</div>
                <div class="row q-mt-md">
                  <div class="col-6">
                    <q-btn
                      color="primary"
                      label="Запустить"
                      class="full-width"
                      @click="runUnitTests"
                    />
                      </div>
                      <div class="col-6">
                    <q-btn
                      color="secondary"
                      label="Настройки"
                      class="full-width"
                      @click="showUnitTestSettings"
                    />
                  </div>
                </div>
                          </q-card-section>
                        </q-card>
                      </div>

          <!-- Integration Tests -->
          <div class="col-md-6 col-lg-4">
            <q-card class="modern-card test-type-card">
              <q-card-section>
                <div class="text-h6">Integration Tests</div>
                <div class="text-subtitle2">Интеграционные тесты</div>
                <div class="row q-mt-md">
                  <div class="col-6">
                    <q-btn
                      color="primary"
                      label="Запустить"
                      class="full-width"
                      @click="runIntegrationTests"
                    />
                  </div>
                  <div class="col-6">
                    <q-btn
                      color="secondary"
                      label="Настройки"
                      class="full-width"
                      @click="showIntegrationTestSettings"
                    />
                    </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

          <!-- E2E Tests -->
          <div class="col-md-6 col-lg-4">
            <q-card class="modern-card test-type-card">
          <q-card-section>
                <div class="text-h6">E2E Tests</div>
                <div class="text-subtitle2">End-to-End тесты</div>
                <div class="row q-mt-md">
                  <div class="col-6">
                    <q-btn
                      color="primary"
                      label="Запустить"
                      class="full-width"
                      @click="runE2ETests"
                    />
                  </div>
                  <div class="col-6">
                    <q-btn
                      color="secondary"
                      label="Настройки"
                      class="full-width"
                      @click="showE2ETestSettings"
                    />
                  </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

          <!-- Load Tests -->
          <div class="col-md-6 col-lg-4">
            <q-card class="modern-card test-type-card">
          <q-card-section>
                <div class="text-h6">Load Tests</div>
                <div class="text-subtitle2">Нагрузочные тесты</div>
                <div class="row q-mt-md">
                  <div class="col-6">
                    <q-btn
                      color="primary"
                      label="Запустить"
                      class="full-width"
                      @click="runLoadTests"
                    />
                  </div>
                  <div class="col-6">
                    <q-btn
                      color="secondary"
                      label="Настройки"
                      class="full-width"
                      @click="showLoadTestSettings"
                    />
                  </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

          <!-- Security Tests -->
          <div class="col-md-6 col-lg-4">
            <q-card class="modern-card test-type-card">
          <q-card-section>
                <div class="text-h6">Security Tests</div>
                <div class="text-subtitle2">Тесты безопасности</div>
                <div class="row q-mt-md">
                  <div class="col-6">
                    <q-btn
                      color="primary"
                      label="Запустить"
                      class="full-width"
                      @click="runSecurityTests"
                    />
                  </div>
                  <div class="col-6">
                    <q-btn
                      color="secondary"
                      label="Настройки"
                      class="full-width"
                      @click="showSecurityTestSettings"
                    />
                  </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

          <!-- Performance Tests -->
          <div class="col-md-6 col-lg-4">
            <q-card class="modern-card test-type-card">
          <q-card-section>
                <div class="text-h6">Performance Tests</div>
                <div class="text-subtitle2">Тесты производительности</div>
                <div class="row q-mt-md">
                  <div class="col-6">
                    <q-btn
                      color="primary"
                      label="Запустить"
                      class="full-width"
                      @click="runPerformanceTests"
                    />
                  </div>
                  <div class="col-6">
                    <q-btn
                      color="secondary"
                      label="Настройки"
                      class="full-width"
                      @click="showPerformanceTestSettings"
                    />
                      </div>
                      </div>
                          </q-card-section>
                        </q-card>
                      </div>
                    </div>
      </div>
      </div>

    <!-- Диалоги настроек -->
    <q-dialog v-model="showSettings" persistent>
      <q-card class="settings-dialog">
          <q-card-section>
          <div class="text-h6">{{ currentSettings.title }}</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-form @submit="onSubmit" class="q-gutter-md">
            <template v-for="(field, key) in currentSettings.fields" :key="key">
              <q-input
                v-if="field.type === 'text'"
                v-model="currentSettings.values[key] as string"
                :label="field.label"
                :rules="field.rules"
                class="modern-input"
              />
              <q-select
                v-else-if="field.type === 'select'"
                v-model="currentSettings.values[key] as string"
                :options="field.options"
                :label="field.label"
                class="modern-select"
              />
              <q-toggle
                v-else-if="field.type === 'toggle'"
                v-model="currentSettings.values[key] as boolean"
                :label="field.label"
                class="modern-toggle"
              />
              <q-checkbox
                v-else-if="field.type === 'checkbox'"
                v-model="currentSettings.values[key] as boolean"
                :label="field.label"
                class="modern-checkbox"
              />
            </template>

            <div class="row q-mt-md">
              <q-space />
              <q-btn label="Отмена" color="grey" v-close-popup />
              <q-btn label="Сохранить" type="submit" color="primary" class="q-ml-sm" />
            </div>
          </q-form>
          </q-card-section>
        </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Chart from 'chart.js/auto'

interface TestSettings {
  title: string
  fields: Record<string, {
    type: 'text' | 'select' | 'toggle' | 'checkbox'
    label: string
    options?: string[]
    rules?: ((val: string) => boolean | string)[]
  }>
}

interface CurrentSettings {
  title: string
  fields: Record<string, {
    type: 'text' | 'select' | 'toggle' | 'checkbox'
    label: string
    options?: string[]
    rules?: ((val: string) => boolean | string)[]
  }>
  values: Record<string, string | number | boolean>
}

// Статистика
const totalTests = ref<number>(0)
const coverage = ref<number>(0)
const successRate = ref<number>(0)
const avgDuration = ref<number>(0)

// Графики
const testDistributionChart = ref<HTMLCanvasElement | null>(null)
const coverageTrendChart = ref<HTMLCanvasElement | null>(null)

// Настройки
const showSettings = ref<boolean>(false)
const currentSettings = ref<CurrentSettings>({
  title: '',
  fields: {},
  values: {}
})

// Уникальные настройки для каждого типа теста
const testSettings: Record<string, TestSettings> = {
  unit: {
    title: 'Настройки модульных тестов',
    fields: {
      framework: {
        type: 'select',
        label: 'Фреймворк',
        options: ['Jest', 'Mocha', 'Vitest'],
        rules: [(val: string) => !!val || 'Выберите фреймворк']
      },
      coverageThreshold: {
        type: 'text',
        label: 'Порог покрытия (%)',
        rules: [
          (val: string) => !!val || 'Введите порог покрытия',
          (val: string) => /^\d+$/.test(val) || 'Только числа'
        ]
      },
      parallel: {
        type: 'toggle',
        label: 'Параллельное выполнение'
      }
    }
  },
  integration: {
    title: 'Настройки интеграционных тестов',
    fields: {
      environment: {
        type: 'select',
        label: 'Окружение',
        options: ['Development', 'Staging', 'Production'],
        rules: [(val: string) => !!val || 'Выберите окружение']
      },
      timeout: {
        type: 'text',
        label: 'Таймаут (секунды)',
        rules: [
          (val: string) => !!val || 'Введите таймаут',
          (val: string) => /^\d+$/.test(val) || 'Только числа'
        ]
      },
      retryCount: {
        type: 'text',
        label: 'Количество попыток',
        rules: [
          (val: string) => !!val || 'Введите количество попыток',
          (val: string) => /^\d+$/.test(val) || 'Только числа'
        ]
      }
    }
  },
  e2e: {
    title: 'Настройки E2E тестов',
    fields: {
      browser: {
        type: 'select',
        label: 'Браузер',
        options: ['Chrome', 'Firefox', 'Safari'],
        rules: [(val: string) => !!val || 'Выберите браузер']
      },
      headless: {
        type: 'toggle',
        label: 'Безголовый режим'
      },
      screenshots: {
        type: 'toggle',
        label: 'Скриншоты при ошибках'
      }
    }
  },
  load: {
    title: 'Настройки нагрузочных тестов',
    fields: {
      users: {
        type: 'text',
        label: 'Количество пользователей',
        rules: [
          (val: string) => !!val || 'Введите количество пользователей',
          (val: string) => /^\d+$/.test(val) || 'Только числа'
        ]
      },
      duration: {
        type: 'text',
        label: 'Длительность (минуты)',
        rules: [
          (val: string) => !!val || 'Введите длительность',
          (val: string) => /^\d+$/.test(val) || 'Только числа'
        ]
      },
      rampUp: {
        type: 'text',
        label: 'Время разгона (секунды)',
        rules: [
          (val: string) => !!val || 'Введите время разгона',
          (val: string) => /^\d+$/.test(val) || 'Только числа'
        ]
      }
    }
  },
  security: {
    title: 'Настройки тестов безопасности',
    fields: {
      scanType: {
        type: 'select',
        label: 'Тип сканирования',
        options: ['Базовое', 'Расширенное', 'Полное'],
        rules: [(val: string) => !!val || 'Выберите тип сканирования']
      },
      vulnerabilities: {
        type: 'checkbox',
        label: 'Проверка уязвимостей'
      },
      dependencies: {
        type: 'checkbox',
        label: 'Проверка зависимостей'
      }
    }
  },
  performance: {
    title: 'Настройки тестов производительности',
    fields: {
      metrics: {
        type: 'select',
        label: 'Метрики',
        options: ['Время отклика', 'Пропускная способность', 'Все'],
        rules: [(val: string) => !!val || 'Выберите метрики']
      },
      threshold: {
        type: 'text',
        label: 'Порог (мс)',
        rules: [
          (val: string) => !!val || 'Введите порог',
          (val: string) => /^\d+$/.test(val) || 'Только числа'
        ]
      },
      iterations: {
        type: 'text',
        label: 'Количество итераций',
        rules: [
          (val: string) => !!val || 'Введите количество итераций',
          (val: string) => /^\d+$/.test(val) || 'Только числа'
        ]
      }
    }
  }
}

// Инициализация графиков
onMounted(() => {
  // Распределение тестов
  if (testDistributionChart.value) {
    new Chart(testDistributionChart.value, {
      type: 'doughnut',
      data: {
        labels: ['Unit', 'Integration', 'E2E', 'Load', 'Security', 'Performance'],
        datasets: [{
          data: [30, 25, 20, 10, 10, 5],
          backgroundColor: [
            '#4CAF50',
            '#2196F3',
            '#FFC107',
            '#9C27B0',
            '#F44336',
            '#FF9800'
          ]
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    })
  }

  // Тренд покрытия
  if (coverageTrendChart.value) {
    new Chart(coverageTrendChart.value, {
      type: 'line',
      data: {
        labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
        datasets: [{
          label: 'Покрытие кода',
          data: [65, 70, 75, 80, 85, 90],
          borderColor: '#4CAF50',
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            max: 100
          }
        }
      }
    })
  }

  // Инициализация статистики
  totalTests.value = 150
  coverage.value = 85
  successRate.value = 92
  avgDuration.value = 45
})

// Обработчики запуска тестов
const runUnitTests = () => console.log('Running unit tests...')
const runIntegrationTests = () => console.log('Running integration tests...')
const runE2ETests = () => console.log('Running E2E tests...')
const runLoadTests = () => console.log('Running load tests...')
const runSecurityTests = () => console.log('Running security tests...')
const runPerformanceTests = () => console.log('Running performance tests...')

// Обработчики настроек
const showUnitTestSettings = () => {
  const settings = testSettings['unit']
  if (!settings) return
  currentSettings.value = {
    title: settings.title,
    fields: settings.fields,
    values: Object.keys(settings.fields).reduce((acc, key) => {
      const field = settings.fields[key]
      if (!field) return acc
      acc[key] = field.type === 'text' ? '' : field.type === 'toggle' || field.type === 'checkbox' ? false : ''
      return acc
    }, {} as Record<string, string | number | boolean>)
  }
  showSettings.value = true
}

const showIntegrationTestSettings = () => {
  const settings = testSettings['integration']
  if (!settings) return
  currentSettings.value = {
    title: settings.title,
    fields: settings.fields,
    values: Object.keys(settings.fields).reduce((acc, key) => {
      const field = settings.fields[key]
      if (!field) return acc
      acc[key] = field.type === 'text' ? '' : field.type === 'toggle' || field.type === 'checkbox' ? false : ''
      return acc
    }, {} as Record<string, string | number | boolean>)
  }
  showSettings.value = true
}

const showE2ETestSettings = () => {
  const settings = testSettings['e2e']
  if (!settings) return
  currentSettings.value = {
    title: settings.title,
    fields: settings.fields,
    values: Object.keys(settings.fields).reduce((acc, key) => {
      const field = settings.fields[key]
      if (!field) return acc
      acc[key] = field.type === 'text' ? '' : field.type === 'toggle' || field.type === 'checkbox' ? false : ''
      return acc
    }, {} as Record<string, string | number | boolean>)
  }
  showSettings.value = true
}

const showLoadTestSettings = () => {
  const settings = testSettings['load']
  if (!settings) return
  currentSettings.value = {
    title: settings.title,
    fields: settings.fields,
    values: Object.keys(settings.fields).reduce((acc, key) => {
      const field = settings.fields[key]
      if (!field) return acc
      acc[key] = field.type === 'text' ? '' : field.type === 'toggle' || field.type === 'checkbox' ? false : ''
      return acc
    }, {} as Record<string, string | number | boolean>)
  }
  showSettings.value = true
}

const showSecurityTestSettings = () => {
  const settings = testSettings['security']
  if (!settings) return
  currentSettings.value = {
    title: settings.title,
    fields: settings.fields,
    values: Object.keys(settings.fields).reduce((acc, key) => {
      const field = settings.fields[key]
      if (!field) return acc
      acc[key] = field.type === 'text' ? '' : field.type === 'toggle' || field.type === 'checkbox' ? false : ''
      return acc
    }, {} as Record<string, string | number | boolean>)
  }
  showSettings.value = true
}

const showPerformanceTestSettings = () => {
  const settings = testSettings['performance']
  if (!settings) return
  currentSettings.value = {
    title: settings.title,
    fields: settings.fields,
    values: Object.keys(settings.fields).reduce((acc, key) => {
      const field = settings.fields[key]
      if (!field) return acc
      acc[key] = field.type === 'text' ? '' : field.type === 'toggle' || field.type === 'checkbox' ? false : ''
      return acc
    }, {} as Record<string, string | number | boolean>)
  }
  showSettings.value = true
}

const onSubmit = () => {
  console.log('Settings saved:', currentSettings.value.values)
  showSettings.value = false
}
</script>

<style scoped lang="scss">
.modern-page {
  background: $bg-gradient-light;
  min-height: 100vh;
}

.modern-card {
  background: $glass-background;
  backdrop-filter: blur(10px);
  border-radius: $border-radius;
  box-shadow: $glass-shadow;
  border: 1px solid $glass-border;
  transition: all 0.3s ease;

  &:hover {
    transform: translateY(-2px);
    box-shadow: $neon-shadow;
  }
}

.metric-card {
  background: $glass-background;
  padding: 16px;
  border-radius: $border-radius;
  text-align: center;
  transition: all 0.3s ease;

  &:hover {
    transform: translateY(-2px);
    box-shadow: $neon-shadow;
  }

  .text-h6 {
    color: $primary;
    font-weight: 600;
  }

  .text-subtitle2 {
    color: $text-secondary;
  }
}

.test-type-card {
  height: 100%;
  display: flex;
  flex-direction: column;

  .q-card__section {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .text-h6 {
    color: $primary;
    font-weight: 600;
  }

  .text-subtitle2 {
    color: $text-secondary;
  }
}

.settings-dialog {
  max-width: 500px;
  width: 100%;
  background: $glass-background;
  backdrop-filter: blur(10px);
  border-radius: $border-radius;
  box-shadow: $glass-shadow;
  border: 1px solid $glass-border;
}

.modern-input {
  .q-field__control {
    background: $glass-background;
    border-radius: $border-radius;
    border: 1px solid $glass-border;
  }
}

.modern-select {
  .q-field__control {
    background: $glass-background;
    border-radius: $border-radius;
    border: 1px solid $glass-border;
  }
}

.modern-toggle {
  .q-toggle__inner {
    color: $primary;
  }
}

.modern-checkbox {
  .q-checkbox__inner {
    color: $primary;
  }
}
</style> 