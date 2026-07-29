# SDD — Software Design Document

## Blink - Sistema de Gestão de Saúde

### Arquitetura Geral

```
┌─────────────────────────────────────────────────────────┐
│                   Frontend (Vue 3 SPA)                   │
│  ┌─────────┐ ┌──────────┐ ┌───────────┐ ┌───────────┐  │
│  │  Login   │ │ Patient  │ │   Staff    │ │  Admin    │  │
│  │  Views   │ │  Views   │ │   Views    │ │  Views    │  │
│  └────┬─────┘ └────┬─────┘ └─────┬─────┘ └─────┬─────┘  │
│       │            │             │             │         │
│  ┌────┴────────────┴─────────────┴─────────────┴────┐   │
│  │              Pinia Store / Vue Router              │   │
│  └────────────────────────┬──────────────────────────┘   │
└───────────────────────────┼──────────────────────────────┘
                            │ HTTP/JSON
┌───────────────────────────┼──────────────────────────────┐
│                  Backend (Laravel API)                    │
│  ┌────────────────────────┴──────────────────────────┐   │
│  │              Middleware Chain                       │   │
│  │  InjectToken → SanitizeInput → Auth → Role → Log   │   │
│  └────────────────────────┬──────────────────────────┘   │
│  ┌────────────────────────┴──────────────────────────┐   │
│  │                  Controllers                        │   │
│  └──────┬──────────┬──────────┬──────────┬───────────┘   │
│  ┌──────┴──┐  ┌────┴────┐ ┌───┴────┐ ┌──┴──────────┐    │
│  │Services │  │Requests │ │Policies│ │Repositories │    │
│  └──────┬──┘  └─────────┘ └────────┘ └──────┬───────┘    │
│  ┌──────┴────────────────────────────────────┴───────┐    │
│  │                   Models (Eloquent)                │    │
│  └────────────────────────┬──────────────────────────┘    │
└───────────────────────────┼──────────────────────────────┘
                            │
┌───────────────────────────┼──────────────────────────────┐
│                  Data Layer                               │
│  ┌────────────────────────┴──────────────────────────┐   │
│  │  PostgreSQL (Primary DB)    │  Redis (Cache/Queue) │   │
│  └─────────────────────────────┴──────────────────────┘   │
└──────────────────────────────────────────────────────────┘
```

### Stack Tecnológica

| Camada | Tecnologia | Versão |
|--------|-----------|--------|
| Backend | Laravel | 13.x |
| Frontend | Vue 3 (SPA) | 3.5 |
| Banco | PostgreSQL | 16 |
| Cache | Redis | 7 |
| Testes | Pest | 4.7 |
| E2E | Playwright | Latest |
| Auth | Laravel Sanctum | 4.x |
| CI/CD | GitHub Actions | - |
| Container | Docker Compose | 3.8 |

### Padrão MVC Expandido

```
app/
├── Http/
│   ├── Controllers/Api/   ← Entrada RESTful
│   ├── Requests/           ← Validação + Sanitização
│   └── Middleware/         ← Auth, Role, Sanitize, RateLimit
├── Models/                 ← Eloquent ORM
├── Services/               ← Regras de negócio
├── Repositories/           ← Consultas complexas
├── Policies/               ← Autorização por entidade
├── Rules/                  ← Validação customizada (CPF, CNPJ)
└── Enums/                  ← UserRole
```

### Modelo de Dados (15 tabelas)

```
users (id, display_name, first_name_hash, first_name_encrypted,
       last_name_hash, last_name_encrypted, email, password,
       role, terms_accepted, terms_accepted_at, terms_version)

patients (id, user_id, full_name, date_of_birth,
          cpf_encrypted, cpf_hash, main_complaint,
          street_hash, street_encrypted,
          neighborhood_hash, neighborhood_encrypted,
          city_hash, city_encrypted,
          state, zip_code, clinical_history,
          phone1_hash, phone1_encrypted,
          phone2_hash, phone2_encrypted,
          health_plan_id)

professionals (id, user_id, full_name, specialty,
               professional_document, phone1, phone2, is_active)

locations (id, name, address, zip_code, neighborhood, city)
location_professional (id, professional_id, location_id)

companies (id, name, trade_name, cnpj_hash, cnpj_encrypted,
           phone, email, contact_person, is_active)
agreements (id, company_id, name, code, type,
            coverage_percentage, consultation_fee, is_active,
            start_date, end_date)
agreement_professional (id, agreement_id, professional_id, custom_fee)

health_plans (id, name, code, agreement_id, category, is_active, monthly_fee)

appointments (id, patient_id, professional_id, location_id,
              date, time, notes, is_paid, payment_method,
              paid_at, is_return, original_appointment_id,
              agreement_id, health_plan_id)

diagnostics (id, patient_id, professional_id, appointment_id,
             diagnosis_date, description, prescription, notes)
messages (id, sender_id, recipient_id, subject, body, is_read, read_at)
reports (id, generated_by, title, type, filters, data, period_start, period_end)
unavailability_periods (id, professional_id, start_date, end_date, reason)

accounts_payable (id, description, amount, due_date, paid_at,
                  status, category, payment_method, notes,
                  created_by, updated_by)
accounts_receivable (id, appointment_id, patient_id, amount,
                     insurance_covered_amount, patient_portion,
                     due_date, paid_at, status, payment_method,
                     invoice_number, notes, created_by, updated_by)

term_acceptances (id, visitor_uuid, user_id, term_type, ip_address,
                  country, region, city, latitude, longitude,
                  user_agent, terms_version)
```

### Paridade PII (LGPD)

Todos os dados sensíveis seguem o padrão de colunas duplas:
- `*_hash` → SHA-256 para busca e índices únicos
- `*_encrypted` → AES-256 para descriptografia em memória

Campos protegidos: `first_name`, `last_name`, `cpf`, `street`, `neighborhood`, `city`, `phone1`, `phone2`, `cnpj`

### Segurança

- **Hash de senha**: Argon2id (64MB memory, 3 iterations, 2 threads)
- **Sanitização**: `strip_tags()` + `trim()` via middleware global
- **Rate Limiting**: 5 req/min em `/api/login` e `/api/register`
- **Tokens**: Sanctum via HttpOnly/Secure/SameSite cookies
- **RBAC**: Patient vs Staff (Admin + Operational) via middleware
- **Mass Assignment**: `$fillable` estrito em todos os Models

### Transações Financeiras

Todas as operações de Contas a Pagar e Receber são atômicas:
```php
DB::transaction(function () {
    // Criação, atualização, pagamento
});
```

O pagamento de uma Conta a Receber sincroniza automaticamente o `Appointment`:
```php
$account->markAsPaid() → $appointment->update(['is_paid' => true])
```

### Rotas da API

Ver documentação completa no [README.md](../README.md).

### Testes Automatizados

- **76 testes • 160 asserções** (Pest/PHPUnit)
- **11 testes E2E** (Playwright — login, permissões, financeiro)
- Cobertura: registro, auth, mensagens, indisponibilidade, contas a pagar/receber

### Deploy com Docker

```bash
docker compose up -d
```

Serviços:
- `blink_pgsql` → PostgreSQL 16 (port 5432)
- `blink_redis` → Redis 7 (port 6379)
- `blink_app` → Laravel API (port 8000)