-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-07-2025 a las 00:48:35
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_login`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cert_349-2024`
--

CREATE TABLE `cert_349-2024` (
  `id` int(11) NOT NULL,
  `fecha_cert_venc` date NOT NULL,
  `id_expediente` varchar(255) NOT NULL,
  `cuit_proveedor` varchar(20) DEFAULT NULL,
  `estado` varchar(100) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cert_349-2024`
--

INSERT INTO `cert_349-2024` (`id`, `fecha_cert_venc`, `id_expediente`, `cuit_proveedor`, `estado`, `fecha_creacion`) VALUES
(1, '2024-02-25', '5600-54654-2024-', '22', 'vencido', '2025-07-23 23:47:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cert_349-2025`
--

CREATE TABLE `cert_349-2025` (
  `id` int(11) NOT NULL,
  `fecha_cert_venc` date DEFAULT NULL,
  `id_expediente` varchar(255) NOT NULL,
  `cuit_proveedor` varchar(20) DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cert_349-2025`
--

INSERT INTO `cert_349-2025` (`id`, `fecha_cert_venc`, `id_expediente`, `cuit_proveedor`, `estado`, `fecha_creacion`) VALUES
(1, '2025-04-14', '5600-235415-2025-', '354654745', 'reemplazado', '2025-07-13 22:54:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `columnas_alias_global`
--

CREATE TABLE `columnas_alias_global` (
  `id` int(11) NOT NULL,
  `nombre_columna` varchar(100) NOT NULL,
  `alias_columna` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `columnas_alias_global`
--

INSERT INTO `columnas_alias_global` (`id`, `nombre_columna`, `alias_columna`) VALUES
(1, 'organismo', 'Organismo'),
(2, 'numero', 'Número'),
(3, 'año', 'Año'),
(4, 'cuerpo', 'Cuerpo'),
(5, 'dependencia', 'Dependencia'),
(6, 'caratula', 'Carátula'),
(7, 'observaciones', 'Observaciones'),
(8, 'resolucion', 'Resolución'),
(9, 'decreto', 'Decreto'),
(10, 'estado', 'Estado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expediente_proveedor_monto-2023`
--

CREATE TABLE `expediente_proveedor_monto-2023` (
  `id` int(11) NOT NULL,
  `id_expediente` int(11) NOT NULL,
  `nombre_proveedor` varchar(255) NOT NULL,
  `mes` varchar(7) NOT NULL,
  `monto` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expediente_proveedor_monto-2024`
--

CREATE TABLE `expediente_proveedor_monto-2024` (
  `id` int(11) NOT NULL,
  `id_expediente` varchar(255) NOT NULL,
  `nombre_proveedor` varchar(255) NOT NULL,
  `tipo_periodo` varchar(20) DEFAULT 'mes',
  `mes` varchar(7) DEFAULT NULL,
  `fecha_exacta` date DEFAULT NULL,
  `periodo_desde` date DEFAULT NULL,
  `periodo_hasta` date DEFAULT NULL,
  `monto` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expediente_proveedor_monto-2025`
--

CREATE TABLE `expediente_proveedor_monto-2025` (
  `id` int(11) NOT NULL,
  `id_expediente` varchar(255) NOT NULL,
  `nombre_proveedor` varchar(255) NOT NULL,
  `mes` varchar(50) DEFAULT NULL,
  `tipo_periodo` varchar(20) DEFAULT 'mes',
  `fecha_exacta` date DEFAULT NULL,
  `periodo_desde` date DEFAULT NULL,
  `periodo_hasta` date DEFAULT NULL,
  `monto` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `expediente_proveedor_monto-2025`
--

INSERT INTO `expediente_proveedor_monto-2025` (`id`, `id_expediente`, `nombre_proveedor`, `mes`, `tipo_periodo`, `fecha_exacta`, `periodo_desde`, `periodo_hasta`, `monto`) VALUES
(87, '5641-235415-2025-', 'DAMSA', NULL, 'mes', NULL, NULL, NULL, 544.00),
(88, '5641-235415-2025-', 'PANORAMA - EL TERRITORIO', NULL, 'mes', NULL, NULL, NULL, 222.22),
(89, '5641-235415-2025-', 'PANORAMA - EL TERRITORIO', NULL, 'periodo', NULL, '2025-07-17', '2025-07-18', 356.65),
(90, '5641-235415-2025-', 'DAMSA', NULL, 'periodo', NULL, '2025-07-17', '2025-07-22', 57865.55),
(91, '676-5446-2025-', 'PANORAMA - EL TERRITORIO', '2025-08', 'mes', NULL, NULL, NULL, 22.00),
(92, '5600-235415-2025-', 'DAMSA', NULL, 'fecha', '2025-07-15', NULL, NULL, 250000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exptes-2023`
--

CREATE TABLE `exptes-2023` (
  `id` int(11) NOT NULL,
  `organismo` varchar(255) DEFAULT NULL,
  `numero` varchar(50) NOT NULL,
  `año` year(4) NOT NULL,
  `cuerpo` text DEFAULT NULL,
  `dependencia` text DEFAULT NULL,
  `id_expediente` varchar(255) GENERATED ALWAYS AS (concat_ws('-',`organismo`,`numero`,`año`,`cuerpo`)) STORED,
  `caratula` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `resolucion` varchar(100) DEFAULT NULL,
  `decreto` varchar(100) DEFAULT NULL,
  `usuario` text DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exptes-2024`
--

CREATE TABLE `exptes-2024` (
  `id` int(11) NOT NULL,
  `organismo` varchar(255) DEFAULT NULL,
  `numero` varchar(50) NOT NULL,
  `año` year(4) NOT NULL,
  `cuerpo` text DEFAULT NULL,
  `dependencia` text DEFAULT NULL,
  `id_expediente` varchar(255) GENERATED ALWAYS AS (concat_ws('-',`organismo`,`numero`,`año`,`cuerpo`)) STORED,
  `caratula` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `resolucion` varchar(100) DEFAULT NULL,
  `decreto` varchar(100) DEFAULT NULL,
  `usuario` text DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `exptes-2024`
--

INSERT INTO `exptes-2024` (`id`, `organismo`, `numero`, `año`, `cuerpo`, `dependencia`, `caratula`, `observaciones`, `resolucion`, `decreto`, `usuario`, `estado`, `fecha_creacion`) VALUES
(3, '5600', '54654', '2024', '', '', '', '', '', '', '1', '', '2025-07-23 17:23:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exptes-2025`
--

CREATE TABLE `exptes-2025` (
  `id` int(11) NOT NULL,
  `organismo` varchar(255) DEFAULT NULL,
  `numero` varchar(50) NOT NULL,
  `año` year(4) NOT NULL,
  `cuerpo` text DEFAULT NULL,
  `dependencia` text DEFAULT NULL,
  `id_expediente` varchar(255) GENERATED ALWAYS AS (concat_ws('-',`organismo`,`numero`,`año`,`cuerpo`)) STORED,
  `caratula` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `resolucion` varchar(100) DEFAULT NULL,
  `decreto` varchar(100) DEFAULT NULL,
  `usuario` text DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `exptes-2025`
--

INSERT INTO `exptes-2025` (`id`, `organismo`, `numero`, `año`, `cuerpo`, `dependencia`, `caratula`, `observaciones`, `resolucion`, `decreto`, `usuario`, `estado`, `fecha_creacion`) VALUES
(2, '5600', '235415', '2025', '', '', 'Limpieza 25 de mayo 1460 Agosto', '', '', '', NULL, '', '2025-07-13 12:40:17'),
(4, '5600', '5446', '2025', '', '', '', '', '', '', '1', '', '2025-07-23 17:00:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id`, `nombre`) VALUES
(4, 'Administradores'),
(5, 'Desarrollo'),
(1, 'Despacho'),
(3, 'Jurídico'),
(2, 'Tesorería');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `menus`
--

INSERT INTO `menus` (`id`, `nombre`, `url`, `orden`) VALUES
(1, 'Inicio', '/index.php', 1),
(2, 'Panel de Control', '/dashboard.php', 2),
(3, 'Usuarios', '#', 3),
(9, 'Desarrollo', '#', 10),
(10, 'Expedientes', '#', 10),
(11, 'Heramientas', '#', 11);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos`
--

CREATE TABLE `modulos` (
  `id` varchar(500) NOT NULL,
  `ruta` varchar(500) NOT NULL,
  `nombre_personalizado` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modulos`
--

INSERT INTO `modulos` (`id`, `ruta`, `nombre_personalizado`) VALUES
('acceso_denegado.php', 'acceso_denegado.php', 'Acceso denegado'),
('admin/admin_grupos.php', 'admin/admin_grupos.php', NULL),
('admin/admin_permisos_grupo.php', 'admin/admin_permisos_grupo.php', NULL),
('admin/admin_permisos_menu.php', 'admin/admin_permisos_menu.php', NULL),
('admin/admin_sesiones.php', 'admin/admin_sesiones.php', NULL),
('admin/cambiar_password.php', 'admin/cambiar_password.php', NULL),
('admin/editar_usuario.php', 'admin/editar_usuario.php', NULL),
('admin/lista_usuarios.php', 'admin/lista_usuarios.php', NULL),
('agregar_expediente.php', 'agregar_expediente.php', NULL),
('auth copyv1.php', 'auth copyv1.php', NULL),
('auth.php', 'auth.php', NULL),
('certificados_alerta.php', 'certificados_alerta.php', NULL),
('convert_num_letra.php', 'convert_num_letra.php', NULL),
('crear_tabla_certificados.php', 'crear_tabla_certificados.php', NULL),
('crear_tabla_expedientes.php', 'crear_tabla_expedientes.php', NULL),
('dashboard.php', 'dashboard.php', NULL),
('db.php', 'db.php', NULL),
('editar.php', 'editar.php', NULL),
('editar_proveedores.php', 'editar_proveedores.php', NULL),
('editar_usuariov1.php', 'editar_usuariov1.php', NULL),
('editar_usuariov2 sin seg.php', 'editar_usuariov2 sin seg.php', NULL),
('editor_permisos copy.php', 'editor_permisos copy.php', NULL),
('editor_permisos.php', 'editor_permisos.php', NULL),
('gestionar_certificados copy.php', 'gestionar_certificados copy.php', NULL),
('gestionar_certificados.php', 'gestionar_certificados.php', NULL),
('gestionar_columnas.php', 'gestionar_columnas.php', NULL),
('gestionar_exptes.php', 'gestionar_exptes.php', NULL),
('gestionar_modulos.php', 'gestionar_modulos.php', NULL),
('gestion_organismo.php', 'gestion_organismo.php', NULL),
('includes/favicon/favicon_editor.php', 'includes/favicon/favicon_editor.php', NULL),
('includes/header.php', 'includes/header.php', NULL),
('index.php', 'index.php', NULL),
('login.php', 'login.php', NULL),
('logout.php', 'logout.php', NULL),
('menu_dinamico.php', 'menu_dinamico.php', NULL),
('mod_despacho.php', 'mod_despacho.php', NULL),
('notificaciones_certificados.php', 'notificaciones_certificados.php', NULL),
('Nueva carpeta/dashboard/phpinfo.php', 'Nueva carpeta/dashboard/phpinfo.php', NULL),
('Nueva carpeta/index.php', 'Nueva carpeta/index.php', NULL),
('ocr_tesseract_windows.php', 'ocr_tesseract_windows.php', NULL),
('poblar_modulos.php', 'poblar_modulos.php', NULL),
('registro.php', 'registro.php', NULL),
('SessionHandlerMySQL copy.php', 'SessionHandlerMySQL copy.php', NULL),
('SessionHandlerMySQL.php', 'SessionHandlerMySQL.php', NULL),
('session_init.php', 'session_init.php', NULL),
('visor-iconos.php', 'visor-iconos.php', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `organismos`
--

CREATE TABLE `organismos` (
  `org_num` int(11) NOT NULL,
  `org_nombre` varchar(255) NOT NULL,
  `fecha` date DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefonos` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `identificador` varchar(270) GENERATED ALWAYS AS (concat(`org_num`,' - ',`org_nombre`)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `organismos`
--

INSERT INTO `organismos` (`org_num`, `org_nombre`, `fecha`, `estado`, `direccion`, `telefonos`, `observaciones`) VALUES
(5600, 'Secretaría de Estado de Cultura de la Provincia de Misiones', '2024-01-01', 'Activo', '25 de Mayo 1460', '', ''),
(5600, 'Ministerio de Cultura de la Provincia de Misiones', '2020-01-01', 'Inactivo', 'testno', '', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_grupos`
--

CREATE TABLE `permisos_grupos` (
  `id` int(11) NOT NULL,
  `grupo_id` varchar(50) NOT NULL,
  `modulo` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permisos_grupos`
--

INSERT INTO `permisos_grupos` (`id`, `grupo_id`, `modulo`) VALUES
(1, 'despacho', 'mod_despacho.php'),
(2, 'tesoreria', 'mod_tesoreria.php'),
(3, 'juridico', 'mod_juridico.php'),
(4, 'despacho', 'mod_general.php'),
(5, 'tesoreria', 'mod_general.php'),
(6, '1', 'editor_permisos.php'),
(7, 'despacho', 'menu_dinamico.php'),
(8, 'despacho', 'header.php'),
(13, '1', 'menu_dinamico.php'),
(18, '1', '#'),
(84, '4', 'crear_tabla_certificados.php'),
(89, '4', 'gestionar_modulos.php'),
(105, '4', 'acceso_denegado.php'),
(116, '4', 'editar_usuario.php'),
(125, '5', 'index.php'),
(126, '5', 'dashboard.php'),
(127, '5', 'lista_usuarios.php'),
(128, '5', 'registro.php'),
(129, '5', 'visor-iconos.php'),
(190, '4', 'lista_usuarios.php'),
(200, '1', 'index.php'),
(201, '1', 'gestionar_exptes.php'),
(202, '1', 'gestionar_certificados.php'),
(203, '1', 'editar_proveedores.php'),
(204, '1', 'certificados_alerta.php'),
(205, '1', 'crear_tabla_expedientes.php'),
(206, '1', 'ocr_tesseract_windows.php'),
(207, '1', 'convert_num_letra.php'),
(209, '4', 'dashboard.php'),
(220, '4', 'index.php'),
(234, '4', 'admin/lista_usuarios.php'),
(235, '4', 'registro.php'),
(236, '4', 'visor-iconos.php'),
(237, '4', 'gestionar_exptes.php'),
(238, '4', 'gestionar_certificados.php'),
(239, '4', 'editar_proveedores.php'),
(240, '4', 'certificados_alerta.php'),
(241, '4', 'crear_tabla_expedientes.php'),
(242, '4', 'ocr_tesseract_windows.php'),
(243, '4', 'convert_num_letra.php'),
(272, '4', '/admin_permisos_grupo.php'),
(315, '4', '/admin_grupos.php'),
(435, '4', '/index.php'),
(436, '4', '/dashboard.php'),
(437, '4', '/admin/admin_permisos_menu.php'),
(438, '4', '/admin/admin_permisos_grupo.php'),
(439, '4', '/admin/admin_grupos.php'),
(440, '4', '/gestionar_columnas.php'),
(441, '4', '/includes/favicon/favicon_editor.php'),
(442, '4', '/admin/lista_usuarios.php'),
(443, '4', '/registro.php'),
(444, '4', '/admin/admin_sesiones.php'),
(445, '4', '/visor-iconos.php'),
(446, '4', '/gestionar_exptes.php'),
(447, '4', '/gestionar_certificados.php'),
(448, '4', '/editar_proveedores.php'),
(449, '4', '/certificados_alerta.php'),
(450, '4', '/gestion_organismo.php'),
(451, '4', '/crear_tabla_expedientes.php'),
(452, '4', '/ocr_tesseract_windows.php'),
(453, '4', '/convert_num_letra.php');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_menu_grupo`
--

CREATE TABLE `permisos_menu_grupo` (
  `id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `menu_id` int(11) DEFAULT NULL,
  `submenu_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permisos_menu_grupo`
--

INSERT INTO `permisos_menu_grupo` (`id`, `grupo_id`, `menu_id`, `submenu_id`) VALUES
(136, 5, 1, NULL),
(137, 5, 2, NULL),
(138, 5, 3, NULL),
(139, 5, 9, NULL),
(140, 5, 3, 1),
(141, 5, 3, 2),
(142, 5, 9, 5),
(249, 1, 1, NULL),
(250, 1, 10, NULL),
(251, 1, 11, NULL),
(252, 1, 10, 7),
(253, 1, 10, 6),
(254, 1, 10, 9),
(255, 1, 10, 12),
(256, 1, 10, 8),
(257, 1, 11, 10),
(258, 1, 11, 11),
(560, 4, 1, NULL),
(561, 4, 2, NULL),
(562, 4, 3, NULL),
(563, 4, 9, NULL),
(564, 4, 10, NULL),
(565, 4, 11, NULL),
(566, 4, 2, 14),
(567, 4, 2, 15),
(568, 4, 2, 17),
(569, 4, 2, 19),
(570, 4, 2, 21),
(571, 4, 3, 1),
(572, 4, 3, 2),
(573, 4, 3, 18),
(574, 4, 9, 5),
(575, 4, 10, 7),
(576, 4, 10, 6),
(577, 4, 10, 9),
(578, 4, 10, 12),
(579, 4, 10, 20),
(580, 4, 10, 8),
(581, 4, 11, 10),
(582, 4, 11, 11);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `nombre_proveedor` varchar(255) NOT NULL,
  `cuit` varchar(15) DEFAULT NULL,
  `domicilio` text DEFAULT NULL,
  `estado` enum('Activo','Inactivo') DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`nombre_proveedor`, `cuit`, `domicilio`, `estado`, `fecha_creacion`) VALUES
('DAMSA', '22', '', 'Activo', '2025-07-16 02:02:06'),
('PANORAMA - EL TERRITORIO', '354654745', '', 'Activo', '2025-07-16 02:02:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_expte-2023`
--

CREATE TABLE `rel_expte-2023` (
  `id` int(11) NOT NULL,
  `id_expediente` varchar(255) NOT NULL,
  `id_expediente_rel` varchar(255) NOT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_expte-2024`
--

CREATE TABLE `rel_expte-2024` (
  `id` int(11) NOT NULL,
  `id_expediente` varchar(255) NOT NULL,
  `id_expediente_rel` varchar(255) NOT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rel_expte-2025`
--

CREATE TABLE `rel_expte-2025` (
  `id` int(11) NOT NULL,
  `id_expediente` varchar(255) NOT NULL,
  `id_expediente_rel` varchar(255) NOT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rel_expte-2025`
--

INSERT INTO `rel_expte-2025` (`id`, `id_expediente`, `id_expediente_rel`, `observaciones`) VALUES
(4, '5600-5446-2025-', '5600-54654-2024-', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `id` varchar(128) NOT NULL,
  `data` blob NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sesiones`
--

INSERT INTO `sesiones` (`id`, `data`, `timestamp`) VALUES
('593aj1p5i9d7c7ulkesf718jho', 0x757365725f69647c693a313b757365725f7573756172696f7c733a353a2261646d696e223b757365725f726f6c7c733a353a2261646d696e223b757365725f677275706f5f69647c693a343b6e6f6d6272655f636f6d706c65746f7c733a31333a2241646d696e6973747261646f72223b67656e65726f7c733a393a224d617363756c696e6f223b69707c733a393a223132372e302e302e31223b696e6963696f7c733a31393a22323032352d30372d31332031313a31323a3132223b, 1752402255),
('b6k3gnd7okfqau6qfgrrvami65', '', 1752365894),
('bseh7btj4t1tj8see08u6ahcvi', 0x757365725f69647c693a313b757365725f7573756172696f7c733a353a2261646d696e223b757365725f726f6c7c733a353a2261646d696e223b757365725f677275706f5f69647c693a343b6e6f6d6272655f636f6d706c65746f7c733a31333a2241646d696e6973747261646f72223b67656e65726f7c733a393a224d617363756c696e6f223b69707c733a393a223132372e302e302e31223b696e6963696f7c733a31393a22323032352d30372d31332031323a34303a3235223b, 1752409263);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `data` longtext NOT NULL,
  `expires` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sessions`
--

INSERT INTO `sessions` (`id`, `data`, `expires`) VALUES
('672ueesf4dv1jdrlbu7u06uqis', 'user_id|i:1;user_usuario|s:5:\"admin\";user_rol|s:5:\"admin\";user_grupo_id|i:4;nombre_completo|s:13:\"Administrador\";genero|s:9:\"Masculino\";ip|s:13:\"192.168.0.159\";inicio|s:19:\"2025-07-27 21:15:39\";', 1753646012),
('asd56bobvb0a8dbpmma159bk0v', '', 1753656754),
('dg006vibjmf3qu1rn1octah8j0', '', 1753657089);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `submenus`
--

CREATE TABLE `submenus` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `submenus`
--

INSERT INTO `submenus` (`id`, `menu_id`, `nombre`, `url`, `orden`) VALUES
(1, 3, 'Lista de Usuarios', '/admin/lista_usuarios.php', 1),
(2, 3, 'Registrar Usuario', '/registro.php', 2),
(5, 9, 'Iconos Boostrap', '/visor-iconos.php', 10),
(6, 10, 'Certificado 349', '/gestionar_certificados.php', 2),
(7, 10, 'Expedientes', '/gestionar_exptes.php', 1),
(8, 10, 'Crear Año de expedientes', '/crear_tabla_expedientes.php', 11),
(9, 10, 'Proveedores', '/editar_proveedores.php', 3),
(10, 11, 'Reconocimiento de texto', '/ocr_tesseract_windows.php', 1),
(11, 11, 'Conversor de número a letra', '/convert_num_letra.php', 2),
(12, 10, 'Certificados 349 a VENCER', '/certificados_alerta.php', 4),
(14, 2, 'Editor de Menú', '/admin/admin_permisos_menu.php', 10),
(15, 2, 'Editor permisos a modulos por grupos', '/admin/admin_permisos_grupo.php', 10),
(17, 2, 'Editar grupos', '/admin/admin_grupos.php', 10),
(18, 3, 'Administrar sesiones', '/admin/admin_sesiones.php', 10),
(19, 2, 'Columnas', '/gestionar_columnas.php', 10),
(20, 10, 'Organismos', '/gestion_organismo.php', 10),
(21, 2, 'Icono Favicon', '/includes/favicon/favicon_editor.php', 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `nombre_completo` varchar(150) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `genero` enum('Masculino','Femenino','@','Otro') DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','usuario') NOT NULL DEFAULT 'usuario',
  `grupo` enum('despacho','tesoreria','juridico') NOT NULL DEFAULT 'despacho',
  `grupo_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `nombre_completo`, `apellido`, `genero`, `usuario`, `email`, `password`, `rol`, `grupo`, `grupo_id`) VALUES
(1, 'Administrador', 'Administrador', NULL, 'Masculino', 'admin', 'admin@example.com', '$2y$10$GIgPCiDSrTVhbJj5YE81HelZfaihb7OAaaZufSBXw.Da7BpA/lTRO', 'admin', 'despacho', 4),
(2, 'Usuario Normal', 'usuario de prueba', NULL, 'Masculino', 'user', 'user@example.com', '$2y$10$1AHnq/JY9MFHEZc2RtyZeOKP73R3oUa6ZoBPB7.PnKwVLY.SNjUVy', 'usuario', 'tesoreria', 3);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cert_349-2024`
--
ALTER TABLE `cert_349-2024`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cert_349-2025`
--
ALTER TABLE `cert_349-2025`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `columnas_alias_global`
--
ALTER TABLE `columnas_alias_global`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `columna_unique` (`nombre_columna`);

--
-- Indices de la tabla `expediente_proveedor_monto-2023`
--
ALTER TABLE `expediente_proveedor_monto-2023`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_expediente` (`id_expediente`);

--
-- Indices de la tabla `expediente_proveedor_monto-2024`
--
ALTER TABLE `expediente_proveedor_monto-2024`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expediente_proveedor_mes` (`id_expediente`,`nombre_proveedor`,`mes`);

--
-- Indices de la tabla `expediente_proveedor_monto-2025`
--
ALTER TABLE `expediente_proveedor_monto-2025`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expediente_proveedor_mes` (`id_expediente`,`nombre_proveedor`,`mes`);

--
-- Indices de la tabla `exptes-2023`
--
ALTER TABLE `exptes-2023`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `exptes-2024`
--
ALTER TABLE `exptes-2024`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `exptes-2025`
--
ALTER TABLE `exptes-2025`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `permisos_grupos`
--
ALTER TABLE `permisos_grupos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `permisos_menu_grupo`
--
ALTER TABLE `permisos_menu_grupo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD UNIQUE KEY `nombre_proveedor` (`nombre_proveedor`),
  ADD UNIQUE KEY `nombre_proveedor_2` (`nombre_proveedor`),
  ADD UNIQUE KEY `cuit` (`cuit`);

--
-- Indices de la tabla `rel_expte-2023`
--
ALTER TABLE `rel_expte-2023`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `relacion_unica` (`id_expediente`,`id_expediente_rel`),
  ADD KEY `id_expediente` (`id_expediente`),
  ADD KEY `id_expediente_rel` (`id_expediente_rel`);

--
-- Indices de la tabla `rel_expte-2024`
--
ALTER TABLE `rel_expte-2024`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `relacion_unica` (`id_expediente`,`id_expediente_rel`),
  ADD KEY `id_expediente` (`id_expediente`);

--
-- Indices de la tabla `rel_expte-2025`
--
ALTER TABLE `rel_expte-2025`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `relacion_unica` (`id_expediente`,`id_expediente_rel`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `submenus`
--
ALTER TABLE `submenus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `fk_grupo` (`grupo_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cert_349-2024`
--
ALTER TABLE `cert_349-2024`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cert_349-2025`
--
ALTER TABLE `cert_349-2025`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `columnas_alias_global`
--
ALTER TABLE `columnas_alias_global`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `expediente_proveedor_monto-2023`
--
ALTER TABLE `expediente_proveedor_monto-2023`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `expediente_proveedor_monto-2024`
--
ALTER TABLE `expediente_proveedor_monto-2024`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `expediente_proveedor_monto-2025`
--
ALTER TABLE `expediente_proveedor_monto-2025`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT de la tabla `exptes-2023`
--
ALTER TABLE `exptes-2023`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exptes-2024`
--
ALTER TABLE `exptes-2024`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `exptes-2025`
--
ALTER TABLE `exptes-2025`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `permisos_grupos`
--
ALTER TABLE `permisos_grupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=454;

--
-- AUTO_INCREMENT de la tabla `permisos_menu_grupo`
--
ALTER TABLE `permisos_menu_grupo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=583;

--
-- AUTO_INCREMENT de la tabla `rel_expte-2023`
--
ALTER TABLE `rel_expte-2023`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rel_expte-2024`
--
ALTER TABLE `rel_expte-2024`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rel_expte-2025`
--
ALTER TABLE `rel_expte-2025`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `submenus`
--
ALTER TABLE `submenus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `submenus`
--
ALTER TABLE `submenus`
  ADD CONSTRAINT `submenus_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
