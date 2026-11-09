-- Esquema y datos de demostración para una base nueva.
CREATE DATABASE IF NOT EXISTS CRM CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE CRM;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','comercial') NOT NULL DEFAULT 'comercial',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document` varchar(40) NOT NULL,
  `name` varchar(180) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `document` (`document`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(60) NOT NULL,
  `name` varchar(180) NOT NULL,
  `price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `company_settings` (
  `id` tinyint(3) unsigned NOT NULL,
  `name` varchar(180) NOT NULL,
  `nit` varchar(40) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(190) NOT NULL DEFAULT '',
  `logo` mediumblob DEFAULT NULL,
  `logo_type` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('borrador','enviada','aprobada','rechazada') NOT NULL DEFAULT 'borrador',
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `quotes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `quotes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `unit_price` decimal(14,2) NOT NULL,
  `subtotal` decimal(14,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `quote_id` (`quote_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `quote_items_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quote_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `status` enum('pendiente','confirmado','enviado','entregado') NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `quote_id` (`quote_id`),
  CONSTRAINT `fk_demo_order_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Los valores TIMESTAMP del conjunto de demostración están expresados en UTC.
SET @crm_previous_time_zone = @@SESSION.time_zone;
SET SESSION time_zone = '+00:00';
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

START TRANSACTION;

INSERT INTO users (id, name, email, password_hash, role, created_at) VALUES
    (2, 'Usuario de prueba', 'evidencia-actividad4@local.com', '$2y$10$VPB1Ia8wI5q63A8QE2w26.6Otifmk64mhG502uaO.MYhudv0JfDq2',
     'comercial', '2026-10-31 23:54:02');

INSERT INTO company_settings (id, name) VALUES
    (1, 'Lujos y Repuestos Lopez');

INSERT INTO `clients` (`id`, `document`, `name`, `email`, `phone`, `created_at`) VALUES
    (1,'12345678','Juan David Martínez Gómez','juan.martinez@latinmail.com',NULL,'2026-10-31 23:54:02'),
    (2,'23456789','María Camila Rodríguez Peña','camila.rodriguez@latinmail.com',NULL,'2026-10-31 23:54:02'),
    (3,'34567890','Andrés Felipe Ramírez Ríos','andres.ramirez@latinmail.com',NULL,'2026-10-31 23:54:02'),
    (4,'45678901','Laura Sofía Hernández Castro','laura.hernandez@latinmail.com',NULL,'2026-10-31 23:54:02'),
    (5,'56789012','Santiago Torres Quintero','santiago.torres@latinmail.com',NULL,'2026-10-31 23:54:02');
INSERT INTO `products` (`id`, `sku`, `name`, `price`, `stock`, `created_at`) VALUES
    (1,'CT-020','Tapetes 3D en caucho para Mazda CX-5 2017–2024',189900.00,14,'2026-10-31 23:54:02'),
    (2,'CT-019','Cámara de reversa HD universal IP67',119900.00,22,'2026-10-31 23:54:02'),
    (3,'CT-018','Radio multimedia 9 pulgadas con CarPlay y Android Auto',689900.00,8,'2026-10-31 23:54:02'),
    (4,'CT-017','Kit de bombillos LED H7 de 6000 K',149900.00,30,'2026-10-31 23:54:02'),
    (5,'CT-016','Funda de volante en cuero sintético de 38 cm',49900.00,25,'2026-10-31 23:54:02'),
    (6,'CT-015','Organizador plegable para baúl de 55 litros',79900.00,18,'2026-10-31 23:54:02'),
    (7,'CT-014','Sensor de parqueo de 4 puntos con pantalla',129900.00,12,'2026-10-31 23:54:02'),
    (8,'CT-013','Cámara de tablero delantera y trasera 1080p',349900.00,11,'2026-10-31 23:54:02'),
    (9,'CT-012','Soporte magnético de celular para rejilla',39900.00,35,'2026-10-31 23:54:02'),
    (10,'CT-011','Cargador de carro USB-C PD de 45 W',59900.00,28,'2026-10-31 23:54:02'),
    (11,'CT-010','Barras portaequipaje de aluminio de 120 cm',329900.00,6,'2026-10-31 23:54:02'),
    (12,'CT-009','Deflectores de ventana para Renault Duster 2021–2025',139900.00,15,'2026-10-31 23:54:02'),
    (13,'CT-008','Bandeja impermeable de baúl para Chevrolet Onix',159900.00,10,'2026-10-31 23:54:02'),
    (14,'CT-007','Protector de umbral en acero inoxidable, juego de 4',119900.00,16,'2026-10-31 23:54:02'),
    (15,'CT-006','Compresor digital de 12 V para llantas',179900.00,13,'2026-10-31 23:54:02'),
    (16,'CT-005','Iluminación LED ambiental RGB, kit de 4 tiras',89900.00,24,'2026-10-31 23:54:02'),
    (17,'CT-004','Cubreasientos universales en ecocuero, juego completo',389900.00,7,'2026-10-31 23:54:02'),
    (18,'CT-003','Alarma vehicular universal con dos controles',219900.00,9,'2026-10-31 23:54:02'),
    (19,'CT-002','Protector solar plegable para parabrisas de 145 cm',44900.00,32,'2026-10-31 23:54:02'),
    (20,'CT-001','Espejo retrovisor con pantalla táctil de 10 pulgadas',419900.00,5,'2026-10-31 23:54:02');
INSERT INTO `quotes` (`id`, `client_id`, `user_id`, `status`, `total`, `created_at`) VALUES
    (1,1,2,'aprobada',239800.00,'2026-11-01 14:00:00'),
    (2,2,2,'aprobada',809800.00,'2026-11-02 15:20:00'),
    (3,3,2,'aprobada',479700.00,'2026-11-03 16:15:00'),
    (4,4,2,'aprobada',219800.00,'2026-11-04 19:30:00'),
    (5,5,2,'aprobada',569700.00,'2026-11-05 20:10:00'),
    (6,1,2,'enviada',409800.00,'2026-11-06 15:00:00'),
    (7,4,2,'rechazada',434800.00,'2026-11-07 17:10:00'),
    (8,2,2,'borrador',549800.00,'2026-11-08 14:30:00');
INSERT INTO `quote_items` (`id`, `quote_id`, `product_id`, `quantity`, `unit_price`, `subtotal`) VALUES
    (1,1,1,1,189900.00,189900.00),
    (2,2,2,1,119900.00,119900.00),
    (3,2,3,1,689900.00,689900.00),
    (4,3,4,2,149900.00,299800.00),
    (5,1,5,1,49900.00,49900.00),
    (6,4,6,1,79900.00,79900.00),
    (7,8,7,1,129900.00,129900.00),
    (8,6,8,1,349900.00,349900.00),
    (9,6,10,1,59900.00,59900.00),
    (10,5,11,1,329900.00,329900.00),
    (11,4,12,1,139900.00,139900.00),
    (12,5,14,2,119900.00,239800.00),
    (13,3,15,1,179900.00,179900.00),
    (14,7,17,1,389900.00,389900.00),
    (15,7,19,1,44900.00,44900.00),
    (16,8,20,1,419900.00,419900.00);
INSERT INTO `orders` (`id`, `quote_id`, `status`, `created_at`) VALUES
    (1,1,'entregado','2026-11-01 18:30:00'),
    (2,2,'enviado','2026-11-02 21:05:00'),
    (3,3,'confirmado','2026-11-03 20:20:00'),
    (4,4,'pendiente','2026-11-04 22:10:00'),
    (5,5,'confirmado','2026-11-05 21:40:00');

COMMIT;
SET SESSION time_zone = @crm_previous_time_zone;
