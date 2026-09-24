# Modelo de dados

As tabelas `segurados` e `apolices` são o cadastro principal (CRUD). As demais guardam os dados usados pela dashboard.

```mermaid
erDiagram
    SEGURADOS ||--o{ APOLICES : "contrata"
    CANAIS ||--o{ APOLICES : "vende"
    CAMPANHAS ||--o{ APOLICES : "atrai"
    CANAIS ||--o{ COTACOES : "origina"
    CAMPANHAS ||--o{ COTACOES : "atrai"
    COTACOES |o--o| APOLICES : "converte em"
    COTACOES ||--o{ FUNIL_EVENTOS : "registra"
    APOLICES ||--o{ SINISTROS : "gera"
    APOLICES ||--o{ ATENDIMENTOS : "gera"

    SEGURADOS {
        bigint id PK
        varchar nome
        char cpf UK
        varchar email
        date data_nascimento
    }
    APOLICES {
        bigint id PK
        varchar numero UK
        bigint segurado_id FK
        bigint canal_id FK "opcional"
        bigint campanha_id FK "opcional"
        varchar destino
        varchar plano
        date inicio_vigencia
        date fim_vigencia
        int valor_premio_centavos
        varchar status "ativa ou cancelada"
        timestamp deleted_at "exclusão lógica"
    }
    CANAIS {
        bigint id PK
        varchar codigo UK "site, agencia, corretor, parceiro, app"
        varchar nome
    }
    CAMPANHAS {
        bigint id PK
        varchar nome
        varchar utm_source
        varchar utm_campaign
        int orcamento_centavos
        int investimento_centavos
        date inicio
        date fim
    }
    COTACOES {
        bigint id PK
        uuid codigo UK
        bigint canal_id FK
        bigint campanha_id FK "opcional"
        bigint apolice_id FK "quando converte"
        varchar destino
        varchar plano
        smallint dias
        int valor_calculado_centavos
        varchar device "mobile ou desktop"
        varchar status "convertida ou abandonada"
        varchar etapa_abandono
    }
    FUNIL_EVENTOS {
        bigint id PK
        bigint cotacao_id FK
        varchar etapa
        varchar utm_source
        varchar device
        timestamp ocorrido_em
    }
    SINISTROS {
        bigint id PK
        varchar numero UK
        bigint apolice_id FK
        varchar cobertura
        date data_ocorrencia
        date data_aviso
        int valor_reclamado_centavos
        int valor_pago_centavos
        varchar status "aberto, em_analise, aprovado, pago, negado"
        varchar motivo_negativa
    }
    ATENDIMENTOS {
        bigint id PK
        bigint apolice_id FK "opcional"
        varchar canal "telefone, whatsapp, app"
        varchar tipo
        timestamp inicio
        int tempo_espera_seg
        boolean dentro_sla
        tinyint nps "0 a 10"
    }
```

## Decisões

- **Valores em centavos (inteiro)** em todas as tabelas, como na apólice, para não ter erro de arredondamento.
- **Migrations novas** (`2026_09_24_*`): as tabelas antigas não foram editadas; `canal_id` e `campanha_id` entraram na apólice por uma migration de alteração e são opcionais, então as apólices cadastradas pela tela continuam funcionando.
- **Índices** nas colunas usadas pelos filtros da dashboard: `apolices.created_at`, `cotacoes (created_at, status)`, `funil_eventos (cotacao_id, etapa)`, `sinistros (status, data_aviso)` e `atendimentos.inicio`.
