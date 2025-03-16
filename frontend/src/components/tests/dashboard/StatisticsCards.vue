<template>
  <div class="dashboard-stats q-mb-lg">
    <div class="row q-col-gutter-md">
      <div class="col-12 col-sm-6 col-md-3">
        <q-card class="stat-card">
          <q-card-section>
            <div class="text-subtitle2">Всего тестов</div>
            <div class="text-h4 q-mt-sm text-primary">
              {{ tests.length }}
              <q-icon name="trending_up" color="positive" class="q-ml-sm" size="24px" />
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <q-card class="stat-card">
          <q-card-section>
            <div class="text-subtitle2">Успешных</div>
            <div class="text-h4 q-mt-sm text-positive">
              {{ tests.filter(t => t.status === 'completed').length }}
              <q-icon name="check_circle" color="positive" class="q-ml-sm" size="24px" />
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <q-card class="stat-card">
          <q-card-section>
            <div class="text-subtitle2">Ошибок</div>
            <div class="text-h4 q-mt-sm text-negative">
              {{ tests.filter(t => t.status === 'failed').length }}
              <q-icon name="error" color="negative" class="q-ml-sm" size="24px" />
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <q-card class="stat-card">
          <q-card-section>
            <div class="text-subtitle2">В процессе</div>
            <div class="text-h4 q-mt-sm text-warning">
              {{ tests.filter(t => t.status === 'running').length }}
              <q-icon name="pending" color="warning" class="q-ml-sm" size="24px" />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { TestResult } from 'src/types/test.type'

defineProps<{
  tests: TestResult[]
}>()
</script>

<style scoped lang="scss">
.stat-card {
  background: white;
  border-radius: 12px;
  transition: all 0.3s ease;
  
  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  }

  .text-h4 {
    display: flex;
    align-items: center;
    font-weight: 500;
  }
}
</style> 