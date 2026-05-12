<?php
require __DIR__ . "/db.php";
require __DIR__ . "/vendor/autoload.php"; // DOMPDF

use Dompdf\Dompdf;
use Dompdf\Options;

// ---------------- FILTROS ------------------
$desde = $_GET['desde'] ?? date('Y-m-d');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

// ---------------- EXPORTAR CSV ------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=corte_caja_{$desde}_a_{$hasta}.csv");

    $output = fopen('php://output','w');
    fputcsv($output, ['Folio','Fecha','Cliente','Total','Método']);

    $q = $conn->prepare("
        SELECT v.id, v.fecha, c.nombre, v.total, v.metodo_pago
        FROM ventas v 
        JOIN clientes c ON v.cliente_id = c.id
        WHERE DATE(v.fecha) BETWEEN ? AND ?
        ORDER BY v.fecha ASC
    ");
    $q->bind_param("ss",$desde,$hasta);
    $q->execute();
    $res = $q->get_result();

    while($row = $res->fetch_assoc())
        fputcsv($output, $row);

    exit;
}

// ---------------- CONSULTA PRINCIPAL ------------------
$q = $conn->prepare("
    SELECT v.id, v.fecha, c.nombre, v.total, v.metodo_pago
    FROM ventas v 
    JOIN clientes c ON v.cliente_id = c.id
    WHERE DATE(v.fecha) BETWEEN ? AND ?
    ORDER BY v.fecha ASC
");
$q->bind_param("ss",$desde,$hasta);
$q->execute();
$res = $q->get_result();

// TOTAL
$sum = $conn->prepare("SELECT IFNULL(SUM(total),0) AS suma FROM ventas WHERE DATE(fecha) BETWEEN ? AND ?");
$sum->bind_param("ss",$desde,$hasta);
$sum->execute();
$total = $sum->get_result()->fetch_assoc()['suma'];

// ---------------- GENERAR PDF ------------------
if (isset($_GET['pdf'])) {

    $tipo = $_GET['pdf']; // ticket o carta

    ob_start();
    ?>

    <html>
    <head>
    <meta charset="utf-8">
    <style>
    body { 
        font-family: DejaVu Sans, Arial, sans-serif; 
        font-size: 12px;
        text-align: center; /* 🔥 CENTRA TODO */
        margin: 0;
        padding: 0;
    }
    .title { 
        font-size: 14px; 
        font-weight: bold; 
        margin-bottom: 10px; 
        text-align: center; 
    }

    /* 🔥 TABLA MÁS ESTRECHA Y CENTRADA */
    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 0 auto;
    }
    th, td { 
        padding: 3px 0; 
        border-bottom: 1px solid #ccc; 
        font-size: 11px; 
        text-align: center; /* 🔥 CENTRAR COLUMNAS */
    }

    /* Para totales */
    .right { text-align: right; }
    .total { 
        margin-top: 10px; 
        font-size: 14px; 
        font-weight: bold; 
        text-align: center; /* 🔥 CENTRAR TOTAL */
    }
</style>

    </head>
    <body>

    <div class="title">📊 Corte de Caja<br><?= $desde ?> a <?= $hasta ?></div>

    <table>
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Método</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $q->execute();
            $res2 = $q->get_result();
            while ($r = $res2->fetch_assoc()):
            ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= $r['fecha'] ?></td>
                <td><?= htmlspecialchars($r['nombre']) ?></td>
                <td><?= htmlspecialchars($r['metodo_pago']) ?></td>
                <td class="right">$<?= number_format($r['total'],2) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="total">
        TOTAL GENERAL: $<?= number_format($total,2) ?>
    </div>

    </body>
    </html>

    <?php
    $html = ob_get_clean();

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont','DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);

    if ($tipo === "ticket") {
        // 80 mm = 226 puntos
        $dompdf->setPaper([0, 0, 226, 800], "portrait");
    } else {
        $dompdf->setPaper("letter", "portrait");
    }

    $dompdf->render();
    $dompdf->stream("corte_caja.pdf", ["Attachment" => false]);
    exit;
}

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Corte de Caja</title>
<style>
    body{font-family:Arial, sans-serif;background:#faf6f0;padding:20px}
    .card{max-width:1100px;margin:20px
    auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0
    4px 18px rgba(0,0,0,0.08)}
    .header{background:linear-gradient(to
    right,#d4af37,#b8860b);padding:12px;color:#fff;border-radius:6px;font-
    size:18px}
    .input, input{padding:8px;border-radius:8px;border:1px solid #ccc}
    .btn{background:#b8860b;color:#fff;padding:8px 14px;border-
      radius:8px;border:none;cursor:pointer}
      .table{width:100%;border-collapse:collapse;margin-top:10px}
      .table th{background:#f3e5ab;padding:8px}
      .table td{padding:8px;border-bottom:1px solid #eee}
      .total{font-size:20px;font-weight:bold;text-align:right;margin-top:15px}
</style>

</head>

<body>
<div class="card">
  <div class="header">📊 Corte de Caja</div>

  <!-- FILTRO -->
  <form method="GET" style="margin-top:12px;display:flex;gap:10px;align-items:center">
    <label>Desde: <input type="date" name="desde" value="<?= $desde ?>"></label>
    <label>Hasta: <input type="date" name="hasta" value="<?= $hasta ?>"></label>
    <button class="btn">Filtrar</button>

    <a class="btn" href="corte_caja.php?desde=<?= $desde ?>&hasta=<?= $hasta ?>&export=csv">CSV</a>
    <a class="btn" href="corte_caja.php?desde=<?= $desde ?>&hasta=<?= $hasta ?>&pdf=ticket">🧾 Ticket 80mm</a>
    <a class="btn" href="corte_caja.php?desde=<?= $desde ?>&hasta=<?= $hasta ?>&pdf=carta">📄 PDF Carta</a>
  </form>

  <!-- TABLA -->
  <table class="table">
    <thead>
      <tr>
        <th>Folio</th><th>Fecha</th><th>Cliente</th><th>Método</th><th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php while($r = $res->fetch_assoc()): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= $r['fecha'] ?></td>
          <td><?= htmlspecialchars($r['nombre']) ?></td>
          <td><?= htmlspecialchars($r['metodo_pago']) ?></td>
          <td>$<?= number_format($r['total'],2) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <p class="total">TOTAL GENERAL: $<?= number_format($total,2) ?></p>

  <a href="panel.php" class="btn">⬅ Volver</a>
</div>
</body>
</html>
