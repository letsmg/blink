<template>
  <!-- AuthHeader: estrutura de layout para área autenticada (sidebar + topo) -->
  <div
    class="min-h-screen bg-gray-50/80 flex relative"
    style="background-image: url('/storage/fundo.png'); background-size: cover; background-position: center; background-blend-mode: overlay;"
  >
    <!-- Overlay para dispositivos móveis -->
    <div
      v-if="isMobile && isSidebarOpen"
      class="fixed inset-0 bg-black bg-opacity-50 z-20 md:hidden"
      @click="toggleSidebar"
    ></div>

    <!-- Sidebar para desktop e menu responsivo para celular -->
    <aside
      class="w-64 bg-white border-r border-gray-200 flex flex-col fixed inset-y-0 left-0 z-30 transform md:relative md:translate-x-0 transition-transform duration-200 ease-in-out"
      :class="{ '-translate-x-full': !isSidebarOpen && isMobile }"
    >
      <div class="p-6 border-b border-gray-200">
        <h1 class="text-xl font-bold text-gray-900">Blink</h1>
        <p class="text-sm text-gray-500">{{ areaLabel }}</p>
      </div>

      <nav class="flex-1 p-4 space-y-1">
        <!-- Dashboard -->
        <router-link
          :to="dashboardPath"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
          :class="isActive(dashboardPath) ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100'"
          @click="isMobile && (isSidebarOpen = false)"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
            />
          </svg>
          Dashboard
        </router-link>

        <!-- Staff-only links -->
        <template v-if="isStaff">
          <router-link
            to="/staff/patients"
            class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
            :class="isActive('/staff/patients') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100'"
            @click="isMobile && (isSidebarOpen = false)"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
              />
            </svg>
            Pacientes
          </router-link>

          <router-link
            to="/staff/messages"
            class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors relative"
            :class="isActive('/staff/messages') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100'"
            @click="isMobile && (isSidebarOpen = false)"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
              />
            </svg>
            Mensagens
            <!-- Unread messages badge -->
            <span
              v-if="unreadCount > 0"
              class="absolute right-3 top-1/2 -translate-y-1/2 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white bg-red-500 rounded-full"
            >
              {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
          </router-link>

          <router-link
            to="/staff/appointments"
            class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors relative"
            :class="isActive('/staff/appointments') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100'"
            @click="isMobile && (isSidebarOpen = false)"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
            Agenda
            <!-- Pending appointments badge (future/today not paid) -->
            <span
              v-if="pendingAppointments > 0"
              class="absolute right-3 top-1/2 -translate-y-1/2 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white bg-red-500 rounded-full"
            >
              {{ pendingAppointments > 99 ? '99+' : pendingAppointments }}
            </span>
          </router-link>

          <router-link
            to="/staff/unavailability"
            class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
            :class="isActive('/staff/unavailability') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100'"
            @click="isMobile && (isSidebarOpen = false)"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
            Indisponibilidade
          </router-link>

          <router-link
            to="/staff/reports"
            class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
            :class="isActive('/staff/reports') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100'"
            @click="isMobile && (isSidebarOpen = false)"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            Relatórios
          </router-link>
        </template>
      </nav>

      <!-- User info & logout -->
      <div class="p-4 border-t border-gray-200">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
            <span class="text-sm font-medium text-emerald-700">{{ userInitials }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ userName }}</p>
            <p class="text-xs text-gray-500">{{ userRole }}</p>
          </div>
          <button @click="logout" class="text-gray-400 hover:text-gray-600" title="Sair">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
              />
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-auto">
      <!-- Top bar para mobile -->
      <header class="w-full bg-white shadow-sm py-4 px-6 flex items-center justify-between md:hidden">
        <button @click="toggleSidebar" class="text-gray-500 hover:text-gray-700">
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
        <h1 class="text-lg font-bold text-gray-900">{{ currentRouteName }}</h1>
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
            <span class="text-sm font-medium text-emerald-700">{{ userInitials }}</span>
          </div>
          <button @click="logout" class="text-gray-400 hover:text-gray-600" title="Sair">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
              />
            </svg>
          </button>
        </div>
      </header>

      <div class="p-8">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import emitter from '../eventBus'

const router = useRouter()
const route = useRoute()
const unreadCount = ref(0)
const pendingAppointments = ref(0)
let pollingInterval: ReturnType<typeof setInterval> | null = null

const isSidebarOpen = ref(false)
const isMobile = ref(false)

const user = JSON.parse(localStorage.getItem('user') || '{}')
const isStaff = computed(() => user.role === 1 || user.role === 2)
const areaLabel = computed(() => (isStaff.value ? 'Área do Staff' : 'Área do Paciente'))
const dashboardPath = computed(() => (isStaff.value ? '/staff/dashboard' : '/patient/dashboard'))

const userName = computed(() => user.name || 'Usuário')
const userRole = computed(() => {
  const roles: Record<number, string> = { 0: 'Paciente', 1: 'Admin', 2: 'Operacional' }
  return roles[user.role] || 'Staff'
})
const userInitials = computed(() => {
  return (user.name || 'U').split(' ').map((s: string) => s[0]).slice(0, 2).join('').toUpperCase()
})

const currentRouteName = computed(() => route.name || 'Dashboard');

function isActive(path: string): boolean {
  return route.path.startsWith(path)
}

function toggleSidebar() {
  isSidebarOpen.value = !isSidebarOpen.value
}

function checkMobile() {
  isMobile.value = window.innerWidth < 768 // Tailwind's 'md' breakpoint is 768px
  if (!isMobile.value) {
    isSidebarOpen.value = true // Always open sidebar on desktop
  }
}

async function fetchUnreadCount() {
  try {
    if (!isStaff.value) return

    // Token é enviado automaticamente via cookie HttpOnly (withCredentials: true)
    const { data } = await axios.get('/staff/messages/unread-count')
    unreadCount.value = data.unread_count
  } catch {
    // Silently fail
  }
}

async function fetchPendingAppointments() {
  try {
    if (!isStaff.value) return

    const { data } = await axios.get('/staff/appointments/pending-count')
    pendingAppointments.value = data.pending_count
  } catch {
    // Silently fail
  }
}

async function logout() {
  try {
    await axios.post('/logout')
  } catch {
    // Silently fail - cookie will be cleared anyway
  }
  localStorage.removeItem('user')
  router.push('/login')
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)

  fetchUnreadCount()
  fetchPendingAppointments()
  pollingInterval = setInterval(() => {
    fetchUnreadCount()
    fetchPendingAppointments()
  }, 30000)

  // Atualização instantânea quando mensagens são marcadas como lida/não lida
  emitter.on('messages:read-status-changed', () => {
    fetchUnreadCount()
  })
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
  if (pollingInterval) {
    clearInterval(pollingInterval)
  }
  emitter.off('messages:read-status-changed')
})
</script>

<style scoped>
/* Adicione estilos específicos de componente aqui, se necessário */
/* Tailwind CSS lida com a maioria da responsividade via classes utilitárias */
</style>
