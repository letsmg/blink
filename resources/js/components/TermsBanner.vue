<template>
  <!-- Banner de consentimento LGPD no rodapé — NÃO bloqueia a navegação -->
  <!-- Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados. -->
  <Teleport to="body">
    <transition name="terms-banner">
      <div
        v-if="show"
        class="fixed bottom-0 left-0 right-0 z-[99999]"
      >
        <!-- Faixa principal do banner -->
        <div class="bg-gray-900/95 backdrop-blur-md border-t border-emerald-800/50 shadow-[0_-4px_24px_rgba(0,0,0,0.4)]">
          <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
              <!-- Ícone e conteúdo textual -->
              <div class="flex items-start gap-3 flex-1 min-w-0">
                <div class="w-8 h-8 rounded-full bg-emerald-600/20 flex items-center justify-center shrink-0 mt-0.5">
                  <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                  </svg>
                </div>
                <div class="min-w-0">
                  <p class="text-sm text-gray-200 leading-relaxed">
                    {{ ui.legal_modal.description }}
                    <router-link to="/terms-of-use" class="text-emerald-400 hover:text-emerald-300 font-medium underline whitespace-nowrap">{{ ui.legal_modal.terms_link }}</router-link>
                    {{ ' ' }}e{{ ' ' }}
                    <router-link to="/privacy-policy" class="text-emerald-400 hover:text-emerald-300 font-medium underline whitespace-nowrap">{{ ui.legal_modal.privacy_link }}</router-link>.
                  </p>
                  <p class="text-xs text-gray-400 mt-1">
                    {{ ui.legal_modal.ip_notice }}
                  </p>
                </div>
              </div>

              <!-- Ações: Aceitar e Fechar -->
              <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                <button
                  @click="dismissBanner"
                  class="p-2 text-gray-500 hover:text-gray-300 hover:bg-gray-800 rounded-lg transition-colors"
                  :title="'Fechar'"
                  aria-label="Fechar banner de consentimento"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
                <button
                  @click="acceptTerms"
                  :disabled="loading"
                  class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-medium rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all disabled:opacity-50 shadow-lg shadow-emerald-900/30 whitespace-nowrap"
                >
                  {{ loading ? '...' : ui.legal_modal.accept_button }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup lang="ts">
/**
 * TermsBanner - Banner de rodapé NÃO BLOQUEANTE de consentimento LGPD.
 *
 * Diferentemente do modal gatekeeping anterior, este banner:
 * - Fica fixo no rodapé da página e NÃO bloqueia a interação com o site.
 * - Pode ser fechado (dispensado) pelo visitante sem aceitar os termos.
 * - Se fechado, permanece oculto durante a sessão atual (sessionStorage),
 *   mas reaparece em uma nova visita/aba.
 * - Ao aceitar, o comportamento é idêntico ao anterior: registra o aceite
 *   no banco de dados, detecta o idioma automaticamente e persiste.
 *
 * Regras LGPD seguidas:
 * 1. NENHUMA coleta de IP, geolocalização ou tracking antes do aceite explícito.
 * 2. O visitor_uuid é gerado APENAS para identificar o visitante, sem coletar dados.
 * 3. O aceite é registrado permanentemente no banco de dados.
 * 4. Se a versão dos termos mudar, o banner é reexibido mesmo para quem já aceitou.
 */
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { getVisitorId } from '../utils/visitorId'
import { useI18n } from '../composables/useI18n'

const { ui, currentLocale } = useI18n()

const show = ref(false)
const loading = ref(false)

// Chave usada para lembrar que o visitante dispensou o banner na sessão atual
const DISMISSED_KEY = 'terms_banner_dismissed'

// Versão atual dos termos (deve ser incrementada quando houver mudanças)
const CURRENT_TERMS_VERSION = '1.0'

/**
 * Verifica se o banner foi dispensado na sessão atual.
 */
function isDismissed(): boolean {
  try {
    return sessionStorage.getItem(DISMISSED_KEY) === '1'
  } catch {
    return false
  }
}

/**
 * Marca o banner como dispensado na sessão atual.
 */
function markDismissed(): void {
  try {
    sessionStorage.setItem(DISMISSED_KEY, '1')
  } catch {
    // Silently fail
  }
}

/**
 * Mapeia código de país para locale suportado.
 */
function mapCountryToLocale(countryCode: string): string | null {
  const normalized = String(countryCode || '').toUpperCase()
  const mapping: Record<string, string[]> = {
    pt: ['BR', 'PT', 'AO', 'MZ', 'CV', 'GW', 'ST', 'TL'],
    es: ['AR', 'BO', 'CL', 'CO', 'CR', 'CU', 'DO', 'EC', 'GT', 'HN', 'MX', 'NI', 'PA', 'PY', 'PE', 'PR', 'ES', 'UY', 'VE'],
    en: ['US', 'GB', 'AU', 'CA', 'NZ', 'IE', 'SG', 'ZA', 'NG', 'JM'],
  }
  for (const [locale, countries] of Object.entries(mapping)) {
    if (countries.includes(normalized)) return locale
  }
  return null
}

/**
 * Detecta localização via IP diretamente do frontend usando ip-api.com.
 */
async function detectLocaleFromFrontendIp() {
  try {
    const controller = new AbortController()
    const timeoutId = setTimeout(() => controller.abort(), 5000)
    const response = await fetch('https://ip-api.com/json/?fields=status,countryCode', {
      signal: controller.signal,
    })
    clearTimeout(timeoutId)
    if (!response.ok) return null
    const data = await response.json()
    if (data.status !== 'success') return null
    const locale = mapCountryToLocale(data.countryCode)
    return locale ? { locale, country_code: data.countryCode } : null
  } catch {
    return null
  }
}

/**
 * Detecta localização via GPS do navegador (requer HTTPS).
 */
async function detectLocaleWithGps() {
  if (!('geolocation' in navigator)) return null
  try {
    const position = await new Promise<GeolocationPosition>((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 8000, maximumAge: 0 })
    })
    const { latitude, longitude } = position.coords
    const controller = new AbortController()
    const timeoutId = setTimeout(() => controller.abort(), 5000)
    const response = await fetch(
      `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${latitude}&longitude=${longitude}&localityLanguage=en`,
      { signal: controller.signal }
    )
    clearTimeout(timeoutId)
    if (!response.ok) return null
    const data = await response.json()
    const locale = mapCountryToLocale(data.countryCode)
    return locale ? { locale, country_code: data.countryCode } : null
  } catch {
    return null
  }
}

/**
 * Detecta localização via IP usando o backend.
 */
async function detectLocaleFromIp() {
  try {
    const response = await fetch('/api/visitor-language/fallback', { credentials: 'include' })
    if (!response.ok) return null
    const data = await response.json()
    return data.locale ? { locale: data.locale, country_code: data.country_code } : null
  } catch {
    return null
  }
}

/**
 * Persiste a preferência de idioma + aceite no backend.
 */
async function persistLocale(locale: string, origin: string, countryCode: string | null = null) {
  try {
    await fetch('/api/visitor-language', {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
      },
      body: JSON.stringify({
        preferred_locale: locale,
        origin,
        country_code: countryCode,
        terms_accepted: true,
        privacy_accepted: true,
      }),
    })
  } catch (err) {
    console.error('Error persisting locale:', err)
  }
}

onMounted(async () => {
  // Gera/obtém o visitor_uuid (apenas identificador, sem coleta de dados)
  getVisitorId()

  // Se o banner foi dispensado nesta sessão, não exibe
  if (isDismissed()) {
    show.value = false
    return
  }

  // Usuários logados: verifica se a versão aceita é a atual
  const user = localStorage.getItem('user')
  if (user) {
    try {
      const parsed = JSON.parse(user)
      // Se o usuário já aceitou e a versão é a atual, não exibe banner
      if (parsed.terms_accepted && parsed.terms_version === CURRENT_TERMS_VERSION) {
        show.value = false
        return
      }
      // Se a versão aceita é inferior à atual, reexibe o banner
      if (parsed.terms_accepted && parsed.terms_version !== CURRENT_TERMS_VERSION) {
        show.value = true
        return
      }
    } catch {
      // Em caso de erro, exibe o banner por segurança
    }
  }

  // Visitante não logado: verifica se já aceitou os termos
  try {
    const visitorId = getVisitorId()
    const { data } = await axios.get('/check-terms', {
      params: { visitor_uuid: visitorId },
    })

    show.value = !data.accepted
  } catch {
    // Em caso de erro, exibe o banner por segurança
    show.value = true
  }
})

/**
 * Dispensa o banner sem aceitar os termos.
 * O banner permanece oculto durante a sessão atual, mas reaparece
 * em uma nova visita ou aba do navegador.
 */
function dismissBanner() {
  markDismissed()
  show.value = false
}

async function acceptTerms() {
  loading.value = true
  try {
    const visitorId = getVisitorId()

    // 1) Registra o aceite dos termos (sistema existente)
    await axios.post('/accept-terms', {
      term_type: 'both',
      terms_version: CURRENT_TERMS_VERSION,
      visitor_uuid: visitorId,
    })

    // 2) Detecta idioma automaticamente após aceite
    let detectedLocale: string | null = null
    try {
      // PRIORIDADE 1: Geolocalização por IP via frontend
      const frontendIpLocale = await detectLocaleFromFrontendIp()
      if (frontendIpLocale && frontendIpLocale.locale) {
        detectedLocale = frontendIpLocale.locale
        await persistLocale(frontendIpLocale.locale, 'ip_frontend', frontendIpLocale.country_code)
      } else {
        // PRIORIDADE 2: GPS
        const gpsLocale = await detectLocaleWithGps()
        if (gpsLocale && gpsLocale.locale) {
          detectedLocale = gpsLocale.locale
          await persistLocale(gpsLocale.locale, 'gps', gpsLocale.country_code)
        } else {
          // PRIORIDADE 3: Geolocalização por IP via backend
          const ipLocale = await detectLocaleFromIp()
          if (ipLocale && ipLocale.locale) {
            detectedLocale = ipLocale.locale
            await persistLocale(ipLocale.locale, 'ip', ipLocale.country_code)
          } else {
            // PRIORIDADE 4: Accept-Language do navegador
            const browserLang = ((navigator.language || '') as string).split('-')[0]
            const supported: Record<string, string> = { pt: 'pt', es: 'es', en: 'en' }
            const chosen = supported[browserLang] || 'en'
            detectedLocale = chosen
            await persistLocale(chosen, 'browser', null)
          }
        }
      }

      // Atualiza o locale reativo no useI18n
      if (detectedLocale) {
        currentLocale.value = detectedLocale
        try {
          localStorage.setItem('preferred_locale', detectedLocale)
        } catch {
          // Silently fail
        }
        console.log('Idioma detectado automaticamente:', detectedLocale)
      }
    } catch (err) {
      console.error('Locale detection error:', err)
    }

    // Atualiza o user no localStorage se estiver logado
    const userData = localStorage.getItem('user')
    if (userData) {
      const parsed = JSON.parse(userData)
      parsed.terms_accepted = true
      parsed.terms_version = CURRENT_TERMS_VERSION
      localStorage.setItem('user', JSON.stringify(parsed))
    }

    // Fecha o banner após aceitar
    show.value = false
  } catch (err) {
    console.error('Erro ao aceitar termos:', err)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* Transição suave para o banner de rodapé */
.terms-banner-enter-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.terms-banner-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.terms-banner-enter-from {
  transform: translateY(100%);
  opacity: 0;
}
.terms-banner-leave-to {
  transform: translateY(100%);
  opacity: 0;
}
</style>