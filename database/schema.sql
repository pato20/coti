-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: cerco_electrico_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `actividades_usuario`
--

DROP TABLE IF EXISTS `actividades_usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actividades_usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario_fecha` (`usuario_id`,`created_at`),
  KEY `idx_accion` (`accion`),
  CONSTRAINT `actividades_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actividades_usuario`
--

LOCK TABLES `actividades_usuario` WRITE;
/*!40000 ALTER TABLE `actividades_usuario` DISABLE KEYS */;
/*!40000 ALTER TABLE `actividades_usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agenda`
--

DROP TABLE IF EXISTS `agenda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agenda` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `cotizacion_id` int(11) DEFAULT NULL,
  `orden_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_hora_inicio` datetime NOT NULL,
  `fecha_hora_fin` datetime DEFAULT NULL,
  `estado` enum('pendiente','completada','cancelada') NOT NULL DEFAULT 'pendiente',
  `tipo` enum('visita','mantencion') NOT NULL DEFAULT 'visita',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `cotizacion_id` (`cotizacion_id`),
  KEY `orden_id` (`orden_id`),
  CONSTRAINT `agenda_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agenda_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agenda_ibfk_3` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agenda_ibfk_4` FOREIGN KEY (`orden_id`) REFERENCES `ordenes_trabajo` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agenda`
--

LOCK TABLES `agenda` WRITE;
/*!40000 ALTER TABLE `agenda` DISABLE KEYS */;
INSERT INTO `agenda` VALUES (13,1,3,NULL,NULL,'erwr','','2025-09-15 03:56:00',NULL,'pendiente','visita','2025-09-15 02:52:46'),(14,1,NULL,NULL,NULL,'rrrrrrrrrr - Mantención Trimestra','','2025-09-16 23:56:00',NULL,'pendiente','visita','2025-09-15 02:57:03');
/*!40000 ALTER TABLE `agenda` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agenda_log`
--

DROP TABLE IF EXISTS `agenda_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agenda_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `evento_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_cambio` varchar(50) NOT NULL,
  `motivo` text DEFAULT NULL,
  `datos_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_anteriores`)),
  PRIMARY KEY (`id`),
  KEY `evento_id` (`evento_id`),
  CONSTRAINT `agenda_log_ibfk_1` FOREIGN KEY (`evento_id`) REFERENCES `agenda` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agenda_log`
--

LOCK TABLES `agenda_log` WRITE;
/*!40000 ALTER TABLE `agenda_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `agenda_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('producto','servicio') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Cercos Eléctricos','servicio','2025-08-27 02:43:36'),(2,'Materiales Eléctricos','producto','2025-08-27 02:43:36'),(3,'Accesorios','producto','2025-08-27 02:43:36'),(4,'Mano de obra','servicio','2025-09-04 01:53:52'),(5,'Soldadura','servicio','2025-09-04 01:57:18'),(6,'Computación','servicio','2025-09-12 00:30:02');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cerco_electrico_config`
--

DROP TABLE IF EXISTS `cerco_electrico_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cerco_electrico_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cotizacion_id` int(11) DEFAULT NULL,
  `metros_lineales` decimal(8,2) NOT NULL,
  `numero_hilos` enum('4','5','6') NOT NULL,
  `tipo_instalacion` enum('basica','media','compleja') NOT NULL,
  `altura_muro` decimal(5,2) DEFAULT NULL,
  `necesita_postes` tinyint(1) DEFAULT 0,
  `cantidad_postes` int(11) DEFAULT 0,
  `necesita_andamios` tinyint(1) DEFAULT 0,
  `certificacion_sec` tinyint(1) DEFAULT 0,
  `observaciones_tecnicas` text DEFAULT NULL,
  `precio_mano_obra_metro` decimal(8,2) DEFAULT NULL,
  `precio_total_mano_obra` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cotizacion_id` (`cotizacion_id`),
  CONSTRAINT `cerco_electrico_config_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cerco_electrico_config`
--

LOCK TABLES `cerco_electrico_config` WRITE;
/*!40000 ALTER TABLE `cerco_electrico_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `cerco_electrico_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `rut` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (3,'ANDRES MOYA','111111111','1@1.COM','+56912345678','RIO ACONCAGUA 790','2025-09-04 01:51:21'),(4,'PEPE','98765432-1','1@2.CL','+56912345678','TERCERA AVENIDA','2025-09-04 01:52:21'),(5,'JORDAN ','98765432-1','1@3.CL','123456789','EL TREBOL','2025-09-04 01:53:05'),(6,'JORGE BERTRAND','98765432-1','INGEX2000@YAHOO.COM','1111111','PEÑAFLOR','2025-09-06 18:01:46');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion`
--

LOCK TABLES `configuracion` WRITE;
/*!40000 ALTER TABLE `configuracion` DISABLE KEYS */;
INSERT INTO `configuracion` VALUES (1,'smtp_host','smtp.gmail.com','Servidor SMTP para envío de emails','2025-08-27 02:43:37'),(2,'smtp_port','587','Puerto SMTP','2025-08-27 02:43:37'),(3,'smtp_username','','Usuario SMTP','2025-08-27 02:43:37'),(4,'smtp_password','','Contraseña SMTP','2025-08-27 02:43:37'),(5,'whatsapp_token','','Token de WhatsApp Business API','2025-08-27 02:43:37'),(6,'whatsapp_phone','','Número de teléfono de WhatsApp','2025-08-27 02:43:37'),(7,'iva_porcentaje','19','Porcentaje de IVA','2025-08-27 02:43:37'),(8,'moneda','CLP','Moneda del sistema','2025-08-27 02:43:37');
/*!40000 ALTER TABLE `configuracion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cotizacion_detalles`
--

DROP TABLE IF EXISTS `cotizacion_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cotizacion_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cotizacion_id` int(11) DEFAULT NULL,
  `producto_servicio_id` int(11) DEFAULT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `descripcion_adicional` text DEFAULT NULL,
  `descuento_item` decimal(10,2) DEFAULT 0.00,
  `precio_base_historico` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `cotizacion_id` (`cotizacion_id`),
  KEY `producto_servicio_id` (`producto_servicio_id`),
  CONSTRAINT `cotizacion_detalles_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cotizacion_detalles_ibfk_2` FOREIGN KEY (`producto_servicio_id`) REFERENCES `productos_servicios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cotizacion_detalles`
--

LOCK TABLES `cotizacion_detalles` WRITE;
/*!40000 ALTER TABLE `cotizacion_detalles` DISABLE KEYS */;
INSERT INTO `cotizacion_detalles` VALUES (37,3,NULL,3.00,7000.00,21000.00,'cambio de ampolletas de horno marraquetas',0.00,0.00),(38,3,NULL,1.00,15000.00,15000.00,'reparación maquina de cecinas ',0.00,0.00),(39,3,NULL,4.00,5000.00,20000.00,'Cambio de cargadores de cámaras',0.00,0.00),(40,3,NULL,1.00,15000.00,15000.00,'instalación y modificación de balanza panadería ',0.00,0.00),(41,3,NULL,1.00,40000.00,40000.00,'reparación maquina revolvedora y sobadora',0.00,0.00),(42,3,NULL,1.00,30000.00,30000.00,'visita técnica parcela 90',0.00,0.00),(43,3,NULL,2.00,10000.00,20000.00,'cambio de canoa, y luces cámara de frio de carnes ',0.00,0.00),(52,5,19,1.00,50000.00,50000.00,NULL,0.00,0.00),(53,5,NULL,1.00,10710.00,10710.00,'COMPRA DE SERVICIO STREAMING ',0.00,0.00),(68,1,16,2.00,25000.00,50000.00,NULL,0.00,0.00),(77,12,NULL,12.00,15000.00,180000.00,'centro electrico',0.00,15000.00),(78,12,NULL,1.00,13500.00,13500.00,'compra',0.00,13500.00),(87,4,17,3.00,15000.00,45000.00,NULL,0.00,15000.00),(88,4,20,3.00,15000.00,45000.00,NULL,0.00,15000.00),(89,4,NULL,18.00,2500.00,45000.00,'limpieza maquinas cooler ',0.00,2500.00),(90,4,NULL,1.00,20000.00,20000.00,'Reparación balanza 2 (cecinas)',0.00,20000.00),(91,4,NULL,1.00,15000.00,15000.00,'Reparación motor Parcela 90 ',0.00,15000.00),(92,4,NULL,1.00,15000.00,15000.00,'reparacion caja 2, cambio de impresora y teclados',0.00,15000.00),(93,4,NULL,1.00,10000.00,10000.00,'cambio de cámara cecinas',0.00,10000.00);
/*!40000 ALTER TABLE `cotizacion_detalles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cotizaciones`
--

DROP TABLE IF EXISTS `cotizaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cotizaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_cotizacion` varchar(20) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `fecha_cotizacion` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `iva` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) DEFAULT 0.00,
  `estado` enum('pendiente','enviada','aceptada','rechazada','vencida') DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `requiere_mantencion` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `con_iva` tinyint(1) NOT NULL DEFAULT 0,
  `usuario_id` int(11) DEFAULT NULL,
  `descuento_general` decimal(5,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_cotizacion` (`numero_cotizacion`),
  KEY `cliente_id` (`cliente_id`),
  KEY `fk_cotizaciones_usuario` (`usuario_id`),
  CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_cotizaciones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cotizaciones`
--

LOCK TABLES `cotizaciones` WRITE;
/*!40000 ALTER TABLE `cotizaciones` DISABLE KEYS */;
INSERT INTO `cotizaciones` VALUES (1,'COT-2025-8239',4,'2025-08-01','2025-08-31',50000.00,0.00,50000.00,'aceptada','CAMBIO DE CHAPA DE PORTON,Y INSTALACION DE CHAPA PUERTA PATIO LATERAL',0,'2025-09-04 02:08:44',0,NULL,0.00),(3,'COT-2025-9821',3,'2025-08-01','2025-10-31',161000.00,0.00,161000.00,'aceptada','visita en la parcela, reprogramar motor de porto, agregar mas controles, reparación enchufe en quinco etc...',0,'2025-09-06 17:50:11',0,NULL,0.00),(4,'COT-2025-3817',3,'2025-09-01','2025-09-30',195000.00,0.00,195000.00,'pendiente','- cambios de proyectores led´s, lado rio Aconcagua.\r\n- mantención a caja 1,2 y 3, limpieza cambio de pasta disipadora, desfragmentación.\r\n- cambio de cabezal de impresión balanza 2 cecinas.\r\n- reparación de motor descontrolado con falla en sensor de cierre y apertura.',0,'2025-09-06 17:58:57',0,NULL,0.00),(5,'COT-2025-1982',6,'2025-09-06','2025-10-17',60710.00,0.00,60710.00,'aceptada','',0,'2025-09-06 18:05:07',0,NULL,0.00),(12,'COT-2025-8387',5,'2025-08-01','2025-08-31',193500.00,0.00,193500.00,'aceptada','',0,'2025-09-12 22:20:59',0,NULL,0.00);
/*!40000 ALTER TABLE `cotizaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empresa`
--

DROP TABLE IF EXISTS `empresa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empresa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `subtitulo` varchar(255) DEFAULT NULL,
  `rut` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresa`
--

LOCK TABLES `empresa` WRITE;
/*!40000 ALTER TABLE `empresa` DISABLE KEYS */;
INSERT INTO `empresa` VALUES (1,'CyC Electric Ltda.','77860655-0','Psje. San Guillermo N°486','+569 3032 4907','patocampos20@gmail.com','+569 3032 4907',NULL,'2025-08-27 02:43:36');
/*!40000 ALTER TABLE `empresa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orden_seguimiento`
--

DROP TABLE IF EXISTS `orden_seguimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orden_seguimiento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orden_id` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `porcentaje_anterior` decimal(5,2) DEFAULT NULL,
  `porcentaje_actual` decimal(5,2) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`),
  CONSTRAINT `orden_seguimiento_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes_trabajo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orden_seguimiento`
--

LOCK TABLES `orden_seguimiento` WRITE;
/*!40000 ALTER TABLE `orden_seguimiento` DISABLE KEYS */;
INSERT INTO `orden_seguimiento` VALUES (13,12,'2025-09-04',0.00,100.00,'INSTALACION DE AMBAS CHAPAS ','Administrador Sistema','2025-09-04 02:09:26'),(15,14,'2025-09-06',0.00,100.00,'completada los servicios.','Administrador Sistema','2025-09-06 18:34:28'),(16,15,'2025-09-06',0.00,100.00,'completa mantención a pagina web y radio de don Jorge','Administrador Sistema','2025-09-06 19:06:06'),(19,17,'2025-09-12',0.00,0.00,'lista','Administrador Sistema','2025-09-12 22:21:16'),(28,17,'2025-09-12',0.00,100.00,'1','Administrador Sistema','2025-09-12 22:45:35');
/*!40000 ALTER TABLE `orden_seguimiento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ordenes_trabajo`
--

DROP TABLE IF EXISTS `ordenes_trabajo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ordenes_trabajo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_orden` varchar(20) NOT NULL,
  `cotizacion_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_estimada_fin` date DEFAULT NULL,
  `fecha_real_fin` date DEFAULT NULL,
  `estado` enum('pendiente','en_proceso','pausada','completada','cancelada') DEFAULT 'pendiente',
  `porcentaje_avance` decimal(5,2) DEFAULT 0.00,
  `monto_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `monto_pagado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estado_pago` enum('pendiente','abonado','pagado') NOT NULL DEFAULT 'pendiente',
  `requiere_mantencion` tinyint(1) NOT NULL DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tecnico_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_orden` (`numero_orden`),
  KEY `cotizacion_id` (`cotizacion_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `fk_ordenes_tecnico` (`tecnico_id`),
  CONSTRAINT `fk_ordenes_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `ordenes_trabajo_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`),
  CONSTRAINT `ordenes_trabajo_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ordenes_trabajo`
--

LOCK TABLES `ordenes_trabajo` WRITE;
/*!40000 ALTER TABLE `ordenes_trabajo` DISABLE KEYS */;
INSERT INTO `ordenes_trabajo` VALUES (12,'OT-2025-5041',1,4,'2025-09-04',NULL,'2025-09-04','completada',100.00,50000.00,0.00,'pendiente',0,NULL,'2025-09-04 02:08:52',NULL),(14,'OT-2025-5290',3,3,'2025-09-06',NULL,'2025-09-06','completada',100.00,161000.00,70000.00,'abonado',0,NULL,'2025-09-06 18:34:05',NULL),(15,'OT-2025-0770',5,6,'2025-09-06',NULL,'2025-09-06','completada',100.00,60710.00,60710.00,'pagado',0,NULL,'2025-09-06 19:05:33',NULL),(17,'OT-2025-5755',12,5,'2025-09-13',NULL,'2025-09-12','completada',100.00,193500.00,193500.00,'pagado',0,NULL,'2025-09-12 22:21:04',NULL);
/*!40000 ALTER TABLE `ordenes_trabajo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orden_id` int(11) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha_pago` date NOT NULL,
  `metodo_pago` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`),
  CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes_trabajo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
INSERT INTO `pagos` VALUES (20,15,60710.00,'2025-09-06','Transferencia','transferencia','2025-09-06 19:06:47'),(21,17,193500.00,'2025-09-13','Transferencia','','2025-09-12 22:21:33'),(26,14,66000.00,'2025-09-14','Transferencia','','2025-09-14 02:19:11'),(27,14,4000.00,'2025-09-15','Transferencia','','2025-09-15 02:51:24');
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos_servicios`
--

DROP TABLE IF EXISTS `productos_servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos_servicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_base` decimal(10,2) DEFAULT NULL,
  `costo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unidad` varchar(50) DEFAULT NULL,
  `tipo` enum('producto','servicio') NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `stock` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `productos_servicios_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos_servicios`
--

LOCK TABLES `productos_servicios` WRITE;
/*!40000 ALTER TABLE `productos_servicios` DISABLE KEYS */;
INSERT INTO `productos_servicios` VALUES (15,4,'Instalación Centro Eléctrico','centro electrico\r\n- enchufe\r\n- interruptor + 1 iluminacion\r\n- 1 iluminacion',25000.00,0.00,'servicio','servicio',1,'2025-09-04 01:56:37',0.00),(16,5,'trabajos de soldadura','Chapas puertas\r\nPomeles\r\ntrabajos básicos ',25000.00,0.00,'servicio','servicio',1,'2025-09-04 02:00:18',0.00),(17,4,'Instalación de proyectores led´s','Instalación de proyectores led',15000.00,0.00,'servicio','servicio',1,'2025-09-04 02:02:31',0.00),(19,4,'Mantención web','mantención, modificación, cambio de servicios, asesorias etc....',50000.00,0.00,'servicio','servicio',1,'2025-09-06 18:11:08',0.00),(20,6,'Mantención equipos informáticos ','',15000.00,0.00,'servicio','servicio',1,'2025-09-12 00:31:09',0.00);
/*!40000 ALTER TABLE `productos_servicios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sesiones`
--

DROP TABLE IF EXISTS `sesiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sesiones` (
  `id` varchar(128) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sesiones`
--

LOCK TABLES `sesiones` WRITE;
/*!40000 ALTER TABLE `sesiones` DISABLE KEYS */;
INSERT INTO `sesiones` VALUES ('1g9ivsi529rqieljaqmrg5kuob',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-12 17:58:23','2025-09-13 17:58:23'),('33gskdsjk6nvn66otninfofi8k',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-09 21:35:18','2025-09-10 21:35:18'),('3rr2ku7at17mn6kbi6r6qloe34',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0','2025-09-13 13:06:43','2025-09-14 13:06:43'),('74n1q8mdq533glp28kvmsm5vft',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-12 00:29:12','2025-09-13 00:29:12'),('7ak4cuqafc4t8p9d05koe7cf67',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-18 20:27:14','2025-09-19 20:27:14'),('e5ke8fp1a9vnsij3u84qs2ja0u',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2025-09-04 01:26:32','2025-09-05 01:26:32'),('ekcr0ep4dl4jtrdsdu8j3cdppg',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-07 02:09:46','2025-09-08 01:09:46'),('f5ag7uoj2r0hglm9d4pm4ef080',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0','2025-09-14 01:35:20','2025-09-15 01:35:20'),('gmv640v2ilr0tke4bb2qiapi5c',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-12 22:13:30','2025-09-13 22:13:30'),('hagnpeu2r627ioqnb2qbv57d1e',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-06 18:33:40','2025-09-07 17:33:40'),('njadmqpdrmevsh2g3dk00b1ll5',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','2025-08-31 21:49:52','2025-09-01 21:49:52'),('nuh14r0gdau5ohokbf9hp3smri',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-05 02:30:54','2025-09-06 02:30:54'),('ol8e56af1sf81v2n0h6l64lc7p',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-15 02:46:53','2025-09-16 02:46:53'),('onjhdc8kjun0c5e305ojlo1qij',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0','2025-09-13 13:05:03','2025-09-14 13:05:03'),('ra5o8otk5pnrgefd7m15b75b7c',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','2025-09-14 02:18:56','2025-09-15 02:18:56');
/*!40000 ALTER TABLE `sesiones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre_completo` varchar(255) NOT NULL,
  `rol` enum('admin','vendedor','tecnico') DEFAULT 'vendedor',
  `activo` tinyint(1) DEFAULT 1,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin','admin@cercoselectricos.cl','$2y$10$rR8jlW5nPp2Jldc58F6DuObDaEqBoSt19DsEAch3RVngTaJtJ8Orq','Administrador Sistema','admin',1,'2025-09-18 20:27:14','2025-08-27 02:43:37','2025-09-18 20:27:14');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `version` VARCHAR(255) NOT NULL UNIQUE,
  `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-18 17:54:43
