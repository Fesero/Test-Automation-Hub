<template>
  <q-card flat bordered class="bubble-map-card">
    <q-card-section class="bg-primary text-white">
      <div class="row items-center justify-between">
        <div>
          <div class="text-h6">Карта проблем</div>
          <div class="text-subtitle2">Визуализация ошибок и предупреждений</div>
        </div>
        <div class="row q-gutter-sm">
          <q-select
            v-model="metric"
            :options="[
              { label: 'Все проблемы', value: 'total' },
              { label: 'Только ошибки', value: 'errors' },
              { label: 'Только предупреждения', value: 'warnings' }
            ]"
            dense
            outlined
            class="bg-white text-dark"
            style="width: 200px"
          />
          <q-btn-group flat>
            <q-btn
              flat
              round
              color="white"
              icon="zoom_in"
              @click="zoom(1.2)"
            >
              <q-tooltip>Увеличить</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              color="white"
              icon="zoom_out"
              @click="zoom(0.8)"
            >
              <q-tooltip>Уменьшить</q-tooltip>
            </q-btn>
            <q-btn
              flat
              round
              color="white"
              icon="restart_alt"
              @click="resetZoom"
            >
              <q-tooltip>Сбросить масштаб</q-tooltip>
            </q-btn>
          </q-btn-group>
        </div>
      </div>
    </q-card-section>
    <q-card-section class="bubble-map-container">
      <svg ref="bubbleMap" width="100%" height="600"></svg>
    </q-card-section>
  </q-card>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue'
import * as d3 from 'd3'
import path from 'path'

interface HierarchyData {
  name?: string
  value?: number
  type: string
  details?: string
  children?: HierarchyData[]
}

interface BubbleNode extends HierarchyData {
  name: string
  value: number
  type: string
  details: string
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

const bubbleMap = ref<SVGSVGElement | null>(null)
const metric = ref('total')
const zoomLevel = ref(1)
const tooltip = ref<BubbleTooltipData | null>(null)

const zoom = (factor: number) => {
  zoomLevel.value = Math.min(Math.max(zoomLevel.value * factor, 0.5), 2)
  void nextTick(drawBubbleMap)
}

const resetZoom = () => {
  zoomLevel.value = 1
  void nextTick(drawBubbleMap)
}

const drawBubbleMap = () => {
  if (!bubbleMap.value) return

  // Создаем корректную иерархическую структуру с учетом выбранной метрики
  const rootData: BubbleNode = {
    name: 'Проект',
    value: 0,
    type: '',
    details: 'Корневой элемент',
    children: Object.entries(props.files)
      .filter(([, data]) => {
        switch (metric.value) {
          case 'errors':
            return data.errors > 0
          case 'warnings':
            return data.warnings > 0
          default:
            return data.errors > 0 || data.warnings > 0
        }
      })
      .map(([filePath, data]) => {
        const value = metric.value === 'warnings' ? data.warnings : 
                     metric.value === 'errors' ? data.errors :
                     data.errors + data.warnings
        return {
          name: filePath.split(/[\\/]/).pop() || 'unknown',
          value,
          type: data.errors > 0 ? 'errors' : 'warnings',
          details: `Ошибок: ${data.errors}\nПредупреждений: ${data.warnings}`
        }
      })
  }

  d3.select(bubbleMap.value).selectAll("*").remove()

  const width = 1200
  const height = 600

  const svg = d3.select(bubbleMap.value)
    .attr("viewBox", `0 0 ${width} ${height}`)
    .style("max-width", "1200px")
    .style("margin", "0 auto")

  const bubble = d3.pack<BubbleNode>()
    .size([width * zoomLevel.value, height * zoomLevel.value])
    .padding(3)

  const root = d3.hierarchy<BubbleNode>(rootData)
    .sum(d => d.value)
    .sort((a, b) => (b.value || 0) - (a.value || 0))

  const nodes = bubble(root).descendants()

  const colorScale = d3.scaleOrdinal<string>()
    .domain(['errors', 'warnings'])
    .range(['#ff4444', '#ffbb33'])

  const container = svg.append("g")
    .attr("transform", `translate(${(width - width * zoomLevel.value) / 2},${(height - height * zoomLevel.value) / 2})`)

  // Отрисовка
  const nodeGroups = container
    .selectAll<SVGGElement, d3.HierarchyCircularNode<BubbleNode>>(".node")
    .data(nodes.filter(d => !d.children))
    .enter()
    .append("g")
    .attr("class", "node")
    .attr("transform", d => `translate(${d.x},${d.y})`)
    .style("opacity", 0)

  nodeGroups
    .transition()
    .duration(750)
    .style("opacity", 1)

  // Круги с улучшенной анимацией
  nodeGroups
    .append("circle")
    .attr("r", 0)
    .attr("fill", (d: d3.HierarchyCircularNode<BubbleNode>) => {
      const primaryType = d.data.type.split(', ')[0]
      if (!primaryType) return "#f0f0f0"
      return colorScale(primaryType)
    })
    .attr("stroke", "#fff")
    .attr("stroke-width", 2)
    .transition()
    .duration(1000)
    .ease(d3.easeCubicOut)
    .attr("r", d => d.r)

  // Добавляем обработчики событий после анимации
  svg
    .selectAll<SVGCircleElement, d3.HierarchyCircularNode<BubbleNode>>("circle")
    .on("mouseover", (event: MouseEvent, d: d3.HierarchyCircularNode<BubbleNode>) => {
      const data = d.data
      const total = data.value || 0
      const maxValue = d3.max(nodes.filter(n => !n.children), n => n.value) || 1

      tooltip.value = {
        x: event.pageX,
        y: event.pageY,
        title: data.name,
        subtitle: path.dirname(data.name),
        errors: parseInt(data.details.match(/Ошибок: (\d+)/)?.[1] || '0'),
        warnings: parseInt(data.details.match(/Предупреждений: (\d+)/)?.[1] || '0'),
        severity: total / maxValue
      }

      const target = event.currentTarget as SVGCircleElement
      d3.select(target)
        .transition()
        .duration(300)
        .attr("transform", "scale(1.1)")
    })
    .on("mouseout", (event: MouseEvent) => {
      tooltip.value = null
      const target = event.currentTarget as SVGCircleElement
      d3.select(target)
        .transition()
        .duration(300)
        .attr("transform", "scale(1)")
    })
    .on("click", (_, d: d3.HierarchyCircularNode<BubbleNode>) => {
      emit('select-file', d.data.name)
    })

  // Текстовые метки с анимацией
  nodeGroups
    .append("text")
    .attr("dy", ".3em")
    .style("text-anchor", "middle")
    .style("fill", "white")
    .style("font-size", "14px")
    .style("opacity", 0)
    .text((d: d3.HierarchyCircularNode<BubbleNode>) => d.data.name.split('.')[0] || '')
    .transition()
    .delay(750)
    .duration(500)
    .style("opacity", 1)

  nodeGroups
    .append("text")
    .attr("dy", "1.3em")
    .style("text-anchor", "middle")
    .style("fill", "white")
    .style("font-size", "12px")
    .style("opacity", 0)
    .text((d: d3.HierarchyCircularNode<BubbleNode>) => {
      const value = d.value || 0
      return value > 0 ? `${value} проблем` : ''
    })
    .transition()
    .delay(1000)
    .duration(500)
    .style("opacity", 1)
}

onMounted(() => {
  void nextTick(drawBubbleMap)
})

watch([metric], () => {
  void nextTick(drawBubbleMap)
})

defineExpose({
  tooltip
})
</script>

<style scoped lang="scss">
.bubble-map-container {
  background: white;
  border-radius: 12px;
  padding: 16px;

  circle {
    transition: all 0.3s ease;
    
    &:hover {
      transform: scale(1.05);
      cursor: pointer;
    }
  }
}
</style> 