-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-08-2024 a las 02:29:58
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
-- Base de datos: `icontpos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `activos`
--

CREATE TABLE `activos` (
  `idactivo` int(11) NOT NULL,
  `imagen` varchar(150) NOT NULL,
  `codigo` varchar(150) NOT NULL,
  `nombreProducto` varchar(150) NOT NULL,
  `cantidad` varchar(150) NOT NULL,
  `fechaRegistro` varchar(150) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `activos`
--

INSERT INTO `activos` (`idactivo`, `imagen`, `codigo`, `nombreProducto`, `cantidad`, `fechaRegistro`) VALUES
(6, 'fotoproducto/escoba.jpg', '10023', 'Escoba verde', '2', '2020-08-29'),
(4, 'fotoproducto/valde.jpg', '1010', 'Balde', '4444', '2020-08-29'),
(5, 'fotoproducto/platos.jpg', 'Platos de plastico', 'Platos', '12', '2020-08-29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alerta`
--

CREATE TABLE `alerta` (
  `alertaId` int(11) NOT NULL,
  `tipoAlerta` varchar(150) NOT NULL,
  `mensaje` varchar(150) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `alerta`
--

INSERT INTO `alerta` (`alertaId`, `tipoAlerta`, `mensaje`) VALUES
(1, '', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `idcliente` int(11) NOT NULL,
  `foto` varchar(200) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `apellido` varchar(200) NOT NULL,
  `direccion` varchar(200) NOT NULL,
  `telefonoFijo` varchar(200) NOT NULL,
  `telefonoCelular` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `contactoReferencia` varchar(200) NOT NULL,
  `telefonoReferencia` varchar(200) NOT NULL,
  `observaciones` text NOT NULL,
  `fechaRegistro` date NOT NULL,
  `ci` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`idcliente`, `foto`, `nombre`, `apellido`, `direccion`, `telefonoFijo`, `telefonoCelular`, `email`, `contactoReferencia`, `telefonoReferencia`, `observaciones`, `fechaRegistro`, `ci`) VALUES
(1, 'fotoproducto/user03.jpg', 'Juan  Manuel l', 'Perez Messil', 'Av. siempre viva Nro 1101l', '43434334l', '676846444l', 'juan@gmail.coml', '', '', '', '2020-08-25', '5555'),
(11, 'fotoproducto/user03.jpg', 'Jose', 'Calani A.', 'Av. siempre viva Nro 1101l', '43434334l', '1234', 'juan@gmail.coml', '', '', '', '2020-08-25', '1234'),
(12, 'fotoproducto/user03.jpg', 'Mike', 'Muirhead', 'Av. siempre viva Nro 1101l', '43434334l', '5678', 'juan@gmail.coml', '', '', '', '2020-08-25', '5678'),
(13, 'fotoproducto/user03.jpg', 'Clint ', 'connell', 'Av. siempre viva Nro 1101l', '43434334l', '9090', 'juan@gmail.coml', '', '', '', '2020-08-25', '9090'),
(14, 'fotoproducto/user03.jpg', '', 'S/N', '', '', '', '', '', '', '', '2020-08-25', '0'),
(15, 'fotoUsuario/user.png', '', 'Calani', '', '', '', '', '', '', '', '0000-00-00', '6444685'),
(16, 'fotoUsuario/user.png', '', 'Perez', '', '', '', '', '', '', '', '2020-10-13', '909090'),
(17, 'fotoUsuario/user.png', '', 'Encinas', '', '', '', '', '', '', '', '2020-10-13', '77777');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientedato`
--

CREATE TABLE `clientedato` (
  `idCliente` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `ci` varchar(50) NOT NULL,
  `fecha` datetime NOT NULL,
  `totalApagar` double NOT NULL,
  `efectivo` double NOT NULL,
  `cambio` double NOT NULL,
  `idClientei` varchar(150) NOT NULL,
  `tipoVenta` varchar(150) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datos`
--

CREATE TABLE `datos` (
  `iddatos` int(11) NOT NULL,
  `propietario` varchar(100) NOT NULL,
  `razon` varchar(100) NOT NULL,
  `direccion` varchar(100) NOT NULL,
  `nro` varchar(100) NOT NULL,
  `telefono` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `datos`
--

INSERT INTO `datos` (`iddatos`, `propietario`, `razon`, `direccion`, `nro`, `telefono`) VALUES
(1, 'Carlos Herrera', 'Pollos Carlitos', 'Av. Circunvalacion Melchor Perez', '15173', '4477129');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datosclienteventa`
--

CREATE TABLE `datosclienteventa` (
  `idClienteVenta` int(11) NOT NULL,
  `fechaVenta` datetime NOT NULL,
  `nitCliente` varchar(50) NOT NULL,
  `cliente` varchar(50) NOT NULL,
  `codigoControl` varchar(50) NOT NULL,
  `idVentas` varchar(50) NOT NULL,
  `estado` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `datosclienteventa`
--

INSERT INTO `datosclienteventa` (`idClienteVenta`, `fechaVenta`, `nitCliente`, `cliente`, `codigoControl`, `idVentas`, `estado`) VALUES
(1, '2020-10-11 05:55:25', '0', 'S/N', 'C0-32-A1-2F', '1', 'Consolidado'),
(2, '2020-10-11 06:15:11', '0', 'S/N', 'C0-32-A1-2F', '2', 'Consolidado'),
(3, '2020-10-11 06:16:16', '0', 'S/N', 'C0-32-A1-2F', '3', 'Consolidado'),
(4, '2020-10-11 06:22:41', '0', 'S/N', 'C0-32-A1-2F', '4', 'Consolidado'),
(5, '2020-10-11 06:23:04', '0', 'S/N', '38-F5-57-B1', '5', 'Consolidado'),
(6, '2020-10-11 07:43:38', '0', 'S/N', '38-F5-57-B1', '6', 'Consolidado'),
(7, '2020-10-11 07:50:09', '0', 'S/N', 'F7-2E-18-10', '7', 'Consolidado'),
(8, '2020-10-11 07:51:51', '0', 'S/N', 'F7-2E-18-10', '8', 'Consolidado'),
(9, '2020-10-12 13:45:12', '6444685', 'Calani', '78-04-D8-27', '9', 'Consolidado'),
(10, '2020-10-13 13:46:17', '909090', 'Perez', 'CD-AF-BF-87', '10', 'Consolidado'),
(11, '2020-10-13 13:48:31', '909090', 'Perez', '43-00-D7-0A', '11', 'Consolidado'),
(12, '2020-10-13 13:48:45', '909090', 'Perez', 'CD-AF-BF-87', '12', 'Consolidado'),
(13, '2020-10-13 13:49:04', '77777', 'Encinas', 'F6-06-55-D5-E3', '13', 'Consolidado'),
(14, '2020-10-13 13:49:18', '77777', 'Encinas', 'F6-06-55-D5-E3', '14', 'Consolidado'),
(15, '2020-10-16 13:19:37', '5555', 'Perez Messil', '64-0F-64-D9-FE', '15', 'Consolidado'),
(16, '2020-10-17 12:16:56', '0', 'S/N', 'F8-8D-73-1D', '16', 'NoConsolidado'),
(17, '2020-10-18 12:46:28', '5555', 'Perez Messil', '18-8E-24-FC-55', '17', 'Consolidado'),
(18, '2020-11-01 22:33:01', '0', 'S/N', '9E-25-79-D2-F8', '18', 'NoConsolidado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datosfacturaventa`
--

CREATE TABLE `datosfacturaventa` (
  `idDatosFactura` int(11) NOT NULL,
  `nit` varchar(50) NOT NULL,
  `factura` varchar(50) NOT NULL,
  `numeroAutorizacion` varchar(50) NOT NULL,
  `codigoControl` varchar(50) NOT NULL,
  `idVentas` varchar(50) NOT NULL,
  `estado` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `datosfacturaventa`
--

INSERT INTO `datosfacturaventa` (`idDatosFactura`, `nit`, `factura`, `numeroAutorizacion`, `codigoControl`, `idVentas`, `estado`) VALUES
(1, '1689353', '1503', '29040011007', 'C0-32-A1-2F', '1', 'Consolidado'),
(2, '1689353', '1503', '29040011007', 'C0-32-A1-2F', '2', 'Consolidado'),
(3, '1689353', '1503', '29040011007', 'C0-32-A1-2F', '3', 'Consolidado'),
(4, '1689353', '1503', '29040011007', 'C0-32-A1-2F', '4', 'Consolidado'),
(5, '1689353', '1503', '29040011007', '38-F5-57-B1', '5', 'Consolidado'),
(6, '1689353', '1503', '29040011007', '38-F5-57-B1', '6', 'Consolidado'),
(7, '1689353', '1503', '29040011007', 'F7-2E-18-10', '7', 'Consolidado'),
(8, '1689353', '1503', '29040011007', 'F7-2E-18-10', '8', 'Consolidado'),
(9, '1689353', '1503', '29040011007', '78-04-D8-27', '9', 'Consolidado'),
(10, '1689353', '1503', '29040011007', 'CD-AF-BF-87', '10', 'Consolidado'),
(11, '1689353', '1503', '29040011007', '43-00-D7-0A', '11', 'Consolidado'),
(12, '1689353', '1503', '29040011007', 'CD-AF-BF-87', '12', 'Consolidado'),
(13, '1689353', '1503', '29040011007', 'F6-06-55-D5-E3', '13', 'Consolidado'),
(14, '1689353', '1503', '29040011007', 'F6-06-55-D5-E3', '14', 'Consolidado'),
(15, '1689353', '1503', '29040011007', '64-0F-64-D9-FE', '15', 'Consolidado'),
(16, '1689353', '1503', '29040011007', 'F8-8D-73-1D', '16', 'NoConsolidado'),
(17, '1689353', '1503', '29040011007', '18-8E-24-FC-55', '17', 'Consolidado'),
(18, '1689353', '1503', '29040011007', '9E-25-79-D2-F8', '18', 'NoConsolidado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datosventa`
--

CREATE TABLE `datosventa` (
  `idDatosVentas` int(11) NOT NULL,
  `cantidad` varchar(50) NOT NULL,
  `descripcion` varchar(50) NOT NULL,
  `precio` varchar(50) NOT NULL,
  `total` varchar(50) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `fechaVenta` datetime NOT NULL,
  `codigoControl` varchar(50) NOT NULL,
  `idVentas` double NOT NULL,
  `estado` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `datosventa`
--

INSERT INTO `datosventa` (`idDatosVentas`, `cantidad`, `descripcion`, `precio`, `total`, `tipo`, `fechaVenta`, `codigoControl`, `idVentas`, `estado`) VALUES
(1, '1', 'DUOPOLLO', '16', '16', 'Mesa', '2020-10-11 05:55:25', 'C0-32-A1-2F', 1, 'Consolidado'),
(2, '2', 'LIMITO', '13', '26', 'Mesa', '2020-10-11 05:55:25', 'C0-32-A1-2F', 1, 'Consolidado'),
(3, '1', 'DUOPOLLO', '16', '16', 'Mesa', '2020-10-11 06:15:11', 'C0-32-A1-2F', 2, 'Consolidado'),
(4, '2', 'LIMITO', '13', '26', 'Mesa', '2020-10-11 06:15:11', 'C0-32-A1-2F', 2, 'Consolidado'),
(5, '1', 'DUOPOLLO', '16', '16', 'Mesa', '2020-10-11 06:16:16', 'C0-32-A1-2F', 3, 'Consolidado'),
(6, '2', 'LIMITO', '13', '26', 'Mesa', '2020-10-11 06:16:16', 'C0-32-A1-2F', 3, 'Consolidado'),
(7, '1', 'DUOPOLLO', '16', '16', 'Mesa', '2020-10-11 06:22:41', 'C0-32-A1-2F', 4, 'Consolidado'),
(8, '2', 'LIMITO', '13', '26', 'Mesa', '2020-10-11 06:22:41', 'C0-32-A1-2F', 4, 'Consolidado'),
(9, '1', 'Pepsi de 500 ml', '5', '5', 'Mesa', '2020-10-11 06:23:04', '38-F5-57-B1', 5, 'Consolidado'),
(10, '1', 'Pepsi de 500 ml', '5', '5', 'Mesa', '2020-10-11 07:43:38', '38-F5-57-B1', 6, 'Consolidado'),
(11, '1', 'LIMITO', '13', '13', 'Mesa', '2020-10-11 07:50:09', 'F7-2E-18-10', 7, 'Consolidado'),
(12, '1', 'LIMITO', '13', '13', 'Mesa', '2020-10-11 07:51:51', 'F7-2E-18-10', 8, 'Consolidado'),
(13, '1', 'LIMITO', '13', '13', 'Mesa', '2020-10-12 13:45:12', '78-04-D8-27', 9, 'Consolidado'),
(14, '1', 'Coca Cola 500 gr', '7', '7', 'Mesa', '2020-10-12 13:45:12', '78-04-D8-27', 9, 'Consolidado'),
(15, '1', 'LIMITO', '13', '13', 'Mesa', '2020-10-13 13:46:17', 'CD-AF-BF-87', 10, 'Consolidado'),
(16, '1', 'DUOPOLLO', '16', '16', 'Mesa', '2020-10-13 13:48:31', '43-00-D7-0A', 11, 'Consolidado'),
(17, '1', 'LIMITO', '13', '13', 'Mesa', '2020-10-13 13:48:45', 'CD-AF-BF-87', 12, 'Consolidado'),
(18, '1', 'DUOPOLLO', '16', '16', 'Mesa', '2020-10-13 13:49:04', 'F6-06-55-D5-E3', 13, 'Consolidado'),
(19, '1', 'DUOPOLLO', '16', '16', 'Mesa', '2020-10-13 13:49:18', 'F6-06-55-D5-E3', 14, 'Consolidado'),
(20, '1', 'LIMITO', '13', '13', 'Mesa', '2020-10-16 13:19:37', '64-0F-64-D9-FE', 15, 'Consolidado'),
(21, '4', 'LIMITO', '13', '52', 'Mesa', '2020-10-17 12:16:56', 'F8-8D-73-1D', 16, 'NoConsolidado'),
(22, '1', 'LIMITO', '13', '13', 'Mesa', '2020-10-18 12:46:28', '18-8E-24-FC-55', 17, 'Consolidado'),
(23, '1', 'LIMITO', '13', '13', 'Mesa', '2020-11-01 22:33:01', '9E-25-79-D2-F8', 18, 'NoConsolidado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datosventatotal`
--

CREATE TABLE `datosventatotal` (
  `idVentas` int(11) NOT NULL,
  `cliente` varchar(50) NOT NULL,
  `cantidad` varchar(50) NOT NULL,
  `precio` varchar(50) NOT NULL,
  `total` varchar(50) NOT NULL,
  `codigoControl` varchar(50) NOT NULL,
  `fechaVenta` datetime NOT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `comentario` varchar(150) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `datosventatotal`
--

INSERT INTO `datosventatotal` (`idVentas`, `cliente`, `cantidad`, `precio`, `total`, `codigoControl`, `fechaVenta`, `estado`, `comentario`) VALUES
(1, 'S/N', '2', '13', '26', 'C0-32-A1-2F', '2020-01-11 05:55:25', 'Consolidado', 'No se encontro la Ficha'),
(2, 'S/N', '2', '13', '26', 'C0-32-A1-2F', '2020-02-11 06:15:11', 'Consolidado', 'La hoja si se encontro'),
(3, 'S/N', '2', '13', '26', 'C0-32-A1-2F', '2020-03-11 06:16:16', 'Consolidado', 'La hoja esta duplicada no tomar en cuenta'),
(4, 'S/N', '2', '13', '26', 'C0-32-A1-2F', '2020-04-11 06:22:41', 'Consolidado', ''),
(5, 'S/N', '1', '5', '5', '38-F5-57-B1', '2020-05-11 06:23:04', 'Consolidado', ''),
(6, 'S/N', '1', '5', '5', '38-F5-57-B1', '2020-06-11 07:43:38', 'Consolidado', ''),
(7, 'S/N', '1', '13', '13', 'F7-2E-18-10', '2020-07-11 07:50:09', 'Consolidado', ''),
(8, 'S/N', '1', '13', '13', 'F7-2E-18-10', '2020-08-11 07:51:51', 'Consolidado', ''),
(9, 'Calani', '1', '7', '7', '78-04-D8-27', '2020-09-12 13:45:12', 'Consolidado', ''),
(10, 'Perez', '1', '13', '13', 'CD-AF-BF-87', '2020-10-13 13:46:17', 'Consolidado', ''),
(11, 'Perez', '1', '16', '16', '43-00-D7-0A', '2020-11-13 13:48:31', 'Consolidado', ''),
(12, 'Perez', '1', '13', '13', 'CD-AF-BF-87', '2020-12-13 13:48:45', 'Consolidado', ''),
(13, 'Encinas', '1', '16', '16', 'F6-06-55-D5-E3', '2020-10-13 13:49:04', 'Consolidado', ''),
(14, 'Encinas', '1', '16', '16', 'F6-06-55-D5-E3', '2020-10-13 13:49:18', 'Consolidado', ''),
(15, 'Perez Messil', '1', '13', '13', '64-0F-64-D9-FE', '2020-10-16 13:19:37', 'Consolidado', ''),
(16, 'S/N', '4', '13', '52', 'F8-8D-73-1D', '2020-10-17 12:16:56', 'NoConsolidado', ''),
(17, 'Perez Messil', '1', '13', '13', '18-8E-24-FC-55', '2020-10-18 12:46:28', 'Consolidado', ''),
(18, 'S/N', '1', '13', '13', '9E-25-79-D2-F8', '2020-11-01 22:33:01', 'NoConsolidado', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dosificacion`
--

CREATE TABLE `dosificacion` (
  `idcodigo` int(11) NOT NULL,
  `autorizacion` varchar(150) NOT NULL,
  `factura` varchar(150) NOT NULL,
  `llave` varchar(500) NOT NULL,
  `nit` varchar(100) NOT NULL,
  `fechaL` date NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `dosificacion`
--

INSERT INTO `dosificacion` (`idcodigo`, `autorizacion`, `factura`, `llave`, `nit`, `fechaL`) VALUES
(1, '29040011007', '1503', '9rCB7Sv4X29d)5k7N%3ab89p-3(5[A', '1689353', '2020-12-31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ficha`
--

CREATE TABLE `ficha` (
  `idficha` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gastos`
--

CREATE TABLE `gastos` (
  `idgastos` int(11) NOT NULL,
  `descripcion` varchar(50) NOT NULL,
  `entrada` double NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `salida` varchar(50) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `fechaRegistro` date NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `gastos`
--

INSERT INTO `gastos` (`idgastos`, `descripcion`, `entrada`, `usuario`, `salida`, `tipo`, `fechaRegistro`) VALUES
(1, 'compra de Botellones', 0, 'henry', '100', 'S', '2020-07-06'),
(2, 'Venta de Helado', 45, 'henry', '0', 'E', '2020-06-29'),
(3, 'Tinte de Cabello platino', 20, 'henry', '0', 'E', '2020-06-01'),
(8, 'Cobro de Alquiler de Botellon', 12, 'henry', '0', 'E', '2020-10-14'),
(7, 'Compra de tarjetas de celulares', 0, 'henry', '100', 'S', '2020-10-14'),
(9, 'Pollo broaster al Team', 50, 'henry', '0', 'E', '2020-10-14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `idioma`
--

CREATE TABLE `idioma` (
  `idIdioma` int(11) NOT NULL,
  `pais` varchar(150) NOT NULL,
  `idioma` varchar(150) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `idioma`
--

INSERT INTO `idioma` (`idIdioma`, `pais`, `idioma`) VALUES
(1, 'Bolivia', 'Espaniol');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu`
--

CREATE TABLE `menu` (
  `idmenu` int(11) NOT NULL,
  `opcion` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `estado` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `icon` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `color` varchar(150) NOT NULL,
  `acceso` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `menu`
--

INSERT INTO `menu` (`idmenu`, `opcion`, `estado`, `icon`, `location`, `color`, `acceso`) VALUES
(1, 'Principal', 'Activo', 'icon_house_alt', 'AccessUsers.php', '#4e4e4e', 'A'),
(2, 'Configuracion', 'NoActivo', 'icon_tools', 'Usuario.php', '#0061c2', 'D'),
(3, 'Proveedores', 'NoActivo', 'icon_briefcase', 'Proveedor.php', '#0061c2', 'D'),
(4, 'Clientes', 'NoActivo', 'icon_group', 'Cliente.php', '#0061c2', 'A'),
(5, 'Productos', 'NoActivo', 'icon_bag_alt', 'Producto.php', '#0061c2', 'A'),
(6, 'Inventario', 'NoActivo', 'icon_refresh', 'Inventario.php', '#0061c2', 'D'),
(7, 'Ventas', 'NoActivo', 'icon_cart', 'Ventas.php', '#0061c2', 'A'),
(8, 'Cuentas', 'NoActivo', 'arrow_down_alt', 'Cuenta.php', '#0061c2', 'D'),
(9, 'Pedidos', 'NoActivo', 'icon_zoom-in_alt', 'Pedido.php', '#0061c2', 'D'),
(10, 'Consolidar', 'NoActivo', 'icon_documents_alt', 'Consolidar.php', '#0061c2', 'D'),
(11, 'Reporte', 'NoActivo', 'icon_piechart', 'ReportesVentas.php', '#0061c2', 'A'),
(12, 'Reportes Graficos', 'NoActivo', 'icon_datareport', 'Estadistica.php', '#0061c2', 'A');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `moneda`
--

CREATE TABLE `moneda` (
  `idMoneda` int(11) NOT NULL,
  `pais` varchar(150) NOT NULL,
  `tipoMoneda` varchar(150) NOT NULL,
  `contexto` varchar(150) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `moneda`
--

INSERT INTO `moneda` (`idMoneda`, `pais`, `tipoMoneda`, `contexto`) VALUES
(1, 'Estados Unidos', '$ USD', 'dólar estadounidense');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `idPedido` int(11) NOT NULL,
  `descripcion` varchar(500) NOT NULL,
  `total` double NOT NULL,
  `proveedor` varchar(500) NOT NULL,
  `usuario` varchar(150) NOT NULL,
  `fechaRegistro` date NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `pedido`
--

INSERT INTO `pedido` (`idPedido`, `descripcion`, `total`, `proveedor`, `usuario`, `fechaRegistro`) VALUES
(1, 'Tela de mascarilla', 34, 'Hypertrading ', 'henry', '2020-06-13'),
(2, '23 computadoras', 10, 'Bolivia Sport', 'henry', '2020-06-15'),
(3, 'Detergente de 500 mg', 12, 'Unilever', 'henry', '2020-07-19'),
(6, 'Grano de Oro exportacion', 255, 'Arroz Okinawa', 'henry', '2020-10-15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preventa`
--

CREATE TABLE `preventa` (
  `idPreventa` int(11) NOT NULL,
  `imagen` varchar(150) NOT NULL,
  `producto` varchar(150) NOT NULL,
  `precio` double NOT NULL,
  `idProducto` varchar(100) NOT NULL,
  `pventa` varchar(150) NOT NULL,
  `idUser` int(11) NOT NULL,
  `tipo` varchar(150) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `idproducto` int(11) NOT NULL,
  `imagen` varchar(150) NOT NULL,
  `codigo` varchar(150) NOT NULL,
  `nombreProducto` varchar(150) NOT NULL,
  `cantidad` varchar(150) NOT NULL,
  `fechaRegistro` varchar(150) NOT NULL,
  `precioVenta` varchar(150) NOT NULL,
  `tipo` varchar(150) NOT NULL,
  `proveedor` varchar(150) NOT NULL,
  `precioCompra` varchar(150) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`idproducto`, `imagen`, `codigo`, `nombreProducto`, `cantidad`, `fechaRegistro`, `precioVenta`, `tipo`, `proveedor`, `precioCompra`) VALUES
(1, 'fotoproducto/imagen_1349563845.jpg', 'Un Cuarto', 'Un Cuarto', '100', '2020-06-12', '23', 'POLLO', '6', '6'),
(2, 'fotoproducto/imagen_1349563882.jpg', 'DUOPOLLO', 'DUOPOLLO', '15', '2018-04-28', '16', 'POLLO', '6', '6'),
(3, 'fotoproducto/imagen_1349563944.jpg', 'LIMITO', 'LIMITO', '12', '2018-04-28', '13', 'POLLO', '6', '6'),
(4, 'fotoproducto/imagen_1349563944.jpg', 'ECONOMICO', 'ECONOMICO', '8', '2018-05-06', '8', 'POLLO', '6', '0'),
(5, 'fotoproducto/imagen_1349564147.jpg', 'SUPER COMBO', 'SUPER COMBO', '100', '2018-05-06', '10', 'POLLO', '6', '6'),
(6, 'fotoproducto/imagen_1349564147.jpg', 'ALMUERZO COMPLETO', 'ALMUERZO COMPLETO', '11', '2018-04-28', '10', 'POLLO', '6', '6'),
(7, 'fotoproducto/imagen_1349564229.jpg', 'SEGUNDO', 'SEGUNDO', '7', '2018-04-28', '8', 'POLLO', '6', '6'),
(8, 'fotoproducto/imagen_1349564269.jpg', 'HAMBURGUESA', 'HAMBURGUESA', '15', '2018-04-28', '8', 'POLLO', '6', '6'),
(9, 'fotoproducto/imagen_1349563882.jpg', 'Pollo Entero', 'Pollo Entero', '12', '2018-04-28', '60', 'POLLO', '6', '6'),
(10, 'fotoproducto/imagen_1349563882.jpg', 'MEDIO POLLO', 'MEDIO POLLO', '25', '2018-04-28', '30', 'POLLO', '6', '6'),
(11, 'fotoproducto/imagen_1349564000.jpg', 'SILLPANCHO', 'SILLPANCHO', '13', '2018-04-28', '13', 'POLLO', '6', '6'),
(12, 'fotoproducto/imagen_1349564103.jpg', 'SOPA DE MANI  GRANDE', 'SOPA DE MANI  GRANDE', '10', '2018-04-28', '7', 'POLLO', '6', '6'),
(13, 'fotoproducto/imagen_1349564195.jpg', 'SOPA DE MANI MEDIANA', 'SOPA DE MANI MEDIANA', '11', '2018-04-28', '4', 'POLLO', '6', '6'),
(14, 'fotoproducto/imagen_1349564195.jpg', 'PIPOCA DE POLLO', 'PIPOCA DE POLLO', '15', '2018-04-28', '12', 'POLLO', '6', '6'),
(15, 'fotoproducto/imagen_1349564365.jpg', 'Cuero', 'Cuero', '11', '2016-08-22', '1', 'POLLO', '6', '6'),
(16, 'fotoproducto/imagen_1349564382.jpg', 'Hueso', 'Hueso', '11', '2016-08-22', '1', 'POLLO', '6', '6'),
(17, 'fotoproducto/imagen_1349564425.jpg', 'Presa', 'Presa', '1', '2016-08-22', '7', 'POLLO', '6', '6'),
(18, 'fotoproducto/imagen_134951011.jpg', 'PORCION DE ARROZ', 'PORCION DE ARROZ', '10', '2018-04-28', '7', 'POLLO', '6', '6'),
(19, 'fotoproducto/imagen_134951110.jpg', 'PORCION DE PAPA', 'PORCION DE PAPA', '100', '2018-04-28', '7', 'POLLO', '6', '6'),
(20, 'fotoproducto/imagen_1385599810.jpg', 'SPIEDO PERSONAL', 'SPIEDO PERSONAL', '12', '2018-04-28', '13', 'POLLO', '6', '6'),
(21, 'fotoproducto/imagen_1349564053.jpg', 'CUEARTO SPIEDO ', 'CUEARTO SPIEDO ', '100', '2018-04-28', '18', 'POLLO', '6', '6'),
(22, 'fotoproducto/imagen_1349564463.jpg', 'SIMBA MANZANA', 'SIMBA MANZANA', '5', '2018-04-28', '10', 'REFRESCO', '6', '6'),
(23, 'fotoproducto/imagen_1349564498.jpg', 'Popular Sprite', 'Popular Sprite', '100', '2016-08-22', '5', 'REFRESCO', '6', '6'),
(24, 'fotoproducto/imagen_1349564524.jpg', 'Popular Coca Cola', 'Popular Coca Cola', '11', '2016-08-22', '5', 'REFRESCO', '6', '6'),
(25, 'fotoproducto/imagen_1349564587.jpg', 'DEL VALLE', 'DEL VALLE', '100', '2018-04-28', '10', 'REFRESCO', '6', '6'),
(26, 'fotoproducto/imagen_1349564926.jpg', 'COCA COLA 1 ltr', 'COCA COLA 1 ltr', '11', '2018-04-28', '8', 'REFRESCO', '6', '6'),
(27, 'fotoproducto/imagen_1349564902.jpg', 'COCA COLA 2 1/2 lts', 'COCA COLA 2 1/2 lts', '10', '2018-04-28', '13', 'REFRESCO', '6', '6'),
(28, 'fotoproducto/imagen_1349564958.jpg', 'COCA COLA DE 3 lts', 'COCA COLA DE 3 lts', '12', '2018-04-28', '14', 'REFRESCO', '6', '6'),
(29, 'fotoproducto/imagen_1349564989.jpg', 'Fanta 2 1/2 lts ', 'Fanta 2 1/2 lts ', '10', '2016-08-22', '10', 'REFRESCO', '6', '6'),
(30, 'fotoproducto/imagen_1349565020.jpg', 'Sprite ', 'Sprite ', '11', '2016-08-22', '11', 'REFRESCO', '6', '6'),
(31, 'fotoproducto/imagen_1349565068.jpg', 'SIMBA PIÃ‘A', 'SIMBA PIÃ‘A', '11', '2018-04-28', '10', 'REFRESCO', '6', '6'),
(32, 'fotoproducto/imagen_1349564618.jpg', 'Tostada', 'Tostada', '4', '2016-08-22', '4', 'HERVIDO', '6', '4'),
(33, 'fotoproducto/imagen_1349564663.jpg', 'Limonada 1/2', 'Limonada 1/2', '10', '2016-08-22', '10', 'HERVIDO', '6', '4'),
(34, 'fotoproducto/imagen_1349564697.jpg', 'Limonada 1 1/2 ', 'Limonada 1 1/2 ', '7', '2016-08-22', '7', 'HERVIDO', '6', '10'),
(35, 'fotoproducto/imagen_1349564726.jpg', 'Limonada 1lts', 'Limonada 1lts', '4', '2016-08-22', '4', 'HERVIDO', '6', '7'),
(36, 'fotoproducto/imagen_1349564784.jpg', 'Tostada 1lts', 'Tostada 1lts', '10', '2016-08-22', '10', 'HERVIDO', '6', '7'),
(37, 'fotoproducto/imagen_1349564824.jpg', 'Tostada 1 1/2 lts', 'Tostada 1 1/2 lts', '7', '2016-08-22', '10', 'HERVIDO', '6', '10'),
(38, 'fotoproducto/imagen_1349564545.jpg', 'POPULAR FANTA', 'POPULAR FANTA', '10', '2018-04-28', '5', 'REFRESCO', '6', '6'),
(39, 'fotoproducto/imagen_1349564587.jpg', 'TROPI FRUT', 'TROPI FRUT', '10', '2018-04-28', '6', 'REFRESCO', '6', '6'),
(40, 'fotoproducto/images (1).jpg', '003', 'Piernas Imba 3', '2', '2020-06-13', '', 'ARIEL SA', '', ''),
(41, 'fotoproducto/imagen_1349565020.jpg', 'Sprite 2 1/2 ', 'Sprite 2 1/2 ', '100', '2016-08-22', '13', 'REFRESCO', '6', '6'),
(42, 'fotoproducto/NoPicture.jpg', 'TOSTADA 2L', 'TOSTADA 2L', '12', '2018-04-28', '12', 'HERVIDO', '6', '6'),
(43, 'fotoproducto/NoPicture.jpg', 'tostada en vaso', 'tostada en vaso', '0', '2018-05-06', '3', 'HERVIDO', '6', '6'),
(44, 'fotoproducto/cocacola.jpg', '101', 'Coca Cola 500 gr', '', '2020-06-12', '7', 'POLLO', '5', '6'),
(45, 'fotoproducto/pepsi.jpg', '001', 'Pepsi de 500 ml', '', '2020-07-06', '12', 'REFRESCO GASEOSA', '10', '11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `idproveedor` int(11) NOT NULL,
  `proveedor` varchar(150) NOT NULL,
  `responsable` varchar(150) NOT NULL,
  `fechaRegistro` date NOT NULL,
  `direccion` varchar(150) NOT NULL,
  `telefono` varchar(150) NOT NULL,
  `estado` varchar(150) NOT NULL,
  `fechaAviso` date NOT NULL,
  `valor` double NOT NULL,
  `valorCobrado` double NOT NULL,
  `saldo` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`idproveedor`, `proveedor`, `responsable`, `fechaRegistro`, `direccion`, `telefono`, `estado`, `fechaAviso`, `valor`, `valorCobrado`, `saldo`) VALUES
(2, 'Arroz Okinawa', 'Kim ', '2020-08-25', 'Av. Suecia entre Av. siglo XX', 'Av. Suecia entre Av. siglo XX', '', '0000-00-00', 0, 0, ''),
(7, 'ARIEL SA', 'ariel moranda', '2020-10-14', 'Av. siempre viva', 'Av. siempre viva', '', '0000-00-00', 0, 0, ''),
(6, 'POLLOS IMBA SRL', 'Ariel Santa Maria', '2020-08-25', 'circuvalacion', 'circuvalacion', '', '0000-00-00', 0, 0, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipoproducto`
--

CREATE TABLE `tipoproducto` (
  `idtipoproducto` int(11) NOT NULL,
  `tipoproducto` varchar(150) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tipoproducto`
--

INSERT INTO `tipoproducto` (`idtipoproducto`, `tipoproducto`) VALUES
(1, 'POLLO'),
(2, 'REFRESCO HERVIDO'),
(3, 'REFRESCO GASEOSA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usu` int(11) NOT NULL,
  `login` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `password` varchar(50) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `foto` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usu`, `login`, `tipo`, `nombre`, `password`, `foto`) VALUES
(1, 'henry', 'ADMINISTRADOR', 'Henrych ', 'henry', 'fotoproducto/user02.jpg'),
(2, 'susy', 'VENTAS', 'Sussy', 'susy', 'fotoproducto/user.png'),
(3, 'juanPeres12345', 'VENTAS', 'Juan Perez', 'juanPeres12345', 'fotoproducto/user04.jpg'),
(5, 'lety123456', 'VENTAS', 'LetyCalani', 'lety123456', 'fotoproducto/user.png'),
(9, 'Test123334', 'ADMINISTRADOR', 'Test123334', 'Test123334', 'fotoproducto/userM3.jpg'),
(10, 'Carolina1234', 'ADMINISTRADOR', 'Carolina Valdivia', 'Carolina1234', 'fotoproducto/user07.jpg'),
(11, 'Thais123456', 'ADMINISTRADOR', 'Thais Calani', 'Thais123456', 'fotoproducto/userM4.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventatotal`
--

CREATE TABLE `ventatotal` (
  `idVentas` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `ci` varchar(50) NOT NULL,
  `fecha` datetime NOT NULL,
  `totalApagar` double NOT NULL,
  `efectivo` double NOT NULL,
  `cambio` double NOT NULL,
  `idClientei` varchar(50) NOT NULL,
  `codigoControl` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `ventatotal`
--

INSERT INTO `ventatotal` (`idVentas`, `nombre`, `ci`, `fecha`, `totalApagar`, `efectivo`, `cambio`, `idClientei`, `codigoControl`) VALUES
(1, 'S/N', '0', '2020-10-09 22:34:17', 42, 50, 8, '14', 'C0-32-A1-2F'),
(2, 'S/N', '0', '2020-10-10 06:15:11', 42, 50, 8, '14', 'C0-32-A1-2F'),
(3, 'S/N', '0', '2020-10-09 06:16:16', 42, 70, 28, '14', 'C0-32-A1-2F'),
(4, 'S/N', '0', '2020-10-09 06:22:41', 42, 50, 8, '14', 'C0-32-A1-2F'),
(5, 'S/N', '0', '2020-10-09 06:23:04', 5, 10, 5, '14', '38-F5-57-B1'),
(6, 'S/N', '0', '2020-10-11 07:43:38', 5, 10, 5, '14', '38-F5-57-B1'),
(7, 'S/N', '0', '2020-10-11 07:50:09', 13, 20, 7, '14', 'F7-2E-18-10'),
(8, 'S/N', '0', '2020-10-11 07:51:51', 13, 20, 7, '14', 'F7-2E-18-10'),
(9, 'Calani', '6444685', '2020-10-12 13:45:12', 20, 30, 10, '15', '78-04-D8-27'),
(10, 'Perez', '909090', '2020-10-13 13:46:17', 13, 20, 7, '16', 'CD-AF-BF-87'),
(11, 'Perez', '909090', '2020-10-13 13:48:31', 16, 20, 4, '16', '43-00-D7-0A'),
(12, 'Perez', '909090', '2020-10-13 13:48:45', 13, 50, 37, '16', 'CD-AF-BF-87'),
(13, 'Encinas', '77777', '2020-10-13 13:49:04', 16, 20, 4, '17', 'F6-06-55-D5-E3'),
(14, 'Encinas', '77777', '2020-10-13 13:49:18', 16, 30, 14, '17', 'F6-06-55-D5-E3'),
(15, 'Perez Messil', '5555', '2020-10-16 13:19:37', 13, 20, 7, '1', '64-0F-64-D9-FE'),
(16, 'S/N', '0', '2020-10-17 12:16:56', 52, 60, 8, '14', 'F8-8D-73-1D'),
(17, 'Perez Messil', '5555', '2020-10-18 12:46:28', 13, 100, 87, '1', '18-8E-24-FC-55'),
(18, 'S/N', '0', '2020-11-01 22:33:01', 13, 20, 7, '14', '9E-25-79-D2-F8');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `activos`
--
ALTER TABLE `activos`
  ADD PRIMARY KEY (`idactivo`);

--
-- Indices de la tabla `alerta`
--
ALTER TABLE `alerta`
  ADD PRIMARY KEY (`alertaId`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`idcliente`);

--
-- Indices de la tabla `clientedato`
--
ALTER TABLE `clientedato`
  ADD PRIMARY KEY (`idCliente`);

--
-- Indices de la tabla `datos`
--
ALTER TABLE `datos`
  ADD PRIMARY KEY (`iddatos`);

--
-- Indices de la tabla `datosclienteventa`
--
ALTER TABLE `datosclienteventa`
  ADD PRIMARY KEY (`idClienteVenta`);

--
-- Indices de la tabla `datosfacturaventa`
--
ALTER TABLE `datosfacturaventa`
  ADD PRIMARY KEY (`idDatosFactura`);

--
-- Indices de la tabla `datosventa`
--
ALTER TABLE `datosventa`
  ADD PRIMARY KEY (`idDatosVentas`);

--
-- Indices de la tabla `datosventatotal`
--
ALTER TABLE `datosventatotal`
  ADD PRIMARY KEY (`idVentas`);

--
-- Indices de la tabla `dosificacion`
--
ALTER TABLE `dosificacion`
  ADD PRIMARY KEY (`idcodigo`);

--
-- Indices de la tabla `ficha`
--
ALTER TABLE `ficha`
  ADD PRIMARY KEY (`idficha`);

--
-- Indices de la tabla `gastos`
--
ALTER TABLE `gastos`
  ADD PRIMARY KEY (`idgastos`);

--
-- Indices de la tabla `idioma`
--
ALTER TABLE `idioma`
  ADD PRIMARY KEY (`idIdioma`);

--
-- Indices de la tabla `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`idmenu`);

--
-- Indices de la tabla `moneda`
--
ALTER TABLE `moneda`
  ADD PRIMARY KEY (`idMoneda`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`idPedido`);

--
-- Indices de la tabla `preventa`
--
ALTER TABLE `preventa`
  ADD PRIMARY KEY (`idPreventa`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`idproducto`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`idproveedor`);

--
-- Indices de la tabla `tipoproducto`
--
ALTER TABLE `tipoproducto`
  ADD PRIMARY KEY (`idtipoproducto`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usu`);

--
-- Indices de la tabla `ventatotal`
--
ALTER TABLE `ventatotal`
  ADD PRIMARY KEY (`idVentas`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `activos`
--
ALTER TABLE `activos`
  MODIFY `idactivo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `alerta`
--
ALTER TABLE `alerta`
  MODIFY `alertaId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `idcliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `clientedato`
--
ALTER TABLE `clientedato`
  MODIFY `idCliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `datos`
--
ALTER TABLE `datos`
  MODIFY `iddatos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `datosclienteventa`
--
ALTER TABLE `datosclienteventa`
  MODIFY `idClienteVenta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `datosfacturaventa`
--
ALTER TABLE `datosfacturaventa`
  MODIFY `idDatosFactura` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `datosventa`
--
ALTER TABLE `datosventa`
  MODIFY `idDatosVentas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `datosventatotal`
--
ALTER TABLE `datosventatotal`
  MODIFY `idVentas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `dosificacion`
--
ALTER TABLE `dosificacion`
  MODIFY `idcodigo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ficha`
--
ALTER TABLE `ficha`
  MODIFY `idficha` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gastos`
--
ALTER TABLE `gastos`
  MODIFY `idgastos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `idioma`
--
ALTER TABLE `idioma`
  MODIFY `idIdioma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `menu`
--
ALTER TABLE `menu`
  MODIFY `idmenu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `moneda`
--
ALTER TABLE `moneda`
  MODIFY `idMoneda` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `idPedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `preventa`
--
ALTER TABLE `preventa`
  MODIFY `idPreventa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `idproducto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `idproveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tipoproducto`
--
ALTER TABLE `tipoproducto`
  MODIFY `idtipoproducto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `ventatotal`
--
ALTER TABLE `ventatotal`
  MODIFY `idVentas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
