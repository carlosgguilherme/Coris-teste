CREATE TABLE IF NOT EXISTS apolices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero VARCHAR(20) NOT NULL UNIQUE,
    segurado_nome VARCHAR(120) NOT NULL,
    segurado_cpf CHAR(11) NOT NULL,
    segurado_email VARCHAR(150) NOT NULL,
    segurado_nascimento DATE NOT NULL,
    destino VARCHAR(30) NOT NULL,
    plano VARCHAR(20) NOT NULL,
    inicio_vigencia DATE NOT NULL,
    fim_vigencia DATE NOT NULL,
    valor_premio DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ativa',
    criado_em DATETIME NOT NULL,
    atualizado_em DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_apolices_cpf ON apolices (segurado_cpf);
CREATE INDEX IF NOT EXISTS idx_apolices_status ON apolices (status);
