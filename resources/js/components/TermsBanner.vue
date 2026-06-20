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
            <h2 class="text-lg font-semibold text-white">{{ ui.legal_modal.title }}</h2>
          </div>
          <p class="text-sm text-gray-400 leading-relaxed">
            {{ ui.legal_modal.description }}
            <router-link to="/terms-of-use" class="text-emerald-400 hover:text-emerald-300 font-medium underline">{{ ui.legal_modal.terms_link }}</router-link>
            e
            <router-link to="/privacy-policy" class="text-emerald-400 hover:text-emerald-300 font-medium underline">{{ ui.legal_modal.privacy_link }}</router-link>.
          </p>
        </div>

        <!-- Body -->
        <div class="px-6 py-4 space-y-3">
          <div class="flex items-start gap-3 p-3 bg-gray-800/50 rounded-lg">
            <svg class="w-5 h-5 text-emerald-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-gray-300">
              {{ ui.legal_modal.ip_notice }}
            </p>
          </div>

          <div class="flex items-start gap-3 p-3 bg-gray-800/50 rounded-lg">
            <svg class="w-5 h-5 text-emerald-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <p class="text-sm text-gray-300">
              {{ ui.legal_modal.consent_notice }}
            </p>
          </div>
        </div>

        <!-- Footer com ações -->
        <div class="px-6 py-4 border-t border-gray-800 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
          <router-link
            to="/privacy-policy"
            class="text-xs text-gray-500 hover:text-gray-300 transition-colors text-center sm:text-left underline"
          >
            {{ ui.legal_modal.privacy_link }}
          </router-link>
          <div class="flex-1"></div>
          <button
            @click="acceptTerms"
            :disabled="loading"
            class="px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-medium rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all disabled:opacity-50 shadow-lg shadow-emerald-900/30"
          >
            {{ loading ? ui.legal_modal.loading : ui.legal_modal.accept_button }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
/**
 * TermsBanner - Modal blocking overlay de consentimento LGPD com detecção automática de idioma.
 *
 * Exibe um modal impositivo que BLOQUEIA totalmente a interação com o site
 * até que o visitante aceite explicitamente os Termos de Uso e Política de
 * Privacidade. Após o aceite, detecta automaticamente o idioma baseado em:
 * 1. GPS (se permitido pelo navegador)
 * 2. Geolocalização por IP
 * 3. Accept-Language do navegador
 *
 * O idioma detectado é salvo na tabela visitor_language_preferences (SQLite).
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
import { useI18n } from '../composables/useI18n'

const { ui, currentLocale } = useI18n()

const show = ref(false)
const loading = ref(false)

// Versão atual dos termos (deve ser incrementada quando houver mudanças)
const CURRENT_TERMS_VERSION = '1.0'

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
 * Esta é a melhor opção quando o usuário está com VPN,
 * pois o navegador tem o IP real da VPN, diferente do backend em localhost.
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
    const { data } = await axios.get('/check-terms', {
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

    // 1) Registra o aceite dos termos (sistema existente)
    await axios.post('/accept-terms', {
      term_type: 'both',
      terms_version: CURRENT_TERMS_VERSION,
      visitor_uuid: visitorId,
    })

    // 2) Detecta idioma automaticamente após aceite
    let detectedLocale: string | null = null
    try {
      // PRIORIDADE 1: Geolocalização por IP via frontend (funciona com VPN,
      // pois o navegador tem o IP real, diferente do backend em localhost)
      const frontendIpLocale = await detectLocaleFromFrontendIp()
      if (frontendIpLocale && frontendIpLocale.locale) {
        detectedLocale = frontendIpLocale.locale
        await persistLocale(frontendIpLocale.locale, 'ip_frontend', frontendIpLocale.country_code)
      } else {
        // PRIORIDADE 2: GPS (requer HTTPS, pode solicitar permissão ao usuário)
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
            // PRIORIDADE 4: Accept-Language do navegador (último recurso)
            const browserLang = ((navigator.language || '') as string).split('-')[0]
            const supported: Record<string, string> = { pt: 'pt', es: 'es', en: 'en' }
            const chosen = supported[browserLang] || 'en'
            detectedLocale = chosen
            await persistLocale(chosen, 'browser', null)
          }
        }
      }

      // Atualiza o locale reativo no useI18n para refletir a mudança imediatamente.
      // O persistLocale() já salvou no backend com terms_accepted=true,
      // então apenas atualizamos o estado local e o localStorage.
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