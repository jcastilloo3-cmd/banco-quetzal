-- =====================================================================
--  BANCO QUETZAL - Sistema de Banca en Línea
--  Universidad Mariano Gálvez de Guatemala - Desarrollo Web
--  Proyecto HTML5 / CSS3 / PHP / MySQL
--  Autor: Jorge Otoniel Castillo Ortega - Carné 0900-13-719
--
--  Script completo de la base de datos:
--    * Tablas (InnoDB, utf8mb4)
--    * Procedimientos almacenados (TODO el acceso a datos pasa por SPs)
--    * Único registro inicial: el usuario administrador (BD en blanco)
--
--  Compatible con MySQL 8.x (WampServer / Aiven) y MariaDB 10.4+.
--  Importar desde phpMyAdmin (pestaña Importar) o con el cliente mysql:
--      mysql -u root -p < banco_quetzal.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS banco_quetzal
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE banco_quetzal;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS movimientos;
DROP TABLE IF EXISTS transferencias;
DROP TABLE IF EXISTS cuentas_terceros;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS cuentas;
DROP TABLE IF EXISTS cajeros;
DROP TABLE IF EXISTS administradores;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- TABLAS
-- ---------------------------------------------------------------------

CREATE TABLE administradores (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(100) NOT NULL,
  usuario     VARCHAR(30)  NOT NULL,
  clave_hash  VARCHAR(255) NOT NULL,
  creado_en   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cajeros (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre_completo  VARCHAR(100) NOT NULL,
  usuario          VARCHAR(30)  NOT NULL,
  clave_hash       VARCHAR(255) NOT NULL,
  bloqueado        TINYINT(1)   NOT NULL DEFAULT 0,
  creado_por       INT UNSIGNED NULL,
  creado_en        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cajero_usuario (usuario),
  CONSTRAINT fk_cajero_admin FOREIGN KEY (creado_por) REFERENCES administradores (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cuentas (
  id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  numero_cuenta  VARCHAR(20)   NOT NULL,
  nombre         VARCHAR(120)  NOT NULL,
  dpi            CHAR(13)      NOT NULL,
  saldo          DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  cajero_id      INT UNSIGNED  NULL,
  creado_en      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cuenta_numero (numero_cuenta),
  KEY ix_cuenta_creado (creado_en),
  CONSTRAINT fk_cuenta_cajero FOREIGN KEY (cajero_id) REFERENCES cajeros (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuarios cliente del portal (el correo es el nombre de usuario).
-- Una cuenta bancaria solo puede tener un usuario (uq_usuario_cuenta).
CREATE TABLE usuarios (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cuenta_id      INT UNSIGNED NOT NULL,
  correo         VARCHAR(120) NOT NULL,
  clave_hash     VARCHAR(255) NOT NULL,
  ultimo_acceso  DATETIME     NULL,
  creado_en      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuario_cuenta (cuenta_id),
  UNIQUE KEY uq_usuario_correo (correo),
  KEY ix_usuario_creado (creado_en),
  CONSTRAINT fk_usuario_cuenta FOREIGN KEY (cuenta_id) REFERENCES cuentas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuentas de terceros: solo existen para el usuario que las crea.
CREATE TABLE cuentas_terceros (
  id                         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  usuario_id                 INT UNSIGNED  NOT NULL,
  cuenta_id                  INT UNSIGNED  NOT NULL,
  alias                      VARCHAR(60)   NOT NULL,
  monto_maximo               DECIMAL(14,2) NOT NULL,
  max_transacciones_diarias  INT UNSIGNED  NOT NULL,
  creado_en                  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tercero_cuenta (usuario_id, cuenta_id),
  UNIQUE KEY uq_tercero_alias (usuario_id, alias),
  CONSTRAINT fk_tercero_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
  CONSTRAINT fk_tercero_cuenta  FOREIGN KEY (cuenta_id)  REFERENCES cuentas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transferencias (
  id                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  usuario_id         INT UNSIGNED  NOT NULL,
  tercero_id         INT UNSIGNED  NOT NULL,
  cuenta_origen_id   INT UNSIGNED  NOT NULL,
  cuenta_destino_id  INT UNSIGNED  NOT NULL,
  monto              DECIMAL(14,2) NOT NULL,
  creado_en          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_trf_tercero_fecha (tercero_id, creado_en),
  KEY ix_trf_fecha (creado_en),
  CONSTRAINT fk_trf_usuario FOREIGN KEY (usuario_id)        REFERENCES usuarios (id),
  CONSTRAINT fk_trf_tercero FOREIGN KEY (tercero_id)        REFERENCES cuentas_terceros (id),
  CONSTRAINT fk_trf_origen  FOREIGN KEY (cuenta_origen_id)  REFERENCES cuentas (id),
  CONSTRAINT fk_trf_destino FOREIGN KEY (cuenta_destino_id) REFERENCES cuentas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Libro de movimientos: cada débito o crédito de cada cuenta.
CREATE TABLE movimientos (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cuenta_id         INT UNSIGNED    NOT NULL,
  tipo              ENUM('DEPOSITO','RETIRO','TRANSFERENCIA_ENVIADA','TRANSFERENCIA_RECIBIDA') NOT NULL,
  naturaleza        ENUM('CREDITO','DEBITO') NOT NULL,
  monto             DECIMAL(14,2)   NOT NULL,
  saldo_resultante  DECIMAL(14,2)   NOT NULL,
  descripcion       VARCHAR(200)    NOT NULL,
  cajero_id         INT UNSIGNED    NULL,
  transferencia_id  INT UNSIGNED    NULL,
  creado_en         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_mov_cuenta_fecha (cuenta_id, creado_en),
  KEY ix_mov_tipo_fecha (tipo, creado_en),
  CONSTRAINT fk_mov_cuenta FOREIGN KEY (cuenta_id)        REFERENCES cuentas (id),
  CONSTRAINT fk_mov_cajero FOREIGN KEY (cajero_id)        REFERENCES cajeros (id),
  CONSTRAINT fk_mov_trf    FOREIGN KEY (transferencia_id) REFERENCES transferencias (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PROCEDIMIENTOS ALMACENADOS
-- Los errores de negocio se devuelven con SIGNAL SQLSTATE '45000'
-- y la aplicación PHP muestra MESSAGE_TEXT al usuario.
-- ---------------------------------------------------------------------

DROP PROCEDURE IF EXISTS sp_admin_obtener;
DROP PROCEDURE IF EXISTS sp_cajero_obtener;
DROP PROCEDURE IF EXISTS sp_cajero_obtener_por_id;
DROP PROCEDURE IF EXISTS sp_cajero_listar;
DROP PROCEDURE IF EXISTS sp_cajero_crear;
DROP PROCEDURE IF EXISTS sp_cajero_cambiar_estado;
DROP PROCEDURE IF EXISTS sp_cuenta_crear;
DROP PROCEDURE IF EXISTS sp_cuenta_consultar;
DROP PROCEDURE IF EXISTS sp_deposito;
DROP PROCEDURE IF EXISTS sp_retiro;
DROP PROCEDURE IF EXISTS sp_cajero_operaciones_recientes;
DROP PROCEDURE IF EXISTS sp_usuario_registrar;
DROP PROCEDURE IF EXISTS sp_usuario_obtener;
DROP PROCEDURE IF EXISTS sp_usuario_registrar_acceso;
DROP PROCEDURE IF EXISTS sp_usuario_resumen;
DROP PROCEDURE IF EXISTS sp_tercero_agregar;
DROP PROCEDURE IF EXISTS sp_tercero_listar;
DROP PROCEDURE IF EXISTS sp_transferir;
DROP PROCEDURE IF EXISTS sp_estado_cuenta;
DROP PROCEDURE IF EXISTS sp_monitor_resumen;
DROP PROCEDURE IF EXISTS sp_monitor_serie;

DELIMITER $$

-- ===================== ADMINISTRADOR =====================

CREATE PROCEDURE sp_admin_obtener(IN p_usuario VARCHAR(30))
BEGIN
  SELECT id, nombre, usuario, clave_hash
    FROM administradores
   WHERE usuario = p_usuario;
END$$

-- ===================== CAJEROS =====================

CREATE PROCEDURE sp_cajero_obtener(IN p_usuario VARCHAR(30))
BEGIN
  SELECT id, nombre_completo, usuario, clave_hash, bloqueado
    FROM cajeros
   WHERE usuario = p_usuario;
END$$

CREATE PROCEDURE sp_cajero_obtener_por_id(IN p_id INT UNSIGNED)
BEGIN
  SELECT id, nombre_completo, usuario, bloqueado
    FROM cajeros
   WHERE id = p_id;
END$$

CREATE PROCEDURE sp_cajero_listar()
BEGIN
  SELECT c.id, c.nombre_completo, c.usuario, c.bloqueado, c.creado_en,
         (SELECT COUNT(*) FROM movimientos m WHERE m.cajero_id = c.id) AS operaciones
    FROM cajeros c
   ORDER BY c.nombre_completo;
END$$

CREATE PROCEDURE sp_cajero_crear(
  IN p_nombre   VARCHAR(100),
  IN p_usuario  VARCHAR(30),
  IN p_hash     VARCHAR(255),
  IN p_admin_id INT UNSIGNED)
BEGIN
  IF EXISTS (SELECT 1 FROM cajeros WHERE usuario = p_usuario) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El nombre de usuario ya está asignado a otro cajero.';
  END IF;

  INSERT INTO cajeros (nombre_completo, usuario, clave_hash, creado_por)
  VALUES (p_nombre, p_usuario, p_hash, p_admin_id);

  SELECT LAST_INSERT_ID() AS id;
END$$

CREATE PROCEDURE sp_cajero_cambiar_estado(IN p_id INT UNSIGNED, IN p_bloqueado TINYINT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM cajeros WHERE id = p_id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El cajero no existe.';
  END IF;

  UPDATE cajeros SET bloqueado = IF(p_bloqueado = 1, 1, 0) WHERE id = p_id;

  SELECT id, usuario, bloqueado FROM cajeros WHERE id = p_id;
END$$

-- ===================== CUENTAS / CAJA =====================

CREATE PROCEDURE sp_cuenta_crear(
  IN p_numero    VARCHAR(20),
  IN p_nombre    VARCHAR(120),
  IN p_dpi       CHAR(13),
  IN p_monto     DECIMAL(14,2),
  IN p_cajero_id INT UNSIGNED)
BEGIN
  DECLARE v_cuenta_id INT UNSIGNED;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_monto IS NULL OR p_monto < 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El monto inicial no puede ser negativo.';
  END IF;

  START TRANSACTION;

  IF EXISTS (SELECT 1 FROM cuentas WHERE numero_cuenta = p_numero) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya existe una cuenta con ese número.';
  END IF;

  INSERT INTO cuentas (numero_cuenta, nombre, dpi, saldo, cajero_id)
  VALUES (p_numero, p_nombre, p_dpi, p_monto, p_cajero_id);

  SET v_cuenta_id = LAST_INSERT_ID();

  -- El monto inicial queda registrado como el primer depósito de la cuenta
  IF p_monto > 0 THEN
    INSERT INTO movimientos (cuenta_id, tipo, naturaleza, monto, saldo_resultante, descripcion, cajero_id)
    VALUES (v_cuenta_id, 'DEPOSITO', 'CREDITO', p_monto, p_monto,
            'Depósito inicial por apertura de cuenta', p_cajero_id);
  END IF;

  COMMIT;

  SELECT id, numero_cuenta, nombre, dpi, saldo, creado_en
    FROM cuentas WHERE id = v_cuenta_id;
END$$

CREATE PROCEDURE sp_cuenta_consultar(IN p_numero VARCHAR(20))
BEGIN
  SELECT id, numero_cuenta, nombre, saldo
    FROM cuentas
   WHERE numero_cuenta = p_numero;
END$$

CREATE PROCEDURE sp_deposito(
  IN p_numero    VARCHAR(20),
  IN p_monto     DECIMAL(14,2),
  IN p_cajero_id INT UNSIGNED)
BEGIN
  DECLARE v_id    INT UNSIGNED DEFAULT NULL;
  DECLARE v_saldo DECIMAL(14,2);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_monto IS NULL OR p_monto <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El monto a depositar debe ser mayor que cero.';
  END IF;

  START TRANSACTION;

  SELECT id, saldo INTO v_id, v_saldo
    FROM cuentas WHERE numero_cuenta = p_numero
    FOR UPDATE;

  IF v_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cuenta bancaria no existe.';
  END IF;

  SET v_saldo = v_saldo + p_monto;

  UPDATE cuentas SET saldo = v_saldo WHERE id = v_id;

  INSERT INTO movimientos (cuenta_id, tipo, naturaleza, monto, saldo_resultante, descripcion, cajero_id)
  VALUES (v_id, 'DEPOSITO', 'CREDITO', p_monto, v_saldo, 'Depósito en ventanilla', p_cajero_id);

  COMMIT;

  SELECT numero_cuenta, nombre, saldo FROM cuentas WHERE id = v_id;
END$$

CREATE PROCEDURE sp_retiro(
  IN p_numero    VARCHAR(20),
  IN p_monto     DECIMAL(14,2),
  IN p_cajero_id INT UNSIGNED)
BEGIN
  DECLARE v_id    INT UNSIGNED DEFAULT NULL;
  DECLARE v_saldo DECIMAL(14,2);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_monto IS NULL OR p_monto <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El monto a retirar debe ser mayor que cero.';
  END IF;

  START TRANSACTION;

  SELECT id, saldo INTO v_id, v_saldo
    FROM cuentas WHERE numero_cuenta = p_numero
    FOR UPDATE;

  IF v_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cuenta bancaria no existe.';
  END IF;

  IF v_saldo < p_monto THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fondos insuficientes para realizar el retiro.';
  END IF;

  SET v_saldo = v_saldo - p_monto;

  UPDATE cuentas SET saldo = v_saldo WHERE id = v_id;

  INSERT INTO movimientos (cuenta_id, tipo, naturaleza, monto, saldo_resultante, descripcion, cajero_id)
  VALUES (v_id, 'RETIRO', 'DEBITO', p_monto, v_saldo, 'Retiro en ventanilla', p_cajero_id);

  COMMIT;

  SELECT numero_cuenta, nombre, saldo FROM cuentas WHERE id = v_id;
END$$

CREATE PROCEDURE sp_cajero_operaciones_recientes(IN p_cajero_id INT UNSIGNED)
BEGIN
  SELECT m.creado_en, m.tipo, m.monto, c.numero_cuenta, c.nombre
    FROM movimientos m
    JOIN cuentas c ON c.id = m.cuenta_id
   WHERE m.cajero_id = p_cajero_id
   ORDER BY m.id DESC
   LIMIT 10;
END$$

-- ===================== USUARIOS CLIENTE =====================

CREATE PROCEDURE sp_usuario_registrar(
  IN p_numero VARCHAR(20),
  IN p_correo VARCHAR(120),
  IN p_dpi    CHAR(13),
  IN p_hash   VARCHAR(255))
BEGIN
  DECLARE v_cuenta_id INT UNSIGNED DEFAULT NULL;
  DECLARE v_dpi       CHAR(13);

  SELECT id, dpi INTO v_cuenta_id, v_dpi
    FROM cuentas WHERE numero_cuenta = p_numero;

  -- 1) La cuenta debe haber sido creada antes por un cajero
  IF v_cuenta_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cuenta bancaria no existe. Primero debe abrirla en una agencia del banco.';
  END IF;

  -- 2) El DPI debe coincidir con el registrado en la cuenta
  IF v_dpi <> p_dpi THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El DPI no coincide con el registrado en la cuenta.';
  END IF;

  -- 3) No debe existir otro usuario para esa cuenta
  IF EXISTS (SELECT 1 FROM usuarios WHERE cuenta_id = v_cuenta_id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya existe un usuario registrado para esta cuenta.';
  END IF;

  -- 4) El correo (nombre de usuario) debe ser único
  IF EXISTS (SELECT 1 FROM usuarios WHERE correo = p_correo) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El correo electrónico ya está registrado.';
  END IF;

  INSERT INTO usuarios (cuenta_id, correo, clave_hash)
  VALUES (v_cuenta_id, p_correo, p_hash);

  SELECT LAST_INSERT_ID() AS id;
END$$

CREATE PROCEDURE sp_usuario_obtener(IN p_correo VARCHAR(120))
BEGIN
  SELECT u.id, u.correo, u.clave_hash, u.cuenta_id, c.numero_cuenta, c.nombre
    FROM usuarios u
    JOIN cuentas c ON c.id = u.cuenta_id
   WHERE u.correo = p_correo;
END$$

CREATE PROCEDURE sp_usuario_registrar_acceso(IN p_id INT UNSIGNED)
BEGIN
  UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = p_id;
END$$

CREATE PROCEDURE sp_usuario_resumen(IN p_usuario_id INT UNSIGNED)
BEGIN
  SELECT u.id, u.correo, u.ultimo_acceso, c.numero_cuenta, c.nombre, c.saldo,
         (SELECT COUNT(*) FROM cuentas_terceros t WHERE t.usuario_id = u.id) AS terceros,
         (SELECT COUNT(*) FROM transferencias tr WHERE tr.usuario_id = u.id
             AND DATE(tr.creado_en) = CURDATE()) AS transferencias_hoy
    FROM usuarios u
    JOIN cuentas c ON c.id = u.cuenta_id
   WHERE u.id = p_usuario_id;
END$$

-- ===================== CUENTAS DE TERCEROS =====================

CREATE PROCEDURE sp_tercero_agregar(
  IN p_usuario_id INT UNSIGNED,
  IN p_numero     VARCHAR(20),
  IN p_monto_max  DECIMAL(14,2),
  IN p_max_trans  INT UNSIGNED,
  IN p_alias      VARCHAR(60))
BEGIN
  DECLARE v_cuenta_id  INT UNSIGNED DEFAULT NULL;
  DECLARE v_cuenta_own INT UNSIGNED DEFAULT NULL;

  IF p_monto_max IS NULL OR p_monto_max <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El monto máximo debe ser mayor que cero.';
  END IF;

  IF p_max_trans IS NULL OR p_max_trans < 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cantidad máxima de transacciones diarias debe ser al menos 1.';
  END IF;

  SELECT id INTO v_cuenta_id FROM cuentas WHERE numero_cuenta = p_numero;

  IF v_cuenta_id IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cuenta bancaria ingresada no existe.';
  END IF;

  SELECT cuenta_id INTO v_cuenta_own FROM usuarios WHERE id = p_usuario_id;

  IF v_cuenta_own = v_cuenta_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No puede agregar su propia cuenta como cuenta de tercero.';
  END IF;

  IF EXISTS (SELECT 1 FROM cuentas_terceros WHERE usuario_id = p_usuario_id AND cuenta_id = v_cuenta_id) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Esa cuenta ya está registrada en sus cuentas de terceros.';
  END IF;

  IF EXISTS (SELECT 1 FROM cuentas_terceros WHERE usuario_id = p_usuario_id AND alias = p_alias) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya tiene una cuenta de tercero con ese alias.';
  END IF;

  INSERT INTO cuentas_terceros (usuario_id, cuenta_id, alias, monto_maximo, max_transacciones_diarias)
  VALUES (p_usuario_id, v_cuenta_id, p_alias, p_monto_max, p_max_trans);

  SELECT LAST_INSERT_ID() AS id;
END$$

-- Solo devuelve las cuentas de terceros del usuario indicado
CREATE PROCEDURE sp_tercero_listar(IN p_usuario_id INT UNSIGNED)
BEGIN
  SELECT t.id, t.alias, c.numero_cuenta, c.nombre AS titular,
         t.monto_maximo, t.max_transacciones_diarias, t.creado_en,
         (SELECT COUNT(*) FROM transferencias tr
           WHERE tr.tercero_id = t.id AND DATE(tr.creado_en) = CURDATE()) AS transacciones_hoy
    FROM cuentas_terceros t
    JOIN cuentas c ON c.id = t.cuenta_id
   WHERE t.usuario_id = p_usuario_id
   ORDER BY t.alias;
END$$

-- ===================== TRANSFERENCIA (contexto de transacción) =====================

CREATE PROCEDURE sp_transferir(
  IN p_usuario_id INT UNSIGNED,
  IN p_tercero_id INT UNSIGNED,
  IN p_monto      DECIMAL(14,2))
BEGIN
  DECLARE v_origen      INT UNSIGNED DEFAULT NULL;
  DECLARE v_destino     INT UNSIGNED DEFAULT NULL;
  DECLARE v_max_monto   DECIMAL(14,2);
  DECLARE v_max_trans   INT UNSIGNED;
  DECLARE v_alias       VARCHAR(60);
  DECLARE v_hoy         INT UNSIGNED DEFAULT 0;
  DECLARE v_saldo_ori   DECIMAL(14,2);
  DECLARE v_saldo_des   DECIMAL(14,2);
  DECLARE v_num_ori     VARCHAR(20);
  DECLARE v_num_des     VARCHAR(20);
  DECLARE v_trf_id      INT UNSIGNED;
  DECLARE v_msg         VARCHAR(255);

  -- Cualquier error deshace TODA la operación
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  IF p_monto IS NULL OR p_monto <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El monto a transferir debe ser mayor que cero.';
  END IF;

  START TRANSACTION;

  SELECT cuenta_id INTO v_origen FROM usuarios WHERE id = p_usuario_id;

  -- La cuenta de tercero debe pertenecer al usuario conectado (se bloquea la fila
  -- para que dos transferencias simultáneas no superen el límite diario)
  SELECT cuenta_id, monto_maximo, max_transacciones_diarias, alias
    INTO v_destino, v_max_monto, v_max_trans, v_alias
    FROM cuentas_terceros
   WHERE id = p_tercero_id AND usuario_id = p_usuario_id
   FOR UPDATE;

  IF v_origen IS NULL OR v_destino IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cuenta de tercero seleccionada no es válida.';
  END IF;

  -- Validación 1: monto máximo por operación
  IF p_monto > v_max_monto THEN
    SET v_msg = CONCAT('El monto supera el máximo permitido para "', v_alias,
                       '" (Q ', FORMAT(v_max_monto, 2), ' por transferencia).');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- Validación 2: cantidad máxima de transferencias diarias a esa cuenta
  SELECT COUNT(*) INTO v_hoy
    FROM transferencias
   WHERE tercero_id = p_tercero_id
     AND creado_en >= CURDATE()
     AND creado_en <  CURDATE() + INTERVAL 1 DAY;

  IF v_hoy >= v_max_trans THEN
    SET v_msg = CONCAT('Ya alcanzó el máximo de ', v_max_trans,
                       ' transferencia(s) diarias hacia "', v_alias, '".');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- Bloqueo de ambas cuentas en orden de id (evita interbloqueos)
  IF v_origen < v_destino THEN
    SELECT saldo, numero_cuenta INTO v_saldo_ori, v_num_ori FROM cuentas WHERE id = v_origen  FOR UPDATE;
    SELECT saldo, numero_cuenta INTO v_saldo_des, v_num_des FROM cuentas WHERE id = v_destino FOR UPDATE;
  ELSE
    SELECT saldo, numero_cuenta INTO v_saldo_des, v_num_des FROM cuentas WHERE id = v_destino FOR UPDATE;
    SELECT saldo, numero_cuenta INTO v_saldo_ori, v_num_ori FROM cuentas WHERE id = v_origen  FOR UPDATE;
  END IF;

  -- Validación 3: fondos suficientes
  IF v_saldo_ori < p_monto THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fondos insuficientes para realizar la transferencia.';
  END IF;

  SET v_saldo_ori = v_saldo_ori - p_monto;
  SET v_saldo_des = v_saldo_des + p_monto;

  UPDATE cuentas SET saldo = v_saldo_ori WHERE id = v_origen;
  UPDATE cuentas SET saldo = v_saldo_des WHERE id = v_destino;

  INSERT INTO transferencias (usuario_id, tercero_id, cuenta_origen_id, cuenta_destino_id, monto)
  VALUES (p_usuario_id, p_tercero_id, v_origen, v_destino, p_monto);

  SET v_trf_id = LAST_INSERT_ID();

  INSERT INTO movimientos (cuenta_id, tipo, naturaleza, monto, saldo_resultante, descripcion, transferencia_id)
  VALUES (v_origen, 'TRANSFERENCIA_ENVIADA', 'DEBITO', p_monto, v_saldo_ori,
          CONCAT('Transferencia a ', v_alias, ' (cta. ', v_num_des, ')'), v_trf_id);

  INSERT INTO movimientos (cuenta_id, tipo, naturaleza, monto, saldo_resultante, descripcion, transferencia_id)
  VALUES (v_destino, 'TRANSFERENCIA_RECIBIDA', 'CREDITO', p_monto, v_saldo_des,
          CONCAT('Transferencia recibida de cta. ', v_num_ori), v_trf_id);

  COMMIT;

  SELECT v_trf_id AS transferencia_id, v_alias AS alias, v_num_des AS cuenta_destino,
         p_monto AS monto, v_saldo_ori AS saldo_disponible;
END$$

-- ===================== ESTADO DE CUENTA =====================

CREATE PROCEDURE sp_estado_cuenta(
  IN p_usuario_id INT UNSIGNED,
  IN p_desde      DATE,
  IN p_hasta      DATE)
BEGIN
  SELECT m.id, m.creado_en, m.tipo, m.naturaleza, m.descripcion,
         IF(m.naturaleza = 'DEBITO',  m.monto, NULL) AS debito,
         IF(m.naturaleza = 'CREDITO', m.monto, NULL) AS credito,
         m.saldo_resultante
    FROM movimientos m
    JOIN usuarios u ON u.cuenta_id = m.cuenta_id
   WHERE u.id = p_usuario_id
     AND (p_desde IS NULL OR m.creado_en >= p_desde)
     AND (p_hasta IS NULL OR m.creado_en <  p_hasta + INTERVAL 1 DAY)
   ORDER BY m.creado_en, m.id;
END$$

-- ===================== MONITOR (ADMINISTRADOR) =====================

CREATE PROCEDURE sp_monitor_resumen(IN p_fecha DATE)
BEGIN
  DECLARE v_ini DATETIME;
  DECLARE v_fin DATETIME;
  SET v_ini = p_fecha;
  SET v_fin = p_fecha + INTERVAL 1 DAY;

  SELECT
    (SELECT COUNT(*) FROM cuentas  WHERE creado_en >= v_ini AND creado_en < v_fin) AS cuentas_creadas,
    (SELECT COUNT(*) FROM usuarios WHERE creado_en >= v_ini AND creado_en < v_fin) AS usuarios_registrados,
    (SELECT COUNT(*) FROM movimientos WHERE tipo IN ('DEPOSITO','RETIRO')
                                       AND creado_en >= v_ini AND creado_en < v_fin)
      + (SELECT COUNT(*) FROM transferencias WHERE creado_en >= v_ini AND creado_en < v_fin) AS transacciones,
    (SELECT COUNT(*) FROM movimientos WHERE tipo = 'DEPOSITO' AND creado_en >= v_ini AND creado_en < v_fin) AS depositos,
    (SELECT COUNT(*) FROM movimientos WHERE tipo = 'RETIRO'   AND creado_en >= v_ini AND creado_en < v_fin) AS retiros,
    (SELECT COUNT(*) FROM transferencias WHERE creado_en >= v_ini AND creado_en < v_fin) AS transferencias,
    (SELECT COALESCE(SUM(monto),0) FROM movimientos WHERE tipo = 'DEPOSITO' AND creado_en >= v_ini AND creado_en < v_fin) AS monto_depositos,
    (SELECT COALESCE(SUM(monto),0) FROM movimientos WHERE tipo = 'RETIRO'   AND creado_en >= v_ini AND creado_en < v_fin) AS monto_retiros,
    (SELECT COALESCE(SUM(monto),0) FROM transferencias WHERE creado_en >= v_ini AND creado_en < v_fin) AS monto_transferencias;
END$$

-- Montos diarios de depósitos y retiros de los últimos p_dias días hasta p_fecha
CREATE PROCEDURE sp_monitor_serie(IN p_fecha DATE, IN p_dias INT)
BEGIN
  SELECT DATE(creado_en) AS fecha,
         SUM(IF(tipo = 'DEPOSITO', monto, 0)) AS monto_depositos,
         SUM(IF(tipo = 'RETIRO',   monto, 0)) AS monto_retiros
    FROM movimientos
   WHERE tipo IN ('DEPOSITO','RETIRO')
     AND creado_en >= p_fecha - INTERVAL (p_dias - 1) DAY
     AND creado_en <  p_fecha + INTERVAL 1 DAY
   GROUP BY DATE(creado_en)
   ORDER BY fecha;
END$$

DELIMITER ;

-- ---------------------------------------------------------------------
-- DATOS INICIALES: solo el administrador (base de datos en blanco)
--   Usuario: admin
--   Clave:   BancoQ2026!   (se guarda como hash bcrypt)
-- ---------------------------------------------------------------------
INSERT INTO administradores (nombre, usuario, clave_hash)
VALUES ('Administrador del Sistema', 'admin',
        '$2y$10$/GWkOakkrHef30XbSzRQiOOMm.i0sbeky1CKu69140xLHzx6t2sr.');
