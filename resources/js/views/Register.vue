<template>
  <div class="min-h-screen bg-gradient-to-br from-emerald-50 to-teal-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-8 md:p-10">
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Criar Conta</h1>
        <p class="text-gray-500 mt-1">Cadastre-se como paciente</p>
      </div>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
            <input
              v-model="form.cpf"
              type="text"
              required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
              placeholder="000.000.000-00"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento</label>
            <input
              v-model="form.birth_date"
              type="date"
              required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
          <input
            v-model="form.phone"
            type="text"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
            placeholder="(11) 99999-9999"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
          <input
            v-model="form.street"
            type="text"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
            placeholder="Rua, número"
          />
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
            <input
              v-model="form.neighborhood"
              type="text"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
            <input
              v-model="form.city"
              type="text"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">UF</label>
            <input
              v-model="form.state"
              type="text"
              maxlength="2"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
          <input
            v-model="form.password"
            type="password"
            required
            minlength="8"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Senha</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            required
            minlength="8"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
          />
        </div>

        <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
          {{ error }}
        </div>

        <div class="flex gap-3">
          <button
            type="button"
            @click="fillTestData"
            class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors text-sm"
          >
            Preencher Teste
          </button>
          <button
            type="button"
            @click="clearForm"
            class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors text-sm"
          >
            Limpar
          </button>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
        >
          {{ loading ? 'Cadastrando...' : 'Cadastrar' }}
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-6">
        Já tem conta?
        <router-link to="/login" class="text-emerald-600 hover:text-emerald-700 font-medium">Entrar</router-link>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { generateTestPatientData } from '../utils/formHelpers'
import { useMeta } from '../composables/useMeta'

onMounted(() => {
  useMeta({
    title: 'Cadastro - Crie sua Conta no Blink',
    description: 'Cadastre-se gratuitamente no Blink e tenha acesso a agendamento de consultas, prontuário digital e gestão da sua saúde em um só lugar.',
    keywords: 'cadastro, criar conta, registro, paciente, agendamento, saúde, clínica online',
    ogType: 'website',
  })
})

const router = useRouter()
const loading = ref(false)
const error = ref('')

const form = ref({
  name: '',
  email: '',
  cpf: '',
  birth_date: '',
  phone: '',
  street: '',
  neighborhood: '',
  city: '',
  state: '',
  password: '',
  password_confirmation: '',
})

function fillTestData() {
  const data = generateTestPatientData()
  form.value.name = data.name
  form.value.email = data.email
  form.value.cpf = data.cpf
  form.value.birth_date = data.birth_date
  form.value.phone = data.phone
  form.value.street = data.street
  form.value.neighborhood = data.neighborhood
  form.value.city = data.city
  form.value.state = data.state
  form.value.password = data.password
  form.value.password_confirmation = data.password_confirmation
  error.value = ''
}

function clearForm() {
  form.value = {
    name: '',
    email: '',
    cpf: '',
    birth_date: '',
    phone: '',
    street: '',
    neighborhood: '',
    city: '',
    state: '',
    password: '',
    password_confirmation: '',
  }
  error.value = ''
}

async function handleRegister() {
  loading.value = true
  error.value = ''

  try {
    // Token é armazenado em cookie HttpOnly pelo backend (inacessível via JS)
    const { data } = await axios.post('/register', form.value)

    localStorage.setItem('user', JSON.stringify(data.user))

    router.push('/patient/dashboard')
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Erro ao cadastrar. Verifique os dados.'
  } finally {
    loading.value = false
  }
}

</script>
