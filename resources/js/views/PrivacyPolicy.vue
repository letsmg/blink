<template>
  <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-blue-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <!-- Header -->
      <div class="text-center mb-12">
        <div class="flex justify-center mb-6">
          <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Política de Privacidade</h1>
        <p class="text-gray-500 text-sm">Última atualização: 23 de maio de 2026</p>
      </div>

      <!-- Content -->
      <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-8 md:p-12 space-y-8">
        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">1. Introdução</h2>
          <p class="text-gray-600 leading-relaxed">
            A Blink Tecnologia Ltda. ("Blink", "nós" ou "nosso") está comprometida com a proteção
            da sua privacidade. Esta Política de Privacidade descreve como coletamos, usamos,
            armazenamos e protegemos seus dados pessoais em conformidade com a Lei Geral de
            Proteção de Dados Pessoais (LGPD - Lei nº 13.709/2018).
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">2. Dados Coletados</h2>
          <p class="text-gray-600 leading-relaxed mb-4">Podemos coletar os seguintes dados pessoais:</p>
          <ul class="list-disc list-inside text-gray-600 space-y-2 leading-relaxed">
            <li><strong>Dados de identificação:</strong> Nome completo, CPF (criptografado), data de nascimento.</li>
            <li><strong>Dados de contato:</strong> E-mail, telefone, endereço.</li>
            <li><strong>Dados de saúde:</strong> Histórico clínico, queixa principal, diagnósticos (com consentimento explícito).</li>
            <li><strong>Dados de navegação:</strong> Endereço IP, geolocalização aproximada, user-agent do navegador.</li>
            <li><strong>Dados de uso:</strong> Páginas visitadas, funcionalidades utilizadas, preferências.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">3. Finalidade do Tratamento</h2>
          <p class="text-gray-600 leading-relaxed mb-4">Seus dados são utilizados para:</p>
          <ul class="list-disc list-inside text-gray-600 space-y-2 leading-relaxed">
            <li>Fornecer e melhorar nossos serviços de gestão de clínicas e consultórios.</li>
            <li>Processar agendamentos de consultas e gerenciar prontuários.</li>
            <li>Comunicar informações relevantes sobre seus agendamentos e nossa plataforma.</li>
            <li>Cumprir obrigações legais e regulatórias.</li>
            <li>Garantir a segurança da plataforma e prevenir fraudes.</li>
            <li>Registrar o aceite dos termos de uso e política de privacidade conforme exigido pela LGPD.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">4. Compartilhamento de Dados</h2>
          <p class="text-gray-600 leading-relaxed">
            Seus dados pessoais não são vendidos a terceiros. Podemos compartilhar dados apenas
            quando necessário para a prestação dos serviços, como com profissionais de saúde
            vinculados à sua clínica, ou por obrigação legal.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">5. Segurança dos Dados</h2>
          <p class="text-gray-600 leading-relaxed">
            Implementamos medidas técnicas e organizacionais rigorosas para proteger seus dados,
            incluindo criptografia AES-256 para dados sensíveis (como CPF), hash Argon2id para
            senhas, autenticação JWT com tokens HttpOnly, e comunicação via HTTPS.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">6. Seus Direitos (LGPD)</h2>
          <p class="text-gray-600 leading-relaxed mb-4">Conforme a LGPD, você possui os seguintes direitos:</p>
          <ul class="list-disc list-inside text-gray-600 space-y-2 leading-relaxed">
            <li>Confirmar a existência de tratamento de seus dados pessoais.</li>
            <li>Acessar seus dados pessoais.</li>
            <li>Corrigir dados incompletos, inexatos ou desatualizados.</li>
            <li>Solicitar a anonimização, bloqueio ou eliminação de dados desnecessários.</li>
            <li>Solicitar a portabilidade dos dados a outro fornecedor de serviço.</li>
            <li>Eliminar dados tratados com seu consentimento.</li>
            <li>Revogar o consentimento a qualquer momento.</li>
          </ul>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">7. Armazenamento de Dados</h2>
          <p class="text-gray-600 leading-relaxed">
            Seus dados são armazenados em servidores seguros no Brasil. Mantemos seus dados
            pelo período necessário para cumprir as finalidades descritas nesta política,
            respeitando os prazos legais aplicáveis.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">8. Cookies e Tecnologias de Rastreamento</h2>
          <p class="text-gray-600 leading-relaxed">
            Utilizamos cookies essenciais para o funcionamento da plataforma. Tokens de autenticação
            são armazenados em cookies HttpOnly (inacessíveis via JavaScript) para proteção contra XSS.
            Não utilizamos cookies de rastreamento de terceiros sem seu consentimento explícito.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">9. Registro de Aceite (LGPD)</h2>
          <p class="text-gray-600 leading-relaxed">
            Quando você aceita esta Política de Privacidade e nossos Termos de Uso, registramos
            permanentemente em nosso banco de dados: seu endereço IP, geolocalização aproximada,
            user-agent do navegador e a data/hora do aceite. Este registro é mantido para
            comprovação de conformidade com a LGPD e não é armazenado apenas em cookies ou sessão.
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">10. Contato com o Encarregado (DPO)</h2>
          <p class="text-gray-600 leading-relaxed">
            Para exercer seus direitos ou esclarecer dúvidas sobre esta política, entre em contato
            com nosso Encarregado de Proteção de Dados (DPO) pelo e-mail:
            <a href="mailto:dpo@blinkmed.com" class="text-emerald-600 hover:text-emerald-700 font-medium">dpo@blinkmed.com</a>
          </p>
        </section>

        <section>
          <h2 class="text-xl font-semibold text-gray-900 mb-4">11. Alterações nesta Política</h2>
          <p class="text-gray-600 leading-relaxed">
            Esta política pode ser atualizada periodicamente. Notificaremos os usuários sobre
            mudanças significativas através da plataforma ou por e-mail. O uso continuado dos
            serviços após as alterações constitui aceitação da nova política.
          </p>
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
 * Página de Política de Privacidade - Documento legal sobre tratamento de dados pessoais.
 * Em conformidade com a LGPD (Lei nº 13.709/2018).
 */
import { onMounted } from 'vue'
import { useMeta } from '../composables/useMeta'

onMounted(() => {
  useMeta({
    title: 'Política de Privacidade - Blink',
    description: 'Conheça a Política de Privacidade do Blink. Saiba como protegemos seus dados pessoais em conformidade com a LGPD, incluindo dados de saúde e informações de navegação.',
    keywords: 'política de privacidade, LGPD, dados pessoais, privacidade, proteção de dados, saúde, clínica',
    ogType: 'website',
  })
})
</script>
