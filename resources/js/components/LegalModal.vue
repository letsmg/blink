<script setup>
/**
 * Modal de Políticas de Privacidade e Termos de Uso
 * 
 * Gerencia o aceite do usuário via localStorage.
 * O botão de ciência só é ativado após rolagem completa (100%) do conteúdo.
 */
import { ref, onMounted, computed } from 'vue';

const emit = defineEmits(['close']);

const isVisible = ref(false);
const hasScrolledToEnd = ref(false);
const hasAccepted = ref(false);
const activeTab = ref('privacidade');
const contentRef = ref(null);

// Texto das políticas armazenado estaticamente (sem requisições HTTP)
const politicas = {
    privacidade: {
        titulo: 'Política de Privacidade',
        conteudo: [
            {
                titulo: '1. Informações que Coletamos',
                texto: 'Ao utilizar nosso site e serviços, podemos coletar informações pessoais fornecidas voluntariamente por você, como nome, endereço de e-mail, número de telefone e dados de navegação (cookies, endereço IP, tipo de navegador, páginas acessadas).'
            },
            {
                titulo: '2. Uso das Informações',
                texto: 'Utilizamos suas informações para: (a) fornecer e melhorar nossos serviços; (b) responder a solicitações e suporte; (c) enviar comunicações relacionadas aos serviços contratados; (d) cumprir obrigações legais e regulatórias; (e) analisar tendências de uso e melhorar a experiência do usuário.'
            },
            {
                titulo: '3. Compartilhamento de Dados',
                texto: 'Não vendemos, trocamos ou transferimos suas informações pessoais a terceiros sem seu consentimento, exceto quando necessário para: (a) prestadores de serviços que nos auxiliam na operação do site; (b) cumprimento de exigências legais, ordens judiciais ou processos legais; (c) proteção de direitos, propriedade ou segurança.'
            },
            {
                titulo: '4. Cookies e Tecnologias de Rastreamento',
                texto: 'Utilizamos cookies e tecnologias similares para melhorar a experiência de navegação, analisar tendências e administrar o site. Você pode controlar o uso de cookies nas configurações do seu navegador. No entanto, a desativação de cookies pode afetar a funcionalidade de alguns recursos do site.'
            },
            {
                titulo: '5. Segurança dos Dados',
                texto: 'Implementamos medidas de segurança técnicas e organizacionais para proteger suas informações pessoais contra acesso não autorizado, alteração, divulgação ou destruição. Utilizamos criptografia SSL/TLS, firewalls e práticas de codificação segura (sanitização de entrada, proteção contra XSS e CSRF).'
            },
            {
                titulo: '6. Seus Direitos (LGPD)',
                texto: 'Conforme a Lei Geral de Proteção de Dados (LGPD - Lei 13.709/2018), você tem direito a: (a) confirmar a existência de tratamento de dados; (b) acessar seus dados pessoais; (c) corrigir dados incompletos, inexatos ou desatualizados; (d) solicitar a anonimização, bloqueio ou eliminação de dados desnecessários; (e) revogar o consentimento a qualquer momento.'
            },
            {
                titulo: '7. Retenção de Dados',
                texto: 'Mantemos seus dados pessoais apenas pelo período necessário para cumprir as finalidades para as quais foram coletados, incluindo para cumprir requisitos legais, contábeis ou de relatórios.'
            },
            {
                titulo: '8. Alterações nesta Política',
                texto: 'Reservamo-nos o direito de atualizar esta Política de Privacidade a qualquer momento. Notificaremos sobre alterações significativas através de aviso em nosso site ou por e-mail. Recomendamos revisar esta política periodicamente.'
            },
            {
                titulo: '9. Contato',
                texto: 'Para exercer seus direitos ou esclarecer dúvidas sobre esta Política de Privacidade, entre em contato conosco através do WhatsApp ou e-mail disponíveis em nosso site.'
            }
        ]
    },
    termos: {
        titulo: 'Termos de Uso',
        conteudo: [
            {
                titulo: '1. Aceitação dos Termos',
                texto: 'Ao acessar e utilizar este site e seus serviços, você declara ter lido, compreendido e aceitado todos os termos e condições descritos neste documento. Se você não concordar com qualquer parte destes termos, não utilize nossos serviços.'
            },
            {
                titulo: '2. Descrição dos Serviços',
                texto: 'Oferecemos serviços de desenvolvimento de software, incluindo: sistemas web sob medida (ERPs, CRMs, plataformas SaaS), integrações e APIs, consultoria em arquitetura de software, e desenvolvimento de aplicações utilizando tecnologias como Laravel, Vue.js e ecossistema PHP.'
            },
            {
                titulo: '3. Propriedade Intelectual',
                texto: 'Todo o conteúdo disponibilizado neste site, incluindo textos, imagens, logotipos, código-fonte e materiais didáticos, é de propriedade exclusiva do desenvolvedor ou de seus licenciadores. É proibida a reprodução, distribuição ou modificação sem autorização prévia por escrito.'
            },
            {
                titulo: '4. Obrigações do Usuário',
                texto: 'O usuário concorda em: (a) fornecer informações precisas e atualizadas quando solicitado; (b) não utilizar os serviços para atividades ilícitas ou não autorizadas; (c) não tentar acessar áreas restritas do sistema sem autorização; (d) não interferir na segurança ou funcionamento dos serviços.'
            },
            {
                titulo: '5. Limitação de Responsabilidade',
                texto: 'Os serviços são fornecidos "como estão", sem garantias expressas ou implícitas. Não nos responsabilizamos por danos diretos, indiretos, incidentais ou consequenciais decorrentes do uso ou da impossibilidade de uso dos serviços, exceto nos casos previstos em lei.'
            },
            {
                titulo: '6. Privacidade e Proteção de Dados',
                texto: 'O tratamento de dados pessoais segue os termos descritos em nossa Política de Privacidade. Ambos os documentos devem ser lidos em conjunto para compreensão completa de nossas práticas.'
            },
            {
                titulo: '7. Cancelamento e Rescisão',
                texto: 'Reservamo-nos o direito de suspender ou encerrar o acesso aos serviços a qualquer momento, por qualquer motivo, incluindo violação destes termos. Em caso de rescisão, você deverá cessar imediatamente o uso dos serviços.'
            },
            {
                titulo: '8. Disposições Gerais',
                texto: 'Estes termos são regidos pelas leis brasileiras. Qualquer disputa será resolvida no foro da comarca de Minas Gerais. Se qualquer disposição destes termos for considerada inválida, as demais permanecerão em pleno vigor.'
            },
            {
                titulo: '9. Atualizações dos Termos',
                texto: 'Estes Termos de Uso podem ser atualizados periodicamente. O uso continuado dos serviços após as alterações constitui aceitação dos novos termos. Recomendamos a revisão periódica deste documento.'
            }
        ]
    }
};

const activeContent = computed(() => {
    return politicas[activeTab.value] || politicas.privacidade;
});

function handleScroll() {
    if (!contentRef.value) return;
    
    const { scrollTop, scrollHeight, clientHeight } = contentRef.value;
    const scrollPercent = (scrollTop + clientHeight) / scrollHeight;
    
    // Ativa o botão quando a rolagem atingir 100%
    if (scrollPercent >= 0.99) {
        hasScrolledToEnd.value = true;
    }
}

function acceptAndClose() {
    if (!hasScrolledToEnd.value) return;
    
    hasAccepted.value = true;
    isVisible.value = false;
    emit('accepted');
    emit('close');
}

function openModal() {
    hasScrolledToEnd.value = false;
    isVisible.value = true;
}

function closeModal() {
    if (hasAccepted.value) {
        isVisible.value = false;
        emit('close');
    }
}

onMounted(() => {
    // A abertura do modal agora é controlada externamente pelo componente pai.
});

// Expõe a função openModal para uso externo
defineExpose({ openModal });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isVisible" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
                <!-- Overlay -->
                <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" @click="closeModal"></div>
                
                <!-- Modal -->
                <div class="relative w-full max-w-2xl max-h-[90vh] bg-white rounded-3xl shadow-2xl flex flex-col overflow-hidden">
                    
                    <!-- Header -->
                    <div class="px-8 pt-8 pb-4 border-b border-slate-100">
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight">
                            {{ activeContent.titulo }}
                        </h2>
                        <p class="text-slate-500 text-sm mt-2">
                            Leia atentamente antes de continuar. Role até o final para aceitar.
                        </p>
                    </div>
                    
                    <!-- Tabs -->
                    <div class="flex border-b border-slate-100 px-8">
                        <button 
                            @click="activeTab = 'privacidade'" 
                            class="py-3 px-1 mr-6 text-xs font-bold uppercase tracking-widest transition border-b-2"
                            :class="activeTab === 'privacidade' ? 'text-emerald-600 border-emerald-600' : 'text-slate-400 border-transparent hover:text-slate-600'">
                            Privacidade
                        </button>
                        <button 
                            @click="activeTab = 'termos'" 
                            class="py-3 px-1 text-xs font-bold uppercase tracking-widest transition border-b-2"
                            :class="activeTab === 'termos' ? 'text-emerald-600 border-emerald-600' : 'text-slate-400 border-transparent hover:text-slate-600'">
                            Termos de Uso
                        </button>
                    </div>
                    
                    <!-- Content (scrollável) -->
                    <div 
                        ref="contentRef"
                        @scroll="handleScroll"
                        class="flex-1 overflow-y-auto px-8 py-6 space-y-6 scroll-smooth"
                        role="document"
                        tabindex="0"
                    >
                        <div 
                            v-for="(item, index) in activeContent.conteudo" 
                            :key="index"
                            class="pb-4 border-b border-slate-50 last:border-b-0"
                        >
                            <h3 class="text-sm font-bold text-slate-900 mb-2">{{ item.titulo }}</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ item.texto }}</p>
                        </div>
                        
                        <!-- Indicador de fim -->
                        <div 
                            v-if="hasScrolledToEnd" 
                            class="flex items-center gap-2 py-3 text-emerald-600 text-xs font-bold uppercase tracking-widest"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Você leu até o final
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-8 py-5 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-4">
                        <p class="text-[10px] text-slate-400 leading-relaxed flex-1">
                            Ao clicar em "Li e Concordo", você declara ter lido e aceitado os termos.
                        </p>
                        <button 
                            @click="acceptAndClose"
                            :disabled="!hasScrolledToEnd"
                            class="px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap"
                            :class="hasScrolledToEnd 
                                ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-lg shadow-emerald-200' 
                                : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                        >
                            Li e Concordo
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
