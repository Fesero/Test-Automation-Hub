<template>
  <div class="dashboard-stats q-mb-lg">
    <div class="row q-col-gutter-md">
      <!-- Budget Card -->
      <div class="col-12 col-sm-6 col-md-3">
        <div class="stats-card hover-card" style="--background-gradient: #{$gradient-primary};">
          <div class="stats-card__title">TOTAL TESTS</div>
          <div class="stats-card__value">{{ tests.length }}</div>
          
          <div class="stats-card__trend" :class="{'stats-card__trend--up': true}">
            <q-icon name="trending_up" size="16px" />
            <span>+5 %</span>
          </div>
          
          <div class="stats-card__chart">
            <q-linear-progress rounded size="2px" :value="Math.min(tests.length / 100, 1)" color="white" track-color="rgba(255,255,255,0.2)" />
            <svg width="100%" height="40" viewBox="0 0 100 20" preserveAspectRatio="none">
              <path d="M0,10 L10,12 L20,8 L30,15 L40,10 L50,12 L60,8 L70,9 L80,5 L90,8 L100,3" 
                    fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            </svg>
          </div>
        </div>
      </div>
      
      <!-- Engagements Card -->
      <div class="col-12 col-sm-6 col-md-3">
        <div class="stats-card hover-card" style="--background-gradient: #{$gradient-success};">
          <div class="stats-card__title">PASSED TESTS</div>
          <div class="stats-card__value">{{ tests.filter(t => t.status === 'completed').length }}</div>
          
          <div class="stats-card__trend" :class="{'stats-card__trend--up': true}">
            <q-icon name="trending_up" size="16px" />
            <span>+8 %</span>
          </div>
          
          <div class="stats-card__chart">
            <q-linear-progress rounded size="2px" :value="Math.min((tests.filter(t => t.status === 'completed').length) / (tests.length || 1), 1)" color="white" track-color="rgba(255,255,255,0.2)" />
            <svg width="100%" height="40" viewBox="0 0 100 20" preserveAspectRatio="none">
              <path d="M0,15 L10,10 L20,12 L30,8 L40,10 L50,5 L60,3 L70,6 L80,2 L90,5 L100,1" 
                    fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            </svg>
          </div>
        </div>
      </div>
      
      <!-- Retail Sales Card -->
      <div class="col-12 col-sm-6 col-md-3">
        <div class="stats-card hover-card" style="--background-gradient: #{$gradient-danger};">
          <div class="stats-card__title">FAILED TESTS</div>
          <div class="stats-card__value">{{ tests.filter(t => t.status === 'failed').length }}</div>
          
          <div class="stats-card__trend" :class="{'stats-card__trend--down': true}">
            <q-icon name="trending_down" size="16px" />
            <span>-3 %</span>
          </div>
          
          <div class="stats-card__chart">
            <q-linear-progress rounded size="2px" :value="Math.min((tests.filter(t => t.status === 'failed').length) / (tests.length || 1), 1)" color="white" track-color="rgba(255,255,255,0.2)" />
            <svg width="100%" height="40" viewBox="0 0 100 20" preserveAspectRatio="none">
              <path d="M0,5 L10,7 L20,5 L30,10 L40,8 L50,12 L60,10 L70,15 L80,12 L90,14 L100,10" 
                    fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            </svg>
          </div>
        </div>
      </div>
      
      <!-- Members Card -->
      <div class="col-12 col-sm-6 col-md-3">
        <div class="stats-card hover-card" style="--background-gradient: #{$gradient-warning};">
          <div class="stats-card__title">RUNNING TESTS</div>
          <div class="stats-card__value">{{ tests.filter(t => t.status === 'running').length }}</div>
          
          <div class="stats-card__trend" :class="{'stats-card__trend--up': tests.filter(t => t.status === 'running').length > 0}">
            <q-icon :name="tests.filter(t => t.status === 'running').length > 0 ? 'trending_up' : 'trending_flat'" size="16px" />
            <span>{{ tests.filter(t => t.status === 'running').length > 0 ? '+2 %' : '0 %' }}</span>
          </div>
          
          <div class="stats-card__chart">
            <q-linear-progress rounded size="2px" :value="Math.min((tests.filter(t => t.status === 'running').length) / (tests.length || 1), 1)" color="white" track-color="rgba(255,255,255,0.2)" />
            <svg width="100%" height="40" viewBox="0 0 100 20" preserveAspectRatio="none">
              <path d="M0,10 L10,11 L20,9 L30,12 L40,10 L50,8 L60,10 L70,7 L80,9 L90,6 L100,8" 
                    fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            </svg>
          </div>
        </div>
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