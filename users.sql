-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-02-2026 a las 14:53:26
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
-- Base de datos: `apidespacho`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `perfil_usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` int(11) NOT NULL DEFAULT 1,
  `identificador_asesor` bigint(11) DEFAULT NULL,
  `identificador_instalador` int(11) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `username`, `perfil_usuario_id`, `estado`, `identificador_asesor`, `identificador_instalador`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Jhon Joel Valencia Villamizar', 'sistemas1@dlux.com.co', NULL, 'jowel12', 1, 1, NULL, NULL, '$2y$12$QZjLVZJTw8Qrnk/bQpOIz.O2MRXNdwhTI0JkGWqWMohmLGctKCDPy', 'DGK9cdfzf3T9wdMGeKVwawktY45TMd4WBQTHqvAGqOFtmr0tWxub7LW3chUx', NULL, '2026-02-20 15:32:47'),
(2, 'Luis Mosquera M', 'produccion1@dlux.com.co', NULL, 'lmosquera', 4, 1, NULL, 0, '$2y$12$jrHWP44zCy7n9cWfy2b2su8NNpjmQjyI4jVUBVlciUQf5R02fDl8a', NULL, NULL, '2025-10-06 16:49:42'),
(3, 'Diana Loaiza', 'comercial4@dlux.com.co', NULL, 'diana', 5, 1, 1, 0, '$2y$12$i79dejXEdhH6ABR2vcaPJOpv7BiX.8KVju.VLN7TKIh6hBzcfn6BG', NULL, NULL, '2025-10-02 02:30:38'),
(4, 'Carlos Alcaraz', 'comercial1@dlux.com.co', NULL, 'carlos', 5, 1, 6, 0, '$2y$12$ud4d9aK8A0pDz8OF5GJC2uUbFyIAnxptfTlD3wVB/6Db6TqwiyQO2', NULL, '2025-09-30 23:33:02', '2025-10-01 00:57:21'),
(5, 'Hernan Alonso Gomez', 'comercial2@dlux.com.co', NULL, 'hgomezh', 5, 1, 4, NULL, '$2y$12$FVxc7TRmctR.swKSpRs18eQC/WYvHo.780wCNGHK.hAk6UW6aUwq6', NULL, '2025-10-01 01:17:30', '2026-02-16 19:04:44'),
(6, 'Mauricio Vargas', 'comercial3@dlux.com.co', NULL, 'mvargasr', 5, 1, 5, 0, '$2y$12$1yEw2SBn7ZkbclHkG1dQj.0xIHopuVpCYDyxqvTjuLvq3cNQu.Niu', NULL, '2025-10-01 01:18:41', '2025-10-01 01:18:41'),
(7, 'Juan Pablo Uribe', 'gerencia@dlux.com.co', NULL, 'juanp', 5, 1, 24, 0, '$2y$12$HflgSv8XtPZ/tZ8DvWYhy.s9M9lZXiAVgb/qqfKPsZXnf00mNO/0C', NULL, '2025-10-01 01:22:56', '2025-11-06 15:03:11'),
(8, 'Arboleda Ramirez Mauro Emilio', 'bodega@dlux.com.co', NULL, 'mauro', 5, 1, 11, NULL, '$2y$12$EWaYqcfuMuTcblxB96i9E.1Z3.sJY6HHwxbQW.cfeAgZt9Vizv0CK', NULL, '2025-10-01 01:25:15', '2026-02-09 15:17:37'),
(9, 'Miguel Bonilla', 'comercial7@dlux.com.co', NULL, 'miguel', 5, 1, 32, 0, '$2y$12$hOE53yZ5U4iRNUWbHayWqerJlljbn6rd6RLtkIpPn.CzDNf4aCCV2', NULL, '2025-10-01 01:26:22', '2025-10-01 01:26:22'),
(10, 'Camilo Rincón', 'comercial8@dlux.com.co', NULL, 'camilo', 5, 1, 31, 0, '$2y$12$ofH5xZMGRhW13Uwwu.YaHeFIoru9XzK3KowStYSp2ZXtLfyA9eMxK', NULL, '2025-10-01 01:27:36', '2025-10-01 01:27:36'),
(11, 'Sergio Ochoa', 'controliluminacion@dlux.com.co', NULL, 'sergio', 6, 1, 28, NULL, '$2y$12$E3ac3LbY3A8FP5/EyFeT6e5Pddk/syDu3sJRAvTrvqXFNEjXjB.pm', NULL, '2025-10-01 01:29:20', '2026-02-23 15:17:34'),
(12, 'Tatiana Giraldo', 'bodega2@dlux.com.co', NULL, 'tatiana', 2, 1, NULL, NULL, '$2y$12$YJ89nF78ObcDM7jC9Y7.jOQK3HH.JxaQXUgEnLLQNjvLCprPx.oXO', 'Mj3reRToYAVmCEhA2CulwzcgE1ElVGzWVxdXDA3QIrDoXkGCEsDLBV9ef0WM', '2025-10-02 01:36:41', '2026-02-25 12:23:56'),
(13, 'Martín Múnera', 'diseno@dlux.com.co', NULL, 'mmunera', NULL, 1, NULL, 0, '$2y$12$TwTENgWbai7teSp8Ngze/.7wctGn8kU5sbivzRI.Znhq1OY.2QLHC', 'ouQGC8EALXOPX762kXe60mfW9TkVvQmEc0ShJZGvdwavqaSsSERPZxdO9I8D', '2025-11-04 12:43:07', '2025-11-04 12:43:07'),
(14, 'Esteban Arredondo', 'coordinadorgeneral@dlux.com.co', NULL, 'estebang', 5, 1, 27, NULL, '$2y$12$JO1LrCSe2rdpN8G2x/BMSem2scAH4dxaU54kF2ltKyyCapmUy.8Qa', NULL, '2025-11-06 15:01:27', '2026-02-23 15:33:07'),
(15, 'William Rios', 'comercial5@dlux.com.co', NULL, 'williamr', 5, 1, 25, 0, '$2y$12$LPJaUkrv/VSPfjU5sDyLROECgANMNj6RBmsriBOg4LJjAV9.mOYuG', NULL, '2025-11-06 15:04:26', '2025-11-06 15:04:26'),
(16, 'Andica Cano', 'dircomercial1@dlux.com.co', NULL, 'andica', NULL, 1, NULL, 0, '$2y$12$e6MOvuLhB33d0nnbgAEN1.PmHKnANW6FsMCsXqvia9NdzgCIPFeOy', NULL, '2025-11-25 18:28:58', '2025-11-25 18:28:58'),
(24, 'Carlos Andres', 'na@gmail.com', NULL, 'carlosa', 7, 1, NULL, 1, '$2y$12$9eM7cr/y2277QxvU2/6iPuTYK22Xlbv6UKbcarqoGXVxiDoHJZIFa', NULL, '2025-12-11 15:39:10', '2025-12-11 15:39:10'),
(25, 'Jiader Emel', 'naa@gmail.com', NULL, 'jaider', 7, 1, NULL, 3, '$2y$12$P.8feBFUv2JoMDSI7ePDW.5VDQQqvBx8RQX5RxfqpvAg7SjTl5c3u', NULL, '2025-12-11 15:40:11', '2026-02-16 19:02:44'),
(26, 'Genaro Muñoz', 'instalaciones@dlux.com.co', NULL, 'genarom', 7, 1, NULL, 2, '$2y$12$ygZ7UsWrjMyFGVAGzJIfNO66iJPuNP94nCe4a7944NfwymsEG7dG6', NULL, '2026-02-11 19:04:19', '2026-02-23 15:51:46');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD KEY `users_perfil_usuario_id_foreign` (`perfil_usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_perfil_usuario_id_foreign` FOREIGN KEY (`perfil_usuario_id`) REFERENCES `perfil_usuarios` (`id_perfil_usuario`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
