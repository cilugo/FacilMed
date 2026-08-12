--- dashboard ---

CREATE DATABASE facilmed;

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

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);


-- ==========================================
-- MÉDICOS
-- ==========================================

CREATE TABLE medicos (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    crm VARCHAR(20) NOT NULL,

    uf CHAR(2) NOT NULL,

    especialidade VARCHAR(100),

    status_profissional VARCHAR(30)
        DEFAULT 'Ativo',

    anos_atuacao INT DEFAULT 0,

    FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)

);


-- ==========================================
-- PACIENTES
-- ==========================================

CREATE TABLE pacientes (

    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,

    data_nascimento DATE,

    FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)

);


-- ==========================================
-- CONSULTAS
-- ==========================================

CREATE TABLE consultas (

    id INT AUTO_INCREMENT PRIMARY KEY,

    paciente_id INT NOT NULL,

    medico_id INT NOT NULL,

    data_consulta DATE NOT NULL,

    horario TIME NOT NULL,

    local VARCHAR(150),

    status ENUM(
        'Agendada',
        'Realizada',
        'Cancelada'
    ) DEFAULT 'Agendada',

    valor DECIMAL(10,2) DEFAULT 0,

    FOREIGN KEY (paciente_id)
        REFERENCES pacientes(id),

    FOREIGN KEY (medico_id)
        REFERENCES medicos(id)

);

