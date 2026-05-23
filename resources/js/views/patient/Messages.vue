<template>
  <div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg p-8">
      <h2 class="text-2xl font-semibold text-gray-900 mb-2">Solicitar Consulta</h2>
      <p class="text-gray-500 mb-6">Envie uma mensagem para solicitar um agendamento ou tirar dúvidas.</p>

      <form @submit.prevent="sendMessage" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Profissional</label>
          <select
            v-model="form.professional_id"
            required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-white"
          >
            <option value="" disabled>Selecione um profissional</option>
            <option v-for="prof in professionals" :key="prof.id" :value="prof.id">
              {{ prof.name }} - {{ prof.specialty }}
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
            placeholder="Ex: Solicitação de consulta"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem</label>
          <textarea
            v-model="form.content"
            required
            rows="5"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors resize-none"
            placeholder="Descreva o motivo do contato ou solicitação de consulta..."
          ></textarea>
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
          {{ loading ? 'Enviando...' : 'Enviar Mensagem' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * PatientMessages - Página para paciente enviar mensagens solicitando consulta.
 * O paciente seleciona um profissional, informa assunto e conteúdo da mensagem.
 */
import { ref, onMounted } from 'vue'
import axios from 'axios'

interface Professional {
  id: number
  name: string
  specialty: string
}

const professionals = ref<Professional[]>([])
const loading = ref(false)
const error = ref('')
const success = ref('')

const form = ref({
  professional_id: '',
  subject: '',
  content: '',
})

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/patient/professionals')
    professionals.value = data.professionals
  } catch {
    error.value = 'Erro ao carregar lista de profissionais.'
  }
})

async function sendMessage() {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    await axios.post('/api/patient/messages', {
      recipient_id: form.value.professional_id,
      subject: form.value.subject,
      content: form.value.content,
    })

    success.value = 'Mensagem enviada com sucesso! Entraremos em contato em breve.'
    form.value = { professional_id: '', subject: '', content: '' }
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Erro ao enviar mensagem. Tente novamente.'
  } finally {
    loading.value = false
  }
}
</script>
