-- =========================================================
-- BANCO DE DADOS DO FACILMED
-- Schema completo (v2): especialidades, locais, convênios,
-- planos e recuperação de senha em tabelas próprias.
--
-- Observações sobre adaptações feitas em cima do modelo
-- original (ver "BANCO DE DADOS DO FACILMED - MySQL
-- COMPUTADOR 27.docx"), para não quebrar o código já
-- existente:
--   - tipo do usuário continua ENUM('paciente','medico','admin')
--     (o código inteiro já checa a string 'admin', não
--     'administrador').
--   - medicos.anos_atuacao foi mantido (já usado no dashboard
--     do médico) e não existia no modelo original.
--   - consultas.tipo_consulta (Presencial/Teleconsulta) foi
--     mantido junto com o novo tipo_atendimento (SUS/convênio/
--     particular) — são conceitos diferentes (modalidade x
--     forma de pagamento) e a tela de agendamento já usava
--     tipo_consulta.
--   - consultas.status manteve os valores em português com
--     inicial maiúscula ('Agendada','Realizada','Cancelada'),
--     que é o que o painel do médico já compara.
-- =========================================================

CREATE DATABASE IF NOT EXISTS facilmed CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE facilmed;

-- ==========================================
-- USUÁRIOS
-- ==========================================

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('paciente', 'medico', 'admin') NOT NULL,
    status ENUM('ativo', 'inativo', 'ferias', 'bloqueado') NOT NULL DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- ESPECIALIDADES
-- ==========================================

CREATE TABLE especialidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE
);

-- ==========================================
-- MÉDICOS
-- ==========================================

CREATE TABLE medicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    crm VARCHAR(20) NOT NULL,
    uf CHAR(2) NOT NULL,
    especialidade_id INT,
    status_profissional ENUM('pendente', 'ativo', 'inativo') NOT NULL DEFAULT 'pendente',
    anos_atuacao INT DEFAULT 0,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (especialidade_id) REFERENCES especialidades(id) ON DELETE SET NULL,
    UNIQUE (crm, uf)
);

-- ==========================================
-- PACIENTES
-- ==========================================

CREATE TABLE pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    data_nascimento DATE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- ==========================================
-- LOCAIS (hospitais e clínicas)
-- ==========================================

CREATE TABLE locais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    tipo ENUM('hospital', 'clinica') NOT NULL,
    categoria ENUM('publico', 'privado') NOT NULL,
    endereco VARCHAR(200),
    cidade VARCHAR(100),
    bairro VARCHAR(100),
    estado CHAR(2),
    telefone VARCHAR(20),
    ativo BOOLEAN NOT NULL DEFAULT TRUE
);

-- ==========================================
-- CONVÊNIOS
-- ==========================================

CREATE TABLE convenios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN NOT NULL DEFAULT TRUE
);

-- ==========================================
-- PLANOS (vinculados a um convênio)
-- ==========================================

CREATE TABLE planos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    convenio_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    valor DECIMAL(10,2) DEFAULT 0.00,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (convenio_id) REFERENCES convenios(id) ON DELETE CASCADE
);

-- ==========================================
-- CONSULTAS
-- ==========================================

CREATE TABLE consultas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    local_id INT,
    convenio_id INT,
    plano_id INT,
    data_consulta DATE NOT NULL,
    horario TIME NOT NULL,
    tipo_atendimento ENUM('SUS', 'convenio', 'particular') NOT NULL DEFAULT 'particular',
    tipo_consulta VARCHAR(50),
    valor DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('Agendada', 'Realizada', 'Cancelada') DEFAULT 'Agendada',
    observacoes TEXT,
    data_agendamento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (medico_id) REFERENCES medicos(id),
    FOREIGN KEY (local_id) REFERENCES locais(id) ON DELETE SET NULL,
    FOREIGN KEY (convenio_id) REFERENCES convenios(id) ON DELETE SET NULL,
    FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE SET NULL
);

-- ==========================================
-- RECUPERAÇÃO DE SENHA
-- (antes eram 2 colunas soltas em usuarios; agora fica
-- em tabela própria, permitindo histórico e múltiplos
-- códigos por usuário sem se sobrescreverem)
-- ==========================================

CREATE TABLE recuperacao_senha (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    codigo VARCHAR(6) NOT NULL,
    expiracao DATETIME NOT NULL,
    utilizado BOOLEAN NOT NULL DEFAULT FALSE,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- ==========================================
-- MIGRAÇÃO (rode isto apenas se o banco "facilmed" já
-- existia ANTES desta versão v2 do schema; se você recriar
-- o banco do zero com o script acima, NÃO precisa rodar
-- as linhas abaixo)
-- ==========================================

-- ALTER TABLE usuarios ADD COLUMN status ENUM('ativo','inativo','ferias','bloqueado') NOT NULL DEFAULT 'ativo';
-- ALTER TABLE usuarios DROP COLUMN codigo_recuperacao, DROP COLUMN codigo_expira;
-- ALTER TABLE medicos ADD COLUMN especialidade_id INT, ADD FOREIGN KEY (especialidade_id) REFERENCES especialidades(id) ON DELETE SET NULL;
-- ALTER TABLE medicos ADD UNIQUE (crm, uf);
-- ALTER TABLE medicos DROP COLUMN especialidade; -- (coluna antiga em texto livre)
-- ALTER TABLE consultas ADD COLUMN local_id INT, ADD COLUMN convenio_id INT, ADD COLUMN plano_id INT,
--     ADD COLUMN tipo_atendimento ENUM('SUS','convenio','particular') NOT NULL DEFAULT 'particular',
--     ADD FOREIGN KEY (local_id) REFERENCES locais(id) ON DELETE SET NULL,
--     ADD FOREIGN KEY (convenio_id) REFERENCES convenios(id) ON DELETE SET NULL,
--     ADD FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE SET NULL;
-- ALTER TABLE consultas DROP COLUMN especialidade, DROP COLUMN local; -- (colunas antigas em texto livre)

-- ==========================================
-- ESPECIALIDADES INICIAIS
-- ==========================================

INSERT INTO especialidades (nome) VALUES
('Cardiologia'),
('Dermatologia'),
('Pediatria'),
('Ortopedia'),
('Ginecologia'),
('Neurologia'),
('Oftalmologia'),
('Psiquiatria'),
('Endocrinologia'),
('Clínica Geral');

-- ==========================================
-- CONVÊNIOS E PLANOS DE EXEMPLO
-- ==========================================

INSERT INTO convenios (nome, descricao) VALUES
('Unimed', 'Plano de saúde'),
('Amil', 'Plano de saúde'),
('Bradesco Saúde', 'Plano de saúde');

INSERT INTO planos (convenio_id, nome, descricao, valor) VALUES
(1, 'Unimed Nacional', 'Cobertura em todo o território nacional', 180.00),
(1, 'Unimed Regional', 'Cobertura na região metropolitana', 120.00),
(2, 'Amil One', 'Rede referenciada ampliada', 220.00),
(3, 'Bradesco Top Nacional', 'Rede nacional ampliada', 250.00);

-- ==========================================
-- LOCAIS DE EXEMPLO
-- ==========================================

INSERT INTO locais (nome, tipo, categoria, endereco, cidade, bairro, estado, telefone) VALUES
('Hospital Municipal São José', 'hospital', 'publico', 'Av. Principal, 1000', 'São José dos Campos', 'Centro', 'SP', '(12) 3211-0000'),
('Clínica FacilMed Vila Nova', 'clinica', 'privado', 'Rua das Flores, 250', 'São José dos Campos', 'Vila Nova', 'SP', '(12) 3211-1111');

-- ==========================================
-- Inserção de usuário de teste
-- ==========================================

-- Senha de teste: "alca12" (hash bcrypt válido, compatível com password_verify do PHP)
INSERT INTO usuarios (nome, cpf, email, telefone, senha, tipo) VALUES
('Marcelo', '123.456.789-10', 'marcelo@gmail.com', '12 98041 3375', '$2b$12$X5wW1dD2MuYF7yUKwws71u.5Cq7FDKPdnEElUKGGyP1Lk91SARX4.', 'paciente');

SELECT * FROM usuarios;
