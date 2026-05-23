<template>
  <div class="min-h-screen bg-gradient-to-br from-emerald-50/80 to-teal-50/80 flex items-center justify-center p-4 relative"
    style="background-image: url('/storage/fundo.png'); background-size: cover; background-position: center; background-blend-mode: overlay;">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl overflow-hidden flex flex-col md:flex-row">
      <!-- Left: Login Form -->
      <div class="p-8 md:p-10 flex-1">
        <div class="mb-8">
          <h1 class="text-2xl font-bold text-gray-900">Blink</h1>
          <p class="text-gray-500 mt-1">Sistema de Gestão de Saúde</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
              placeholder="seu@email.com"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
            <input
              v-model="form.password"
              type="password"
              required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
              placeholder="••••••••"
            />
          </div>

          <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
            {{ error }}
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
          >
            {{ loading ? 'Entrando...' : 'Entrar' }}
          </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
          Não tem conta?
          <router-link to="/register" class="text-emerald-600 hover:text-emerald-700 font-medium">Cadastre-se</router-link>
        </p>
      </div>

      <!-- Right: Test Users Panel -->
      <div class="bg-gray-50 p-6 md:p-8 md:w-80 border-t md:border-t-0 md:border-l border-gray-200">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
          Usuários de Teste
        </h2>
        <p class="text-xs text-gray-500 mb-4">Clique em um usuário para preencher automaticamente o formulário.</p>

        <div class="space-y-2">
          <div v-for="user in testUsers" :key="user.email">
            <button
              @click="fillLogin(user)"
              class="w-full text-left p-3 rounded-lg border border-gray-200 bg-white hover:border-emerald-300 hover:bg-emerald-50/50 transition-all text-sm"
            >
              <span class="font-medium text-gray-900 block truncate">{{ user.name }}</span>
              <span class="text-xs text-gray-500 block">{{ user.email }}</span>
              <span
                class="inline-block mt-1 px-2 py-0.5 text-xs rounded-full"
                :class="roleBadgeClass(user.role)"
              >
                {{ user.role }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { testUsers, type TestUser } from '../utils/formHelpers'
import { useMeta } from '../composables/useMeta'

onMounted(() => {
  useMeta({
    title: 'Entrar - Plataforma de Gestão de Saúde',
    description: 'Acesse sua conta no Blink para gerenciar agendamentos, pacientes e sua clínica. Ambiente seguro com autenticação JWT e proteção de dados.',
    keywords: 'login, entrar, gestão de clínicas, agendamento, sistema de saúde, acesso seguro',
    ogType: 'website',
  })
})

const router = useRouter()
const loading = ref(false)
const error = ref('')

const form = ref({
  email: '',
  password: '',
})

function roleBadgeClass(role: string): string {
  switch (role) {
    case 'Admin': return 'bg-purple-100 text-purple-700'
    case 'Operacional': return 'bg-blue-100 text-blue-700'
    default: return 'bg-gray-100 text-gray-600'
  }
}

function fillLogin(user: TestUser) {
  form.value.email = user.email
  form.value.password = user.password
  error.value = ''
}

async function handleLogin() {
  loading.value = true
  error.value = ''

  try {
    // Token é armazenado em cookie HttpOnly pelo backend (inacessível via JS)
    // O cookie é enviado automaticamente graças ao withCredentials: true
    const { data } = await axios.post('/login', form.value)

    localStorage.setItem('user', JSON.stringify(data.user))

    // Redirect based on role
    const role = data.user.role
    if (role === 0) {
      router.push('/patient/dashboard')
    } else {
      router.push('/staff/dashboard')
    }
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Erro ao fazer login. Verifique suas credenciais.'
  } finally {
    loading.value = false
  }
}

</script>
