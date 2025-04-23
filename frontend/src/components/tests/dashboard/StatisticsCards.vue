<template>
  <div class="dashboard-stats q-mb-lg">
    <div class="row q-col-gutter-md">
      <!-- Total Tests Card -->
      <div class="col-12 col-sm-6 col-md-3">
        <div class="stats-card hover-card" style="--background-gradient: #{$gradient-primary};">
          <div class="stats-card__title">TOTAL TESTS</div>
          <div class="stats-card__value">{{ totalTests }}</div>
          <div class="stats-card__trend" :class="{'stats-card__trend--up': true}">
            <q-icon name="trending_up" size="16px" />
          </div>
          <div class="stats-card__chart">
            <q-linear-progress rounded size="2px" :value="1" color="white" track-color="rgba(255,255,255,0.2)" />
          </div>
        </div>
      </div>

      <!-- Passed Tests Card -->
      <div class="col-12 col-sm-6 col-md-3">
        <div class="stats-card hover-card" style="--background-gradient: #{$gradient-success};">
          <div class="stats-card__title">PASSED TESTS</div>
          <div class="stats-card__value">{{ passedTests }}</div>
          <div class="stats-card__trend" :class="{'stats-card__trend--up': true}">
            <q-icon name="trending_up" size="16px" />
          </div>
          <div class="stats-card__chart">
            <q-linear-progress rounded size="2px" :value="totalTests > 0 ? passedTests / totalTests : 0" color="white" track-color="rgba(255,255,255,0.2)" />
          </div>
        </div>
      </div>

      <!-- Failed Tests Card -->
      <div class="col-12 col-sm-6 col-md-3">
        <div class="stats-card hover-card" style="--background-gradient: #{$gradient-danger};">
          <div class="stats-card__title">FAILED TESTS</div>
          <div class="stats-card__value">{{ failedTests }}</div>
          <div class="stats-card__trend" :class="{'stats-card__trend--down': true}">
            <q-icon name="trending_down" size="16px" />
          </div>
          <div class="stats-card__chart">
             <q-linear-progress rounded size="2px" :value="totalTests > 0 ? failedTests / totalTests : 0" color="white" track-color="rgba(255,255,255,0.2)" />
          </div>
        </div>
      </div>

      <!-- Running Tests Card -->
      <div class="col-12 col-sm-6 col-md-3">
        <div class="stats-card hover-card" style="--background-gradient: #{$gradient-warning};">
          <div class="stats-card__title">RUNNING/PENDING TESTS</div>
          <div class="stats-card__value">{{ runningTests }}</div>
           <div class="stats-card__trend" :class="{'stats-card__trend--up': runningTests > 0}">
            <q-icon :name="runningTests > 0 ? 'trending_up' : 'trending_flat'" size="16px" />
          </div>
          <div class="stats-card__chart">
            <q-linear-progress rounded size="2px" :value="totalTests > 0 ? runningTests / totalTests : 0" color="white" track-color="rgba(255,255,255,0.2)" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Test } from 'src/types/test.type';

const props = defineProps<{
  tests: Test[];
}>();

const totalTests = computed(() => props.tests.length);

const passedTests = computed(() =>
  props.tests.filter(t => t.status === 'completed').length
);

const failedTests = computed(() =>
  props.tests.filter(t => t.status === 'failed' || t.status === 'error').length
);

const runningTests = computed(() =>
  props.tests.filter(t => t.status === 'running' || t.status === 'pending').length
);
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

.stats-card {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    color: white;
    padding: 20px;
    background-image: var(--background-gradient);
    transition: all 0.3s ease;

    &__title {
        font-size: 0.8rem;
        font-weight: 600;
        opacity: 0.8;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    &__value {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1.2;
    }

    &__trend {
        display: inline-flex;
        align-items: center;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 6px;
        margin-top: 10px;
        background-color: rgba(255, 255, 255, 0.15);

        &--up {
            color: #a5d6a7; // Light green
        }
        &--down {
            color: #ef9a9a; // Light red
        }

        .q-icon {
            margin-right: 4px;
        }
    }

    &__chart {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 50px; // Adjust height
        opacity: 0.5;

        svg {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
    }
}

.hover-card {
    &:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
}
</style> 