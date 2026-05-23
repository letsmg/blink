<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-emerald-900">Agenda</h1>
        <p class="text-emerald-600 mt-1">Gerenciar agendamentos de atendimentos.</p>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors text-sm shadow-sm"
      >
        Novo Agendamento
      </button>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-emerald-200 p-4 mb-6 shadow-sm">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
          <label class="block text-sm font-medium text-emerald-800 mb-1">Profissional</label>
          <select
            v-model="filterProfessional"
            @change="fetchAppointments"
            class="w-full px-3 py-2 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-emerald-50/30 text-sm"
          >
            <option value="">Todos os profissionais</option>
            <option v-for="prof in professionals" :key="prof.id" :value="prof.id">
              {{ prof.full_name }} ({{ prof.specialty }})
            </option>
          </select>
        </div>
        <div class="min-w-[180px]">
          <label class="block text-sm font-medium text-emerald-800 mb-1">Mês</label>
          <select
            v-model="currentMonth"
            @change="fetchAppointments"
            class="w-full px-3 py-2 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-emerald-50/30 text-sm"
          >
            <option v-for="(m, i) in months" :key="i" :value="i">
              {{ m }}
            </option>
          </select>
        </div>
        <div class="min-w-[150px]">
          <label class="block text-sm font-medium text-emerald-800 mb-1">Ano</label>
          <select
            v-model="currentYear"
            @change="fetchAppointments"
            class="w-full px-3 py-2 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-emerald-50/30 text-sm"
          >
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Calendário -->
    <div class="bg-white rounded-xl border border-emerald-200 overflow-hidden shadow-sm mb-6">
      <!-- Cabeçalho do mês -->
      <div class="flex items-center justify-between px-6 py-4 bg-emerald-50 border-b border-emerald-200">
        <button @click="prevMonth" class="p-2 hover:bg-emerald-100 rounded-lg transition-colors">
          <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <h2 class="text-lg font-semibold text-emerald-900">{{ months[currentMonth] }} {{ currentYear }}</h2>
        <button @click="nextMonth" class="p-2 hover:bg-emerald-100 rounded-lg transition-colors">
          <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <!-- Dias da semana -->
      <div class="grid grid-cols-7 border-b border-emerald-100">
        <div v-for="day in dayNames" :key="day" class="px-3 py-2 text-center text-xs font-semibold text-emerald-600 uppercase tracking-wider bg-emerald-50/50">
          {{ day }}
        </div>
      </div>

      <!-- Grade de dias -->
      <div class="grid grid-cols-7">
        <div
          v-for="(day, idx) in calendarDays"
          :key="idx"
          class="min-h-[100px] border-b border-r border-emerald-100 p-1.5 transition-colors relative"
          :class="{
            'bg-emerald-50/30': day.isCurrentMonth,
            'bg-gray-50': !day.isCurrentMonth,
            'cursor-pointer hover:bg-emerald-50': day.isCurrentMonth,
          }"
          @click="day.isCurrentMonth && selectDate(day)"
        >
          <div class="flex items-center justify-between mb-1">
            <span
              class="text-xs font-medium w-6 h-6 flex items-center justify-center rounded-full"
              :class="{
                'text-gray-400': !day.isCurrentMonth,
                'text-emerald-900': day.isCurrentMonth && !day.isToday,
                'bg-emerald-600 text-white': day.isToday,
                'bg-red-100 text-red-700': day.isUnavailable && day.isCurrentMonth,
              }"
            >
              {{ day.day }}
            </span>
          </div>

          <!-- Indicador de indisponibilidade -->
          <div v-if="day.isUnavailable && day.isCurrentMonth" class="absolute inset-0 bg-red-50/40 pointer-events-none rounded"></div>

          <!-- Nomes dos profissionais indisponíveis (tooltip) -->
          <div v-if="day.isUnavailable && day.isCurrentMonth && day.unavailableProfessionals.length > 0" class="mt-1 space-y-0.5">
            <div
              v-for="prof in day.unavailableProfessionals"
              :key="prof.id"
              class="text-[9px] leading-tight px-1 py-0.5 rounded bg-red-100 text-red-700 truncate"
              :title="`${prof.name} - Indisponível`"
            >
              {{ getShortName(prof.name) }}
            </div>
          </div>

          <!-- Appointments do dia -->
          <div class="space-y-0.5">
            <div
              v-for="apt in day.appointments"
              :key="apt.id"
              class="text-[10px] leading-tight px-1 py-0.5 rounded truncate cursor-pointer hover:opacity-80"
              :class="apt.is_paid ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
              @click.stop="viewAppointment(apt)"
              :title="`${apt.patient?.full_name || 'Paciente'} - ${formatTime(apt.appointment_date)}`"
            >
              {{ formatTime(apt.appointment_date) }} {{ apt.patient?.full_name?.split(' ')[0] || 'N/A' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista de agendamentos do dia selecionado -->
    <div v-if="selectedDate" class="bg-white rounded-xl border border-emerald-200 overflow-hidden shadow-sm">
      <div class="px-6 py-4 bg-emerald-50 border-b border-emerald-200 flex items-center justify-between">
        <h3 class="font-semibold text-emerald-900">
          Agendamentos de {{ formatDateFull(selectedDate) }}
          <span v-if="selectedProfessionalName" class="text-emerald-500 text-sm font-normal ml-2">
            — {{ selectedProfessionalName }}
          </span>
        </h3>
        <span class="text-xs text-emerald-500 bg-emerald-100 px-2 py-1 rounded-full">
          {{ selectedDayAppointments.length }} agendamento(s)
        </span>
      </div>
      <div v-if="selectedDayAppointments.length === 0" class="p-6 text-center text-emerald-500 text-sm">
        Nenhum agendamento para esta data.
      </div>
      <div v-for="apt in selectedDayAppointments" :key="apt.id" class="p-4 border-b border-emerald-100 last:border-b-0 hover:bg-emerald-50/50 transition-colors">
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1 min-w-0">
            <!-- Paciente -->
            <div class="flex items-center gap-2 mb-1">
              <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <p class="font-medium text-emerald-900 truncate">{{ apt.patient?.full_name || 'Paciente' }}</p>
            </div>
            <!-- Profissional -->
            <div class="flex items-center gap-2 mb-1">
              <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <p class="text-sm text-emerald-700 truncate">
                <span class="font-medium">{{ apt.professional?.full_name }}</span>
                <span v-if="apt.professional?.specialty" class="text-emerald-500"> ({{ apt.professional.specialty }})</span>
              </p>
            </div>
            <!-- Data e Hora -->
            <div class="flex items-center gap-2 mb-1">
              <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="text-xs text-emerald-600">{{ formatDate(apt.date) }} às {{ apt.time }}</p>
            </div>
            <!-- Local -->
            <div class="flex items-center gap-2 mb-1">
              <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <p class="text-xs text-emerald-600">{{ apt.location?.name || 'Local não informado' }}</p>
            </div>
            <!-- Observações -->
            <p v-if="apt.notes" class="text-sm text-emerald-700 mt-2 ml-6 italic border-l-2 border-emerald-200 pl-3">{{ apt.notes }}</p>
            <!-- Tags -->
            <div class="flex flex-wrap gap-2 mt-2 ml-6">
              <span class="text-xs px-2.5 py-1 rounded-full font-medium" :class="apt.is_paid ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                {{ apt.is_paid ? 'Pago' : 'A pagar' }}
              </span>
              <span v-if="apt.is_return" class="text-xs px-2.5 py-1 rounded-full font-medium bg-blue-100 text-blue-700">
                Retorno
              </span>
              <span v-if="!apt.is_return" class="text-xs px-2.5 py-1 rounded-full font-medium bg-purple-100 text-purple-700">
                Consulta inicial
              </span>
              <span v-if="apt.payment_method" class="text-xs px-2.5 py-1 rounded-full font-medium bg-gray-100 text-gray-700">
                {{ paymentMethodLabel(apt.payment_method) }}
              </span>
            </div>
          </div>
          <div class="flex gap-2 shrink-0">
            <button @click="editAppointment(apt)" class="px-3 py-1.5 text-xs text-emerald-600 hover:bg-emerald-100 rounded-lg transition-colors border border-emerald-200">
              Editar
            </button>
            <button @click="deleteAppointment(apt)" class="px-3 py-1.5 text-xs text-red-600 hover:bg-red-100 rounded-lg transition-colors border border-red-200">
              Remover
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Criação/Edição -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="closeModal">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-emerald-200">
          <h3 class="text-lg font-semibold text-emerald-900">{{ editingAppointment ? 'Editar Agendamento' : 'Novo Agendamento' }}</h3>
        </div>

        <form @submit.prevent="handleSave" class="p-6 space-y-4">
          <!-- Busca de Paciente -->
          <div>
            <label class="block text-sm font-medium text-emerald-800 mb-1">Paciente *</label>
            <div class="relative">
              <input
                v-model="patientSearch"
                type="text"
                placeholder="Digite o nome do paciente..."
                required
                @input="searchPatients"
                @focus="searchPatients"
                autocomplete="off"
                class="w-full px-4 py-2.5 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-emerald-50/30"
              />
              <ul
                v-if="patientResults.length > 0 && !selectedPatient"
                class="absolute z-10 w-full mt-1 bg-white border border-emerald-200 rounded-lg shadow-lg max-h-40 overflow-y-auto"
              >
                <li
                  v-for="p in patientResults"
                  :key="p.id"
                  @click="selectPatient(p)"
                  class="px-4 py-2 text-sm text-emerald-900 hover:bg-emerald-50 cursor-pointer"
                >
                  {{ p.full_name }}
                </li>
              </ul>
            </div>
            <p v-if="selectedPatient" class="text-sm text-emerald-600 mt-1">
              Selecionado: <strong>{{ selectedPatient.full_name }}</strong>
              <button type="button" @click="clearPatient" class="ml-2 text-red-500 hover:text-red-700 text-xs">Limpar</button>
            </p>
          </div>

          <!-- Busca de Profissional -->
          <div>
            <label class="block text-sm font-medium text-emerald-800 mb-1">Profissional *</label>
            <div class="relative">
              <input
                v-model="professionalSearch"
                type="text"
                placeholder="Digite o nome do profissional..."
                required
                @input="searchProfessionals"
                @focus="searchProfessionals"
                autocomplete="off"
                class="w-full px-4 py-2.5 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-emerald-50/30"
              />
              <ul
                v-if="professionalResults.length > 0 && !selectedProfessional"
                class="absolute z-10 w-full mt-1 bg-white border border-emerald-200 rounded-lg shadow-lg max-h-40 overflow-y-auto"
              >
                <li
                  v-for="p in professionalResults"
                  :key="p.id"
                  @click="selectProfessional(p)"
                  class="px-4 py-2 text-sm text-emerald-900 hover:bg-emerald-50 cursor-pointer"
                >
                  {{ p.full_name }} <span class="text-emerald-500">({{ p.specialty }})</span>
                </li>
              </ul>
            </div>
            <p v-if="selectedProfessional" class="text-sm text-emerald-600 mt-1">
              Selecionado: <strong>{{ selectedProfessional.full_name }}</strong>
              <button type="button" @click="clearProfessional" class="ml-2 text-red-500 hover:text-red-700 text-xs">Limpar</button>
            </p>
          </div>

          <!-- Local (dinâmico conforme profissional) -->
          <div>
            <label class="block text-sm font-medium text-emerald-800 mb-1">Local de Atendimento *</label>
            <select
              v-model="selectedLocation"
              required
              class="w-full px-4 py-2.5 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-emerald-50/30"
            >
              <option value="">Selecione o profissional primeiro</option>
              <option v-for="loc in locations" :key="loc.id" :value="loc.id">
                {{ loc.name }} — {{ loc.city }}
              </option>
            </select>
            <p v-if="!selectedProfessional" class="text-xs text-amber-600 mt-1">Escolha um profissional para ver os locais disponíveis.</p>
          </div>

          <!-- Data e Hora -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-emerald-800 mb-1">Data *</label>
              <input
                v-model="formDate"
                type="date"
                required
                :min="today"
                class="w-full px-4 py-2.5 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-emerald-50/30"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-emerald-800 mb-1">Horário *</label>
              <input
                v-model="formTime"
                type="time"
                required
                class="w-full px-4 py-2.5 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-emerald-50/30"
              />
            </div>
          </div>

          <!-- Observações -->
          <div>
            <label class="block text-sm font-medium text-emerald-800 mb-1">Observações</label>
            <textarea
              v-model="formNotes"
              rows="3"
              class="w-full px-4 py-2.5 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors resize-none bg-emerald-50/30"
            ></textarea>
          </div>

          <!-- É retorno? -->
          <div class="flex items-center gap-2">
            <input
              v-model="formIsReturn"
              type="checkbox"
              id="is_return"
              class="w-4 h-4 text-emerald-600 border-emerald-300 rounded focus:ring-emerald-500"
            />
            <label for="is_return" class="text-sm text-emerald-800">É retorno</label>
          </div>

          <!-- Status de pagamento (só na edição) -->
          <div v-if="editingAppointment" class="space-y-3 pt-2 border-t border-emerald-100">
            <h4 class="text-sm font-medium text-emerald-800">Status do Pagamento</h4>
            <div class="flex items-center gap-2">
              <input
                v-model="formIsPaid"
                type="checkbox"
                id="is_paid"
                class="w-4 h-4 text-emerald-600 border-emerald-300 rounded focus:ring-emerald-500"
              />
              <label for="is_paid" class="text-sm text-emerald-800">Pago</label>
            </div>
            <div v-if="formIsPaid">
              <label class="block text-sm font-medium text-emerald-800 mb-1">Método de Pagamento</label>
              <select
                v-model="formPaymentMethod"
                class="w-full px-4 py-2.5 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-colors bg-emerald-50/30"
              >
                <option value="">Selecione</option>
                <option value="dinheiro">Dinheiro</option>
                <option value="cartao_credito">Cartão de Crédito</option>
                <option value="cartao_debito">Cartão de Débito</option>
                <option value="pix">PIX</option>
                <option value="convenio">Convênio</option>
              </select>
            </div>
          </div>

          <div v-if="formError" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
            {{ formError }}
          </div>

          <div class="flex gap-3 pt-2">
            <button
              type="button"
              @click="fillTestData"
              class="px-4 py-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 font-medium rounded-lg transition-colors text-sm"
            >
              Preencher Teste
            </button>
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors text-sm"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="formLoading"
              class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors text-sm disabled:opacity-50 shadow-sm"
            >
              {{ formLoading ? 'Salvando...' : 'Salvar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de Visualização -->
    <div v-if="showViewModal && viewingAppointment" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showViewModal = false">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4">
        <div class="px-6 py-4 border-b border-emerald-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-emerald-900">Detalhes do Agendamento</h3>
          <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6 space-y-3">
          <div>
            <span class="text-xs text-emerald-500 uppercase tracking-wider">Paciente</span>
            <p class="text-emerald-900 font-medium">{{ viewingAppointment.patient?.full_name }}</p>
          </div>
          <div>
            <span class="text-xs text-emerald-500 uppercase tracking-wider">Profissional</span>
            <p class="text-emerald-900">{{ viewingAppointment.professional?.full_name }} ({{ viewingAppointment.professional?.specialty }})</p>
          </div>
          <div>
            <span class="text-xs text-emerald-500 uppercase tracking-wider">Data e Horário</span>
            <p class="text-emerald-900">{{ formatDate(viewingAppointment.date) }} às {{ viewingAppointment.time }}</p>
          </div>
          <div>
            <span class="text-xs text-emerald-500 uppercase tracking-wider">Local</span>
            <p class="text-emerald-900">{{ viewingAppointment.location?.name || 'Não informado' }}</p>
          </div>
          <div v-if="viewingAppointment.notes">
            <span class="text-xs text-emerald-500 uppercase tracking-wider">Observações</span>
            <p class="text-emerald-700 text-sm">{{ viewingAppointment.notes }}</p>
          </div>
          <div class="flex gap-2 pt-2">
            <span class="text-xs px-3 py-1 rounded-full" :class="viewingAppointment.is_paid ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
              {{ viewingAppointment.is_paid ? 'Pago' : 'A pagar' }}
            </span>
            <span v-if="viewingAppointment.is_return" class="text-xs px-3 py-1 rounded-full bg-blue-100 text-blue-700">
              Retorno
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'

// ========== Estados ==========
const appointments = ref<any[]>([])
const professionals = ref<any[]>([])
const loading = ref(false)
const error = ref('')

// Filtros
const filterProfessional = ref('')
const currentMonth = ref(new Date().getMonth())
const currentYear = ref(new Date().getFullYear())

// Modal de criação/edição
const showModal = ref(false)
const editingAppointment = ref<any>(null)
const formLoading = ref(false)
const formError = ref('')
const patientSearch = ref('')
const professionalSearch = ref('')
const patientResults = ref<any[]>([])
const professionalResults = ref<any[]>([])
const selectedPatient = ref<any>(null)
const selectedProfessional = ref<any>(null)
const selectedLocation = ref('')
const locations = ref<any[]>([])
const formDate = ref('')
const formTime = ref('')
const formNotes = ref('')
const formIsReturn = ref(false)
const formIsPaid = ref(false)
const formPaymentMethod = ref('')

// Modal de visualização
const showViewModal = ref(false)
const viewingAppointment = ref<any>(null)

// Datas indisponíveis
const unavailableDates = ref<string[]>([])
// Indisponibilidades com detalhes do profissional (quando sem filtro)
const allUnavailability = ref<any[]>([])

// Dia selecionado
const selectedDate = ref<string | null>(null)

// ========== Constantes ==========
const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro']
const dayNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']

const today = computed(() => new Date().toISOString().split('T')[0])

const years = computed(() => {
  const y = new Date().getFullYear()
  return Array.from({ length: 5 }, (_, i) => y + i - 1)
})

// ========== Computed ==========
const calendarDays = computed(() => {
  const firstDay = new Date(currentYear.value, currentMonth.value, 1)
  const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0)
  const startPad = firstDay.getDay()
  const totalDays = lastDay.getDate()
  const todayStr = new Date().toISOString().split('T')[0]

  const days: any[] = []

  // Dias do mês anterior
  const prevLastDay = new Date(currentYear.value, currentMonth.value, 0).getDate()
  for (let i = startPad - 1; i >= 0; i--) {
    const d = prevLastDay - i
    const dateStr = `${currentYear.value}-${String(currentMonth.value).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    days.push({
      day: d,
      date: dateStr,
      isCurrentMonth: false,
      isToday: false,
      isUnavailable: false,
      appointments: [],
    })
  }

  // Dias do mês atual
  for (let d = 1; d <= totalDays; d++) {
    const dateStr = `${currentYear.value}-${String(currentMonth.value + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    // Profissionais indisponíveis nesta data (quando sem filtro)
    const profsForDate = allUnavailability.value
      .filter((u: any) => u.date === dateStr)
      .map((u: any) => ({ id: u.professional_id, name: u.professional_name }))
    // Remove duplicatas (mesmo profissional pode ter períodos sobrepostos)
    const uniqueProfs = profsForDate.filter((p: any, i: number, arr: any[]) =>
      arr.findIndex((x: any) => x.id === p.id) === i
    )
    days.push({
      day: d,
      date: dateStr,
      isCurrentMonth: true,
      isToday: dateStr === todayStr,
      isUnavailable: unavailableDates.value.includes(dateStr),
      unavailableProfessionals: uniqueProfs,
      appointments: appointments.value.filter((a: any) => {
        const aptDate = a.appointment_date.split(' ')[0] || a.appointment_date.substring(0, 10)
        return aptDate === dateStr
      }),
    })
  }

  // Preencher resto da última semana
  const remaining = 7 - (days.length % 7)
  if (remaining < 7) {
    const nextMonth = currentMonth.value === 11 ? 0 : currentMonth.value + 1
    const nextYear = currentMonth.value === 11 ? currentYear.value + 1 : currentYear.value
    for (let d = 1; d <= remaining; d++) {
      const dateStr = `${nextYear}-${String(nextMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`
      days.push({
        day: d,
        date: dateStr,
        isCurrentMonth: false,
        isToday: false,
        isUnavailable: false,
        appointments: [],
      })
    }
  }

  return days
})

const selectedDayAppointments = computed(() => {
  if (!selectedDate.value) return []
  return appointments.value.filter((a: any) => {
    const aptDate = a.appointment_date.split(' ')[0] || a.appointment_date.substring(0, 10)
    return aptDate === selectedDate.value
  })
})

const selectedProfessionalName = computed(() => {
  if (!filterProfessional.value) return ''
  const prof = professionals.value.find((p: any) => p.id === Number(filterProfessional.value))
  return prof ? `${prof.full_name} (${prof.specialty})` : ''
})

// ========== Métodos ==========
function formatDate(date: string) {
  return new Date(date).toLocaleDateString('pt-BR')
}

function formatDateFull(date: string) {
  return new Date(date).toLocaleDateString('pt-BR', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}

function formatTime(date: string) {
  return new Date(date).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
}

/**
 * Extrai o primeiro nome real, ignorando prefixos como "Dr(a).", "Dr.", "Dra."
 */
function getShortName(fullName: string): string {
  const name = fullName.trim()
  // Remove prefixos comuns como "Dr(a).", "Dr.", "Dra." no início
  const cleaned = name.replace(/^(Dr\(a\)\.?\s*|Dr\.?\s*|Dra\.?\s*)/i, '').trim()
  // Pega a primeira palavra do nome limpo
  const firstName = cleaned.split(' ')[0]
  return firstName || name.split(' ')[0]
}

function paymentMethodLabel(method: string): string {

  const labels: Record<string, string> = {
    dinheiro: 'Dinheiro',
    cartao_credito: 'Cartão de Crédito',
    cartao_debito: 'Cartão de Débito',
    pix: 'PIX',
    convenio: 'Convênio',
    credit_card: 'Cartão de Crédito',
    debit_card: 'Cartão de Débito',
    cash: 'Dinheiro',
    health_insurance: 'Convênio',
  }
  return labels[method] || method
}

function prevMonth() {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
  fetchAppointments()
}

function nextMonth() {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
  fetchAppointments()
}

function selectDate(day: any) {
  selectedDate.value = day.date
}

async function fetchAppointments() {
  loading.value = true
  try {
    const params: any = {}
    if (filterProfessional.value) {
      params.professional_id = filterProfessional.value
    }
    const { data } = await axios.get('/staff/appointments', { params })
    appointments.value = data.data?.data || data.data || []
  } catch {
    error.value = 'Erro ao carregar agendamentos.'
  } finally {
    loading.value = false
  }
}

async function fetchProfessionals() {
  try {
    const { data } = await axios.get('/staff/professionals')
    professionals.value = data.data || []
  } catch {
    // silent
  }
}

async function fetchUnavailableDates() {
  if (!filterProfessional.value) {
    // Sem filtro: busca indisponibilidades de TODOS os profissionais
    try {
      const { data } = await axios.get('/staff/appointments/all-unavailable-dates')
      allUnavailability.value = data.data || []
      unavailableDates.value = [...new Set(allUnavailability.value.map((u: any) => u.date))]
    } catch {
      unavailableDates.value = []
      allUnavailability.value = []
    }
    return
  }
  try {
    const { data } = await axios.get(`/staff/appointments/professionals/${filterProfessional.value}/unavailable-dates`)
    unavailableDates.value = data.data || []
    allUnavailability.value = []
  } catch {
    unavailableDates.value = []
    allUnavailability.value = []
  }
}

// Busca de pacientes (autocomplete)
let patientSearchTimeout: ReturnType<typeof setTimeout> | null = null
function searchPatients() {
  if (patientSearchTimeout) clearTimeout(patientSearchTimeout)
  if (!patientSearch.value || patientSearch.value.length < 2) {
    patientResults.value = []
    return
  }
  patientSearchTimeout = setTimeout(async () => {
    try {
      const { data } = await axios.get('/staff/appointments/search/patients', { params: { q: patientSearch.value } })
      patientResults.value = data.data || []
    } catch {
      patientResults.value = []
    }
  }, 300)
}

function selectPatient(p: any) {
  selectedPatient.value = p
  patientSearch.value = p.full_name
  patientResults.value = []
}

function clearPatient() {
  selectedPatient.value = null
  patientSearch.value = ''
  patientResults.value = []
}

// Busca de profissionais (autocomplete)
let professionalSearchTimeout: ReturnType<typeof setTimeout> | null = null
function searchProfessionals() {
  if (professionalSearchTimeout) clearTimeout(professionalSearchTimeout)
  if (!professionalSearch.value || professionalSearch.value.length < 2) {
    professionalResults.value = []
    return
  }
  professionalSearchTimeout = setTimeout(async () => {
    try {
      const { data } = await axios.get('/staff/appointments/search/professionals', { params: { q: professionalSearch.value } })
      professionalResults.value = data.data || []
    } catch {
      professionalResults.value = []
    }
  }, 300)
}

async function fetchLocations() {
  if (!selectedProfessional.value) {
    locations.value = []
    selectedLocation.value = ''
    return
  }
  try {
    const { data } = await axios.get(`/staff/professionals/${selectedProfessional.value.id}/locations`)
    locations.value = data.data || []
    // Auto-select first location if only one available
    if (locations.value.length === 1) {
      selectedLocation.value = locations.value[0].id
    }
  } catch {
    locations.value = []
  }
}

function selectProfessional(p: any) {
  selectedProfessional.value = p
  professionalSearch.value = p.full_name
  professionalResults.value = []
  selectedLocation.value = ''
  fetchLocations()
}

function clearProfessional() {
  selectedProfessional.value = null
  professionalSearch.value = ''
  professionalResults.value = []
  locations.value = []
  selectedLocation.value = ''
}

function openCreateModal() {
  editingAppointment.value = null
  selectedPatient.value = null
  selectedProfessional.value = null
  patientSearch.value = ''
  professionalSearch.value = ''
  formDate.value = ''
  formTime.value = ''
  formNotes.value = ''
  formIsReturn.value = false
  formIsPaid.value = false
  formPaymentMethod.value = ''
  formError.value = ''
  showModal.value = true
}

function editAppointment(apt: any) {
  editingAppointment.value = apt
  selectedPatient.value = { id: apt.patient_id, full_name: apt.patient?.full_name }
  selectedProfessional.value = { id: apt.professional_id, full_name: apt.professional?.full_name, specialty: apt.professional?.specialty }
  patientSearch.value = apt.patient?.full_name || ''
  professionalSearch.value = apt.professional?.full_name || ''
  selectedLocation.value = apt.location_id || ''
  formDate.value = apt.date || ''
  formTime.value = apt.time || ''
  formNotes.value = apt.notes || ''
  formIsReturn.value = apt.is_return || false
  formIsPaid.value = apt.is_paid || false
  formPaymentMethod.value = apt.payment_method || ''
  formError.value = ''
  showModal.value = true
  // Carrega os locais do profissional para edição
  fetchLocations()
}

function viewAppointment(apt: any) {
  viewingAppointment.value = apt
  showViewModal.value = true
}

function closeModal() {
  showModal.value = false
  editingAppointment.value = null
}

function fillTestData() {
  const now = new Date()
  now.setDate(now.getDate() + Math.floor(Math.random() * 14) + 1)
  formDate.value = now.toISOString().split('T')[0]
  formTime.value = `${String(8 + Math.floor(Math.random() * 10)).padStart(2, '0')}:${String(Math.floor(Math.random() * 4) * 15).padStart(2, '0')}`
  formNotes.value = 'Paciente com queixa de dor lombar há 3 meses.'
  formIsReturn.value = Math.random() > 0.5
  formError.value = ''
}

async function handleSave() {
  if (!selectedPatient.value || !selectedProfessional.value) {
    formError.value = 'Selecione um paciente e um profissional.'
    return
  }

  if (!selectedLocation.value) {
    formError.value = 'Selecione o local de atendimento.'
    return
  }

  formLoading.value = true
  formError.value = ''

  const payload: any = {
    patient_id: selectedPatient.value.id,
    professional_id: selectedProfessional.value.id,
    location_id: selectedLocation.value,
    date: formDate.value,
    time: formTime.value,
    notes: formNotes.value || null,
    is_return: formIsReturn.value,
  }

  if (editingAppointment.value) {
    payload.is_paid = formIsPaid.value
    payload.payment_method = formIsPaid.value ? formPaymentMethod.value : null
  }

  try {
    if (editingAppointment.value) {
      await axios.put(`/staff/appointments/${editingAppointment.value.id}`, payload)
    } else {
      await axios.post('/staff/appointments', payload)
    }
    closeModal()
    await fetchAppointments()
  } catch (e: any) {
    formError.value = e.response?.data?.message || 'Erro ao salvar agendamento.'
  } finally {
    formLoading.value = false
  }
}

async function deleteAppointment(apt: any) {
  if (!confirm(`Remover agendamento de ${apt.patient?.full_name}?`)) return
  try {
    await axios.delete(`/staff/appointments/${apt.id}`)
    await fetchAppointments()
  } catch {
    // silent
  }
}

// Watch para recarregar datas indisponíveis quando muda o profissional
watch(filterProfessional, () => {
  fetchUnavailableDates()
  fetchAppointments()
})

onMounted(() => {
  fetchProfessionals()
  fetchAppointments()
  fetchUnavailableDates()
})
</script>
