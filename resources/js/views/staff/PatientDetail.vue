<template>
  <div class="max-w-4xl mx-auto">
    <div class="mb-6">
      <router-link to="/staff/patients" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium inline-flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Voltar
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-12 text-emerald-500">Carregando...</div>

    <template v-if="patient">
      <div class="bg-white rounded-xl shadow-sm border border-emerald-200 p-6 mb-6">
        <h1 class="text-2xl font-bold text-emerald-900 mb-6">{{ patient.user?.name || patient.full_name }}</h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="p-3 bg-emerald-50 rounded-lg">
            <p class="text-xs text-emerald-700 uppercase font-medium">E-mail</p>
            <p class="text-sm text-emerald-900 mt-1">{{ patient.user?.email || '-' }}</p>
          </div>
          <div class="p-3 bg-emerald-50 rounded-lg">
            <p class="text-xs text-emerald-700 uppercase font-medium">CPF</p>
            <p class="text-sm text-emerald-900 mt-1">{{ patient.cpf_masked || '***' }}</p>
          </div>
          <div class="p-3 bg-emerald-50 rounded-lg">
            <p class="text-xs text-emerald-700 uppercase font-medium">Data de Nascimento</p>
            <p class="text-sm text-emerald-900 mt-1">{{ patient.birth_date || '-' }}</p>
          </div>
          <div class="p-3 bg-emerald-50 rounded-lg">
            <p class="text-xs text-emerald-700 uppercase font-medium">Queixa Principal</p>
            <p class="text-sm text-emerald-900 mt-1">{{ patient.main_complaint || '-' }}</p>
          </div>
        </div>

        <div class="mt-4 p-3 bg-emerald-50 rounded-lg">
          <p class="text-xs text-emerald-700 uppercase font-medium">Endereço</p>
          <p class="text-sm text-emerald-900 mt-1">
            {{ [patient.street, patient.neighborhood, patient.city, patient.state]
              .filter(Boolean).join(', ') || '-' }}
            {{ patient.zip_code ? ' - CEP: ' + patient.zip_code : '' }}
          </p>
        </div>

        <div v-if="patient.clinical_history" class="mt-4 p-3 bg-emerald-50 rounded-lg">
          <p class="text-xs text-emerald-700 uppercase font-medium">Histórico Clínico</p>
          <p class="text-sm text-emerald-900 mt-1">{{ patient.clinical_history }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-emerald-200 p-6">
        <h2 class="text-lg font-semibold text-emerald-900 mb-4">Consultas</h2>
        <div v-if="patient.appointments?.length" class="space-y-3">
          <div v-for="appt in patient.appointments" :key="appt.id" class="p-3 bg-emerald-50 rounded-lg flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-emerald-900">{{ formatDate(appt.date) }} às {{ appt.time }}</p>
              <p class="text-xs text-emerald-600">{{ appt.professional?.full_name || 'Profissional' }}</p>
            </div>
            <span class="px-2 py-1 text-xs font-medium rounded-full"
              :class="appt.is_paid ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
              {{ appt.is_paid ? 'Pago' : 'A pagar' }}
            </span>
          </div>
        </div>
        <p v-else class="text-sm text-emerald-500">Nenhuma consulta registrada.</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const patient = ref(null);
const loading = ref(true);

function formatDate(date) {
  if (!date) return '-'
  return new Date(date + 'T12:00:00').toLocaleDateString('pt-BR')
}

onMounted(async () => {
  try {
    const { data } = await axios.get(`/staff/patients/${route.params.id}`);
    patient.value = data.data || data;
  } catch (e) {
    console.error('Erro ao carregar paciente:', e);
    patient.value = null;
  } finally {
    loading.value = false;
  }
});
</script>
