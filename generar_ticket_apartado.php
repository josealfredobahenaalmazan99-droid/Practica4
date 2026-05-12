<?php
// ===================== GENERAR TICKET DE APARTADO =====================
require 'vendor/autoload.php';
require 'db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// -------------------------------------------------------
// Validar ID
// -------------------------------------------------------
$apartado_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($apartado_id <= 0) {
    die("ID de apartado no válido.");
}

// -------------------------------------------------------
// Obtener datos del apartado + cliente + producto
// -------------------------------------------------------
$sql = "
    SELECT 
        a.id,
        a.fecha_apartado,
        a.fecha_limite,
        a.anticipo,
        a.saldo_restante,
        a.estatus,
        c.nombre AS cliente,
        c.telefono AS cliente_telefono,
        p.nombre AS producto,
        p.precio AS precio_producto
    FROM apartados a
    INNER JOIN clientes c ON a.cliente_id = c.id
    INNER JOIN productos p ON a.producto_id = p.id
    WHERE a.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $apartado_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Apartado no encontrado.");
}

// -------------------------------------------------------
// Obtener todos los pagos registrados
// -------------------------------------------------------
$qPagos = $conn->prepare("
    SELECT fecha_pago, monto, metodo_pago 
    FROM pagos_apartado 
    WHERE apartado_id = ?
    ORDER BY fecha_pago ASC
");
$qPagos->bind_param("i", $apartado_id);
$qPagos->execute();
$resPagos = $qPagos->get_result();

// -------------------------------------------------------
// Construir tabla de pagos + calcular totales
// -------------------------------------------------------
$pagos_html = "";

// Incluir anticipo como PRIMER PAGO siempre
$pagos_html .= "
<tr>
    <td style='padding:6px; border:1px solid #ddd;'>" . htmlspecialchars($data['fecha_apartado']) . " (Anticipo)</td>
    <td style='padding:6px; border:1px solid #ddd; text-align:right;'>$" . number_format($data['anticipo'], 2) . "</td>
    <td style='padding:6px; border:1px solid #ddd;'>Efectivo</td>
</tr>";

$total_abonado = floatval($data['anticipo']); // inicia con anticipo

// Agregar pagos posteriores
while ($p = $resPagos->fetch_assoc()) {
    $total_abonado += floatval($p['monto']);
    $pagos_html .= "
    <tr>
        <td style='padding:6px; border:1px solid #ddd;'>" . htmlspecialchars($p['fecha_pago']) . "</td>
        <td style='padding:6px; border:1px solid #ddd; text-align:right;'>$" . number_format($p['monto'], 2) . "</td>
        <td style='padding:6px; border:1px solid #ddd;'>" . htmlspecialchars($p['metodo_pago']) . "</td>
    </tr>";
}

// -------------------------------------------------------
// Calcular saldo final
// -------------------------------------------------------
$precio_total = floatval($data['precio_producto']);
$saldo_calculado = max(0, $precio_total - $total_abonado);
$estatus_pago = ($saldo_calculado <= 0) ? "✔ PAGADO COMPLETAMENTE" : "Pendiente";

// -------------------------------------------------------
// HTML DEL PDF
// -------------------------------------------------------
$html = '
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Ticket Apartado #' . $apartado_id . '</title>
<style>
body { font-family: DejaVu Sans, Arial; font-size:14px; color:#222; }
.ticket { width:100%; max-width:700px; margin:auto; padding:12px; }
h1 { text-align:center; font-size:20px; margin-bottom:8px; }
.meta p { margin:3px 0; }
table { width:100%; border-collapse: collapse; margin-top:10px; }
th { background:#f3e5ab; padding:7px; border:1px solid #ccc; }
td { padding:6px; border:1px solid #ccc; }
.right { text-align:right; }
.bold { font-weight:bold; }
.paid { color:green; font-size:18px; font-weight:bold; margin-top:10px; }
</style>
</head>

<body>
<div class="ticket">

<h1>Joyería Sahori - Ticket de Apartado</h1>

<div class="meta">
    <p><strong>Folio:</strong> ' . $apartado_id . '</p>
    <p><strong>Cliente:</strong> ' . htmlspecialchars($data['cliente']) . ' — ' . htmlspecialchars($data['cliente_telefono']) . '</p>
    <p><strong>Producto:</strong> ' . htmlspecialchars($data['producto']) . '</p>
    <p><small><strong>Fecha del apartado:</strong> ' . $data['fecha_apartado'] . ' |
       <strong>Fecha límite:</strong> ' . $data['fecha_limite'] . '</small></p>
</div>

<table>
    <tr>
        <th>Fecha pago</th>
        <th>Monto</th>
        <th>Método</th>
    </tr>
    ' . $pagos_html . '
</table>

<div class="totals">
    <p class="bold">Total del producto: <span class="right">$' . number_format($precio_total, 2) . '</span></p>
    <p class="bold">Total abonado: <span class="right">$' . number_format($total_abonado, 2) . '</span></p>
    <p class="bold">Saldo restante: <span class="right">$' . number_format($saldo_calculado, 2) . '</span></p>
</div>

' . ($saldo_calculado <= 0 ? '<p class="paid">✔ APARTADO PAGADO COMPLETAMENTE</p>' : '') . '

</div>
</body>
</html>
';

// -------------------------------------------------------
// GENERAR PDF
// -------------------------------------------------------
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

$filename = 'ticket_apartado_' . $apartado_id . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);
exit;
?>
