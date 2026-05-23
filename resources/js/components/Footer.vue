<template>
  <!-- Footer público: rodapé com tema hospitalar/saúde -->
  <footer class="bg-gray-900 text-gray-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
        <!-- Brand -->
        <div>
          <div class="flex items-center gap-2.5 mb-4">
            <div class="w-8 h-8 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
              </svg>
            </div>
            <span class="text-lg font-bold text-white">Blink</span>
          </div>
          <p class="text-sm text-gray-400 leading-relaxed">
            Sistema inteligente de gestão de clínicas e consultórios. 
            Tecnologia a serviço da saúde.
          </p>
        </div>

        <!-- Quick Links -->
        <div>
          <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Links</h3>
          <ul class="space-y-2">
            <li>
              <router-link to="/" class="text-sm text-gray-400 hover:text-emerald-400 transition-colors">Início</router-link>
            </li>
            <li>
              <router-link to="/login" class="text-sm text-gray-400 hover:text-emerald-400 transition-colors">Entrar</router-link>
            </li>
            <li>
              <router-link to="/register" class="text-sm text-gray-400 hover:text-emerald-400 transition-colors">Cadastre-se</router-link>
            </li>
          </ul>
        </div>

        <!-- Contact -->
        <div>
          <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Contato</h3>
          <ul class="space-y-2 text-sm text-gray-400">
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              contato@blinkmed.com
            </li>
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
              (11) 99999-8888
            </li>
          </ul>
        </div>
      </div>

      <div class="border-t border-gray-800 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-sm text-gray-500">
          &copy; {{ year }} Blink. Todos os direitos reservados.
        </p>
        <div class="flex items-center gap-4 text-sm text-gray-500">
          <router-link to="/terms-of-use" class="hover:text-emerald-400 transition-colors">Termos de Uso</router-link>
          <router-link to="/privacy-policy" class="hover:text-emerald-400 transition-colors">Privacidade</router-link>
        </div>

      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
/**
 * Footer público - rodapé com tema hospitalar/saúde.
 * Usado em páginas não autenticadas (Home, Login, Register).
 */
const year = new Date().getFullYear()
</script>
