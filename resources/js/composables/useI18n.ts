/**
 * Composable para internacionalização (i18n) do Blink.
 *
 * Carrega as traduções do backend via /api/translations,
 * gerencia o locale atual e fornece acesso reativo às traduções.
 *
 * Uso:
 *   const { ui, currentLocale, supportedLanguages, languageLabel, changeLocale, loading } = useI18n()
 *   // No template: {{ ui.nav.home }}
 */
import { ref, computed, onMounted } from 'vue'

// Cache global das traduções (carregado uma única vez)
let translationsCache: Record<string, any> | null = null
let loadingPromise: Promise<void> | null = null

/**
 * Hook para internacionalização.
 * Pode ser usado em qualquer componente Vue.
 */
export function useI18n() {
  const currentLocale = ref('pt')
  const translations = ref<Record<string, any>>({})
  const loading = ref(true)

  /**
   * Mapeia as traduções carregadas para um objeto reativo.
   * Retorna as traduções do locale atual, ou fallback para pt/en.
   */
  const currentTranslations = computed(() => {
    return translations.value[currentLocale.value] 
      ?? translations.value.pt 
      ?? translations.value.en 
      ?? {}
  })

  /**
   * Objeto ui com todas as strings traduzidas do frontend público.
   * Inclui fallback seguro para evitar undefined em templates.
   */
  const ui = computed(() => ({
    nav: {
      home: currentTranslations.value?.ui?.nav?.home ?? 'Início',
      features: currentTranslations.value?.ui?.nav?.features ?? 'Recursos',
      login: currentTranslations.value?.ui?.nav?.login ?? 'Entrar',
      register: currentTranslations.value?.ui?.nav?.register ?? 'Cadastre-se',
      current_language: currentTranslations.value?.ui?.nav?.current_language ?? 'Idioma',
    },
    hero: {
      title: currentTranslations.value?.ui?.hero?.title ?? 'Sua saúde em boas mãos',
      description: currentTranslations.value?.ui?.hero?.description ?? '',
      cta: currentTranslations.value?.ui?.hero?.cta ?? 'Comece Agora',
      login: currentTranslations.value?.ui?.hero?.login ?? 'Já tenho conta',
    },
    features: {
      label: currentTranslations.value?.ui?.features?.label ?? 'Recursos',
      title: currentTranslations.value?.ui?.features?.title ?? 'Tudo que sua clínica precisa',
      subtitle: currentTranslations.value?.ui?.features?.subtitle ?? '',
      items: currentTranslations.value?.ui?.features?.items ?? [],
    },
    stats: {
      specialties: currentTranslations.value?.ui?.stats?.specialties ?? 'Especialidades',
      appointments: currentTranslations.value?.ui?.stats?.appointments ?? 'Consultas/mês',
      uptime: currentTranslations.value?.ui?.stats?.uptime ?? 'Uptime',
      lgpd: currentTranslations.value?.ui?.stats?.lgpd ?? 'Conformidade',
    },
    cta: {
      title: currentTranslations.value?.ui?.cta?.title ?? 'Pronto para transformar sua clínica?',
      description: currentTranslations.value?.ui?.cta?.description ?? '',
      button: currentTranslations.value?.ui?.cta?.button ?? 'Criar Conta Gratuita',
    },
    footer: {
      copyright: currentTranslations.value?.ui?.footer?.copyright ?? '© :year Blink. Todos os direitos reservados.',
      privacy: currentTranslations.value?.ui?.footer?.privacy ?? 'Política de Privacidade',
      terms: currentTranslations.value?.ui?.footer?.terms ?? 'Termos de Uso',
    },
    legal_modal: {
      title: currentTranslations.value?.ui?.legal_modal?.title ?? 'Privacidade e Proteção de Dados',
      description: currentTranslations.value?.ui?.legal_modal?.description ?? 'Para continuar navegando, você precisa aceitar nossos',
      terms_link: currentTranslations.value?.ui?.legal_modal?.terms_link ?? 'Termos de Uso',
      privacy_link: currentTranslations.value?.ui?.legal_modal?.privacy_link ?? 'Política de Privacidade',
      ip_notice: currentTranslations.value?.ui?.legal_modal?.ip_notice ?? 'Seus dados de navegação (IP, geolocalização aproximada e user-agent) serão registrados conforme a LGPD. Nenhum dado é coletado antes do seu aceite explícito.',
      consent_notice: currentTranslations.value?.ui?.legal_modal?.consent_notice ?? 'Você pode revogar seu consentimento a qualquer momento. Seus dados são tratados com segurança e não compartilhados sem autorização.',
      accept_button: currentTranslations.value?.ui?.legal_modal?.accept_button ?? 'Aceitar e Continuar',
      loading: currentTranslations.value?.ui?.legal_modal?.loading ?? 'Aguarde...',
    },
    languages: currentTranslations.value?.languages ?? {
      pt: 'Português',
      en: 'English',
      es: 'Español',
    },
    legal_modal_button: currentTranslations.value?.ui?.legal_modal_button ?? 'Política de Privacidade & Termos de Uso',
  }))

  /**
   * Lista de idiomas suportados no formato { code, label }.
   */
  const supportedLanguages = computed(() => {
    const keys = Object.keys(currentTranslations.value?.languages ?? {})
    
    if (keys.length === 0) {
      return [
        { code: 'pt', label: 'Português' },
        { code: 'en', label: 'English' },
        { code: 'es', label: 'Español' },
      ]
    }

    return keys.map((code: string) => ({
      code,
      label: currentTranslations.value.languages[code],
    }))
  })

  /**
   * Label do idioma atual.
   */
  const languageLabel = computed(() => {
    return supportedLanguages.value.find((l: { code: string }) => l.code === currentLocale.value)?.label ?? 'Português'
  })

  /**
   * Verifica se um locale é suportado.
   */
  function isSupportedLocale(locale: string): boolean {
    return supportedLanguages.value.some((l: { code: string }) => l.code === locale)
  }

  /**
   * Carrega as traduções do backend.
   */
  async function loadTranslations() {
    if (translationsCache) {
      translations.value = translationsCache
      loading.value = false
      return
    }

    if (loadingPromise) {
      await loadingPromise
      translations.value = translationsCache ?? {}
      loading.value = false
      return
    }

    loadingPromise = (async () => {
      try {
        const response = await fetch('/api/translations', {
          credentials: 'include',
        })

        if (!response.ok) {
          console.error('loadTranslations failed:', response.status)
          return
        }

        const data = await response.json()
        translationsCache = data
        translations.value = data
      } catch (err) {
        console.error('loadTranslations error:', err)
      } finally {
        loading.value = false
        loadingPromise = null
      }
    })()

    await loadingPromise
  }

  /**
   * Restaura o locale salvo no localStorage.
   */
  function restoreLocale() {
    try {
      const saved = localStorage.getItem('preferred_locale')
      if (saved && isSupportedLocale(saved)) {
        currentLocale.value = saved
      }
    } catch {
      // Silently fail
    }
  }

  /**
   * Altera o locale e persiste a preferência.
   */
  async function changeLocale(locale: string) {
    if (!isSupportedLocale(locale)) {
      console.warn(`Locale "${locale}" não é suportado`)
      return
    }

    currentLocale.value = locale

    // Salva no localStorage
    try {
      localStorage.setItem('preferred_locale', locale)
    } catch {
      // Silently fail
    }

    // Persiste no backend
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
          origin: 'manual',
        }),
      })
    } catch (err) {
      console.error('Failed to persist locale change:', err)
    }
  }

  // Inicialização
  onMounted(async () => {
    restoreLocale()
    await loadTranslations()
  })

  return {
    // State
    currentLocale,
    translations,
    loading,

    // Computed
    ui,
    currentTranslations,
    supportedLanguages,
    languageLabel,

    // Methods
    changeLocale,
    isSupportedLocale,
    loadTranslations,
  }
}