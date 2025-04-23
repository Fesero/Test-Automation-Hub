<template>
  <router-view />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'

// Load Google Fonts for our modern design
onMounted(() => {
  // Add Inter font (modern, clean sans-serif)
  const interLink = document.createElement('link')
  interLink.rel = 'stylesheet'
  interLink.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap'
  document.head.appendChild(interLink)
})
</script>

<style>
/* Apply base font settings */
:root {
  --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
  --font-family-secondary: 'Inter', sans-serif;
}

body {
  font-family: var(--font-family);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Apply smooth transitions throughout the app */
* {
  transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}
</style>
