<template>
  <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-blue-50">
    <!-- Hero Section -->
    <section class="relative overflow-hidden">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-5">
        <div class="absolute top-0 -left-4 w-72 h-72 bg-emerald-400 rounded-full mix-blend-multiply filter blur-xl animate-pulse"></div>
        <div class="absolute top-0 -right-4 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl animate-pulse delay-1000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-teal-400 rounded-full mix-blend-multiply filter blur-xl animate-pulse delay-2000"></div>
      </div>

      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
        <div class="text-center">
          <!-- Logo Icon -->
          <div class="flex justify-center mb-8">
            <div class="relative">
              <div class="w-20 h-20 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342" />
                </svg>
              </div>
              <!-- Pulse ring -->
              <div class="absolute -inset-2 bg-emerald-400/20 rounded-3xl animate-ping"></div>
            </div>
          </div>

          <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-6 leading-tight">
            Sua saúde em
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-teal-500">boas mãos</span>
          </h1>
          <p class="text-lg sm:text-xl text-gray-600 max-w-2xl mx-auto mb-10 leading-relaxed">
            O <strong class="text-emerald-600">Blink</strong> é o sistema inteligente de gestão de clínicas e consultórios.
            Agende consultas, gerencie pacientes e organize sua agenda com praticidade e segurança.
          </p>

          <!-- CTA Buttons -->
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <router-link
              to="/register"
              class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold rounded-xl shadow-lg shadow-emerald-200 hover:shadow-xl hover:from-emerald-700 hover:to-teal-700 transition-all duration-300 text-center"
            >
              Comece Agora
              <span class="ml-2">→</span>
            </router-link>
            <router-link
              to="/login"
              class="w-full sm:w-auto px-8 py-4 bg-white text-emerald-700 font-semibold rounded-xl border-2 border-emerald-200 hover:border-emerald-400 hover:bg-emerald-50 transition-all duration-300 text-center"
            >
              Já tenho conta
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white/50 backdrop-blur-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <span class="inline-block px-4 py-1.5 bg-emerald-100 text-emerald-700 text-sm font-medium rounded-full mb-4">
            Recursos
          </span>
          <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
            Tudo que sua clínica precisa
          </h2>
          <p class="text-gray-600 max-w-2xl mx-auto">
            Uma plataforma completa para gerenciar sua clínica ou consultório com eficiência.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <!-- Feature 1 -->
          <div class="group bg-white rounded-2xl p-8 shadow-sm border border-emerald-100 hover:shadow-lg hover:border-emerald-300 transition-all duration-300">
            <div class="w-14 h-14 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">Agenda Inteligente</h3>
            <p class="text-gray-600 leading-relaxed">
              Gerencie agendamentos com calendário interativo, visualização por profissional e controle de disponibilidade em tempo real.
            </p>
          </div>

          <!-- Feature 2 -->
          <div class="group bg-white rounded-2xl p-8 shadow-sm border border-emerald-100 hover:shadow-lg hover:border-emerald-300 transition-all duration-300">
            <div class="w-14 h-14 bg-gradient-to-br from-teal-400 to-teal-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">Gestão de Pacientes</h3>
            <p class="text-gray-600 leading-relaxed">
              Cadastro completo com histórico clínico, prontuário digital e dados protegidos com criptografia de ponta a ponta.
            </p>
          </div>

          <!-- Feature 3 -->
          <div class="group bg-white rounded-2xl p-8 shadow-sm border border-emerald-100 hover:shadow-lg hover:border-emerald-300 transition-all duration-300">
            <div class="w-14 h-14 bg-gradient-to-br from-cyan-400 to-cyan-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">Segurança Total</h3>
            <p class="text-gray-600 leading-relaxed">
              Dados sensíveis protegidos com criptografia AES-256, autenticação JWT e conformidade com LGPD para sua tranquilidade.
            </p>
          </div>

          <!-- Feature 4 -->
          <div class="group bg-white rounded-2xl p-8 shadow-sm border border-emerald-100 hover:shadow-lg hover:border-emerald-300 transition-all duration-300">
            <div class="w-14 h-14 bg-gradient-to-br from-sky-400 to-sky-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">Comunicação Interna</h3>
            <p class="text-gray-600 leading-relaxed">
              Sistema de mensagens integrado para comunicação entre equipe, com notificações em tempo real e histórico completo.
            </p>
          </div>

          <!-- Feature 5 -->
          <div class="group bg-white rounded-2xl p-8 shadow-sm border border-emerald-100 hover:shadow-lg hover:border-emerald-300 transition-all duration-300">
            <div class="w-14 h-14 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">Relatórios Detalhados</h3>
            <p class="text-gray-600 leading-relaxed">
              Relatórios financeiros, clínicos e operacionais com dados consolidados para tomada de decisões estratégicas.
            </p>
          </div>

          <!-- Feature 6 -->
          <div class="group bg-white rounded-2xl p-8 shadow-sm border border-emerald-100 hover:shadow-lg hover:border-emerald-300 transition-all duration-300">
            <div class="w-14 h-14 bg-gradient-to-br from-teal-400 to-teal-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">Multiplataforma</h3>
            <p class="text-gray-600 leading-relaxed">
              Acesse de qualquer dispositivo. Interface responsiva e adaptável para desktop, tablet e mobile.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20 bg-gradient-to-r from-emerald-600 to-teal-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
          <div>
            <div class="text-4xl font-bold text-white mb-2">10+</div>
            <div class="text-emerald-100 text-sm font-medium">Especialidades</div>
          </div>
          <div>
            <div class="text-4xl font-bold text-white mb-2">500+</div>
            <div class="text-emerald-100 text-sm font-medium">Consultas/mês</div>
          </div>
          <div>
            <div class="text-4xl font-bold text-white mb-2">99.9%</div>
            <div class="text-emerald-100 text-sm font-medium">Uptime</div>
          </div>
          <div>
            <div class="text-4xl font-bold text-white mb-2">LGPD</div>
            <div class="text-emerald-100 text-sm font-medium">Conformidade</div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-6">
          Pronto para transformar sua clínica?
        </h2>
        <p class="text-lg text-gray-600 mb-10 max-w-2xl mx-auto">
          Junte-se a centenas de profissionais que já confiam no Blink para gerenciar seus atendimentos com excelência.
        </p>
        <router-link
          to="/register"
          class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold rounded-xl shadow-lg shadow-emerald-200 hover:shadow-xl hover:from-emerald-700 hover:to-teal-700 transition-all duration-300"
        >
          Criar Conta Gratuita
          <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
          </svg>
        </router-link>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
/**
 * Home page - Landing page institucional com tema hospitalar/saúde.
 * Apresenta os recursos do sistema Blink de forma atrativa e responsiva.
 */
import { onMounted } from 'vue'
import { useMeta } from '../composables/useMeta'

onMounted(() => {
  useMeta({
    title: 'Sistema de Gestão de Clínicas e Consultórios',
    description: 'Blink é o sistema inteligente de gestão de clínicas e consultórios. Agende consultas, gerencie pacientes e organize sua agenda com praticidade e segurança.',
    keywords: 'gestão de clínicas, agendamento médico, sistema de saúde, prontuário digital, consultório, clínica médica, software para médicos',
    ogType: 'website',
  })
})
</script>
