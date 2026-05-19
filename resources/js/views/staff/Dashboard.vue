<template>
  <StaffLayout>
    <div class="max-w-6xl mx-auto">
      <h1 class="text-2xl font-semibold text-gray-900 mb-6">Dashboard</h1>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center gap-3 mb-3">
            <div class="p-2 bg-blue-50 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
              <p class="text-sm text-gray-500">Total de Pacientes</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.totalPatients }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center gap-3 mb-3">
            <div class="p-2 bg-emerald-50 rounded-lg">
              <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            </div>
            <div>
              <p class="text-sm text-gray-500">Mensagens Não Lidas</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.unreadMessages }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center gap-3 mb-3">
            <div class="p-2 bg-amber-50 rounded-lg">
              <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
              <p class="text-sm text-gray-500">Profissionais</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.totalProfessionals }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Acesso Rápido</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <router-link to="/staff/patients" class="p-4 bg-gray-50 rounded-xl hover:bg-emerald-50 transition-colors">
            <p class="font-medium text-gray-900">Gerenciar Pacientes</p>
            <p class="text-sm text-gray-500 mt-1">Visualizar e editar cadastros</p>
          </router-link>
          <router-link to="/staff/messages" class="p-4 bg-gray-50 rounded-xl hover:bg-emerald-50 transition-colors">
            <p class="font-medium text-gray-900">Mensagens</p>
            <p class="text-sm text-gray-500 mt-1">Comunicação interna</p>
          </router-link>
          <router-link to="/staff/unavailability" class="p-4 bg-gray-50 rounded-xl hover:bg-emerald-50 transition-colors">
            <p class="font-medium text-gray-900">Indisponibilidade</p>
            <p class="text-sm text-gray-500 mt-1">Gerenciar períodos</p>
          </router-link>
        </div>
      </div>
    </div>
  </StaffLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import StaffLayout from '../../components/StaffLayout.vue';
import axios from 'axios';

const stats = ref({
  totalPatients: 0,
  unreadMessages: 0,
  totalProfessionals: 0,
});

onMounted(async () => {
  try {
    const [patients, messages, professionals] = await Promise.all([
      axios.get('/staff/patients?per_page=1'),
      axios.get('/staff/messages/unread-count'),
      axios.get('/staff/professionals'),
    ]);
    stats.value.totalPatients = patients.data.total || patients.data.data?.total || 0;
    stats.value.unreadMessages = messages.data.count || 0;
    stats.value.totalProfessionals = professionals.data.length || professionals.data.total || 0;
  } catch (e) {
    // silent
  }
});
</script>
