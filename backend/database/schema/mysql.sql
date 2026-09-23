CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    criado_em DATETIME NOT NULL,
    UNIQUE KEY uk_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS segurados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    cpf CHAR(11) NOT NULL,
    email VARCHAR(150) NOT NULL,
    data_nascimento DATE NOT NULL,
    criado_em DATETIME NOT NULL,
    atualizado_em DATETIME NULL,
    UNIQUE KEY uk_segurados_cpf (cpf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS apolices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL,
    segurado_id INT UNSIGNED NOT NULL,
    destino VARCHAR(30) NOT NULL,
    plano VARCHAR(20) NOT NULL,
    inicio_vigencia DATE NOT NULL,
    fim_vigencia DATE NOT NULL,
    valor_premio_centavos INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'ativa',
    criado_em DATETIME NOT NULL,
    atualizado_em DATETIME NULL,
    excluido_em DATETIME NULL,
    UNIQUE KEY uk_apolices_numero (numero),
    KEY idx_apolices_status (status),
    KEY idx_apolices_excluido_em (excluido_em),
    CONSTRAINT fk_apolices_segurado FOREIGN KEY (segurado_id) REFERENCES segurados (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS endossos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    apolice_id INT UNSIGNED NOT NULL,
    numero INT UNSIGNED NOT NULL,
    alteracoes TEXT NOT NULL,
    premio_anterior_centavos INT UNSIGNED NOT NULL,
    premio_novo_centavos INT UNSIGNED NOT NULL,
    usuario VARCHAR(150) NOT NULL,
    criado_em DATETIME NOT NULL,
    UNIQUE KEY uk_endossos_apolice_numero (apolice_id, numero),
    CONSTRAINT fk_endossos_apolice FOREIGN KEY (apolice_id) REFERENCES apolices (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
