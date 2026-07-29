# Blink 👁️

Sistema de gestão de saúde com arquitetura baseada em APIs RESTful, construído com Laravel.

> [English version](#english-version)

## Sobre o Projeto

Blink é um sistema de gestão clínica/hospitalar que oferece:

- Cadastro e gerenciamento de pacientes
- Agendamento de consultas com controle de pagamento e retorno
- Gestão de profissionais de saúde
- Diagnósticos e histórico clínico
- Sistema de mensageria interna
- **Gestão de indisponibilidade de profissionais** (períodos em que não atendem)
- **Módulo Financeiro**: Contas a Pagar e Contas a Receber
- **Empresas Conveniadas e Convênios**: Gestão de empresas parceiras, convênios e vínculo com profissionais
- **Planos de Saúde**: Vinculados a pacientes e agendamentos para cálculo dinâmico de cobrança
- Relatórios consolidados

### Arquitetura

- **Backend**: Laravel (API RESTful)
- **Banco de Dados**: PostgreSQL
- **Frontend**: Vue.js (SPA)
- **Mobile**: Flutter (futuro)

### Padrão de Projeto

MVC expandido com:

- **Controllers**: Pontos de entrada da API
- **Services**: Regras de negócio
- **Repositories**: Consultas e persistência
- **Requests**: Validação de formulários
- **Policies**: Autorização de acesso

### Segurança

- Hash Argon2id (64MB memory, 3 time, 2 threads) para senhas
- CPF/CNPJ criptografados com paridade `_hash`/`_encrypted` (SHA-256 + AES-256)
- Sanitização de entrada com `trim()` e `strip_tags()` via middleware
- Rate Limiting agressivo em rotas de autenticação (5 req/min)
- Validação dupla (front-end + back-end)
- Controle de acesso por níveis (Patient, Admin, Operational)
- Cookies HttpOnly/Secure/SameSite para tokens
- Aceite obrigatório de Termos de Uso e Políticas de Privacidade

### Funcionalidades Implementadas

#### ✅ Registro e Autenticação

- Registro de pacientes com validação de CPF
- Login com geração de token (Sanctum)
- Logout e perfil do usuário
- Aceite de termos de uso
- **Facilitador de Login**: Lista de usuários de teste na tela de login com preenchimento automático ao clicar
- Rate limiting: 5 tentativas/min por IP+email

#### ✅ Gestão de Pacientes (Staff)

- Listagem paginada
- Visualização detalhada (com consultas e diagnósticos)
- Atualização de perfil (incluindo vínculo com plano de saúde)

#### ✅ Sistema de Mensageria (Staff)

- Envio de mensagens internas
- Listagem de mensagens recebidas
- Marcação de leitura
- **Indicador dinâmico de mensagens não lidas** no menu lateral (polling a cada 30s)
- Botão "Preencher Teste" no formulário de nova mensagem

#### ✅ Gestão de Indisponibilidade de Profissionais

- Cadastro de períodos em que o profissional não atenderá
- Validação de sobreposição de períodos
- Listagem de períodos futuros para calendário
- CRUD completo (Staff apenas)
- Botão "Preencher Teste" no formulário

#### ✅ Módulo Financeiro (Contas a Pagar e Receber)

- **Contas a Pagar (`accounts_payable`)**: Gestão de despesas operacionais da clínica
  - CRUD completo (descrição, valor, vencimento, categoria, status)
  - Marcação de pagamento com data e método
  - Dashboard de totais (pendentes e vencidas)
  - Auditoria de transações (`created_by`/`updated_by`)
- **Contas a Receber (`accounts_receivable`)**: Gestão financeira de entradas
  - Vinculação direta com agendamentos
  - Cálculo dinâmico: valor total, cobertura do convênio e porção do paciente
  - Status: pending, paid, overdue, canceled, invoiced
  - Sincronização atômica com `appointments` (marcação de pagamento propaga)
  - Dashboard de totais (pendentes e recebidos)
- **Todas as operações financeiras são executadas em `DB::transaction()`**

#### ✅ Empresas Conveniadas e Convênios

- **Empresas (`companies`)**: Cadastro de empresas parceiras com CNPJ criptografado (paridade PII)
- **Convênios (`agreements`)**: Regras vinculadas a uma empresa
  - Tipo: private, public, corporate
  - Percentual de cobertura e valor base de consulta
  - Tabela pivô `agreement_professional`: **restrição de quais profissionais atendem por cada convênio** com valor customizado opcional
- **Planos de Saúde (`health_plans`)**: Planos disponíveis vinculados a convênios
- **Pacientes** possuem vínculo obrigatório com `health_plan_id`
- **Agendamentos** consideram `agreement_id` e `health_plan_id` para cálculo financeiro dinâmico

#### ✅ Auxiliares de Desenvolvimento (Front-end)

- **`formHelpers.ts`**: Arquivo TypeScript com funções helper para preenchimento automático de formulários
- Botão **"Preencher Teste"** em todas as páginas de cadastro (Register, Messages, Unavailability)
- Botão **"Limpar"** no formulário de cadastro de paciente
- Geração de dados realistas: CPF, nomes, endereços, datas, mensagens

### Rotas da API

#### Públicas

| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/api/register` | Registro de paciente (rate limited: 5/min) |
| POST | `/api/login` | Login (rate limited: 5/min) |

#### Autenticadas

| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/api/logout` | Logout |
| GET | `/api/me` | Perfil do usuário |
| POST | `/api/accept-terms` | Aceitar termos |

#### Staff (Admin + Operational)

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api/staff/messages` | Listar mensagens |
| POST | `/api/staff/messages` | Enviar mensagem |
| GET | `/api/staff/messages/unread-count` | Contagem de não lidas |
| PATCH | `/api/staff/messages/{message}/read` | Marcar como lida |
| GET | `/api/staff/patients` | Listar pacientes |
| GET | `/api/staff/patients/{patient}` | Ver paciente |
| PUT | `/api/staff/patients/{patient}` | Atualizar paciente |
| GET | `/api/staff/professionals/{professional}/unavailability` | Listar indisponibilidades |
| GET | `/api/staff/professionals/{professional}/unavailability/future` | Listar futuras |
| POST | `/api/staff/professionals/{professional}/unavailability` | Criar indisponibilidade |
| PUT | `/api/staff/professionals/{professional}/unavailability/{period}` | Atualizar |
| DELETE | `/api/staff/professionals/{professional}/unavailability/{period}` | Remover |

#### Financeiro (Staff)

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api/staff/accounts-payable` | Listar contas a pagar (filtros: status, category, due_date_from/to) |
| POST | `/api/staff/accounts-payable` | Criar conta a pagar |
| GET | `/api/staff/accounts-payable/totals` | Totais (pending, overdue) |
| PUT | `/api/staff/accounts-payable/{account}` | Atualizar conta a pagar |
| POST | `/api/staff/accounts-payable/{account}/pay` | Marcar como paga |
| DELETE | `/api/staff/accounts-payable/{account}` | Remover (SoftDelete) |
| GET | `/api/staff/accounts-receivable` | Listar contas a receber (filtros: status, patient_id, due_date_from/to) |
| POST | `/api/staff/accounts-receivable` | Criar conta a receber (vinculada a appointment) |
| GET | `/api/staff/accounts-receivable/totals` | Totais (pending, received) |
| PUT | `/api/staff/accounts-receivable/{account}` | Atualizar conta a receber |
| POST | `/api/staff/accounts-receivable/{account}/pay` | Marcar como paga (sincroniza appointment) |
| DELETE | `/api/staff/accounts-receivable/{account}` | Remover (SoftDelete) |

### Estrutura do Banco de Dados

| Tabela | Descrição |
|--------|-----------|
| `users` | Autenticação (email puro para login) |
| `patients` | Pacientes (CPF criptografado, health_plan_id) |
| `professionals` | Profissionais de saúde |
| `locations` | Locais de atendimento |
| `location_professional` | Pivô: profissionais por local |
| `appointments` | Agendamentos (agreement_id, health_plan_id) |
| `diagnostics` | Diagnósticos |
| `messages` | Mensagens internas |
| `reports` | Relatórios |
| `unavailability_periods` | Indisponibilidade de profissionais |
| `term_acceptances` | Aceite de termos |
| `companies` | Empresas conveniadas (CNPJ criptografado) |
| `agreements` | Convênios |
| `agreement_professional` | Pivô: profissionais autorizados por convênio |
| `health_plans` | Planos de saúde |
| `accounts_payable` | Contas a pagar |
| `accounts_receivable` | Contas a receber |

### Pré-requisitos

- PHP 8.3+
- PostgreSQL
- Composer
- Node.js + NPM

### Instalação

```bash
# Clone o repositório
git clone https://github.com/letsmg/blink.git
cd blink

# Instale as dependências
composer install
npm install --ignore-scripts

# Configure o ambiente
cp .env.example .env
php artisan key:generate

# Configure o banco PostgreSQL no .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=blink_db
# DB_USERNAME=seu_usuario
# DB_PASSWORD=sua_senha

# Execute as migrations e seeders
php artisan migrate:fresh --seed

# Compile os assets front-end
npm run build

# Inicie o servidor de desenvolvimento
composer run dev
```

### Testes

```bash
# Executar todos os testes
php artisan test

# Executar testes específicos
php artisan test --filter=AccountPayableTest
php artisan test --filter=PatientRegistrationTest
php artisan test --filter=UnavailabilityPeriodTest
```

**56 testes • 119 asserções** — todos passando ✅

Cobertura atual de testes:
- Registro de pacientes (CPF, email, senha, sanitização)
- Indisponibilidade de profissionais (CRUD, sobreposição, permissões)
- Contas a pagar (CRUD, pagamento, totais, permissões)
- Sanitização de entrada (trim, strip_tags)
- Validação estrutural de CPF

### Licença

Este projeto está licenciado sob CC BY-NC-SA 4.0.

Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.

---

## English Version

# Blink 👁️

Healthcare management system with RESTful API architecture, built with Laravel.

### About

Blink is a clinical/hospital management system featuring patient registration, appointment scheduling, professional management, diagnostics, internal messaging, **financial module (accounts payable/receivable)**, **partner companies & agreements**, **health insurance plans**, and consolidated reports.

### Architecture

- **Backend**: Laravel (RESTful API)
- **Database**: PostgreSQL
- **Frontend**: Vue.js (SPA)
- **Mobile**: Flutter (future)

### Security Highlights

- Argon2id password hashing (64MB, 3 iterations, 2 threads)
- PII data parity: `_hash` (SHA-256) + `_encrypted` (AES-256) for CPF, CNPJ, phones, addresses
- `strip_tags()` + `trim()` input sanitization
- Aggressive rate limiting on auth routes (5 requests/min)
- JWT tokens stored in HttpOnly/Secure/SameSite cookies
- Role-based access control (Patient, Admin, Operational)
- Mandatory Terms of Use acceptance modal

### Financial Module

- **Accounts Payable**: Operational expense management with due dates, categories, payment tracking, and transaction auditing
- **Accounts Receivable**: Revenue management linked to appointments, with dynamic calculation considering insurance coverage and patient portion
- All financial operations run within `DB::transaction()` for data integrity

### Partner Companies & Agreements

- **Companies**: Partner registration with encrypted CNPJ
- **Agreements**: Agreement rules per company with coverage percentage and consultation fees
- **Professional restriction**: Each agreement grants access to specific professionals via `agreement_professional` pivot table
- **Health Plans**: Available plans linked to agreements, assigned to patients and considered during appointment scheduling for billing calculation

### Tests

```bash
php artisan test
```

**56 tests • 119 assertions** — all passing ✅

### License

Licensed under CC BY-NC-SA 4.0.

Copyright (c) 2026 Luiz Eduardo T. Silva. All rights reserved.