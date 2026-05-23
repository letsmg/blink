<template>
  <!-- Modal de consentimento LGPD - bloqueia totalmente a interação com o site -->
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[99999] flex items-center justify-center"
      style="background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(8px);"
    >
      <!-- Overlay que bloqueia qualquer interação fora do modal -->
      <div class="absolute inset-0" style="pointer-events: auto;"></div>

      <!-- Modal de consentimento -->
      <div
        class="relative z-10 w-full max-w-lg mx-4 bg-gray-900 rounded-2xl shadow-2xl border border-emerald-800/50 overflow-hidden"
        style="pointer-events: auto;"
      >
        <!-- Header -->
        <div class="px-6 pt-6 pb-4 border-b border-gray-800">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-full bg-emerald-600/20 flex items-center justify-center">
              <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <h2 class="text-lg font-semibold text-white">Privacidade e Proteção de Dados</h2>
          </div>
          <p class="text-sm text-gray-400 leading-relaxed">
            Para continuar navegando, você precisa aceitar nossos
            <router-link to="/terms-of-use" class="text-emerald-400 hover:text-emerald-300 font-medium underline">Termos de Uso</router-link>
            e
            <router-link to="/privacy-policy" class="text-emerald-400 hover:text-emerald-300 font-medium underline">Política de Privacidade</router-link>.
          </p>
        </div>

        <!-- Body -->
        <div class="px-6 py-4 space-y-3">
          <div class="flex items-start gap-3 p-3 bg-gray-800/50 rounded-lg">
            <svg class="w-5 h-5 text-emerald-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-gray-300">
              Seus dados de navegação (IP, geolocalização aproximada e user-agent) serão registrados conforme a LGPD.
              Nenhum dado é coletado antes do seu aceite explícito.
            </p>
          </div>

          <div class="flex items-start gap-3 p-3 bg-gray-800/50 rounded-lg">
            <svg class="w-5 h-5 text-emerald-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <p class="text-sm text-gray-300">
              Você pode revogar seu consentimento a qualquer momento. Seus dados são tratados com segurança e não compartilhados sem autorização.
            </p>
          </div>
        </div>

        <!-- Footer com ações -->
        <div class="px-6 py-4 border-t border-gray-800 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
          <router-link
            to="/privacy-policy"
            class="text-xs text-gray-500 hover:text-gray-300 transition-colors text-center sm:text-left underline"
          >
            Política de Privacidade
          </router-link>
          <div class="flex-1"></div>
          <button
            @click="acceptTerms"
            :disabled="loading"
            class="px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-medium rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all disabled:opacity-50 shadow-lg shadow-emerald-900/30"
          >
            {{ loading ? 'Aguarde...' : 'Aceitar e Continuar' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
/**
 * TermsBanner - Modal blocking overlay de consentimento LGPD.
 *
 * Exibe um modal impositivo que BLOQUEIA totalmente a interação com o site
 * até que o visitante aceite explicitamente os Termos de Uso e Política de
 * Privacidade. O conteúdo do site fica oculto atrás de um overlay com blur.
 *
 * Regras LGPD seguidas:
 * 1. NENHUMA coleta de IP, geolocalização ou tracking antes do aceite
 * 2. O visitor_uuid é gerado APENAS para identificar o visitante, sem coletar dados
 * 3. O aceite é registrado permanentemente no banco de dados (não apenas em cookie/sessão)
 * 4. Se a versão dos termos mudar, o modal é reexibido
 * 5. Para usuários logados, verifica se a versão aceita é a atual
 */
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { getVisitorId } from '../utils/visitorId'

const show = ref(false)
const loading = ref(false)

// Versão atual dos termos (deve ser incrementada quando houver mudanças)
const CURRENT_TERMS_VERSION = '1.0'

onMounted(async () => {
  // Gera/obtém o visitor_uuid (apenas identificador, sem coleta de dados)
  getVisitorId()

  // Usuários logados: verifica se a versão aceita é a atual
  const user = localStorage.getItem('user')
  if (user) {
    try {
      const parsed = JSON.parse(user)
      // Se o usuário já aceitou e a versão é a atual, não exibe modal
      if (parsed.terms_accepted && parsed.terms_version === CURRENT_TERMS_VERSION) {
        show.value = false
        return
      }
      // Se a versão aceita é inferior à atual, reexibe o modal
      if (parsed.terms_accepted && parsed.terms_version !== CURRENT_TERMS_VERSION) {
        show.value = true
        return
      }
    } catch {
      // Em caso de erro, exibe o modal por segurança
    }
  }

  // Visitante não logado: verifica se já aceitou os termos
  try {
    const visitorId = getVisitorId()
    const { data } = await axios.get('/api/check-terms', {
      params: { visitor_uuid: visitorId },
    })
    show.value = !data.accepted
  } catch {
    // Em caso de erro, exibe o modal por segurança
    show.value = true
  }

})

async function acceptTerms() {
  loading.value = true
  try {
    const visitorId = getVisitorId()

    await axios.post('/api/accept-terms', {
      term_type: 'both',
      terms_version: CURRENT_TERMS_VERSION,
      visitor_uuid: visitorId,
    })

    // Atualiza o user no localStorage se estiver logado
    const user = localStorage.getItem('user')
    if (user) {
      const parsed = JSON.parse(user)
      parsed.terms_accepted = true
      parsed.terms_version = CURRENT_TERMS_VERSION
      localStorage.setItem('user', JSON.stringify(parsed))
    }

    // Fecha o modal imediatamente após aceitar
    show.value = false
  } catch (err) {
    // Se falhar, mantém o modal visível
    console.error('Erro ao aceitar termos:', err)
  } finally {

    loading.value = false
  }
}
</script>
