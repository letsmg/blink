<template>
  <!-- Header público: navegação superior responsiva com tema hospitalar/saúde -->
  <header class="bg-gradient-to-r from-emerald-700 to-teal-800 shadow-lg sticky top-0 z-50">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16 lg:h-20">
        <!-- Logo -->
        <router-link to="/" class="flex items-center gap-2.5 group">
          <div class="w-9 h-9 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow-md shadow-emerald-200 group-hover:shadow-lg group-hover:shadow-emerald-300 transition-all">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </div>
          <span class="text-xl font-bold text-white group-hover:text-emerald-200 transition-colors">Blink</span>

        </router-link>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex items-center gap-8">
          <router-link
            to="/"
            class="text-sm font-medium text-emerald-100 hover:text-white transition-colors relative after:absolute after:bottom-0 after:left-0 after:h-0.5 after:w-0 after:bg-emerald-300 after:transition-all hover:after:w-full"
            exact
          >
            {{ ui.nav.home }}
          </router-link>
          <a
            href="#recursos"
            class="text-sm font-medium text-emerald-100 hover:text-white transition-colors relative after:absolute after:bottom-0 after:left-0 after:h-0.5 after:w-0 after:bg-emerald-300 after:transition-all hover:after:w-full"
          >
            {{ ui.nav.features }}
          </a>

          <!-- Language Selector -->
          <div class="flex items-center gap-1.5">
            <span class="text-[10px] text-emerald-300 uppercase tracking-[0.15em] font-medium">{{ ui.nav.current_language }}</span>
            <select
              v-model="currentLocale"
              @change="handleLocaleChange"
              class="rounded-lg border border-emerald-500/30 bg-emerald-700/50 text-emerald-100 px-2 py-1.5 text-[11px] font-bold uppercase tracking-[0.15em] focus:outline-none focus:ring-2 focus:ring-emerald-400 cursor-pointer hover:bg-emerald-600/50 transition-colors"
            >
              <option
                v-for="language in supportedLanguages"
                :key="language.code"
                :value="language.code"
                class="bg-emerald-800 text-emerald-100"
              >
                {{ language.label }}
              </option>
            </select>
          </div>

          <router-link
            to="/login"
            class="text-sm font-medium text-emerald-100 hover:text-white transition-colors"
          >
            {{ ui.nav.login }}
          </router-link>
          <router-link
            to="/register"
            class="text-sm font-medium px-5 py-2.5 bg-white text-emerald-700 rounded-xl hover:bg-emerald-50 transition-all shadow-md hover:shadow-lg"
          >
            {{ ui.nav.register }}
          </router-link>
        </nav>

        <!-- Mobile Menu Button -->
        <button
          @click="mobileOpen = !mobileOpen"
          class="md:hidden p-2 rounded-lg text-emerald-100 hover:bg-emerald-600/30 hover:text-white transition-colors"
          :aria-label="mobileOpen ? 'Fechar menu' : 'Abrir menu'"
        >

          <svg v-if="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile Menu -->
    <transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 max-h-0"
      enter-to-class="opacity-100 max-h-96"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 max-h-96"
      leave-to-class="opacity-0 max-h-0"
    >
      <div v-if="mobileOpen" class="md:hidden border-t border-emerald-100 bg-white overflow-hidden">
        <div class="px-4 py-4 space-y-2">
          <router-link
            to="/"
            class="block px-4 py-3 text-sm font-medium text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl transition-colors"
            @click="mobileOpen = false"
            exact
          >
            <div class="flex items-center gap-3">
              <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              {{ ui.nav.home }}
            </div>
          </router-link>
          <a
            href="#recursos"
            class="block px-4 py-3 text-sm font-medium text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl transition-colors"
            @click="mobileOpen = false"
          >
            <div class="flex items-center gap-3">
              <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
              {{ ui.nav.features }}
            </div>
          </a>

          <!-- Mobile Language Selector -->
          <div class="px-4 py-3 border-t border-emerald-100 mt-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ ui.nav.current_language }}</span>
              <select
                v-model="currentLocale"
                @change="handleLocaleChange"
                class="rounded-lg border border-gray-200 bg-white text-gray-700 px-2 py-1.5 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-emerald-400 cursor-pointer"
              >
                <option
                  v-for="language in supportedLanguages"
                  :key="language.code"
                  :value="language.code"
                >
                  {{ language.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="border-t border-emerald-100 pt-2 mt-2">
            <router-link
              to="/login"
              class="block px-4 py-3 text-sm font-medium text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl transition-colors"
              @click="mobileOpen = false"
            >
              <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                {{ ui.nav.login }}
              </div>
            </router-link>
            <router-link
              to="/register"
              class="block px-4 py-3 mt-1 text-sm font-medium text-white bg-gradient-to-r from-emerald-600 to-teal-600 rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all text-center"
              @click="mobileOpen = false"
            >
              {{ ui.nav.register }}
            </router-link>
          </div>
        </div>
      </div>
    </transition>
  </header>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from '../composables/useI18n'

/**
 * Header público - navegação responsiva com tema hospitalar/saúde.
 * Usado em páginas não autenticadas (Home, Login, Register).
 * Inclui menu mobile com animação de transição e seletor de idioma.
 */
const mobileOpen = ref(false)

const { ui, currentLocale, supportedLanguages, changeLocale } = useI18n()

function handleLocaleChange() {
  changeLocale(currentLocale.value)
}
</script>