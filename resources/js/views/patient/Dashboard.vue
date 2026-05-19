<template>
  <div class="min-h-screen bg-gradient-to-br from-emerald-50 to-teal-50">
    <header class="bg-white border-b border-gray-200">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        <h1 class="text-xl font-bold text-emerald-700">LaraHealth</h1>
        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-600">Olá, {{ user.name }}</span>
          <button @click="logout" class="text-sm text-gray-500 hover:text-gray-700 font-medium">Sair</button>
        </div>
      </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <h2 class="text-2xl font-semibold text-gray-900 mb-2">Meu Perfil</h2>
        <p class="text-gray-500 mb-6">Bem-vindo ao LaraHealth, {{ user.name }}!</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div class="p-4 bg-emerald-50 rounded-xl">
            <p class="text-sm text-gray-500">Nome</p>
            <p class="font-medium text-gray-900">{{ user.name }}</p>
          </div>
          <div class="p-4 bg-emerald-50 rounded-xl">
            <p class="text-sm text-gray-500">E-mail</p>
            <p class="font-medium text-gray-900">{{ user.email }}</p>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const user = computed(() => JSON.parse(localStorage.getItem('user') || '{}'));

function logout() {
  axios.post('/logout').finally(() => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    router.push('/login');
  });
}
</script>
