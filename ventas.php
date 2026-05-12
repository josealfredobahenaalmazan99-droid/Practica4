<?php
// ventas.php
session_start();
include 'db.php'; // Asegúrate que $conn es la conexión válida

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Cargar clientes y productos (stock incluido)
$clientes = $conn->query("SELECT id, nombre FROM clientes ORDER BY nombre ASC");
$productos_res = $conn->query("SELECT id, nombre, precio, cantidad FROM productos ORDER BY nombre ASC");

// Procesamiento del formulario de venta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_venta'])) {

    // CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['mensaje'] = "Token CSRF inválido.";
        header("Location: ventas.php");
        exit();
    }

    // Datos generales
    $cliente_id = intval($_POST['cliente_id'] ?? 0);
    $metodo_pago = trim($_POST['metodo_pago'] ?? 'Efectivo');
    $usuario = isset($_SESSION['username']) ? $_SESSION['username'] : 'usuario';

    // Arrays de productos
    $prod_ids = $_POST['producto_id'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $precios = $_POST['precio_unitario'] ?? [];

    // Validaciones básicas
    if ($cliente_id <= 0 || empty($prod_ids)) {
        $_SESSION['mensaje'] = "Completa cliente y agrega al menos un producto.";
        header("Location: ventas.php");
        exit();
    }

    // Construir detalle y validar cantidades (numérico y <= stock)
    $detalle = [];
    $total = 0.0;
    for ($i = 0; $i < count($prod_ids); $i++) {
        $pid = intval($prod_ids[$i]);
        $qty = intval($cantidades[$i]);
        $price = floatval($precios[$i]);

        if ($pid <= 0 || $qty <= 0) continue;

        // Obtener stock actual de forma segura
        $stmtStock = $conn->prepare("SELECT cantidad FROM productos WHERE id = ?");
        $stmtStock->bind_param("i", $pid);
        $stmtStock->execute();
        $stmtStock->bind_result($stock_now);
        $stmtStock->fetch();
        $stmtStock->close();

        if ($stock_now === null) {
            $_SESSION['mensaje'] = "Producto ID $pid no existe.";
            header("Location: ventas.php");
            exit();
        }

        if ($qty > $stock_now) {
            $_SESSION['mensaje'] = "No hay suficiente stock para el producto ID $pid. Disponible: $stock_now.";
            header("Location: ventas.php");
            exit();
        }

        $subtotal = round($qty * $price, 2);
        $detalle[] = [
            'producto_id' => $pid,
            'cantidad' => $qty,
            'precio_unitario' => $price,
            'subtotal' => $subtotal
        ];
        $total += $subtotal;
    }

    if (count($detalle) === 0) {
        $_SESSION['mensaje'] = "Agrega cantidades válidas para los productos.";
        header("Location: ventas.php");
        exit();
    }

    // Iniciar transacción para evitar race conditions
    $conn->begin_transaction();
    try {
        // Insert venta
        $stmtIns = $conn->prepare("INSERT INTO ventas (cliente_id, total, metodo_pago, usuario, fecha) VALUES (?, ?, ?, ?, NOW())");
        $stmtIns->bind_param("idss", $cliente_id, $total, $metodo_pago, $usuario);
        $stmtIns->execute();
        $venta_id = $stmtIns->insert_id;
        $stmtIns->close();

        // Preparar inserts y updates
        $stmtDet = $conn->prepare("INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmtUpd = $conn->prepare("UPDATE productos SET cantidad = cantidad - ? WHERE id = ? AND cantidad >= ?");

        foreach ($detalle as $d) {
            // Verificar stock otra vez (en la misma transacción)
            $stmtCheck = $conn->prepare("SELECT cantidad FROM productos WHERE id = ? FOR UPDATE");
            $stmtCheck->bind_param("i", $d['producto_id']);
            $stmtCheck->execute();
            $stmtCheck->bind_result($stock_now2);
            $stmtCheck->fetch();
            $stmtCheck->close();

            if ($stock_now2 === null) {
                throw new Exception("Producto {$d['producto_id']} no existe.");
            }
            if ($stock_now2 < $d['cantidad']) {
                throw new Exception("Stock insuficiente para producto ID {$d['producto_id']}. Disponible: $stock_now2.");
            }

            // Insert detalle
            $stmtDet->bind_param("iiidd", $venta_id, $d['producto_id'], $d['cantidad'], $d['precio_unitario'], $d['subtotal']);
            $stmtDet->execute();

            // Update stock
            $stmtUpd->bind_param("iii", $d['cantidad'], $d['producto_id'], $d['cantidad']);
            $stmtUpd->execute();
            if ($stmtUpd->affected_rows === 0) {
                throw new Exception("No se pudo actualizar stock del producto {$d['producto_id']}.");
            }
        }

        // Cerrar statements
        $stmtDet->close();
        $stmtUpd->close();

        $conn->commit();

        // Redirigir al ticket (si tienes ticket.php que genera PDF)
        header("Location: ticket.php?id=" . intval($venta_id));
        exit();

    } catch (Exception $ex) {
        $conn->rollback();
        $_SESSION['mensaje'] = "Error al guardar la venta: " . $ex->getMessage();
        header("Location: ventas.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>💎 Joyería Sahori - Nueva Venta</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
    /* Estilos compactos pero agradables */
    :root{--gold:#b8860b;--gold-dark:#8b6508}
    body{font-family:Inter, Poppins, Arial, sans-serif;background:#faf6f0;margin:0;padding:0}
    .header{background:linear-gradient(90deg,var(--gold),var(--gold-dark));color:#fff;padding:18px;text-align:center;font-weight:700}
    .container{max-width:1100px;margin:24px auto;background:#fff;padding:20px;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.08)}
    h2{color:var(--gold);text-align:center;margin:6px 0 18px}
    .row{display:flex;gap:12px;align-items:center}
    .col{flex:1}
    label{display:block;font-weight:600;margin-bottom:6px}
    select,input{padding:10px;border-radius:8px;border:1px solid #ddd;width:100%}
    .table{width:100%;border-collapse:collapse;margin-top:16px}
    .table th{background:#f3e5ab;color:#333;padding:10px;text-align:left}
    .table td{border-bottom:1px solid #eee;padding:8px;vertical-align:middle}
    .btn{background:var(--gold);color:#fff;padding:10px 16px;border-radius:8px;border:none;cursor:pointer;font-weight:700}
    .btn.secondary{background:#666}
    .small{font-size:13px;color:#333}
    .stock-badge{display:inline-block;background:#eee;padding:4px 8px;border-radius:999px;font-weight:700;margin-left:8px;font-size:12px}
    .actions-area{display:flex;gap:12px;margin-bottom:12px}
    .add-row{background:#2ecc71;border:none;color:#fff;padding:8px 12px;border-radius:8px;cursor:pointer}
    .remove-row{background:#e74c3c;border:none;color:#fff;padding:6px 10px;border-radius:8px;cursor:pointer}
    .right{text-align:right}
    .message{background:#fff9e6;border:1px solid #ffd966;padding:10px;border-radius:8px;margin-bottom:12px}
    @media(max-width:800px){.row{flex-direction:column}}
</style>
</head>
<body>
<div class="header">💎 Joyería Sahori — Nueva Venta</div>

<div class="container">
    <?php if (!empty($_SESSION['mensaje'])): ?>
        <div class="message"><?= htmlspecialchars($_SESSION['mensaje']); unset($_SESSION['mensaje']); ?></div>
    <?php endif; ?>

    <h2>Registrar nueva venta</h2>

    <div class="actions-area">
        <a href="lista_tickets.php" class="btn">🧾 Ver últimos tickets</a>
        <a href="corte_caja.php" class="btn">💰 Corte de Caja</a>
        <a href="panel.php" class="btn secondary">⬅ Volver</a>
    </div>

    <form method="POST" id="ventaForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="row" style="margin-bottom:12px">
            <div class="col">
                <label>Cliente</label>
                <select name="cliente_id" required>
                    <option value="">-- Selecciona cliente --</option>
                    <?php $clientes->data_seek(0); while($c = $clientes->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="width:220px">
                <label>Método de pago</label>
                <select name="metodo_pago" id="metodoPago">
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Transferencia">Transferencia</option>
                </select>
            </div>
        </div>

        <!-- Campos dinámicos según método -->
        <div id="metodoCampos" style="margin-bottom:12px;"></div>

        <hr>

        <h3>Productos</h3>
        <table class="table" id="itemsTable" aria-live="polite">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio unitario</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <!-- filas dinámicas -->
            </tbody>
        </table>

        <div style="margin-top:12px">
            <button type="button" id="addItem" class="add-row">➕ Agregar producto</button>
        </div>

        <div style="margin-top:18px; display:flex; justify-content:space-between; align-items:center; gap:12px;">
            <div class="small">Los productos sin stock aparecen deshabilitados. No se permite vender más que el stock disponible.</div>
            <div style="text-align:right">
                <p style="margin:0">Total: $ <span id="total">0.00</span></p>
                <button type="submit" name="guardar_venta" class="btn" style="margin-top:8px">💾 Registrar Venta</button>
            </div>
        </div>
    </form>
</div>

<script>
/* ---------- Datos de productos disponibles (para JS) ---------- */
let productos = {};
<?php
$productos_res->data_seek(0);
while ($p = $productos_res->fetch_assoc()) {
    // sanitize to JSON
    $js = json_encode($p, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
    echo "productos[{$p['id']}] = $js;\n";
}
?>

function formatMoney(v){ return Number(v).toFixed(2); }

function addRow(prefill = {}) {
    const tbody = document.querySelector("#itemsTable tbody");
    const tr = document.createElement("tr");

    // Producto select
    const prodSelect = document.createElement("select");
    prodSelect.name = "producto_id[]";
    prodSelect.required = true;
    const opt0 = document.createElement("option");
    opt0.value = ""; opt0.text = "-- Selecciona --";
    prodSelect.appendChild(opt0);

    for (let id in productos) {
        const p = productos[id];
        const o = document.createElement("option");
        o.value = p.id;
        o.text = p.nombre + " ( $"+ formatMoney(p.precio) + " )";
        // add stock info as data attribute and disable if stock<=0
        o.setAttribute("data-stock", p.cantidad);
        if (p.cantidad <= 0) {
            o.disabled = true;
            o.text += " — SIN STOCK";
        } else {
            o.text += " — stock: " + p.cantidad;
        }
        prodSelect.appendChild(o);

        if (prefill.producto_id && prefill.producto_id == p.id) {
            o.selected = true;
        }
    }

    // Price input
    const priceIn = document.createElement("input");
    priceIn.type = "number"; priceIn.name = "precio_unitario[]"; priceIn.step = "0.01"; priceIn.required = true;
    priceIn.style.width = "120px";
    priceIn.value = prefill.precio_unitario ? prefill.precio_unitario : "";

    // Qty input
    const qtyIn = document.createElement("input");
    qtyIn.type = "number"; qtyIn.name = "cantidad[]"; qtyIn.min = 1; qtyIn.required = true;
    qtyIn.style.width = "100px"; qtyIn.value = prefill.cantidad ? prefill.cantidad : 1;

    // subtotal cell
    const subTd = document.createElement("td");
    subTd.innerText = "$0.00";

    // remove button
    const removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.className = "remove-row";
    removeBtn.innerText = "🗑";
    removeBtn.addEventListener("click", ()=> { tr.remove(); calcTotal(); });

    // events: when select changes, set price & max qty
    prodSelect.addEventListener("change", () => {
        const pid = prodSelect.value;
        if (!pid) {
            priceIn.value = "";
            qtyIn.value = 1;
            subTd.innerText = "$0.00";
            calcTotal();
            return;
        }
        const p = productos[pid];
        priceIn.value = p.precio;
        // set max attribute to limit by stock
        qtyIn.max = p.cantidad;
        if (parseInt(qtyIn.value) > p.cantidad) qtyIn.value = p.cantidad;
        subTd.innerText = "$" + formatMoney((parseFloat(priceIn.value)||0) * (parseInt(qtyIn.value)||0));
        calcTotal();
    });

    priceIn.addEventListener("input", ()=> {
        subTd.innerText = "$" + formatMoney((parseFloat(priceIn.value)||0) * (parseInt(qtyIn.value)||0));
        calcTotal();
    });

    qtyIn.addEventListener("input", ()=> {
        const pid = prodSelect.value;
        if (pid) {
            const max = productos[pid].cantidad;
            if (qtyIn.value === "" || parseInt(qtyIn.value) <= 0) qtyIn.value = 1;
            if (parseInt(qtyIn.value) > max) qtyIn.value = max; // client-side cap
        }
        subTd.innerText = "$" + formatMoney((parseFloat(priceIn.value)||0) * (parseInt(qtyIn.value)||0));
        calcTotal();
    });

    // append cells
    const tdProd = document.createElement("td"); tdProd.appendChild(prodSelect);
    const tdPrice = document.createElement("td"); tdPrice.appendChild(priceIn);
    const tdQty = document.createElement("td"); tdQty.appendChild(qtyIn);
    const tdAction = document.createElement("td"); tdAction.appendChild(removeBtn);

    tr.appendChild(tdProd);
    tr.appendChild(tdPrice);
    tr.appendChild(tdQty);
    tr.appendChild(subTd);
    tr.appendChild(tdAction);

    tbody.appendChild(tr);

    // if prefill product exists trigger change to set price/stock
    if (prefill.producto_id) {
        prodSelect.dispatchEvent(new Event('change'));
    }
    calcTotal();
}

function calcTotal() {
    let total = 0;
    document.querySelectorAll("#itemsTable tbody tr").forEach(tr => {
        const price = parseFloat(tr.querySelector("input[name='precio_unitario[]']").value) || 0;
        const qty = parseInt(tr.querySelector("input[name='cantidad[]']").value) || 0;
        const sub = price * qty;
        tr.querySelector("td:nth-child(4)").innerText = "$" + formatMoney(sub);
        total += sub;
    });
    document.getElementById("total").innerText = formatMoney(total);
}

// Add initial row
document.getElementById("addItem").addEventListener("click", ()=> addRow());
addRow();

// -------------------- Métodos de pago dinámicos --------------------
function actualizarCamposPago() {
    const metodo = document.getElementById("metodoPago").value;
    const div = document.getElementById("metodoCampos");
    let html = "";
    if (metodo === "Efectivo") {
        html = `
            <div class="row" style="gap:8px">
                <div style="flex:1">
                    <label>Monto recibido</label>
                    <input type="number" step="0.01" id="montoRecibido" class="input">
                </div>
                <div style="width:140px">
                    <label>Cambio</label>
                    <input type="text" id="cambio" class="input" readonly>
                </div>
            </div>
        `;
    } else if (metodo === "Tarjeta") {
        html = `
            <div class="row" style="gap:8px">
                <div style="flex:1"><label>Banco</label><input name="banco_tarjeta" class="input"></div>
                <div style="width:160px"><label>Últimos 4</label><input name="ultimos4" class="input" maxlength="4"></div>
            </div>
            <div class="row" style="gap:8px;margin-top:8px">
                <div style="flex:1"><label>Tipo</label><select name="tipo_tarjeta" class="input"><option>Crédito</option><option>Débito</option></select></div>
                <div style="flex:1"><label>Autorización</label><input name="autorizacion" class="input"></div>
            </div>
        `;
    } else if (metodo === "Transferencia") {
        html = `
            <div class="row" style="gap:8px">
                <div style="flex:1"><label>Banco</label><input name="banco_transf" class="input"></div>
                <div style="flex:1"><label>No. operación</label><input name="n_operacion" class="input"></div>
            </div>
            <div style="margin-top:8px"><label>Fecha transferencia</label><input type="datetime-local" name="fecha_transf" class="input"></div>
        `;
    }
    div.innerHTML = html;
    const montoRecibido = document.getElementById("montoRecibido");
    if (montoRecibido) montoRecibido.addEventListener("input", calcularCambio);
}

function calcularCambio() {
    const total = parseFloat(document.getElementById("total").innerText) || 0;
    const recibido = parseFloat(document.getElementById("montoRecibido").value || 0);
    const cambio = recibido - total;
    const campoCambio = document.getElementById("cambio");
    if (campoCambio) campoCambio.value = cambio.toFixed(2);
}

document.getElementById("metodoPago").addEventListener("change", actualizarCamposPago);
actualizarCamposPago();

// -------------------- Antes de enviar: validar cantidades con stock client-side --------------------
document.getElementById("ventaForm").addEventListener("submit", function(e){
    // ensure there is at least one product row
    const rows = document.querySelectorAll("#itemsTable tbody tr");
    if (rows.length === 0) {
        e.preventDefault();
        alert("Agrega al menos un producto.");
        return false;
    }

    // validate stock client-side
    for (const tr of rows) {
        const sel = tr.querySelector("select[name='producto_id[]']");
        const pid = sel.value;
        if (!pid) { e.preventDefault(); alert("Selecciona un producto."); return false; }
        const qty = parseInt(tr.querySelector("input[name='cantidad[]']").value) || 0;
        const stock = parseInt(sel.options[sel.selectedIndex].getAttribute('data-stock') || 0);
        if (qty <= 0) { e.preventDefault(); alert("Ingrese una cantidad válida."); return false; }
        if (qty > stock) {
            e.preventDefault();
            alert("La cantidad solicitada es mayor al stock disponible (" + stock + ").");
            return false;
        }
    }
    // All good; let the form submit (server will re-check stock)
});
</script>
</body>
</html>
