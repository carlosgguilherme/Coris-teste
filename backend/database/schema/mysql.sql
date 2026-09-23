CREATE TABLE IF NOT EXISTS apolices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL,
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
    atualizado_em DATETIME NULL,
    UNIQUE KEY uk_apolices_numero (numero),
    KEY idx_apolices_cpf (segurado_cpf),
    KEY idx_apolices_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
