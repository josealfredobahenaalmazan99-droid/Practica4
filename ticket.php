<?php
require __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

include 'db.php';

if (!isset($_GET['id'])) die("ID venta no especificado.");
$id = intval($_GET['id']);

// Obtener venta + cliente
$stmt = $conn->prepare("
    SELECT v.*, c.nombre AS cliente
    FROM ventas v 
    JOIN clientes c ON v.cliente_id = c.id
    WHERE v.id = ?
");
$stmt->bind_param("i",$id);
$stmt->execute();
$res = $stmt->get_result();
$venta = $res->fetch_assoc();

if (!$venta) die("Venta no encontrada.");

// Detalle venta
$detalle = $conn->prepare("
    SELECT dv.*, p.nombre 
    FROM detalle_venta dv 
    JOIN productos p ON dv.producto_id = p.id
    WHERE dv.venta_id = ?
");
$detalle->bind_param("i", $id);
$detalle->execute();
$det_res = $detalle->get_result();

// Generar QR (Google Chart API)
$qr_url = "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=VENTA-" . $id;

// Crear HTML del ticket
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11px;
    margin: 0;
    padding: 0;
    text-align: center;
}

.ticket {
    width: 220px; /* ancho real ticket térmico */
    margin: 0 auto;
    padding: 5px;
}

.logo-title {
    font-size: 15px;
    font-weight: bold;
    margin-bottom: 3px;
}

.line {
    border-top: 1px dashed #000;
    margin: 6px 0;
}

.section {
    text-align: left;
    width: 100%;
}

.table-prods {
    width: 100%;
    border-collapse: collapse;
}

.table-prods td {
    padding: 2px 0;
}

.bold {
    font-weight: bold;
}

.right {
    text-align: right;
}

.small {
    font-size: 10px;
}

.footer {
    margin-top: 8px;
    font-size: 10px;
}

</style>
</head>
<body>

<div class="ticket">

<div class="logo-title">Joyería Sahori</div>

<div class="small">
Ignacio M. Altamirano #69, Col. Centro<br>
C.P. 40000, Iguala de la Independencia, Gro.<br>
Frente a la Central de Autobuses, Local 37A
</div>

<div class="line"></div>

<div class="section small">
<strong>Folio:</strong> '.h($venta['id']).'<br>
<strong>Cliente:</strong> '.h($venta['cliente']).'<br>
<strong>Fecha:</strong> '.h($venta['fecha']).'<br>
<strong>Método:</strong> '.h($venta['metodo_pago']).'
</div>

<div class="line"></div>

<table class="table-prods">
<thead>
<tr>
<td class="bold">Producto</td>
<td class="right bold">Cant</td>
<td class="right bold">Imp.</td>
</tr>
</thead>
<tbody>';


while($r = $det_res->fetch_assoc()){
    $html .= '
    <tr>
        <td>'.h($r['nombre']).'</td>
        <td class="right">'.h($r['cantidad']).'</td>
        <td class="right">$'.number_format($r['subtotal'],2).'</td>
    </tr>';
}

$html .= '
</tbody>
</table>

<div class="line"></div>

<table width="100%">
<tr>
    <td class="bold">TOTAL:</td>
    <td class="right bold">$'.number_format($venta['total'],2).'</td>
</tr>
<tr>
    <td><strong>PAGO:</strong></td>
    <td class="right">$'.number_format($venta['monto_recibido'],2).'</td>
</tr>
<tr>
    <td><strong>CAMBIO:</strong></td>
    <td class="right">$'.number_format($venta['cambio'],2).'</td>
</tr>
</table>


<div class="center small" style="margin-top:10px;">
Gracias por su compra ♥<br>
¡Lo esperamos pronto!
</div>

<div class="line"></div>

<div class="center small">Joyería Sahori — Ticket de Venta</div>

</div>

</body>
</html>
';

// Configurar DOMPDF
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// **TICKET TÉRMICO 80MM (270px aprox)**
$dompdf->setPaper([0,0,226.77,1000]); // 80mm ancho (226 px) - alto flexible

$dompdf->render();

// Mostrar PDF en navegador
$dompdf->stream("ticket_venta_$id.pdf", ["Attachment" => false]);
exit;
?>
