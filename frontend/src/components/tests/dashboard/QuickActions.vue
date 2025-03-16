<template>
  <div class="quick-actions q-mb-lg">
    <div class="row items-center justify-between">
      <div class="col-12 col-sm-auto">
        <div class="row q-col-gutter-sm">
          <div class="col-auto">
            <q-btn color="primary" icon="add" label="Новый тест" @click="$emit('new-test')" />
          </div>
          <div class="col-auto">
            <q-btn-dropdown color="secondary" icon="filter_list" label="Быстрые фильтры">
              <q-list>
                <q-item clickable v-close-popup @click="$emit('update:filterType', 'sniffer')">
                  <q-item-section avatar>
                    <q-icon name="code" color="primary" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Sniffer тесты</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="$emit('update:filterType', 'static_analysis')">
                  <q-item-section avatar>
                    <q-icon name="analytics" color="purple" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Статический анализ</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </div>
          <div class="col-auto">
            <q-btn-dropdown color="accent" icon="more_vert" flat>
              <q-list>
                <q-item clickable v-close-popup @click="$emit('export')">
                  <q-item-section avatar>
                    <q-icon name="file_download" />
                  </q-item-section>
                  <q-item-section>Экспорт CSV</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="$emit('refresh')">
                  <q-item-section avatar>
                    <q-icon name="refresh" />
                  </q-item-section>
                  <q-item-section>Обновить все</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </div>
        </div>
      </div>

      <div class="col-12 col-sm-auto q-mt-sm q-mt-sm-none">
        <div class="row q-col-gutter-sm">
          <div class="col-auto">
            <q-select
              :model-value="filterType"
              @update:model-value="$emit('update:filterType', $event)"
              :options="['Все', 'sniffer', 'static_analysis']"
              label="Тип теста"
              outlined
              dense
              class="filter-select"
              :class="{ 'active': filterType !== 'Все' }"
              emit-value
            >
              <template v-slot:prepend>
                <q-icon name="filter_list" />
              </template>
            </q-select>
          </div>
          <div class="col-auto">
            <q-input
              :model-value="dateRange"
              @update:model-value="(val: string | number | null) => $emit('update:dateRange', val?.toString() || '')"
              label="Период"
              outlined
              dense
              class="filter-input"
              type="date"
            >
              <template v-slot:prepend>
                <q-icon name="event" />
              </template>
            </q-input>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  filterType: string
  dateRange: string
}>()

defineEmits<{
  'update:filterType': [value: string]
  'update:dateRange': [value: string]
  'new-test': []
  'export': []
  'refresh': []
}>()
</script>

<style scoped lang="scss">
.quick-actions {
  background: white;
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);

  .q-btn {
    min-width: 120px;
  }
}

.filter-select, .filter-input {
  min-width: 200px;
}
</style> 