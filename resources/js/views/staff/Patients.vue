<template>
  <StaffLayout>
    <div class="max-w-6xl mx-auto">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Pacientes</h1>
        <div class="relative">
          <input v-model="search" type="text" placeholder="Buscar paciente..."
            class="w-64 pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
            @input="debouncedSearch">
          <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
      </div>

      <div v-if="loading" class="text-center py-12 text-gray-500">Carregando...</div>

      <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50">
              <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Nome</th>
              <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">E-mail</th>
              <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">CPF</th>
              <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Data Nasc.</th>
              <th class="text-right px-6 py-3 text-xs font-medium text-gray-500 uppercase">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="patient in patients" :key="patient.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ patient.user?.name || patient.name }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ patient.user?.email || patient.email }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ patient.cpf_masked || '***' }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ patient.birth_date || '-' }}</td>
              <td class="px-6 py-4 text-right">
                <router-link :to="`/staff/patients/${patient.id}`"
                  class="inline-flex items-center gap-1 text-sm text-emerald-600 hover:text-emerald-700 font-medium">
                  Detalhes
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </router-link>
              </td>
            </tr>
            <tr v-if="patients.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-gray-500">Nenhum paciente encontrado.</td>
            </tr>
          </tbody>
        </table>

        <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
          <p class="text-sm text-gray-500">Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }}</p>
          <div class="flex gap-2">
            <button :disabled="!pagination.prev_page_url" @click="changePage(pagination.current_page - 1)"
              class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Anterior</button>
            <button :disabled="!pagination.next_page_url" @click="changePage(pagination.current_page + 1)"
              class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Próximo</button>
          </div>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import StaffLayout from '../../components/StaffLayout.vue';
import axios from 'axios';

const patients = ref([]);
const loading = ref(true);
const search = ref('');
const pagination = ref({});
let debounceTimer = null;

async function fetchPatients(page = 1) {
  loading.value = true;
  try {
    const params = { page };
    if (search.value) params.search = search.value;
    const { data } = await axios.get('/staff/patients', { params });
    patients.value = data.data || data;
    pagination.value = {
      current_page: data.current_page || 1,
      last_page: data.last_page || 1,
      from: data.from || 0,
      to: data.to || 0,
      total: data.total || 0,
      prev_page_url: data.prev_page_url,
      next_page_url: data.next_page_url,
    };
  } catch (e) {
    patients.value = [];
  } finally {
    loading.value = false;
  }
}

function changePage(page) {
  fetchPatients(page);
}

function debouncedSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => fetchPatients(), 300);
}

onMounted(() => fetchPatients());
</script>
