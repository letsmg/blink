<template>
  <div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg p-8">
      <h2 class="text-2xl font-semibold text-gray-900 mb-2">Editar Meus Dados</h2>
      <p class="text-gray-500 mb-6">Atualize suas informações pessoais.</p>

      <form @submit.prevent="updateProfile" class="space-y-4">
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

        <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
          {{ error }}
        </div>

        <div v-if="success" class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-600">
          {{ success }}
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
        >
          {{ loading ? 'Salvando...' : 'Salvar Alterações' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * PatientEditProfile - Página para paciente editar seus dados pessoais.
 * Carrega os dados atuais do usuário e permite atualizar informações.
 */
import { ref, onMounted } from 'vue'
import axios from 'axios'

const loading = ref(false)
const error = ref('')
const success = ref('')

const form = ref({
  name: '',
  email: '',
  phone: '',
  street: '',
  neighborhood: '',
  city: '',
  state: '',
})

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/patient/profile')
    const user = data.user
    form.value.name = user.name || ''
    form.value.email = user.email || ''
    form.value.phone = user.patient?.phone || ''
    form.value.street = user.patient?.street || ''
    form.value.neighborhood = user.patient?.neighborhood || ''
    form.value.city = user.patient?.city || ''
    form.value.state = user.patient?.state || ''
  } catch {
    error.value = 'Erro ao carregar dados do perfil.'
  }
})

async function updateProfile() {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    const { data } = await axios.put('/api/patient/profile', form.value)

    // Atualiza o localStorage com os novos dados
    localStorage.setItem('user', JSON.stringify(data.user))

    success.value = 'Dados atualizados com sucesso!'
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Erro ao atualizar dados.'
  } finally {
    loading.value = false
  }
}
</script>
