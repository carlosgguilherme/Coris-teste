# Seguro Viagem - Gestão de Apólices

Seguro Viagem é uma aplicação web para **gestão de apólices de seguro viagem**, desenvolvida como teste técnico, utilizando **PHP 8.3** (sem framework), **React 18** e **MySQL**, com desenho de solução na **Azure**. O foco foi entregar o CRUD completo com regras reais do negócio de seguros (cálculo de prêmio, endosso, exclusão lógica), arquitetura em camadas seguindo **SOLID** e **Clean Code**, autenticação e testes automatizados.

---

## Requisitos atendidos

- Leitura, cadastro, edição e exclusão de apólices
- Conceitos de **SOLID** e **Clean Code** aplicados em camadas (Domain, Application, Infrastructure e Http)
- Frontend em **React 18**
- Banco de dados relacional (**MySQL**) com relacionamentos **1:N**
- Desenho de solução com componentes da **Azure** + infraestrutura como código (Bicep)
- Autenticação com **JWT**
- Testes automatizados no backend e no frontend, rodando no CI (GitHub Actions)
- Docker para desenvolvimento local

---

## Tecnologias

- **Backend:** PHP 8.3 sem framework, Composer (PSR-4), firebase/php-jwt
- **Frontend:** React 18 + Vite + React Router
- **Banco:** MySQL 8 (SQLite para rodar local sem instalar banco)
- **Testes:** PHPUnit (backend) e Vitest + Testing Library (frontend)
- **Docker:** Apache + PHP, MySQL e Nginx servindo o React
- **Azure:** App Service, Static Web Apps, Database for MySQL, Key Vault, Log Analytics
- **CI/CD:** GitHub Actions

---

## Desenho da solução

![Arquitetura na Azure](docs/arquitetura-azure.svg)

- **Static Web Apps** hospeda o React (estático, em CDN, com HTTPS)
- **App Service (Linux)** roda a API em PHP
- **Azure Database for MySQL** guarda segurados, apólices e endossos
- **Key Vault** guarda a senha do banco e o segredo do JWT. A API acessa com **Managed Identity**, então nenhuma senha fica no código ou no GitHub
- **Log Analytics** recebe os logs HTTP, erros da API e métricas do App Service
- **GitHub Actions** roda os testes a cada push e publica front e API

Toda a infraestrutura está em [`infra/main.bicep`](infra/main.bicep).

---

## Modelagem do Domínio

- **Segurado** → possui muitas **Apólices** (1:N). É identificado pelo CPF e reaproveitado entre apólices
- **Apólice** → pertence a um **Segurado** e possui muitos **Endossos** (1:N)
- **Endosso** → registro de cada alteração feita em uma apólice já emitida: o que mudou, prêmio anterior, prêmio novo e quem alterou
- **Usuário** → operador que acessa o sistema

---

## Regras de negócio

- **Prêmio** = diária do plano × dias de viagem × % do destino × % da idade do segurado
  - Planos: Essencial (R$ 12,90/dia), Plus (R$ 24,90/dia), Premium (R$ 39,90/dia)
  - Destino: Nacional 50%, América do Sul 100%, Europa 130%, América do Norte 140%, Ásia/África/Oceania 150%
  - Idade: até 59 anos 100%, 60 a 74 anos 160%, 75+ anos 250%
- Valores em dinheiro são tratados sempre em **centavos (inteiro)**, nunca em `float`, para não ter erro de arredondamento
- A vigência não pode começar antes de hoje e tem no máximo 365 dias
- O CPF é validado pelos dígitos verificadores e não pode ser trocado em uma apólice já emitida
- Apólice emitida não é editada livremente: toda alteração gera um **endosso** com o histórico
- Apólice cancelada só pode ser alterada para ser reativada
- A exclusão é **lógica**: a apólice some do sistema, mas o registro fica no banco para auditoria

---

## Fluxo

1. Operador faz **login** (JWT, válido por 8 horas).
2. Cadastra uma **Apólice**.
   - O prêmio é calculado em tempo real enquanto o formulário é preenchido.
   - Se o CPF já existir, o **Segurado** é reaproveitado.
3. Consulta a lista com busca (nome, CPF, e-mail ou número), filtro por status e paginação.
4. Edita uma apólice.
   - Cada alteração gera um **Endosso** com o que mudou e a diferença de prêmio.
5. Cancela ou reativa pelo status.
6. Exclui a apólice (exclusão lógica).

---

## Arquitetura do backend

```
Http            → Router, Controllers, Resources, Kernel (auth, erros, CORS)
Application     → ApoliceService, AuthService, ApoliceValidator, CalculadoraPremio
Domain          → Apolice, Endosso, Segurado, Usuario, Cpf, Dinheiro, Vigencia + interfaces dos repositórios
Infrastructure  → Repositórios PDO, transação, JWT, relógio, conexão com o banco
```

A dependência sempre aponta para o **Domain**, que não conhece banco, HTTP nem framework.

- **S** - cada classe tem uma responsabilidade: o validador só valida formato, a calculadora só calcula, o repositório só persiste
- **O** - novo plano ou destino é um novo `case` no enum; nova regra de preço é outra implementação de `CalculadoraPremio`
- **L** - os repositórios PDO podem ser trocados por qualquer outra implementação da interface (nos testes uso SQLite em memória)
- **I** - interfaces pequenas: `CalculadoraPremio`, `Relogio`, `Transacao`, `EmissorToken`
- **D** - os serviços recebem interfaces no construtor; quem monta as implementações é o `Container`

---

## Testes Unitarios

```bash
cd backend
vendor/bin/phpunit
```

```bash
cd frontend
npm test
```

> Todos os testes estão passando (58 no backend e 13 no frontend)

---

# Docker (modo principal)

> Pré-requisitos: Docker Desktop instalado e em execução.

A aplicação roda no Docker em **http://localhost:3000**

## 1) Subir os containers

```bash
docker compose up -d --build
```

Na subida a API cria as tabelas e o usuário administrador sozinha.

## 2) Popular com dados de exemplo (opcional)

```bash
docker compose exec api php bin/seed.php
```

## 3) Acessar

- App: **http://localhost:3000**
- API: **http://localhost:8000/api**
- Login: **admin@seguroviagem.com** / **Admin@123**

> Se mudar a estrutura do banco, rode `docker compose down -v` antes de subir de novo para recriar o MySQL.

---

## Setup local sem uso de Docker

Requisitos: PHP 8.2+, Composer, Node.js 18+. Localmente a API usa SQLite, então não precisa instalar MySQL.

```bash
cd backend
composer install
cp .env.example .env
php bin/migrate.php
php bin/criar-admin.php
php bin/seed.php
php -S localhost:8000 -t public
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

Todos exigem `Authorization: Bearer <token>`, menos o login e o health check.

- `POST /api/auth/login` - login, retorna o token
- `GET /api/apolices?busca=&status=&pagina=` - lista paginada
- `GET /api/apolices/resumo` - totais para os cards da tela inicial
- `GET /api/apolices/{id}` - detalhe
- `GET /api/apolices/{id}/endossos` - histórico de endossos
- `POST /api/apolices` - emite uma apólice
- `PUT /api/apolices/{id}` - altera e gera endosso
- `DELETE /api/apolices/{id}` - exclusão lógica
- `POST /api/apolices/cotacao` - calcula o prêmio sem salvar
- `GET /api/opcoes` - planos, destinos e status

Erros de validação voltam com status `422` e a mensagem por campo, para o front mostrar no lugar certo.

---

## Deploy na Azure

```bash
az login
az group create --name rg-coris-seguros --location brazilsouth

export MYSQL_ADMIN_PASSWORD='<senha-forte>'
export JWT_SECRET="$(openssl rand -base64 48)"
export ADMIN_SENHA='<senha-do-admin>'

az deployment group create --resource-group rg-coris-seguros --parameters infra/main.bicepparam
```

Depois, no GitHub (**Settings → Secrets and variables → Actions**):

- Variable `AZURE_WEBAPP_NAME` com o nome da API (saída `apiName`)
- Variable `AZURE_API_URL` com a URL da API (saída `apiUrl`)
- Secret `AZURE_WEBAPP_PUBLISH_PROFILE` (Portal → App Service → Download publish profile)
- Secret `AZURE_STATIC_WEB_APPS_API_TOKEN` (Portal → Static Web App → Manage deployment token)

A partir daí cada push na `main` publica a aplicação.

---

## Autor do projeto - teste tecnico para CORIS

Carlos Guilherme Fontes Pereira
