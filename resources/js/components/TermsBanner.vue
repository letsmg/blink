<template>
  <!-- Banner de aceite de termos - exibido apenas para visitantes NÃO logados que ainda não aceitaram -->
  <div
    v-if="show"
    class="fixed bottom-0 left-0 right-0 z-[9999] bg-gray-900/95 backdrop-blur-sm border-t border-emerald-800 shadow-2xl"
    style="transition: opacity 0.5s ease-out, transform 0.5s ease-out;"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-5">
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <div class="flex-1">
          <p class="text-sm text-gray-200 leading-relaxed">
            Ao continuar navegando, você aceita nossos
            <router-link to="/terms-of-use" class="text-emerald-400 hover:text-emerald-300 font-medium underline">Termos de Uso</router-link>
            e
            <router-link to="/privacy-policy" class="text-emerald-400 hover:text-emerald-300 font-medium underline">Política de Privacidade</router-link>.
            Seus dados de navegação (IP, geolocalização e user-agent) serão registrados conforme a LGPD.
          </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
          <router-link
            to="/terms-of-use"
            class="text-xs text-gray-400 hover:text-gray-200 transition-colors"
          >
            Ler Termos
          </router-link>
          <button
            @click="acceptTerms"
            :disabled="loading"
            class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-medium rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all disabled:opacity-50 shadow-lg shadow-emerald-900/30"
          >
            {{ loading ? 'Aguarde...' : 'Aceitar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * TermsBanner - Banner fixo no rodapé para aceite de termos de uso e privacidade.
 * 
 * Exibe APENAS para visitantes NÃO LOGADOS que ainda não aceitaram os termos.
 * Usuários logados já aceitaram os termos no momento do cadastro e não precisam
 * do banner. O aceite é registrado permanentemente no banco de dados com IP,
 * geolocalização e user-agent.
 * Após o aceite, o banner é ocultado permanentemente (não apenas por sessão).
 */
import { ref, onMounted } from 'vue'
import axios from 'axios'

const show = ref(false)
const loading = ref(false)

onMounted(async () => {
  // Usuários logados já aceitaram os termos no cadastro - não exibir banner
  const user = localStorage.getItem('user')
  if (user) {
    show.value = false
    return
  }

  // Verifica se o visitante (não logado) já aceitou os termos
  try {
    const { data } = await axios.get('/api/check-terms')
    show.value = !data.accepted
  } catch {
    // Em caso de erro, exibe o banner por segurança
    show.value = true
  }
})

async function acceptTerms() {
  loading.value = true
  try {
    await axios.post('/api/accept-terms', {
      term_type: 'both',
      terms_version: '1.0',
    })
    // Fecha o banner imediatamente após aceitar
    show.value = false
  } catch {
    // Se falhar, mantém o banner visível
  } finally {
    loading.value = false
  }
}
</script>
