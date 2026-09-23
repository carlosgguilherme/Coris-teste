# Seguro Viagem - Gestão de Apólices

Seguro Viagem é uma aplicação web para **gestão de apólices de seguro viagem**, desenvolvida como teste técnico, utilizando **Laravel 12**, **React 18** e **MySQL**, com desenho de solução na **Azure**. O foco foi entregar o CRUD completo com regras reais do negócio de seguros (cálculo do prêmio, vigência, exclusão lógica), código organizado seguindo **SOLID** e **Clean Code** e testes automatizados.

---

## Requisitos atendidos

- Leitura, cadastro, edição e exclusão de apólices
- Conceitos de **SOLID** e **Clean Code** (Controller, Form Request, Service e Calculadora separados)
- Frontend em **React 18**
- Banco de dados relacional (**MySQL**) com relacionamento **1:N**
- Desenho de solução com componentes da **Azure**
- Testes automatizados (PHPUnit)
- Docker para desenvolvimento local

---

## Tecnologias

- **Backend:** Laravel 12 (PHP 8.3)
- **Frontend:** React 18 + Vite + React Router
- **Banco:** MySQL 8 (SQLite para rodar local sem instalar banco)
- **Testes:** PHPUnit
- **Docker:** Apache + PHP, MySQL e Nginx servindo o React
- **Azure:** App Service, Static Web Apps, Database for MySQL e Key Vault

---

## Desenho da solução

![Arquitetura na Azure](docs/arquitetura-azure.svg)

- **Static Web Apps** hospeda o React (estático, em CDN, com HTTPS)
- **App Service (Linux)** roda a API Laravel
- **Azure Database for MySQL** guarda segurados e apólices
- **Key Vault** guarda a senha do banco e a `APP_KEY`, lidas pelo App Service com **Managed Identity**
- **GitHub Actions** faz o deploy a cada push (workflow gerado pelo Deployment Center do Azure)

---

## Modelagem do Domínio

- **Segurado** → possui muitas **Apólices** (1:N). É identificado pelo CPF e reaproveitado entre apólices
- **Apólice** → pertence a um **Segurado**, tem destino, plano, vigência, prêmio e status (ativa ou cancelada)

---

## Regras de negócio

- **Prêmio** = diária do plano × dias de viagem × % do destino × % da idade do segurado
  - Planos: Essencial (R$ 12,90/dia), Plus (R$ 24,90/dia), Premium (R$ 39,90/dia)
  - Destino: Nacional 50%, América do Sul 100%, Europa 130%, América do Norte 140%, Ásia/África/Oceania 150%
  - Idade no início da viagem: até 59 anos 100%, 60 a 74 anos 160%, 75+ anos 250%
- Valores em dinheiro são guardados em **centavos (inteiro)**, nunca em `float`, para não ter erro de arredondamento
- A vigência não pode começar antes de hoje e tem no máximo 365 dias
- CPF validado pelos dígitos verificadores
- Apólice cancelada só pode ser alterada para ser reativada
- A exclusão é **lógica** (`SoftDeletes`): a apólice some do sistema, mas o registro fica no banco

---

## Fluxo

1. Operador cadastra uma **Apólice**.
   - O prêmio é calculado em tempo real enquanto o formulário é preenchido (endpoint de cotação).
   - Se o CPF já existir, o **Segurado** é reaproveitado.
2. Consulta a lista com busca (nome, CPF, e-mail ou número), filtro por status e paginação.
3. Edita a apólice e o prêmio é recalculado.
4. Cancela ou reativa pelo status.
5. Exclui a apólice (exclusão lógica).

---

## Organização do backend

```
routes/api.php                  rotas da API
app/Http/Requests               validação (SalvarApoliceRequest, CotacaoRequest) e regra de CPF
app/Http/Controllers/Api        recebe a requisição e chama o service
app/Services/ApoliceService     regras de negócio (criar, atualizar, excluir, cotar, resumo)
app/Services/Premio             CalculadoraPremio (interface) e CalculadoraPremioViagem
app/Models                      Segurado e Apolice (Eloquent)
app/Enums                       Plano, Destino e StatusApolice
app/Http/Resources              formato do JSON de resposta
```

- **S** - cada classe com uma responsabilidade: o Form Request valida, o Controller só trata HTTP, o Service tem a regra e a Calculadora só calcula
- **O** - plano ou destino novo é um novo `case` no enum; outra regra de preço é outra classe que implementa `CalculadoraPremio`
- **L / D** - o `ApoliceService` recebe a interface `CalculadoraPremio` por injeção de dependência; a implementação é definida no `AppServiceProvider`
- **I** - a interface da calculadora tem um método só

---

## Testes Unitarios

```bash
cd backend
php artisan test
```

> Todos os testes estão passando

---

# Docker (modo principal)

> Pré-requisitos: Docker Desktop instalado e em execução.

A aplicação roda no Docker em **http://localhost:3000**

## 1) Subir os containers

```bash
docker compose up -d --build
```

Na subida a API roda as migrations sozinha.

## 2) Popular com dados de exemplo (opcional)

```bash
docker compose exec api php artisan db:seed
```

## 3) Acessar

- App: **http://localhost:3000**
- API: **http://localhost:8000/api/apolices**

> Se precisar recriar o banco do zero: `docker compose down -v` e subir de novo.

---

## Setup local sem uso de Docker

Requisitos: PHP 8.2+, Composer, Node.js 18+. Localmente a API usa SQLite, então não precisa instalar MySQL.

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Em outro terminal:

```bash
cd frontend
npm install
npm run dev
```

- App: **http://localhost:5173**

---

## Endpoints

- `GET /api/apolices?busca=&status=&page=` - lista paginada
- `GET /api/apolices/resumo` - totais para os cards da tela inicial
- `GET /api/apolices/{id}` - detalhe
- `POST /api/apolices` - cadastra
- `PUT /api/apolices/{id}` - edita
- `DELETE /api/apolices/{id}` - exclusão lógica
- `POST /api/apolices/cotacao` - calcula o prêmio sem salvar
- `GET /api/opcoes` - planos, destinos e status

Erros de validação voltam com status `422` e a mensagem por campo.

---

## Deploy na Azure

Feito pelo Portal da Azure, no Resource Group `rg-coris-seguros` (Brazil South).

1. **Azure Database for MySQL - Flexible Server**
   - Criar o servidor e o banco `coris_seguros`
   - Em *Networking*, liberar acesso para serviços do Azure
2. **App Service** (Linux, PHP 8.3)
   - Em *Environment variables*: `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` e `MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt`
   - Em *Configuration > Startup Command*: `bash /home/site/wwwroot/azure/startup.sh` (aponta o nginx para a pasta `public` e roda as migrations)
   - Em *Deployment Center*: conectar o GitHub (pasta `backend`)
3. **Key Vault** (opcional)
   - Guardar a senha do banco e usar referência `@Microsoft.KeyVault(...)` nas variáveis do App Service, com Managed Identity
4. **Static Web App**
   - Conectar o GitHub, app location `frontend`, output `dist`
   - Variável de build `VITE_API_URL` com a URL do App Service + `/api`

---

## Próximos passos

- Autenticação de usuários (Laravel Sanctum)
- Endosso: registrar o histórico de cada alteração feita em uma apólice emitida
- Infraestrutura como código (Bicep) para criar os recursos da Azure
- Testes no frontend
- Pipeline de CI próprio rodando os testes antes do deploy

---

## Autor do projeto - teste tecnico para CORIS

Carlos Guilherme Fontes Pereira
