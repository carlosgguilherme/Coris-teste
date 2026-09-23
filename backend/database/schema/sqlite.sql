CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    criado_em DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS segurados (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(120) NOT NULL,
    cpf CHAR(11) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL,
    data_nascimento DATE NOT NULL,
    criado_em DATETIME NOT NULL,
    atualizado_em DATETIME NULL
);

CREATE TABLE IF NOT EXISTS apolices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero VARCHAR(20) NOT NULL UNIQUE,
    segurado_id INTEGER NOT NULL REFERENCES segurados (id),
    destino VARCHAR(30) NOT NULL,
    plano VARCHAR(20) NOT NULL,
    inicio_vigencia DATE NOT NULL,
    fim_vigencia DATE NOT NULL,
    valor_premio_centavos INTEGER NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ativa',
    criado_em DATETIME NOT NULL,
    atualizado_em DATETIME NULL,
    excluido_em DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_apolices_status ON apolices (status);
CREATE INDEX IF NOT EXISTS idx_apolices_excluido_em ON apolices (excluido_em);

CREATE TABLE IF NOT EXISTS endossos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    apolice_id INTEGER NOT NULL REFERENCES apolices (id),
    numero INTEGER NOT NULL,
    alteracoes TEXT NOT NULL,
    premio_anterior_centavos INTEGER NOT NULL,
    premio_novo_centavos INTEGER NOT NULL,
    usuario VARCHAR(150) NOT NULL,
    criado_em DATETIME NOT NULL,
    UNIQUE (apolice_id, numero)
);
