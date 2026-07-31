# Blink 👁️

Sistema de gestão de saúde com arquitetura baseada em APIs RESTful, construído com Laravel.

> 🇬🇧 [English Version](#english-version) | 🇧🇷 **Versão em Português**

---

## 📑 Índice

### Português
- [Sobre o Projeto](#sobre-o-projeto)
- [Arquitetura](#arquitetura)
- [Funcionalidades](#funcionalidades)
- [Padrão de Projeto](#padrão-de-projeto)
- [Segurança](#segurança)
- [Estrutura de Banco de Dados](#estrutura-de-banco-de-dados)
- [Rotas da API](#rotas-da-api)
- [Infraestrutura e Deploy](#infraestrutura-e-deploy)
- [Pré-requisitos](#pré-requisitos)
- [Instalação](#instalação)
- [Testes](#testes)
- [Licença](#licença)

---

## 🏥 Sobre o Projeto

Blink é um sistema de gestão clínica/hospitalar completo que oferece:

- ✅ Cadastro e gerenciamento de pacientes
- ✅ Agendamento de consultas com controle de pagamento e retorno
- ✅ Gestão de profissionais de saúde
- ✅ Diagnósticos e histórico clínico
- ✅ Sistema de mensageria interna
- ✅ Gestão de indisponibilidade de profissionais
- ✅ Módulo Financeiro (Contas a Pagar e Receber)
- ✅ Empresas Conveniadas e Convênios
- ✅ Planos de Saúde
- ✅ Relatórios consolidados

---

## 🏗️ Arquitetura

| Componente | Tecnologia |
|---|---|
| **Backend** | Laravel (API RESTful) |
| **Banco de Dados** | PostgreSQL |
| **Frontend** | Vue.js (SPA) |
| **Mobile** | Flutter (futuro) |

---

## ✨ Funcionalidades

### ✅ Registro e Autenticação

- Registro de pacientes com validação de CPF
- Login com geração de token (Sanctum)
- Logout e perfil do usuário
- Aceite de termos de uso
- Facilitador de Login: Lista de usuários de teste com preenchimento automático
- Rate limiting: 5 tentativas/min por IP+email

### ✅ Gestão de Pacientes (Staff)

- Listagem paginada
- Visualização detalhada (com consultas e diagnósticos)
- Atualização de perfil (incluindo vínculo com plano de saúde)

### ✅ Sistema de Mensageria (Staff)

- Envio de mensagens internas
- Listagem de mensagens recebidas
- Marcação de leitura
- Indicador dinâmico de mensagens não lidas (polling a cada 30s)
- Botão "Preencher Teste" no formulário

### ✅ Gestão de Indisponibilidade de Profissionais

- Cadastro de períodos em que o profissional não atenderá
- Validação de sobreposição de períodos
- Listagem de períodos futuros para calendário
- CRUD completo (Staff apenas)
- Botão "Preencher Teste" no formulário

### ✅ Módulo Financeiro

#### Contas a Pagar
- Gestão de despesas operacionais da clínica
- CRUD completo (descrição, valor, vencimento, categoria, status)
- Marcação de pagamento com data e método
- Dashboard de totais (pendentes e vencidas)
- Auditoria de transações (created_by/updated_by)

#### Contas a Receber
- Gestão financeira de entradas
- Vinculação direta com agendamentos
- Cálculo dinâmico: valor total, cobertura do convênio e porção do paciente
- Status: pending, paid, overdue, canceled, invoiced
- Sincronização atômica com appointments
- Dashboard de totais (pendentes e recebidos)
- ⚠️ Todas as operações executadas em `DB::transaction()`

### ✅ Empresas Conveniadas e Convênios

**Empresas (companies)**
- Cadastro de empresas parceiras com CNPJ criptografado (paridade PII)

**Convênios (agreements)**
- Regras vinculadas a uma empresa
- Tipo: private, public, corporate
- Percentual de cobertura e valor base de consulta
- Tabela pivô `agreement_professional`: restrição de profissionais por convênio

**Planos de Saúde (health_plans)**
- Planos disponíveis vinculados a convênios
- Vínculo obrigatório do paciente com health_plan_id
- Agendamentos consideram agreement_id e health_plan_id para cálculo financeiro

### ✅ Auxiliares de Desenvolvimento (Front-end)

- `formHelpers.ts`: Arquivo TypeScript com funções helper
- Botão "Preencher Teste" em todas as páginas de cadastro
- Botão "Limpar" no formulário de cadastro de paciente
- Geração de dados realistas: CPF, nomes, endereços, datas, mensagens

---

## 📐 Padrão de Projeto

MVC expandido com:

| Camada | Responsabilidade |
|---|---|
| **Controllers** | Pontos de entrada da API |
| **Services** | Regras de negócio |
| **Repositories** | Consultas e persistência |
| **Requests** | Validação de formulários |
| **Policies** | Autorização de acesso |

---

## 🔐 Segurança

- **Hash Argon2id** (64MB memory, 3 time, 2 threads) para senhas
- **CPF/CNPJ criptografados** com paridade `_hash` (SHA-256) + `_encrypted` (AES-256)
- **Sanitização de entrada** com `trim()` e `strip_tags()` via middleware
- **Rate Limiting** agressivo em rotas de autenticação (5 req/min)
- **Validação dupla** (front-end + back-end)
- **Controle de acesso** por níveis (Patient, Admin, Operational)
- **Cookies HttpOnly/Secure/SameSite** para tokens
- **Aceite obrigatório** de Termos de Uso e Políticas de Privacidade

---

## 🗄️ Estrutura de Banco de Dados

| Tabela | Descrição |
|---|---|
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

---

## 🔌 Rotas da API

### 🌐 Rotas Públicas

| Método | Rota | Descrição |
|---|---|---|
| `POST` | `/api/register` | Registro de paciente (rate limited: 5/min) |
| `POST` | `/api/login` | Login (rate limited: 5/min) |

### 🔒 Rotas Autenticadas

| Método | Rota | Descrição |
|---|---|---|
| `POST` | `/api/logout` | Logout |
| `GET` | `/api/me` | Perfil do usuário |
| `POST` | `/api/accept-terms` | Aceitar termos |

### 👥 Rotas Staff (Admin + Operational)

#### Mensageria
| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/staff/messages` | Listar mensagens |
| `POST` | `/api/staff/messages` | Enviar mensagem |
| `GET` | `/api/staff/messages/unread-count` | Contagem de não lidas |
| `PATCH` | `/api/staff/messages/{message}/read` | Marcar como lida |

#### Pacientes
| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/staff/patients` | Listar pacientes |
| `GET` | `/api/staff/patients/{patient}` | Ver paciente |
| `PUT` | `/api/staff/patients/{patient}` | Atualizar paciente |

#### Indisponibilidade
| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/staff/professionals/{professional}/unavailability` | Listar indisponibilidades |
| `GET` | `/api/staff/professionals/{professional}/unavailability/future` | Listar futuras |
| `POST` | `/api/staff/professionals/{professional}/unavailability` | Criar indisponibilidade |
| `PUT` | `/api/staff/professionals/{professional}/unavailability/{period}` | Atualizar |
| `DELETE` | `/api/staff/professionals/{professional}/unavailability/{period}` | Remover |

#### Contas a Pagar
| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/staff/accounts-payable` | Listar (filtros: status, category, due_date_from/to) |
| `POST` | `/api/staff/accounts-payable` | Criar conta |
| `GET` | `/api/staff/accounts-payable/totals` | Totais (pending, overdue) |
| `PUT` | `/api/staff/accounts-payable/{account}` | Atualizar |
| `POST` | `/api/staff/accounts-payable/{account}/pay` | Marcar como paga |
| `DELETE` | `/api/staff/accounts-payable/{account}` | Remover (SoftDelete) |

#### Contas a Receber
| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/staff/accounts-receivable` | Listar (filtros: status, patient_id, due_date_from/to) |
| `POST` | `/api/staff/accounts-receivable` | Criar conta |
| `GET` | `/api/staff/accounts-receivable/totals` | Totais (pending, received) |
| `PUT` | `/api/staff/accounts-receivable/{account}` | Atualizar |
| `POST` | `/api/staff/accounts-receivable/{account}/pay` | Marcar como paga (sincroniza appointment) |
| `DELETE` | `/api/staff/accounts-receivable/{account}` | Remover (SoftDelete) |

---

## 🚀 Infraestrutura e Deploy

### Arquitetura de Deploy Atômico

O projeto adota uma arquitetura de **deploy atômico baseado em releases** (zero-downtime) isolado por ambiente dentro da pasta `/var/www/blk/` no servidor host.

```
/var/www/blk/
├── blink-hom/                          # Ambiente de Homologação
│   ├── shared/                         # Arquivos persistentes compartilhados
│   │   ├── .env                        # Configuração (CRIADO MANUALMENTE)
│   │   └── storage/                    # Uploads, logs, caches, sessões
│   ├── releases/                       # Histórico de builds (5 mais recentes)
│   │   ├── 2026-07-31_18-00-001/
│   │   └── 2026-07-31_20-30-002/
│   └── current -> releases/...         # Symlink da release ativa
│
└── blink-prod/                         # Ambiente de Produção (estrutura idêntica)
    ├── shared/
    │   ├── .env
    │   └── storage/
    ├── releases/
    └── current -> releases/...
```

### ⚠️ Configuração Prévia Requerida

Por razões de segurança e versionamento:

1. A pasta `/var/www/blk/<ambiente>` deve ser criada **manualmente** no servidor host
2. O arquivo de configuração `shared/.env` deve ser criado **antes** do primeiro deploy
3. A aplicação utiliza o symlink `current` para mapear a release ativa
4. Docker containers apontam para este symlink para garantir atualizações sem interrupção

---

## 📋 Pré-requisitos

- PHP 8.3+
- PostgreSQL
- Composer
- Node.js + NPM
- Docker + Docker Compose (para implantação em servidor)

---

## 🛠️ Instalação

### Desenvolvimento Local

```bash
# Clone o repositório
git clone https://github.com/letsmg/blink.git
cd blink

# Instale as dependências PHP
composer install

# Instale as dependências Node.js
npm install --ignore-scripts

# Configure o ambiente
cp .env.example .env
php artisan key:generate
```

### Configurar PostgreSQL

No arquivo `.env`, configure:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=blink_db
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

### Executar Migrations e Seeders

```bash
php artisan migrate:fresh --seed
```

### Compilar Assets Front-end

```bash
npm run build
```

### Iniciar Servidor de Desenvolvimento

```bash
composer run dev
```

---

## 🧪 Testes

### Executar Todos os Testes

```bash
php artisan test
```

### Executar Testes Específicos

```bash
php artisan test --filter=AccountPayableTest
php artisan test --filter=PatientRegistrationTest
php artisan test --filter=UnavailabilityPeriodTest
```

### Cobertura de Testes

**56 testes • 119 asserções — todos passando ✅**

- ✅ Registro de pacientes (CPF, email, senha, sanitização)
- ✅ Indisponibilidade de profissionais (CRUD, sobreposição, permissões)
- ✅ Contas a pagar (CRUD, pagamento, totais, permissões)
- ✅ Sanitização de entrada (trim, strip_tags)
- ✅ Validação estrutural de CPF

---

## 📄 Licença

Este projeto está licenciado sob **CC BY-NC-SA 4.0**.

```
Copyright (c) 2026 Luiz Eduardo T. Silva. Todos os direitos reservados.
```

---

---

# Blink 👁️

Healthcare management system with RESTful API architecture, built with Laravel.

> 🇧🇷 [Versão em Português](#-sobre-o-projeto) | 🇬🇧 **English Version**

---

## 📑 Table of Contents

- [About the Project](#-about-the-project-1)
- [Architecture](#-architecture-1)
- [Features](#-features)
- [Project Pattern](#-project-pattern)
- [Security](#-security-1)
- [Database Structure](#-database-structure)
- [API Routes](#-api-routes)
- [Infrastructure & Deployment](#-infrastructure--deployment)
- [Prerequisites](#-prerequisites-1)
- [Installation](#-installation-1)
- [Tests](#-tests-1)
- [License](#-license-1)

---

## 🏥 About the Project

Blink is a comprehensive clinical/hospital management system featuring:

- ✅ Patient registration and management
- ✅ Appointment scheduling with payment and follow-up control
- ✅ Healthcare professional management
- ✅ Diagnostics and clinical history
- ✅ Internal messaging system
- ✅ Professional unavailability management
- ✅ Financial Module (Accounts Payable/Receivable)
- ✅ Partner Companies and Agreements
- ✅ Health Insurance Plans
- ✅ Consolidated reports

---

## 🏗️ Architecture

| Component | Technology |
|---|---|
| **Backend** | Laravel (RESTful API) |
| **Database** | PostgreSQL |
| **Frontend** | Vue.js (SPA) |
| **Mobile** | Flutter (future) |

---

## ✨ Features

### ✅ Registration & Authentication

- Patient registration with CPF validation
- Login with token generation (Sanctum)
- Logout and user profile
- Terms of use acceptance
- Login helper: Test user list with auto-fill
- Rate limiting: 5 attempts/min per IP+email

### ✅ Patient Management (Staff)

- Paginated listing
- Detailed view (with appointments and diagnostics)
- Profile update (including health plan linkage)

### ✅ Internal Messaging (Staff)

- Send internal messages
- Message listing
- Mark as read
- Dynamic unread indicator (polling every 30s)
- "Fill Test" button in form

### ✅ Professional Unavailability Management

- Register periods when professionals are unavailable
- Overlap validation
- Future periods listing for calendar
- Complete CRUD (Staff only)
- "Fill Test" button in form

### ✅ Financial Module

#### Accounts Payable
- Operational expense management
- Complete CRUD (description, amount, due date, category, status)
- Payment tracking with date and method
- Dashboard with totals (pending and overdue)
- Transaction audit (created_by/updated_by)

#### Accounts Receivable
- Revenue management
- Direct appointment linkage
- Dynamic calculation: total value, insurance coverage, patient portion
- Status: pending, paid, overdue, canceled, invoiced
- Atomic sync with appointments
- Dashboard with totals (pending and received)
- ⚠️ All operations run within `DB::transaction()`

### ✅ Partner Companies & Agreements

**Companies**
- Partner registration with encrypted CNPJ (PII parity)

**Agreements**
- Rules linked to a company
- Types: private, public, corporate
- Coverage percentage and consultation fee
- `agreement_professional` pivot: restrict professionals per agreement

**Health Plans**
- Available plans linked to agreements
- Mandatory patient linkage via health_plan_id
- Appointments consider agreement_id and health_plan_id for billing

### ✅ Development Helpers (Front-end)

- `formHelpers.ts`: TypeScript helper file
- "Fill Test" button on all registration pages
- "Clear" button on patient registration form
- Realistic data generation: CPF, names, addresses, dates, messages

---

## 📐 Project Pattern

Expanded MVC with:

| Layer | Responsibility |
|---|---|
| **Controllers** | API entry points |
| **Services** | Business rules |
| **Repositories** | Queries and persistence |
| **Requests** | Form validation |
| **Policies** | Access authorization |

---

## 🔐 Security

- **Argon2id hashing** (64MB memory, 3 iterations, 2 threads) for passwords
- **CPF/CNPJ encrypted** with `_hash` (SHA-256) + `_encrypted` (AES-256) parity
- **Input sanitization** with `trim()` and `strip_tags()` via middleware
- **Aggressive rate limiting** on auth routes (5 req/min)
- **Dual validation** (front-end + back-end)
- **Access control** by levels (Patient, Admin, Operational)
- **HttpOnly/Secure/SameSite cookies** for tokens
- **Mandatory acceptance** of Terms of Use and Privacy Policies

---

## 🗄️ Database Structure

| Table | Description |
|---|---|
| `users` | Authentication (plain email for login) |
| `patients` | Patients (encrypted CPF, health_plan_id) |
| `professionals` | Healthcare professionals |
| `locations` | Service locations |
| `location_professional` | Pivot: professionals per location |
| `appointments` | Appointments (agreement_id, health_plan_id) |
| `diagnostics` | Diagnoses |
| `messages` | Internal messages |
| `reports` | Reports |
| `unavailability_periods` | Professional unavailability |
| `term_acceptances` | Terms acceptance |
| `companies` | Partner companies (encrypted CNPJ) |
| `agreements` | Agreements |
| `agreement_professional` | Pivot: authorized professionals per agreement |
| `health_plans` | Health insurance plans |
| `accounts_payable` | Accounts payable |
| `accounts_receivable` | Accounts receivable |

---

## 🔌 API Routes

### 🌐 Public Routes

| Method | Route | Description |
|---|---|---|
| `POST` | `/api/register` | Patient registration (rate limited: 5/min) |
| `POST` | `/api/login` | Login (rate limited: 5/min) |

### 🔒 Authenticated Routes

| Method | Route | Description |
|---|---|---|
| `POST` | `/api/logout` | Logout |
| `GET` | `/api/me` | User profile |
| `POST` | `/api/accept-terms` | Accept terms |

### 👥 Staff Routes (Admin + Operational)

#### Messaging
| Method | Route | Description |
|---|---|---|
| `GET` | `/api/staff/messages` | List messages |
| `POST` | `/api/staff/messages` | Send message |
| `GET` | `/api/staff/messages/unread-count` | Unread count |
| `PATCH` | `/api/staff/messages/{message}/read` | Mark as read |

#### Patients
| Method | Route | Description |
|---|---|---|
| `GET` | `/api/staff/patients` | List patients |
| `GET` | `/api/staff/patients/{patient}` | View patient |
| `PUT` | `/api/staff/patients/{patient}` | Update patient |

#### Unavailability
| Method | Route | Description |
|---|---|---|
| `GET` | `/api/staff/professionals/{professional}/unavailability` | List unavailability |
| `GET` | `/api/staff/professionals/{professional}/unavailability/future` | List future |
| `POST` | `/api/staff/professionals/{professional}/unavailability` | Create |
| `PUT` | `/api/staff/professionals/{professional}/unavailability/{period}` | Update |
| `DELETE` | `/api/staff/professionals/{professional}/unavailability/{period}` | Delete |

#### Accounts Payable
| Method | Route | Description |
|---|---|---|
| `GET` | `/api/staff/accounts-payable` | List (filters: status, category, due_date_from/to) |
| `POST` | `/api/staff/accounts-payable` | Create account |
| `GET` | `/api/staff/accounts-payable/totals` | Totals (pending, overdue) |
| `PUT` | `/api/staff/accounts-payable/{account}` | Update |
| `POST` | `/api/staff/accounts-payable/{account}/pay` | Mark as paid |
| `DELETE` | `/api/staff/accounts-payable/{account}` | Delete (SoftDelete) |

#### Accounts Receivable
| Method | Route | Description |
|---|---|---|
| `GET` | `/api/staff/accounts-receivable` | List (filters: status, patient_id, due_date_from/to) |
| `POST` | `/api/staff/accounts-receivable` | Create account |
| `GET` | `/api/staff/accounts-receivable/totals` | Totals (pending, received) |
| `PUT` | `/api/staff/accounts-receivable/{account}` | Update |
| `POST` | `/api/staff/accounts-receivable/{account}/pay` | Mark as paid (syncs appointment) |
| `DELETE` | `/api/staff/accounts-receivable/{account}` | Delete (SoftDelete) |

---

## 🚀 Infrastructure & Deployment

### Atomic Deployment Architecture

The project leverages **zero-downtime atomic release deployments** isolated per environment within `/var/www/blk/` on the target host server.

```
/var/www/blk/
├── blink-hom/                          # Staging/Homologation Environment
│   ├── shared/                         # Persistent assets shared across releases
│   │   ├── .env                        # Configuration (CREATED MANUALLY)
│   │   └── storage/                    # Uploads, logs, caches, sessions
│   ├── releases/                       # Sequential release builds (retains 5 most recent)
│   │   ├── 2026-07-31_18-00-001/
│   │   └── 2026-07-31_20-30-002/
│   └── current -> releases/...         # Symlink pointing to active release
│
└── blink-prod/                         # Production Environment (identical structure)
    ├── shared/
    │   ├── .env
    │   └── storage/
    ├── releases/
    └── current -> releases/...
```

### ⚠️ Pre-Setup Requirements

For security and versioning reasons:

1. The parent root `/var/www/blk/<environment>` must be created **manually** on the host server
2. The configuration file `shared/.env` must be created **before** running the first deploy
3. The application uses the `current` symlink to map the active release
4. Docker containers point to this symlink to ensure zero-downtime updates

---

## 📋 Prerequisites

- PHP 8.3+
- PostgreSQL
- Composer
- Node.js + NPM
- Docker + Docker Compose (for server deployment)

---

## 🛠️ Installation

### Local Development

```bash
# Clone repository
git clone https://github.com/letsmg/blink.git
cd blink

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install --ignore-scripts

# Setup environment
cp .env.example .env
php artisan key:generate
```

### Configure PostgreSQL

In your `.env` file:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=blink_db
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

### Run Migrations & Seeders

```bash
php artisan migrate:fresh --seed
```

### Compile Front-end Assets

```bash
npm run build
```

### Start Development Server

```bash
composer run dev
```

---

## 🧪 Tests

### Run All Tests

```bash
php artisan test
```

### Run Specific Tests

```bash
php artisan test --filter=AccountPayableTest
php artisan test --filter=PatientRegistrationTest
php artisan test --filter=UnavailabilityPeriodTest
```

### Test Coverage

**56 tests • 119 assertions — all passing ✅**

- ✅ Patient registration (CPF, email, password, sanitization)
- ✅ Professional unavailability (CRUD, overlap, permissions)
- ✅ Accounts payable (CRUD, payment, totals, permissions)
- ✅ Input sanitization (trim, strip_tags)
- ✅ CPF structural validation

---

## 📄 License

Licensed under **CC BY-NC-SA 4.0**.

```
Copyright (c) 2026 Luiz Eduardo T. Silva. All rights reserved.
```