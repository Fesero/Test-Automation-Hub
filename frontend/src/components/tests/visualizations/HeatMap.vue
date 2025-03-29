<template>
  <q-card flat bordered class="heatmap-card">
    <q-card-section class="bg-primary text-white">
      <div class="text-h6">Тепловая карта ошибок</div>
      <div class="text-subtitle2">Распределение проблем по файлам</div>
    </q-card-section>
    <q-card-section>
      <div class="row items-center q-mb-md">
        <div class="col">
          <q-btn-toggle
            v-model="metric"
            :options="[
              { label: 'Ошибки', value: 'errors' },
              { label: 'Предупреждения', value: 'warnings' },
              { label: 'Общее', value: 'total' }
            ]"
            color="primary"
            text-color="white"
            class="q-mr-md"
          />
        </div>
        <div class="col-auto">
          <div class="heatmap-legend">
            <span class="text-caption">Меньше</span>
            <div class="legend-gradient" :class="metric"></div>
            <span class="text-caption">Больше</span>
          </div>
        </div>
      </div>
      <div class="heatmap-container" ref="container"></div>
    </q-card-section>
  </q-card>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue'
import * as d3 from 'd3'

interface FileMetrics {
  path: string
  fullPath: string
  errors: number
  warnings: number
  total: number
}

interface BubbleTooltipData {
  x: number
  y: number
  title: string
  subtitle: string
  errors: number
  warnings: number
  severity: number
}

const props = defineProps<{
  files: Record<string, { errors: number, warnings: number }>
}>()

const emit = defineEmits<{
  'select-file': [path: string]
}>()

const container = ref<HTMLElement | null>(null)
const metric = ref('total')
const tooltip = ref<BubbleTooltipData | null>(null)

const getMetricValue = (file: FileMetrics) => {
  switch (metric.value) {
    case 'errors':
      return file.errors
    case 'warnings':
      return file.warnings
    case 'total':
      return file.total
    default:
      return 0
  }
}

const drawHeatmap = () => {
  if (!container.value) return

  // Очищаем контейнер
  d3.select(container.value).selectAll("*").remove()

  // Подготавливаем данные
  const data = Object.entries(props.files).map(([path, stats]) => ({
    path: path.split(/[\\/]/).pop() || path,
    fullPath: path,
    errors: stats.errors,
    warnings: stats.warnings,
    total: stats.errors + stats.warnings
  }))

  // Размеры и отступы
  const margin = { top: 20, right: 20, bottom: 30, left: 200 }
  const width = container.value.clientWidth - margin.left - margin.right
  const cellHeight = 40
  const height = Math.min(600, data.length * cellHeight)

  // Создаем SVG
  const svg = d3.select(container.value)
    .append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom)
    .append("g")
    .attr("transform", `translate(${margin.left},${margin.top})`)

  // Scales
  const yScale = d3.scaleBand()
    .domain(data.map(d => d.path))
    .range([0, height])
    .padding(0.1)

  const maxValue = d3.max(data, d => getMetricValue(d)) || 0
  const colorScale = d3.scaleSequential(d3.interpolateOrRd)
    .domain([0, maxValue])

  // Отрисовка ячеек
  svg.selectAll("rect")
    .data(data)
    .enter()
    .append("rect")
    .attr("y", d => yScale(d.path) || 0)
    .attr("height", yScale.bandwidth())
    .attr("width", width)
    .attr("fill", d => colorScale(getMetricValue(d)))
    .attr("rx", 4)
    .attr("ry", 4)
    .style("cursor", "pointer")
    .on("mouseover", (event, d) => {
      const metricValue = getMetricValue(d)
      tooltip.value = {
        x: event.pageX,
        y: event.pageY,
        title: d.path,
        subtitle: d.fullPath,
        errors: d.errors,
        warnings: d.warnings,
        severity: metricValue / maxValue
      }
    })
    .on("mouseout", () => {
      tooltip.value = null
    })
    .on("click", (_, d) => {
      emit('select-file', d.fullPath)
    })

  // Добавляем названия файлов
  svg.selectAll(".file-label")
    .data(data)
    .enter()
    .append("text")
    .attr("class", "file-label")
    .attr("x", -10)
    .attr("y", d => (yScale(d.path) || 0) + yScale.bandwidth() / 2)
    .attr("dy", ".35em")
    .attr("text-anchor", "end")
    .text(d => d.path)
    .style("font-size", "12px")
    .style("fill", "#2c3e50")
}

onMounted(() => {
  void nextTick(drawHeatmap)
})

watch([metric], () => {
  void nextTick(drawHeatmap)
})

defineExpose({
  tooltip
})
</script>

<style scoped lang="scss">
.heatmap-card {
  .heatmap-container {
    height: 600px;
    overflow-y: auto;
    padding: 1rem;
  }

  .heatmap-legend {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 16px;

    .legend-gradient {
      width: 100px;
      height: 12px;
      border-radius: 6px;
      
      &.errors {
        background: linear-gradient(to right, rgba($negative, 0.1), $negative);
      }
      
      &.warnings {
        background: linear-gradient(to right, rgba($warning, 0.1), $warning);
      }
      
      &.total {
        background: linear-gradient(to right, rgba($primary, 0.1), $primary);
      }
    }
  }

  .file-label {
    transition: all 0.3s ease;
    
    &:hover {
      fill: var(--q-primary);
      font-weight: 500;
    }
  }
}
</style> 