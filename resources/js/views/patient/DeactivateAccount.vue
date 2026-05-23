<template>
  <div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg p-8">
      <h2 class="text-2xl font-semibold text-gray-900 mb-2">Desativar Conta</h2>
      <p class="text-gray-500 mb-6">Solicite a desativação da sua conta. Esta ação pode ser revertida posteriormente entrando em contato conosco.</p>

      <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-6">
        <div class="flex items-start gap-3">
          <svg class="w-6 h-6 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <div>
            <h3 class="font-medium text-amber-800 mb-1">Atenção</h3>
            <p class="text-sm text-amber-700">
              Ao desativar sua conta, você não poderá agendar novas consultas nem acessar seus dados.
              Seus registros de saúde serão preservados conforme exigido pela LGPD.
              Para reativar sua conta, entre em contato pelo e-mail: 
              <a href="mailto:contato@blinkmed.com" class="font-medium underline">contato@blinkmed.com</a>
            </p>
          </div>
        </div>
      </div>

      <form @submit.prevent="requestDeactivation" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Motivo da desativação (opcional)</label>
          <textarea
            v-model="form.reason"
            rows="4"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors resize-none"
            placeholder="Conte-nos o motivo pelo qual deseja desativar sua conta..."
          ></textarea>
        </div>

        <div class="flex items-start gap-3">
          <input
            v-model="form.confirmed"
            type="checkbox"
            required
            class="mt-1 h-4 w-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
          />
          <label class="text-sm text-gray-600">
            Estou ciente de que ao desativar minha conta perderei o acesso aos serviços e que meus dados serão preservados conforme a LGPD.
          </label>
        </div>

        <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
          {{ error }}
        </div>

        <div v-if="success" class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-600">
          {{ success }}
        </div>

        <button
          type="submit"
          :disabled="loading || !form.confirmed"
          class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
        >
          {{ loading ? 'Enviando...' : 'Solicitar Desativação' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * PatientDeactivateAccount - Página para paciente solicitar desativação da conta.
 * Envia uma solicitação para a equipe administrativa.
 */
import { ref } from 'vue'
import axios from 'axios'

const loading = ref(false)
const error = ref('')
const success = ref('')

const form = ref({
  reason: '',
  confirmed: false,
})

async function requestDeactivation() {
  loading.value = true
  error.value = ''
  success.value = ''

  try {
    await axios.post('/api/patient/deactivate-request', {
      reason: form.value.reason,
    })

    success.value = 'Solicitação enviada com sucesso! Entraremos em contato para confirmar a desativação.'
    form.value = { reason: '', confirmed: false }
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Erro ao enviar solicitação. Tente novamente.'
  } finally {
    loading.value = false
  }
}
</script>
