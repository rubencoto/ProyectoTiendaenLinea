-- Fix for clientes table structure
-- Run these SQL commands in your database

-- Add missing columns
ALTER TABLE clientes ADD COLUMN apellidos VARCHAR(100) AFTER nombre;
ALTER TABLE clientes ADD COLUMN cedula VARCHAR(20) UNIQUE;
ALTER TABLE clientes ADD COLUMN provincia VARCHAR(50);
ALTER TABLE clientes ADD COLUMN fecha_nacimiento DATE;
ALTER TABLE clientes ADD COLUMN genero CHAR(1);
ALTER TABLE clientes ADD COLUMN newsletter BOOLEAN DEFAULT FALSE;
ALTER TABLE clientes ADD COLUMN reset_token VARCHAR(100);
ALTER TABLE clientes ADD COLUMN token_expira DATETIME;
ALTER TABLE clientes ADD COLUMN codigo_expira DATETIME;

-- If you have 'apellido' column, rename it to 'apellidos'
-- ALTER TABLE clientes CHANGE apellido apellidos VARCHAR(100) NOT NULL;
