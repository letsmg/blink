<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Períodos de Indisponibilidade</h1>
        <p class="text-gray-500 mt-1">Gerencie os períodos em que profissionais não estarão disponíveis.</p>
      </div>
      <button
        @click="showForm = true"
        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors text-sm"
      >
        Novo Período
      </button>
    </div>

    <!-- Create/Edit Form -->
    <div v-if="showForm" class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ editingPeriod ? 'Editar' : 'Novo' }} Período de Indisponibilidade</h2>

      <form @submit.prevent="handleSubmit" class="space-y-4 max-w-lg">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Profissional</label>
          <select
            v-model="form.professional_id"
            required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
          >
            <option value="" disabled>Selecione um profissional</option>
            <option v-for="prof in professionals" :key="prof.id" :value="prof.id">
              {{ prof.full_name }} - {{ prof.specialty }}
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data Início</label>
            <input
              v-model="form.start_date"
              type="date"
              required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data Fim</label>
            <input
              v-model="form.end_date"
              type="date"
              required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Motivo (opcional)</label>
          <input
            v-model="form.reason"
            type="text"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors"
            placeholder="Ex: Férias, Licença, Congresso..."
          />
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
            {{ loading ? 'Salvando...' : 'Salvar' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Periods List -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="periods.length === 0" class="p-8 text-center text-gray-500">
        Nenhum período de indisponibilidade cadastrado.
      </div>

      <div v-for="period in periods" :key="period.id" class="p-4 border-b border-gray-100 last:border-b-0">
        <div class="flex items-center justify-between">
          <div>
            <p class="font-medium text-gray-900">{{ period.professional?.full_name || 'Profissional' }}</p>
            <p class="text-sm text-gray-500 mt-1">
              {{ formatDate(period.start_date) }} até {{ formatDate(period.end_date) }}
            </p>
            <p v-if="period.reason" class="text-sm text-gray-400 mt-0.5">{{ period.reason }}</p>
          </div>
          <div class="flex gap-2">
            <button
              @click="editPeriod(period)"
              class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
            >
              Editar
            </button>
            <button
              @click="deletePeriod(period)"
              class="px-3 py-1.5 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
            >
              Excluir
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { generateTestUnavailabilityData } from '../../utils/formHelpers'

const loading = ref(false)
const error = ref('')
const showForm = ref(false)
const editingPeriod = ref<any>(null)
const periods = ref<any[]>([])
const professionals = ref<any[]>([])

const form = ref({
  professional_id: '',
  start_date: '',
  end_date: '',
  reason: '',
})

function fillTestData() {
  const data = generateTestUnavailabilityData()
  form.value.start_date = data.start_date
  form.value.end_date = data.end_date
  form.value.reason = data.reason
  if (professionals.value.length > 0) {
    form.value.professional_id = String(professionals.value[Math.floor(Math.random() * professionals.value.length)].id)
  }
  error.value = ''
}

function formatDate(date: string) {
  return new Date(date + 'T12:00:00').toLocaleDateString('pt-BR')
}

function cancelForm() {
  showForm.value = false
  editingPeriod.value = null
  form.value = { professional_id: '', start_date: '', end_date: '', reason: '' }
  error.value = ''
}

async function fetchPeriods() {
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get('/staff/professionals/1/unavailability', {
      headers: { Authorization: `Bearer ${token}` },
    })
    periods.value = data.data || data
  } catch {
    // Silently fail
  }
}

async function fetchProfessionals() {
  try {
    const token = localStorage.getItem('token')
    const { data } = await axios.get('/api/professionals', {
      headers: { Authorization: `Bearer ${token}` },
    })
    professionals.value = data.data || data
  } catch {
    // Silently fail
  }
}

async function handleSubmit() {
  loading.value = true
  error.value = ''

  try {
    const token = localStorage.getItem('token')
    const url = editingPeriod.value
      ? `/staff/professionals/${form.value.professional_id}/unavailability/${editingPeriod.value.id}`
      : `/staff/professionals/${form.value.professional_id}/unavailability`

    const method = editingPeriod.value ? 'put' : 'post'

    await axios({
      method,
      url,
      data: {
        start_date: form.value.start_date,
        end_date: form.value.end_date,
        reason: form.value.reason || null,
      },
      headers: { Authorization: `Bearer ${token}` },
    })

    cancelForm()
    await fetchPeriods()
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Erro ao salvar período.'
  } finally {
    loading.value = false
  }
}

function editPeriod(period: any) {
  editingPeriod.value = period
  form.value = {
    professional_id: String(period.professional_id),
    start_date: period.start_date,
    end_date: period.end_date,
    reason: period.reason || '',
  }
  showForm.value = true
  error.value = ''
}

async function deletePeriod(period: any) {
  if (!confirm('Tem certeza que deseja excluir este período?')) return

  try {
    const token = localStorage.getItem('token')
    await axios.delete(`/staff/professionals/${period.professional_id}/unavailability/${period.id}`, {
      headers: { Authorization: `Bearer ${token}` },
    })
    await fetchPeriods()
  } catch {
    // Silently fail
  }
}

onMounted(() => {
  fetchPeriods()
  fetchProfessionals()
})
</script>
