<?php
// ticket_pedido.php — Ticket PDF de Pedido Especial (Dompdf)

// --- Configuración general ---
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';  // Dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

// Conexión a la BD
include __DIR__ . '/db.php';

// Validación del ID recibido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit("❌ Parámetro 'id' faltante o inválido.");
}

$pedido_id = intval($_GET['id']);
if ($pedido_id <= 0) {
    http_response_code(400);
    exit("❌ ID inválido.");
}

// --- Consulta del pedido ---
$sql = "SELECT p.*, 
        c.nombre AS cliente_nombre, 
        c.telefono AS cliente_telefono, 
        c.email AS cliente_email
        FROM pedidos p
        LEFT JOIN clientes c ON p.cliente_id = c.id
        WHERE p.id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    exit("❌ Error preparando consulta: " . $conn->error);
}

$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();
$stmt->close();

// Si no existe el pedido
if (!$pedido) {
    http_response_code(404);
    exit("❌ Pedido no encontrado.");
}

// --- Cálculos automáticos ---
$total      = floatval($pedido['monto_total']);
$anticipo   = floatval($pedido['anticipo']);
$saldo      = floatval($pedido['saldo_restante']);
$folio      = "PED-" . str_pad((string)$pedido_id, 6, "0", STR_PAD_LEFT);
$empresa    = "Joyería Sahori";
$fecha_gen  = date("Y-m-d H:i");

// Función para escapar HTML
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// --- HTML del ticket ---
$html = '
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Ticket Pedido ' . h($folio) . '</title>

<style>
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color:#222; }
.ticket { width: 340px; margin: 0 auto; padding: 12px; border: 1px dashed #333; }
.center { text-align:center; }
.empresa { font-size: 16px; font-weight:700; color:#b8860b; }
.small { font-size: 11px; color:#555; }
.line { border-top: 1px dashed #999; margin: 8px 0; }
table { width:100%; border-collapse: collapse; }
td { padding: 4px 0; vertical-align: top; }
.right { text-align:right; }
.bold { font-weight:700; }
.total { font-size:14px; font-weight:700; }
.footer { margin-top:10px; font-size:11px; text-align:center; color:#666; }

.chip { 
    display:inline-block; 
    padding:4px 8px; 
    font-size:11px; 
    border-radius:8px; 
    color:#fff; 
}
.chip.pendiente { background:#d9534f; }
.chip.proceso   { background:#0275d8; }
.chip.completado{ background:#5cb85c; }
.chip.cancelado { background:#6c757d; }
</style>
</head>

<body>
<div class="ticket">

    <div class="center empresa">' . h($empresa) . '</div>
    <div class="center small">Pedido Especial - Ticket</div>
    <div class="center small">Folio: <b>' . h($folio) . '</b></div>

    <div class="line"></div>

    <table>
        <tr><td class="bold small">Cliente:</td><td class="small">' . h($pedido['cliente_nombre']) . '</td></tr>
        <tr><td class="bold small">Tel:</td><td class="small">' . h($pedido['cliente_telefono']) . '</td></tr>
        <tr><td class="bold small">Email:</td><td class="small">' . h($pedido['cliente_email']) . '</td></tr>
    </table>

    <div class="line"></div>

    <div class="small bold">Descripción:</div>
    <div class="small">' . nl2br(h($pedido['descripcion'])) . '</div>

    <table style="margin-top:6px;">
        <tr>
            <td class="small">Material: ' . h($pedido['material']) . '</td>
            <td class="small right">Talla: ' . h($pedido['talla']) . '</td>
        </tr>
        <tr>
            <td class="small">Fecha Pedido: ' . h($pedido['fecha_pedido']) . '</td>
            <td class="small right">Entrega: ' . h($pedido['fecha_entrega']) . '</td>
        </tr>
    </table>

    <div class="line"></div>

    <table>
        <tr><td class="small">Total</td><td class="right total">$' . number_format($total, 2) . '</td></tr>
        <tr><td class="small">Anticipo</td><td class="right">$' . number_format($anticipo, 2) . '</td></tr>
        <tr><td class="bold small">Saldo Restante</td><td class="right bold">$' . number_format($saldo, 2) . '</td></tr>
    </table>

    <div style="margin-top:8px;">
        Estatus: <span class="chip ' . strtolower(str_replace(" ", "", $pedido['estatus'])) . '">' . h($pedido['estatus']) . '</span>
    </div>

    <div class="line"></div>

    <div class="footer">
        Generado: ' . h($fecha_gen) . '<br>
        ¡Gracias por su preferencia!
    </div>
</div>
</body>
</html>
';

// --- Configurar Dompdf ---
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// Tamaño tipo ticket
$dompdf->setPaper([0, 0, 340, 680]);

$dompdf->render();

// Mostrar en navegador (sin descargar)
$dompdf->stream("ticket_pedido_$pedido_id.pdf", ["Attachment" => false]);
exit;

