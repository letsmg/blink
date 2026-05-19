<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Mensagens</h1>
        <p class="text-gray-500 mt-1">Comunicação interna entre a equipe.</p>
      </div>
      <button
        @click="showForm = true"
        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors text-sm"
      >
        Nova Mensagem
      </button>
    </div>

    <!-- Send Message Form -->
    <div v-if="showForm" class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Nova Mensagem</h2>

      <form @submit.prevent="handleSend" class="space-y-4 max-w-lg">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Destinatário</label>
          <select
            v-model="form.recipient_id"
            required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
          >
            <option value="" disabled>Selecione um destinatário</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }} ({{ user.email }})
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Assunto</label>
          <input
            v-model="form.subject"
            type="text"
            required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem</label>
          <textarea
            v-model="form.body"
            required
            rows="4"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors resize-none"
          ></textarea>
        </div>

        <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
          {{ error }}
        </div>

        <div class="flex gap-3">
          <button
            type="button"
            @click="fillTestData"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors text-sm"
          >
            Preencher Teste
          </button>
          <button
            type="button"
            @click="cancelForm"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors text-sm"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors text-sm disabled:opacity-50"
          >
            {{ loading ? 'Enviando...' : 'Enviar' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Messages List -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="messages.length === 0" class="p-8 text-center text-gray-500">
        Nenhuma mensagem recebida.
      </div>

      <div v-for="msg in messages" :key="msg.id" class="p-4 border-b border-gray-100 last:border-b-0">
        <div class="flex items-start justify-between">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span
                v-if="!msg.is_read"
                class="w-2 h-2 bg-emerald-500 rounded-full flex-shrink-0"
                title="Não lida"
              ></span>
              <p class="font-medium text-gray-900 truncate">{{ msg.subject }}</p>
            </div>
            <p class="text-sm text-gray-500 mt-1">
              De: {{ msg.sender?.name || 'Desconhecido' }} — {{ formatDate(msg.created_at) }}
            </p>
            <p class="text-sm text-gray-600 mt-2 line-clamp-2">{{ msg.body }}</p>
          </div>
          <button
            v-if="!msg.is_read"
            @click="markAsRead(msg)"
            class="ml-4 px-3 py-1.5 text-xs text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors flex-shrink-0"
          >
            Marcar como lida
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { generateTestMessageData } from '../../utils/formHelpers'

const loading = ref(false)
const error = ref('')
const showForm = ref(false)
const messages = ref<any[]>([])
const users = ref<any[]>([])

const form = ref({
  recipient_id: '',
  subject: '',
  body: '',
})

function fillTestData() {
  const data = generateTestMessageData()
  form.value.subject = data.subject
  form.value.body = data.content
  if (users.value.length > 0) {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}')
    const otherUsers = users.value.filter((u: any) => u.id !== currentUser.id)
    if (otherUsers.length > 0) {
      form.value.recipient_id = String(otherUsers[Math.floor(Math.random() * otherUsers.length)].id)
    }
  }
  error.value = ''
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function cancelForm() {
  showForm.value = false
  form.value = { recipient_id: '', subject: '', body: '' }
  error.value = ''
}

async function fetchMessages() {
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get('/staff/messages', {
      headers: { Authorization: `Bearer ${token}` },
    })
    messages.value = data.data?.data || data.data || data
  } catch {
    // Silently fail
  }
}

async function fetchUsers() {
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get('/api/users', {
      headers: { Authorization: `Bearer ${token}` },
    })
    users.value = data.data || data
  } catch {
    // Silently fail
  }
}

async function handleSend() {
  loading.value = true
  error.value = ''

  try {
    const token = localStorage.getItem('token')
    await axios.post('/staff/messages', {
      recipient_id: Number(form.value.recipient_id),
      subject: form.value.subject,
      body: form.value.body,
    }, {
      headers: { Authorization: `Bearer ${token}` },
    })

    cancelForm()
    await fetchMessages()
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Erro ao enviar mensagem.'
  } finally {
    loading.value = false
  }
}

async function markAsRead(msg: any) {
  try {
    const token = localStorage.getItem('token')
    await axios.patch(`/staff/messages/${msg.id}/read`, {}, {
      headers: { Authorization: `Bearer ${token}` },
    })
    msg.is_read = true
  } catch {
    // Silently fail
  }
}

onMounted(() => {
  fetchMessages()
  fetchUsers()
})
</script>
