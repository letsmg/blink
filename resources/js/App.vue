<template>
  <div id="app">
    <!-- Renderiza o layout baseado na rota atual -->
    <component :is="layout">
      <router-view />
    </component>
    <!-- Banner de aceite de termos (exibido globalmente para visitantes) -->
    <TermsBanner />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import PublicLayout from './layouts/PublicLayout.vue'
import AuthLayout from './layouts/AuthLayout.vue'
import TermsBanner from './components/TermsBanner.vue'

const route = useRoute()

// Define qual layout usar baseado na meta da rota
const layout = computed(() => {
  if (route.meta?.requiresAuth) {
    return AuthLayout
  }
  return PublicLayout
})
</script>


