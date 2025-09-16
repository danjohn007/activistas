-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 16-09-2025 a las 11:31:06
-- Versión del servidor: 5.7.23-23
-- Versión de PHP: 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ejercito_activistas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades`
--

CREATE TABLE `actividades` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_actividad_id` int(11) NOT NULL,
  `titulo` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8_unicode_ci,
  `enlace_1` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enlace_2` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fecha_actividad` date NOT NULL,
  `fecha_cierre` date DEFAULT NULL,
  `hora_cierre` time DEFAULT NULL,
  `estado` enum('programada','en_progreso','completada','cancelada') COLLATE utf8_unicode_ci DEFAULT 'programada',
  `tarea_pendiente` tinyint(1) DEFAULT '0',
  `propuesto_por` int(11) DEFAULT NULL,
  `autorizado_por` int(11) DEFAULT NULL,
  `autorizada` tinyint(1) DEFAULT '0',
  `bonificacion_ranking` int(11) DEFAULT '0',
  `solicitante_id` int(11) DEFAULT NULL,
  `hora_evidencia` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Tabla de actividades con campos opcionales de enlaces (enlace_1, enlace_2) para mostrar links relacionados';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones`
--

CREATE TABLE `configuraciones` (
  `id` int(11) NOT NULL,
  `clave` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `valor` text COLLATE utf8_unicode_ci,
  `descripcion` text COLLATE utf8_unicode_ci,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `configuraciones`
--

INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `descripcion`, `fecha_actualizacion`) VALUES
(1, 'sistema_nombre', 'Sistema de Activistas Digitales', 'Nombre del sistema', '2025-08-24 05:56:28'),
(2, 'email_verificacion_requerida', '0', 'Si requiere verificación de email (1=Sí, 0=No)', '2025-08-24 05:56:28'),
(3, 'max_tamaño_archivo', '20971520', 'Tamaño máximo de archivos en bytes (5MB)', '2025-08-24 05:57:05'),
(4, 'formatos_imagen_permitidos', 'jpg,jpeg,png,gif', 'Formatos de imagen permitidos', '2025-08-24 05:56:28'),
(5, 'zona_horaria', 'America/Mexico_City', 'Zona horaria del sistema', '2025-08-24 05:56:28'),
(6, 'ultimo_reset_ranking', '2025-09-16 04:43:25', 'Fecha y hora del último reset de ranking mensual', '2025-09-16 10:43:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evidencias`
--

CREATE TABLE `evidencias` (
  `id` int(11) NOT NULL,
  `actividad_id` int(11) NOT NULL,
  `tipo_evidencia` enum('foto','video','audio','comentario','live') COLLATE utf8_unicode_ci NOT NULL,
  `archivo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contenido` text COLLATE utf8_unicode_ci,
  `fecha_subida` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bloqueada` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `mensaje` text COLLATE utf8_unicode_ci NOT NULL,
  `tipo` enum('info','success','warning','error') COLLATE utf8_unicode_ci DEFAULT 'info',
  `leida` tinyint(1) DEFAULT '0',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rankings_mensuales`
--

CREATE TABLE `rankings_mensuales` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `puntos` int(11) NOT NULL DEFAULT '0',
  `posicion` int(11) NOT NULL DEFAULT '0',
  `actividades_completadas` int(11) NOT NULL DEFAULT '0',
  `porcentaje_cumplimiento` decimal(5,2) NOT NULL DEFAULT '0.00',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Tabla de rankings mensuales para mantener historial de puntuaciones por mes y año';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_actividades`
--

CREATE TABLE `tipos_actividades` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8_unicode_ci,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `tipos_actividades`
--

INSERT INTO `tipos_actividades` (`id`, `nombre`, `descripcion`, `activo`, `fecha_creacion`) VALUES
(1, 'Campaña de Redes Sociales', 'Publicaciones en redes sociales para difundir información', 1, '2025-08-24 05:56:28'),
(2, 'Evento Presencial', 'Actividades presenciales como marchas, reuniones, etc.', 1, '2025-08-24 05:56:28'),
(3, 'Capacitación', 'Sesiones de formación y capacitación', 1, '2025-08-24 05:56:28'),
(4, 'Encuesta/Sondeo', 'Recolección de opiniones y datos: \r\nCrea encuesta en Whatsapp:', 1, '2025-08-24 05:56:28'),
(5, 'Live/Transmisión', 'Transmisiones en vivo en redes sociales', 1, '2025-08-24 05:56:28'),
(6, 'Volanteo', 'Distribución de material impreso', 0, '2025-08-24 05:56:28'),
(7, 'Reunión de Equipo', 'Reuniones de coordinación y planificación', 1, '2025-08-24 05:56:28'),
(8, 'Reacción a publicación', 'Interactua reaccionando ante esta publicación:\r\nDe la siguiente manera:', 1, '2025-08-24 14:40:57'),
(9, 'Crear Contenido', 'Genera contenido referente a este tema:\r\nLink de referencia:\r\nImagen de referencia:', 1, '2025-08-24 15:57:00'),
(10, 'Pautar una publicación', 'Directriz del mensaje: \r\nEnlace de referencia:\r\nImagen de Referencia:', 1, '2025-08-25 17:48:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre_completo` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `foto_perfil` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `direccion` text COLLATE utf8_unicode_ci NOT NULL,
  `rol` enum('SuperAdmin','Gestor','Líder','Activista') COLLATE utf8_unicode_ci NOT NULL,
  `lider_id` int(11) DEFAULT NULL,
  `estado` enum('pendiente','activo','suspendido','desactivado') COLLATE utf8_unicode_ci DEFAULT 'pendiente',
  `vigencia_hasta` date DEFAULT NULL,
  `email_verificado` tinyint(1) DEFAULT '0',
  `token_verificacion` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `facebook` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `instagram` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tiktok` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `x` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cuenta_pago` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ranking_puntos` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Tabla de usuarios con campo de vigencia para control de vencimiento de acceso';

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_completo`, `telefono`, `email`, `foto_perfil`, `password_hash`, `direccion`, `rol`, `lider_id`, `estado`, `vigencia_hasta`, `email_verificado`, `token_verificacion`, `fecha_registro`, `fecha_actualizacion`, `facebook`, `instagram`, `tiktok`, `x`, `cuenta_pago`, `ranking_puntos`) VALUES
(1, 'Administrador del Sistema', '0000000000', 'admin@activistas.com', '68aaab4f1f9e3.jpeg', '$2y$10$JnHWePwvnrWklVHR4mAbgehHPaTGXnGEFwxrdm/4bZigQRrz2GB4e', 'Oficina Central', 'SuperAdmin', NULL, 'activo', NULL, 1, NULL, '2025-08-24 05:56:28', '2025-09-16 16:18:35', 'https://www.facebook.com/danjohnraso/', 'https://www.instagram.com/danraso', 'https://www.tiktok.com/@jonathanraso', 'https://x.com/danjohn007', 'Cuenta Inbursa Dan Jonathan Raso Ríos CLABE: 036260711591102879', 1400);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_actividades_usuario`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_actividades_usuario` (
`usuario_id` int(11)
,`nombre_completo` varchar(255)
,`rol` enum('SuperAdmin','Gestor','Líder','Activista')
,`total_actividades` bigint(21)
,`actividades_completadas` bigint(21)
,`total_evidencias` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_estadisticas_usuarios`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_estadisticas_usuarios` (
`rol` enum('SuperAdmin','Gestor','Líder','Activista')
,`total` bigint(21)
,`activos` bigint(21)
,`pendientes` bigint(21)
,`suspendidos` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_actividades_usuario`
--
DROP TABLE IF EXISTS `vista_actividades_usuario`;

CREATE ALGORITHM=UNDEFINED DEFINER=`ejercitodigitalc`@`localhost` SQL SECURITY DEFINER VIEW `vista_actividades_usuario`  AS SELECT `u`.`id` AS `usuario_id`, `u`.`nombre_completo` AS `nombre_completo`, `u`.`rol` AS `rol`, count(`a`.`id`) AS `total_actividades`, count((case when (`a`.`estado` = 'completada') then 1 end)) AS `actividades_completadas`, count(`e`.`id`) AS `total_evidencias` FROM ((`usuarios` `u` left join `actividades` `a` on((`u`.`id` = `a`.`usuario_id`))) left join `evidencias` `e` on((`a`.`id` = `e`.`actividad_id`))) GROUP BY `u`.`id`, `u`.`nombre_completo`, `u`.`rol` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_estadisticas_usuarios`
--
DROP TABLE IF EXISTS `vista_estadisticas_usuarios`;

CREATE ALGORITHM=UNDEFINED DEFINER=`ejercitodigitalc`@`localhost` SQL SECURITY DEFINER VIEW `vista_estadisticas_usuarios`  AS SELECT `usuarios`.`rol` AS `rol`, count(0) AS `total`, count((case when (`usuarios`.`estado` = 'activo') then 1 end)) AS `activos`, count((case when (`usuarios`.`estado` = 'pendiente') then 1 end)) AS `pendientes`, count((case when (`usuarios`.`estado` = 'suspendido') then 1 end)) AS `suspendidos` FROM `usuarios` GROUP BY `usuarios`.`rol` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `tipo_actividad_id` (`tipo_actividad_id`),
  ADD KEY `fk_actividades_solicitante_id` (`solicitante_id`),
  ADD KEY `fk_actividades_autorizado_por` (`autorizado_por`),
  ADD KEY `idx_actividades_autorizada` (`autorizada`),
  ADD KEY `idx_actividades_propuesto_por` (`propuesto_por`),
  ADD KEY `idx_actividades_cierre` (`fecha_cierre`,`hora_cierre`);

--
-- Indices de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `evidencias`
--
ALTER TABLE `evidencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `actividad_id` (`actividad_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `rankings_mensuales`
--
ALTER TABLE `rankings_mensuales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_month` (`usuario_id`,`anio`,`mes`),
  ADD KEY `idx_rankings_mensuales_fecha` (`anio`,`mes`),
  ADD KEY `idx_rankings_mensuales_puntos` (`anio`,`mes`,`puntos`);

--
-- Indices de la tabla `tipos_actividades`
--
ALTER TABLE `tipos_actividades`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `lider_id` (`lider_id`),
  ADD KEY `idx_usuarios_vigencia` (`vigencia_hasta`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades`
--
ALTER TABLE `actividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2208;

--
-- AUTO_INCREMENT de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `evidencias`
--
ALTER TABLE `evidencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=196;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `rankings_mensuales`
--
ALTER TABLE `rankings_mensuales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=385;

--
-- AUTO_INCREMENT de la tabla `tipos_actividades`
--
ALTER TABLE `tipos_actividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD CONSTRAINT `actividades_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `actividades_ibfk_2` FOREIGN KEY (`tipo_actividad_id`) REFERENCES `tipos_actividades` (`id`),
  ADD CONSTRAINT `fk_actividades_autorizado_por` FOREIGN KEY (`autorizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_actividades_propuesto_por` FOREIGN KEY (`propuesto_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_actividades_solicitante_id` FOREIGN KEY (`solicitante_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `evidencias`
--
ALTER TABLE `evidencias`
  ADD CONSTRAINT `evidencias_ibfk_1` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `rankings_mensuales`
--
ALTER TABLE `rankings_mensuales`
  ADD CONSTRAINT `rankings_mensuales_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`lider_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
