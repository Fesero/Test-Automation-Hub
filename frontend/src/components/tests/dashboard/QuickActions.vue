<template>
  <div class="quick-actions q-mb-lg">
    <div class="row items-center justify-between q-gutter-md">
      <!-- Left side actions -->
      <div class="col-auto">
        <div class="row q-gutter-sm">
          <div class="col-auto">
            <q-btn 
                color="primary" 
                icon="add" 
                label="Новый тест" 
                @click="$emit('new-test')" 
                unelevated 
                :disable="disabled"
            />
          </div>
           <div class="col-auto">
             <q-btn 
                 outline 
                 color="secondary" 
                 icon="refresh" 
                 label="Обновить" 
                 @click="$emit('refresh')" 
                 :disable="disabled"
             />
           </div>
           <div class="col-auto">
             <q-btn 
                 flat 
                 color="secondary" 
                 icon="file_download" 
                 label="Экспорт"
                 @click="$emit('export')" 
                 :disable="disabled"
             />
           </div>
           <!-- Removed filter dropdowns as filters are separate inputs now -->
        </div>
      </div>

      <!-- Right side filters -->
      <div class="col-auto">
        <div class="row items-center q-gutter-sm">
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
              dark
              popup-content-class="bg-surface-alt"
              options-dark
              :disable="disabled"
            >
              <template v-slot:prepend>
                <q-icon name="filter_list" color="secondary" />
              </template>
            </q-select>
          </div>
          <div class="col-auto">
            <q-btn icon="event" round flat color="secondary" :disable="disabled">
              <q-popup-proxy 
                transition-show="scale" 
                transition-hide="scale" 
                class="bg-surface-alt"
                cover
              >
                <q-date 
                  :model-value="dateRange" 
                  @update:model-value="$emit('update:dateRange', $event)"
                  range 
                  minimal 
                  dark
                  color="primary"
                >
                  <div class="row items-center justify-end q-gutter-sm q-pa-sm">
                    <q-btn label="Отмена" color="primary" flat v-close-popup />
                    <q-btn label="OK" color="primary" flat v-close-popup /> <!-- Apply happens via v-model -->
                    <q-btn label="Сбросить" color="negative" flat @click="$emit('update:dateRange', null)" v-close-popup />
                  </div>
                </q-date>
              </q-popup-proxy>
               <q-tooltip>Выбрать период</q-tooltip>
               <q-badge v-if="dateRange" color="primary" floating transparent rounded>!</q-badge>
            </q-btn>
            <span v-if="dateRange" class="text-caption text-secondary q-ml-xs">
                {{ formattedDateRange }}
            </span>
             <q-btn v-if="dateRange" icon="close" round flat dense size="sm" color="negative" @click="$emit('update:dateRange', null)" class="q-ml-xs">
                <q-tooltip>Сбросить период</q-tooltip>
             </q-btn>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// Define types for props and emits
type DateRange = { from: string; to: string } | null;

const props = defineProps<{
  filterType: string;
  dateRange: DateRange;
  disabled?: boolean; // Add disabled prop
}>();

defineEmits<{
  'update:filterType': [value: string];
  'update:dateRange': [value: DateRange]; // Update emit type
  'new-test': [];
  'export': [];
  'refresh': [];
}>();

const formattedDateRange = computed(() => {
  if (!props.dateRange) return '';
  const options: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'short', day: 'numeric' };
  const from = new Date(props.dateRange.from).toLocaleDateString('ru-RU', options);
  const to = new Date(props.dateRange.to).toLocaleDateString('ru-RU', options);
  return `${from} - ${to}`;
});

</script>

<style scoped lang="scss">
.quick-actions {
  background: $surface-background; // Use dark surface
  border-radius: $card-border-radius; // Use variable
  padding: 1rem;
  border: 1px solid $separator-color; // Add border
  box-shadow: $card-box-shadow; // Add shadow
}

// Style select and input for dark theme
.filter-select {
  min-width: 180px;
  :deep(.q-field__native), 
  :deep(.q-field__control), 
  :deep(.q-field__marginal) {
    color: $text-primary;
  }
   :deep(.q-field__label) {
        color: $text-secondary !important;
    }
  :deep(.q-field__control) {
      background-color: lighten($surface-background, 5%) !important;
      &:before, &:after {
        border-color: $separator-color !important;
      }
  }
}

</style> 