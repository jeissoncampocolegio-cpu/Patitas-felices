-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-11-2026 a las 16:39:52
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `patitas_felices`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascotas`
--

CREATE TABLE `mascotas` (
  `id_mascota` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `especie` enum('Perro','Gato','Otro') NOT NULL,
  `edad` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `sexo` enum('Macho','Hembra','No especificado') NOT NULL DEFAULT 'No especificado',
  `tamano` enum('Pequeño','Mediano','Grande') NOT NULL DEFAULT 'Mediano',
  `descripcion` text DEFAULT NULL,
  `foto` varchar(500) DEFAULT NULL,
  `estado` enum('Disponible','En proceso','Adoptado') NOT NULL DEFAULT 'Disponible',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mascotas`
--

INSERT INTO `mascotas` (`id_mascota`, `nombre`, `especie`, `edad`, `sexo`, `tamano`, `descripcion`, `foto`, `estado`, `fecha_registro`) VALUES
(1, 'Rocky', 'Perro', 2, 'Macho', 'Mediano', 'Cariñoso y juguetón, busca una familia que le dé tiempo y cuidado.', '../assets/img/mascotas/e7aab583de3afa0f439d7a18ff0c9bd1.jpg', 'Disponible', '2026-10-21 20:21:50'),
(2, 'Nala', 'Gato', 1, 'Hembra', 'Pequeño', 'Tranquila y curiosa. Ideal para una familia responsable.', '../assets/img/mascotas/c97d48feb5bb229ff5fca060b20fdc84.jpg', 'Disponible', '2026-10-21 20:21:50'),
(3, 'Max', 'Perro', 3, 'Macho', 'Grande', 'Activo y leal. Necesita un hogar con espacio para jugar.', '../assets/img/mascotas/ca58343aa4056bda75caec1588f94740.jpg', 'Disponible', '2026-10-21 20:21:50'),
(4, 'Luna', 'Gato', 2, 'Hembra', 'Mediano', 'Dulce y sociable, espera una familia que la cuide.', '../assets/img/mascotas/feb59d68cdb0031da46c0dcc96d4f0b4.jpg', 'Disponible', '2026-10-21 20:21:50'),
(5, 'salome', 'Gato', 2, 'Hembra', 'Pequeño', '', '../assets/img/mascotas/043f587dbcc9247f89c8c4eafb87bf6a.jpg', 'Disponible', '2026-10-21 20:38:25'),
(6, 'Toby', 'Perro', 3, 'Macho', 'Pequeño', '', '../assets/img/mascotas/440771d129783f00915852601e30dd50.jpg', 'Disponible', '2026-10-21 20:41:04'),
(7, 'Coco', 'Gato', 2, 'Hembra', 'Mediano', 'Coco es una gatita dulce, juguetona y curiosa. Le gusta tener un espacio cómodo para descansar y compartir momentos con quienes la cuidan. Está esperando una familia que le dé una segunda oportunidad y la acompañe durante toda su vida.', '../assets/img/mascotas/bec017d3f759e5415043dae46e3cb110.jpg', 'Disponible', '2026-10-21 20:45:29'),
(8, 'Bruno', 'Gato', 4, 'Macho', 'Mediano', 'Bruno es un gato tranquilo e independiente que disfruta descansar y observar todo a su alrededor. Busca un hogar donde respeten su espacio, le tengan paciencia y le permitan crear un vínculo de confianza con su nueva familia.', '../assets/img/mascotas/569c416346ba71ea233a6eb515d096b8.jpg', 'Disponible', '2026-10-21 20:47:50'),
(9, 'Mia', 'Gato', 2, 'Hembra', 'Pequeño', 'Mía es una gatita adorable, curiosa y llena de vida. Disfruta jugar con sus juguetes y descubrir cosas nuevas. Busca una familia responsable que le brinde alimentación adecuada, atención veterinaria y mucho cariño.', '../assets/img/mascotas/fe296234e01b629a6bc48cc2460448c2.jpg', 'Disponible', '2026-10-21 20:49:12'),
(10, 'Luna', 'Perro', 4, 'Hembra', 'Grande', 'Luna es una perrita dulce y compañera, ideal para quienes buscan una mascota con la que compartir momentos especiales. Merece una segunda oportunidad con personas que la cuiden, respeten y quieran como parte de su familia.', '../assets/img/mascotas/f61a8b6bed57ff978f1cd538aafdf6d3.jpg', 'Disponible', '2026-10-21 20:51:19'),
(11, 'Kiara', 'Perro', 3, 'Hembra', 'Pequeño', 'Kiara es una perrita tierna, curiosa y muy especial. Le encanta jugar y recibir atención. Está esperando una familia responsable que le ofrezca paciencia, protección y mucho amor.', '../assets/img/mascotas/1b3905566fbe28453eedeb16547221a1.jpg', 'Disponible', '2026-10-21 20:54:05'),
(12, 'Zeus', 'Perro', 7, 'Macho', 'Grande', 'Zeus es un perro noble y activo que disfruta los paseos y los juegos al aire libre. Necesita una familia comprometida con su bienestar, que tenga tiempo para acompañarlo y brindarle los cuidados que merece.', '../assets/img/mascotas/0cad4f4cbd10d17d22945905418044bd.jpg', 'Disponible', '2026-10-21 20:55:55'),
(13, 'Lola', 'Perro', 3, 'Hembra', 'Pequeño', 'Lola es una compañera cariñosa que disfruta descansar cerca de las personas y jugar durante el día. Busca un hogar tranquilo donde reciba atención, respeto y cariño todos los días.', '../assets/img/mascotas/2c84a138354372577d48889bc0453a2c.webp', 'Disponible', '2026-10-21 20:58:17'),
(14, 'Milo', 'Perro', 3, 'Macho', 'Pequeño', 'Perrito alegre y juguetón. Le encanta correr, explorar y compartir con las personas.', '../assets/img/mascotas/146640cf6492f1b4bdd58801ad611c92.webp', 'Disponible', '2026-11-04 13:52:36'),
(15, 'Simba', 'Perro', 4, 'Macho', 'Mediano', 'Cachorro curioso y amigable. Disfruta de los juegos y de conocer nuevos lugares.', '../assets/img/mascotas/133c52514ec84736c37703c1b91e7a11.jpg', 'Disponible', '2026-11-04 14:04:32'),
(16, 'Dante', 'Perro', 3, 'Macho', 'Mediano', 'Perro tranquilo y sociable. Le encanta descansar cerca de las personas y recibir caricias', '../assets/img/mascotas/b5fd4360b628242ad0e69ffd3bf0aa88.jpg', 'Disponible', '2026-11-04 14:14:14'),
(17, 'Oliver', 'Perro', 5, 'Macho', 'Mediano', 'perrito curioso y cariñoso. Disfruta de explorar su entorno y compartir momentos con su familia', '../assets/img/mascotas/af8f984840c64effa47ceedc1a3ca0e8.jpg', 'Disponible', '2026-11-04 14:17:43'),
(18, 'Felix', 'Gato', 1, 'Macho', 'Pequeño', 'Gato tranquilo e independiente. Busca un hogar donde pueda descansar y sentirse seguro.', '../assets/img/mascotas/f5703699f0beba964a72ec844c5bc5fb.jpg', 'Adoptado', '2026-11-04 14:22:30'),
(19, 'Manchitas', 'Gato', 4, 'Macho', 'Mediano', 'Gatito divertido y sociable. Disfruta de los juegos y de la compañía de las personas.', '../assets/img/mascotas/4d9d68fca4ef504d00dc1a4c2eb9cc9c.jpg', 'Disponible', '2026-11-04 14:33:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_contacto`
--

CREATE TABLE `mensajes_contacto` (
  `id_mensaje` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `asunto` varchar(150) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mensajes_contacto`
--

INSERT INTO `mensajes_contacto` (`id_mensaje`, `nombre`, `correo`, `asunto`, `mensaje`, `leido`, `fecha_envio`) VALUES
(2, 'Jeissoncampo', 'jeissoncampocolegio@gmail.com', 'jkhjh', 'mkbhjghjghghjg', 1, '2026-11-04 14:48:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_adopcion`
--

CREATE TABLE `solicitudes_adopcion` (
  `id_solicitud` int(10) UNSIGNED NOT NULL,
  `id_mascota` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED DEFAULT NULL,
  `nombre_solicitante` varchar(120) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `telefono` varchar(30) NOT NULL,
  `ciudad` varchar(100) NOT NULL,
  `motivo` text NOT NULL,
  `estado` enum('Pendiente','En revisión','Aprobada','Rechazada') NOT NULL DEFAULT 'Pendiente',
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitudes_adopcion`
--

INSERT INTO `solicitudes_adopcion` (`id_solicitud`, `id_mascota`, `id_usuario`, `nombre_solicitante`, `correo`, `telefono`, `ciudad`, `motivo`, `estado`, `fecha_solicitud`) VALUES
(2, 18, 2, 'Jeissoncampo Colegio', 'jeissoncampocolegio@gmail.com', '309839383', 'medellin', 'cuento con los recursos y tiemppo', 'Aprobada', '2026-11-04 15:09:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('admin','usuario') NOT NULL DEFAULT 'usuario',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `contrasena`, `rol`, `activo`, `fecha_registro`) VALUES
(1, 'Administrador', 'admin@patitasfelices.com', '$2y$10$s1kTQOZZgq6Cy69MeHGZU.z71SKuyGVyyH4choDiUuYNF1d6dKlaW', 'admin', 1, '2026-10-21 20:21:50'),
(2, 'Jeissoncampo Colegio', 'jeissoncampocolegio@gmail.com', '$2y$10$lJlQMkAK4G8Do335xv7M8.vblmcRB55QN.x91EcukAyExBYB8AyNu', 'usuario', 1, '2026-11-04 14:55:11');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD PRIMARY KEY (`id_mascota`),
  ADD KEY `idx_mascotas_estado` (`estado`),
  ADD KEY `idx_mascotas_especie` (`especie`);

--
-- Indices de la tabla `mensajes_contacto`
--
ALTER TABLE `mensajes_contacto`
  ADD PRIMARY KEY (`id_mensaje`),
  ADD KEY `idx_mensajes_leido` (`leido`);

--
-- Indices de la tabla `solicitudes_adopcion`
--
ALTER TABLE `solicitudes_adopcion`
  ADD PRIMARY KEY (`id_solicitud`),
  ADD KEY `idx_solicitudes_estado` (`estado`),
  ADD KEY `idx_solicitudes_mascota` (`id_mascota`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uq_usuarios_correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  MODIFY `id_mascota` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `mensajes_contacto`
--
ALTER TABLE `mensajes_contacto`
  MODIFY `id_mensaje` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `solicitudes_adopcion`
--
ALTER TABLE `solicitudes_adopcion`
  MODIFY `id_solicitud` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `solicitudes_adopcion`
--
ALTER TABLE `solicitudes_adopcion`
  ADD CONSTRAINT `fk_solicitudes_mascotas` FOREIGN KEY (`id_mascota`) REFERENCES `mascotas` (`id_mascota`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
