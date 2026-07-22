# BDD — Behavior-Driven Development

## Blink - Sistema de Gestão de Saúde

### Features e Cenários de Teste

---

### Feature: Registro de Paciente

**Como** um novo paciente  
**Quero** me registrar no sistema  
**Para** agendar consultas médicas

```gherkin
Feature: Patient Registration

  Scenario: Registro com dados válidos
    Given que estou na página de registro
    When preencho nome "Maria Silva", email "maria@teste.com", senha "12345678"
    And CPF válido "529.982.247-25" e data de nascimento "1990-05-15"
    And submeto o formulário
    Then recebo status 201 com token de acesso
    And meu CPF está criptografado no banco de dados
    And minha role é "Patient" (0)

  Scenario: Registro com CPF inválido
    Given que estou na página de registro
    When preencho CPF "111.111.111-11"
    And submeto o formulário
    Then recebo erro 422 com mensagem de CPF inválido

  Scenario: Registro com email duplicado
    Given que "maria@teste.com" já está registrado
    When tento registrar com o mesmo email
    Then recebo erro 422 com mensagem de email já cadastrado

  Scenario: Rate limiting bloqueia múltiplas tentativas
    Given que faço 5 requisições de registro em 1 minuto
    When tento a 6ª requisição
    Then recebo status 429 (Too Many Requests)
```

---

### Feature: Autenticação e Perfis

**Como** usuário do sistema  
**Quero** fazer login com minhas credenciais  
**Para** acessar minha área restrita

```gherkin
Feature: Login and Role-Based Access

  Scenario: Login como Admin
    Given estou na página de login
    When insiro "admin@blink.com" e senha "password"
    Then sou redirecionado para /staff/dashboard
    And vejo os menus Agenda, Pacientes, Financeiro

  Scenario: Login como Patient
    Given estou na página de login
    When insiro "patient0@blink.com" e senha "password"
    Then sou redirecionado para /patient/dashboard

  Scenario: Patient tenta acessar área Staff
    Given estou logado como Patient
    When tento acessar /staff/accounts-payable
    Then recebo status 403 "Acesso restrito"

  Scenario: Staff tenta acessar área Patient
    Given estou logado como Admin
    When tento acessar /patient/profile
    Then recebo status 403
```

---

### Feature: Gestão de Convênios

**Como** administrador da clínica  
**Quero** cadastrar empresas conveniadas e convênios  
**Para** gerenciar as regras de cobrança

```gherkin
Feature: Agreement Management

  Scenario: Cadastrar empresa conveniada
    Given estou logado como Admin
    When cadastro a empresa "Unimed Regional" com CNPJ "11.222.333/0001-81"
    Then a empresa é salva com CNPJ criptografado (cnpj_hash + cnpj_encrypted)

  Scenario: Criar convênio vinculado à empresa
    Given a empresa "Unimed Regional" está cadastrada
    When crio convênio "Unimed Tipo A" com cobertura 80% e valor R$250
    Then o convênio fica vinculado à empresa

  Scenario: Vincular profissional ao convênio
    Given o convênio "Unimed Tipo A" existe
    And o profissional "Dr. Carlos" está cadastrado
    When vinculo o profissional ao convênio com valor customizado R$200
    Then apenas profissionais vinculados podem atender por esse convênio
```

---

### Feature: Agendamento e Cobrança

**Como** staff operacional  
**Quero** agendar consultas considerando convênios  
**Para** calcular a cobrança corretamente

```gherkin
Feature: Appointment with Billing

  Scenario: Agendamento com plano de saúde
    Given o paciente "Maria Silva" tem plano "Unimed Básico"
    And o plano tem convênio com 80% de cobertura
    When agendo consulta para "Maria Silva" com "Dr. Carlos"
    Then a consulta registra agreement_id e health_plan_id
    And uma Conta a Receber é gerada com insurance_covered_amount = 80% do valor

  Scenario: Agendamento particular (sem convênio)
    Given o paciente "João Santos" não tem convênio
    When agendo consulta para "João Santos"
    Then patient_portion = valor total da consulta
    And insurance_covered_amount = 0
```

---

### Feature: Contas a Pagar

**Como** administrador financeiro  
**Quero** gerenciar despesas operacionais  
**Para** controlar o fluxo de caixa da clínica

```gherkin
Feature: Accounts Payable Management

  Scenario: Registrar despesa de aluguel
    Given estou logado como Admin
    When registro despesa "Aluguel da clínica" no valor de R$5.000
    And vencimento em 30 dias, categoria "aluguel"
    Then a despesa é salva com status "pending"

  Scenario: Marcar despesa como paga
    Given existe uma despesa pendente de R$350
    When marco como paga com método "pix" e data de hoje
    Then status muda para "paid"
    And paid_at e payment_method são registrados

  Scenario: Dashboard de totais
    Given existem 2 contas pendentes (R$300 + R$200)
    And 1 conta vencida (R$400)
    When acesso GET /staff/accounts-payable/totals
    Then total_pending = R$500 e total_overdue = R$400
```

---

### Feature: Contas a Receber

**Como** administrador financeiro  
**Quero** gerenciar recebimentos de consultas  
**Para** sincronizar pagamentos com agendamentos

```gherkin
Feature: Accounts Receivable Management

  Scenario: Criar conta a receber vinculada a agendamento
    Given existe um agendamento do paciente "Maria Silva"
    When crio conta a receber de R$300 com cobertura de R$180
    Then patient_portion é calculado automaticamente como R$120

  Scenario: Marcar conta como paga sincroniza agendamento
    Given existe conta a receber pendente vinculada a agendamento
    When marco como paga
    Then status da conta muda para "paid"
    And o agendamento é atualizado com is_paid = true

  Scenario: Operações financeiras são atômicas
    Given uma operação de pagamento está em andamento
    When ocorre erro no meio da transação
    Then nenhuma alteração é persistida (rollback)
```

---

### Feature: Segurança e Privacidade (PII)

**Como** responsável pela LGPD  
**Quero** garantir que dados sensíveis estejam protegidos  
**Para** cumprir a legislação de proteção de dados

```gherkin
Feature: PII Data Protection

  Scenario: Nome do usuário armazenado com paridade
    Given um novo usuário é registrado
    Then first_name e last_name possuem hash (SHA-256) e encrypted (AES-256)
    And apenas display_name está em texto puro

  Scenario: Endereço do paciente criptografado
    Given um paciente é cadastrado com endereço completo
    Then street, neighborhood, city possuem hash + encrypted
    And zip_code permanece em texto puro

  Scenario: Telefones do paciente criptografados
    Given um paciente é cadastrado com phone1 e phone2
    Then phone1_hash, phone1_encrypted, phone2_hash, phone2_encrypted existem
    And os telefones originais não estão em texto puro

  Scenario: Sanitização de entrada previne XSS
    Given um formulário recebe "<script>alert('xss')</script>"
    When o middleware SanitizeInput processa
    Then tags HTML são removidas (strip_tags)
    And espaços são trimados
```

---

### Feature: Validação de Documentos

**Como** sistema  
**Quero** validar CPF e CNPJ  
**Para** garantir integridade dos dados cadastrais

```gherkin
Feature: Document Validation

  Scenario: Validar CPF tradicional
    Given CPF "529.982.247-25"
    When aplicada a regra Cpf
    Then CPF é considerado válido (dígitos verificadores corretos)

  Scenario: Rejeitar CPF com todos dígitos iguais
    Given CPF "111.111.111-11"
    When aplicada a regra Cpf
    Then CPF é rejeitado

  Scenario: Validar CNPJ alfanumérico
    Given CNPJ com caracteres alfanuméricos no novo padrão RFB
    When aplicada a regra Cnpj (alphanumeric)
    Then CNPJ é validado usando pesos [5,4,3,2,9,8,7,6,5,4,3,2]
```

---

### Cobertura de Testes

| Feature | Pest (Backend) | Playwright (E2E) |
|---------|---------------|-----------------|
| Registro de Paciente | 10 testes | - |
| Login e RBAC | - | 7 testes |
| Indisponibilidade | 17 testes | - |
| Contas a Pagar | 13 testes | 4 testes |
| Contas a Receber | 14 testes | - |
| Sanitização de Input | 6 testes | - |
| Validação CPF | 14 testes | - |
| **TOTAL** | **76 testes • 160 assertions** | **11 testes** |