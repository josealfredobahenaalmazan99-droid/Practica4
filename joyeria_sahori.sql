-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-05-2026 a las 21:27:35
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
-- Base de datos: `joyeria_sahori`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `apartados`
--

CREATE TABLE `apartados` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `fecha_apartado` date DEFAULT NULL,
  `fecha_limite` date DEFAULT NULL,
  `anticipo` decimal(10,2) DEFAULT NULL,
  `saldo_restante` decimal(10,2) DEFAULT NULL,
  `estatus` enum('Pendiente','Liquidado') DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `apartados`
--

INSERT INTO `apartados` (`id`, `cliente_id`, `producto_id`, `fecha_apartado`, `fecha_limite`, `anticipo`, `saldo_restante`, `estatus`) VALUES
(9, 5, 3, '2025-12-06', '2026-01-05', 300.00, 0.00, ''),
(10, 2, 8, '2025-12-07', '2026-01-06', 200.00, 0.00, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `observacion` varchar(150) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre`, `telefono`, `email`, `direccion`, `observacion`, `fecha_registro`) VALUES
(2, 'jose alberto', '7331234556', 'admin@example.com', 'igualayork su casa', 'cliente moroso', '2025-11-14 23:10:01'),
(4, 'SEñora flores', '7331456868', '', '', 'Dejo ', '2025-12-01 23:19:56'),
(5, 'manuel example ', '777 123 1212', 'manuel@example.com', 'iguala', 'cliente frecuente', '2025-12-05 17:10:52'),
(6, 'eduardo', '7621250821', '22670041@itiguala.edu.mx', 'taxco Guerrero', 'cliente frecuente', '2025-12-05 17:40:55'),
(7, 'maritza', '7621250822', 'indalialara@gmail.com', 'taxco de alarcon', 'dnndn', '2025-12-11 17:26:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`id`, `venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 3, 1, 600.00, 600.00),
(2, 2, 3, 1, 600.00, 600.00),
(3, 3, 3, 5, 600.00, 3000.00),
(4, 3, 7, 6, 700.00, 4200.00),
(5, 4, 7, 1, 700.00, 700.00),
(6, 5, 3, 4, 600.00, 2400.00),
(7, 5, 8, 5, 380.00, 1900.00),
(8, 5, 9, 1, 850.00, 850.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devoluciones`
--

CREATE TABLE `devoluciones` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_apartado`
--

CREATE TABLE `pagos_apartado` (
  `id` int(11) NOT NULL,
  `apartado_id` int(11) NOT NULL,
  `fecha_pago` date NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `saldo_restante` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) DEFAULT 'Efectivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos_apartado`
--

INSERT INTO `pagos_apartado` (`id`, `apartado_id`, `fecha_pago`, `monto`, `saldo_restante`, `metodo_pago`) VALUES
(1, 9, '2025-12-06', 200.00, 100.00, 'Efectivo'),
(2, 9, '2025-12-07', 50.00, 50.00, 'Efectivo'),
(3, 9, '2025-12-07', 50.00, 0.00, 'Efectivo'),
(4, 10, '2025-12-07', 100.00, 80.00, 'Efectivo'),
(5, 10, '2025-12-07', 80.00, 0.00, 'Efectivo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_pedidos`
--

CREATE TABLE `pagos_pedidos` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `fecha_pago` date NOT NULL,
  `monto_pago` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `material` varchar(100) DEFAULT NULL,
  `talla` varchar(50) DEFAULT NULL,
  `peso_aproximado` decimal(10,2) DEFAULT NULL,
  `fecha_pedido` date DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `monto_total` decimal(10,2) DEFAULT NULL,
  `anticipo` decimal(10,2) DEFAULT NULL,
  `saldo_restante` decimal(10,2) DEFAULT NULL,
  `estatus` varchar(50) DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `cliente_id`, `descripcion`, `material`, `talla`, `peso_aproximado`, `fecha_pedido`, `fecha_entrega`, `monto_total`, `anticipo`, `saldo_restante`, `estatus`) VALUES
(1, 4, 'Esclava con letras sobrepuestas', 'plata', '9', 2.32, '2025-12-01', '2025-12-02', 1500.00, 500.00, 400.00, 'Pendiente'),
(2, 5, 'anillo de diamante', 'plata 925', 'talla 8', 5.00, '2025-12-04', '2025-12-08', 7000.00, 2000.00, 5000.00, 'Cancelado'),
(3, 5, 'anillo para matrimonio con nombres', 'oro', '8', 15.00, '2025-12-05', '2025-12-12', 25000.00, 25000.00, 0.00, 'Completado'),
(4, 7, 'cadena de oro', 'oro', '40 cm', 7.00, '2025-12-11', '2026-01-11', 10000.00, 5000.00, 5000.00, 'En proceso');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_especiales`
--

CREATE TABLE `pedidos_especiales` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_pedido` date DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `anticipo` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `estatus` enum('En proceso','Completado','Cancelado') DEFAULT 'En proceso'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `material` varchar(50) DEFAULT NULL,
  `color` varchar(100) NOT NULL,
  `precio` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `tipo`, `material`, `color`, `precio`, `cantidad`, `id_proveedor`, `codigo`) VALUES
(3, 'anillo de oro', 'que mas quieres que te diga, es un anillo de oro\r\n', 'anillo', 'oro', 'oro', 600, 1, NULL, NULL),
(5, 'Cadena', 'Cadena torsal de 55 cm', 'Torsal', 'Plata', 'Plateado ', 650, 20, NULL, NULL),
(7, 'Cadena', 'Cadena torsalina de 55 cm', 'torsalina', 'plata', 'plata', 700, 5, NULL, '2267'),
(8, 'anillo example', 'anillo de plata de circonia', 'anillo de circonia', 'plata', 'azul', 380, 5, NULL, '1234'),
(9, 'collar', 'wdss', 'plata', 'plata', 'Plateado', 850, 9, NULL, '0123');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(20) NOT NULL DEFAULT 'empleado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `rol`) VALUES
(3, 'emilio', '$2y$10$AH6RCSa4RLGDbiMIXukYyeVBjCi2V6vwZczlUhbkHIATIjvX5zSfu', 'admin'),
(6, 'admin', '$2y$10$eNqzA.9rOFb6SwD4t/vxF.ksfLkd2DtMSKyF5d9NP9g5BMP9hGIR6', 'admin'),
(7, 'maritza', '$2y$10$XuETxGP4EFt0.w3Anwm4xuFLdYpgqdzAqvqAS9Md0heCg5qS.Tz1.', 'admin'),
(8, 'morris', '$2y$10$K/UZiDeKvYc3vyUWOTHvquLJo/2wyCxUEo3QVGkjWgN2m7yR02zGq', 'empleado'),
(9, 'farina', '$2y$10$WXWZU5CTyyBCVv4S2KUB0.fWwnYVG2rAuYKLXeD.e/Mnei5m.VCtu', 'empleado'),
(10, 'chofis', '$2y$10$eZUlXeZK3CgDto7M63RHNuOpjU1/56lI2jRVcLd8AJosWrxniunka', 'empleado'),
(11, 'kenia', '$2y$10$elWM1E1b2Zi8gt53NCi3v..MxpFuEEW3IX95eu/eL.AFWdZHNJxfu', 'empleado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `metodo_pago` varchar(50) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `monto_recibido` decimal(10,2) DEFAULT NULL,
  `cambio` decimal(10,2) DEFAULT NULL,
  `banco_tarjeta` varchar(100) DEFAULT NULL,
  `ultimos4` varchar(4) DEFAULT NULL,
  `tipo_tarjeta` varchar(20) DEFAULT NULL,
  `autorizacion` varchar(50) DEFAULT NULL,
  `banco_transf` varchar(100) DEFAULT NULL,
  `n_operacion` varchar(100) DEFAULT NULL,
  `fecha_transf` datetime DEFAULT NULL,
  `pago_cliente` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `cliente_id`, `fecha`, `metodo_pago`, `total`, `usuario`, `monto_recibido`, `cambio`, `banco_tarjeta`, `ultimos4`, `tipo_tarjeta`, `autorizacion`, `banco_transf`, `n_operacion`, `fecha_transf`, `pago_cliente`) VALUES
(1, 2, '2025-11-14 17:11:09', 'Efectivo', 600.00, 'emilio', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2, '2025-12-05 11:15:33', 'Tarjeta', 600.00, 'emilio', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 5, '2025-12-05 11:34:03', 'Efectivo', 7200.00, 'emilio', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 5, '2025-12-07 13:04:38', 'Efectivo', 700.00, 'farina', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 7, '2025-12-11 11:36:32', 'Efectivo', 5150.00, 'emilio', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `apartados`
--
ALTER TABLE `apartados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `pagos_apartado`
--
ALTER TABLE `pagos_apartado`
  ADD PRIMARY KEY (`id`),
  ADD KEY `apartado_id` (`apartado_id`);

--
-- Indices de la tabla `pagos_pedidos`
--
ALTER TABLE `pagos_pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `pedidos_especiales`
--
ALTER TABLE `pedidos_especiales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`) USING BTREE;

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `apartados`
--
ALTER TABLE `apartados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos_apartado`
--
ALTER TABLE `pagos_apartado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pagos_pedidos`
--
ALTER TABLE `pagos_pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pedidos_especiales`
--
ALTER TABLE `pedidos_especiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `apartados`
--
ALTER TABLE `apartados`
  ADD CONSTRAINT `apartados_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `apartados_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `devoluciones`
--
ALTER TABLE `devoluciones`
  ADD CONSTRAINT `devoluciones_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`),
  ADD CONSTRAINT `devoluciones_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `devoluciones_ibfk_3` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `pagos_apartado`
--
ALTER TABLE `pagos_apartado`
  ADD CONSTRAINT `pagos_apartado_ibfk_1` FOREIGN KEY (`apartado_id`) REFERENCES `apartados` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos_pedidos`
--
ALTER TABLE `pagos_pedidos`
  ADD CONSTRAINT `pagos_pedidos_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `pedidos_especiales`
--
ALTER TABLE `pedidos_especiales`
  ADD CONSTRAINT `pedidos_especiales_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
