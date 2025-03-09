<template>
  <q-page padding>
    <q-table
      title="Тесты"
      :rows="tests"
      :columns="columns"
      row-key="id"
      :loading="loading"
    >
      <template v-slot:body-cell-status="props">
        <q-td :props="props">
          <q-badge :color="statusColor(props.value)">
            {{ props.value }}
          </q-badge>
        </q-td>
      </template>
    </q-table>
  </q-page>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useTestStore } from 'stores/testStore'
import { onMounted } from 'vue'

const testStore = useTestStore()
const { tests, loading } = storeToRefs(testStore)

onMounted(async () => {
  await testStore.fetchTests()
})

const columns = [
  { name: 'id', label: 'ID', field: 'id' },
  { name: 'name', label: 'Название', field: 'name' },
  { name: 'description', label: 'Описание', field: 'description' },
  { name: 'execution_time', label: 'Время выполнения', field: 'execution_time' },
  { name: 'status', label: 'Статус', field: 'status' },
]

const statusColor = (status: string) => {
  switch(status) {
    case 'completed': return 'green'
    case 'failed': return 'red'
    default: return 'orange'
  }
}
</script>