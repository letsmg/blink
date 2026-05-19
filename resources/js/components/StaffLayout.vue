<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col">
      <div class="p-6 border-b border-gray-200">
        <h1 class="text-xl font-bold text-gray-900">LaraHealth</h1>
        <p class="text-sm text-gray-500">Área do Staff</p>
      </div>

      <nav class="flex-1 p-4 space-y-1">
        <router-link
          to="/staff/dashboard"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
          :class="isActive('/staff/dashboard') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100'"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Dashboard
        </router-link>

        <router-link
          to="/staff/patients"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
          :class="isActive('/staff/patients') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100'"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          Pacientes
        </router-link>

        <router-link
          to="/staff/messages"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors relative"
          :class="isActive('/staff/messages') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100'"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
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
          to="/staff/unavailability"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
          :class="isActive('/staff/unavailability') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100'"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Indisponibilidade
        </router-link>
      </nav>

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
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-auto">
      <div class="p-8">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const unreadCount = ref(0)
let pollingInterval: ReturnType<typeof setInterval> | null = null

const user = JSON.parse(localStorage.getItem('user') || '{}')
const userName = computed(() => user.name || 'Usuário')
const userRole = computed(() => {
  const roles: Record<number, string> = { 0: 'Paciente', 1: 'Admin', 2: 'Operacional' }
  return roles[user.role] || 'Staff'
})
const userInitials = computed(() => {
  return (user.name || 'U').split(' ').map((s: string) => s[0]).slice(0, 2).join('').toUpperCase()
})

function isActive(path: string): boolean {
  return route.path.startsWith(path)
}

async function fetchUnreadCount() {
  try {
    const token = localStorage.getItem('token')
    if (!token) return

    const { data } = await axios.get('/staff/messages/unread-count', {
      headers: { Authorization: `Bearer ${token}` },
    })
    unreadCount.value = data.unread_count
  } catch {
    // Silently fail - user may not be authenticated
  }
}

function logout() {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  delete axios.defaults.headers.common['Authorization']
  router.push('/login')
}

onMounted(() => {
  fetchUnreadCount()
  // Poll every 30 seconds for new messages
  pollingInterval = setInterval(fetchUnreadCount, 30000)
})

onUnmounted(() => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
  }
})
</script>
