-- Adicionar campo 'usuario' à tabela de usuários se ainda não existir
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS usuario VARCHAR(50) UNIQUE;

-- Se o campo já existir mas não for UNIQUE, adicionar a restrição
-- (Isso pode falhar se houver valores duplicados)
-- ALTER TABLE usuarios ADD UNIQUE (usuario);

-- Preencher o campo 'usuario' com base no email para usuários existentes
-- que ainda não têm um nome de usuário definido
UPDATE usuarios 
SET usuario = SUBSTRING_INDEX(email, '@', 1) 
WHERE usuario IS NULL OR usuario = '';

-- Atualizar a tabela de tentativas de login para usar nome de usuário
CREATE TABLE IF NOT EXISTS tentativas_login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criar tabela de log de atividades se ainda não existir
CREATE TABLE IF NOT EXISTS log_atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    acao VARCHAR(50) NOT NULL,
    descricao TEXT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    data_hora DATETIME NOT NULL
);
