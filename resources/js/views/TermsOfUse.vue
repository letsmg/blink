<template>
  <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-blue-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <!-- Header -->
      <div class="text-center mb-12">
        <div class="flex justify-center mb-6">
          <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Termos de Uso</h1>
        <p class="text-gray-500 text-sm">Última atualização: 23 de maio de 2026</p>
      </div>

      <!-- Content -->
      <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-8 md:p-12 space-y-8">
        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">1. Aceitação dos Termos</h2>
          <p class="text-gray-600 leading-relaxed">
            Ao acessar e utilizar a plataforma Blink, você declara ter lido, compreendido e concordado
            com todos os termos e condições descritos neste documento. Caso não concorde com qualquer
            parte destes termos, você não deve utilizar nossos serviços.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">2. Definições</h2>
          <p class="text-gray-600 leading-relaxed mb-4">Para os fins destes Termos de Uso, considera-se:</p>
          <ul class="list-disc list-inside text-gray-600 space-y-2 leading-relaxed">
            <li><strong>Plataforma:</strong> O sistema Blink, incluindo website, aplicativo e todos os serviços oferecidos.</li>
            <li><strong>Usuário:</strong> Toda pessoa física que acessar ou utilizar a plataforma.</li>
            <li><strong>Paciente:</strong> Usuário que utiliza a plataforma para agendar consultas e gerenciar sua saúde.</li>
            <li><strong>Profissional:</strong> Médico, dentista, fisioterapeuta ou outro profissional de saúde cadastrado.</li>
            <li><strong>Dados Pessoais:</strong> Qualquer informação relacionada a uma pessoa natural identificada ou identificável.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">3. Serviços Oferecidos</h2>
          <p class="text-gray-600 leading-relaxed">
            O Blink oferece uma plataforma de gestão de clínicas e consultórios, incluindo:
            agendamento de consultas, prontuário digital, gestão de pacientes, comunicação interna,
            relatórios gerenciais e demais funcionalidades disponibilizadas na plataforma.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">4. Obrigações do Usuário</h2>
          <ul class="list-disc list-inside text-gray-600 space-y-2 leading-relaxed">
            <li>Fornecer dados cadastrais verdadeiros, precisos e atualizados.</li>
            <li>Não compartilhar credenciais de acesso com terceiros.</li>
            <li>Utilizar a plataforma de acordo com a legislação vigente e a moralidade.</li>
            <li>Não realizar qualquer ação que possa danificar, sobrecarregar ou prejudicar a infraestrutura da plataforma.</li>
            <li>Manter a confidencialidade dos dados de pacientes quando aplicável.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">5. Privacidade e Proteção de Dados</h2>
          <p class="text-gray-600 leading-relaxed">
            O tratamento de dados pessoais é realizado conforme descrito em nossa
            <router-link to="/privacy-policy" class="text-emerald-600 hover:text-emerald-700 font-medium">Política de Privacidade</router-link>.
            Ao aceitar estes termos, você também declara ter lido e concordado com nossa política de privacidade.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">6. Propriedade Intelectual</h2>
          <p class="text-gray-600 leading-relaxed">
            Todo o conteúdo, código, design e funcionalidades da plataforma Blink são de propriedade
            exclusiva da Blink Tecnologia Ltda. É proibida a reprodução, distribuição ou modificação
            sem autorização expressa.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">7. Limitação de Responsabilidade</h2>
          <p class="text-gray-600 leading-relaxed">
            A plataforma Blink atua como intermediária na relação entre pacientes e profissionais de saúde.
            Não nos responsabilizamos pela qualidade dos serviços médicos prestados, sendo esta de
            responsabilidade exclusiva do profissional contratado.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">8. Disposições Gerais</h2>
          <ul class="list-disc list-inside text-gray-600 space-y-2 leading-relaxed">
            <li>Estes termos podem ser alterados a qualquer momento, sendo comunicado aos usuários sobre mudanças significativas.</li>
            <li>O foro eleito é o da comarca de São Paulo - SP.</li>
            <li>Estes termos são regidos pela legislação brasileira.</li>
          </ul>
        </section>

        <div class="border-t border-emerald-100 pt-6 mt-8">
          <p class="text-sm text-gray-500">
            Em caso de dúvidas, entre em contato pelo e-mail: 
            <a href="mailto:contato@blinkmed.com" class="text-emerald-600 hover:text-emerald-700 font-medium">contato@blinkmed.com</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Página de Termos de Uso - Documento legal com as condições de uso da plataforma Blink.
 */
import { onMounted } from 'vue'
import { useMeta } from '../composables/useMeta'

onMounted(() => {
  useMeta({
    title: 'Termos de Uso - Blink',
    description: 'Leia os Termos de Uso da plataforma Blink. Conheça as condições, direitos e deveres ao utilizar nosso sistema de gestão de clínicas e consultórios.',
    keywords: 'termos de uso, condições, contrato, plataforma, saúde, clínica, gestão, LGPD',
    ogType: 'website',
  })
})
</script>
