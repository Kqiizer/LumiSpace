/* =======================================================
   📦 Control de cantidades y precios
   ======================================================= */
function updateQuantity(btn, change) {
    const productItem = btn.closest('.product-item');
    const qtySpan = productItem.querySelector('.qty-value');
    const unitPriceText = productItem.querySelector('.unit-price').textContent;

    let qty = parseInt(qtySpan.textContent);
    if (isNaN(qty)) qty = 1;
    qty = Math.max(1, qty + change);
    qtySpan.textContent = qty;

    const unitPrice = parseFloat(unitPriceText.replace(/[^\d.]/g, '')) || 0;
    const totalPrice = (unitPrice * qty).toFixed(2);
    productItem.querySelector('.total-price').textContent = '$' + totalPrice;

    // 🔁 Actualizar servidor si aplica
    syncCartQuantity(productItem.dataset.id, qty);

    updateOrderSummary();
}

/* =======================================================
   ❌ Eliminar producto
   ======================================================= */
function removeProduct(btn) {
    const productItem = btn.closest('.product-item');
    const productId = productItem.dataset.id;

    if (confirm('¿Estás seguro de eliminar este producto del carrito?')) {
        productItem.remove();
        updateOrderSummary();

        // 🔁 Eliminar del servidor
        fetch('../api/carrito/remove.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ producto_id: productId })
        }).catch(err => console.error('Error eliminando producto:', err));
    }
}

/* =======================================================
   💰 Recalcular totales
   ======================================================= */
function updateOrderSummary() {
    const products = document.querySelectorAll('.product-item');
    let subtotal = 0;

    products.forEach(product => {
        const totalText = product.querySelector('.total-price').textContent;
        const total = parseFloat(totalText.replace(/[^\d.]/g, '')) || 0;
        subtotal += total;
    });

    const shipping = products.length > 0 ? 50.00 : 0.00;
    const tax = subtotal * 0.16;
    const total = subtotal + shipping + tax;

    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('shipping').textContent = '$' + shipping.toFixed(2);
    document.getElementById('tax').textContent = '$' + tax.toFixed(2);
    document.getElementById('total').textContent = '$' + total.toFixed(2);
}

/* =======================================================
   🔁 Sincronizar cantidades con servidor
   ======================================================= */
async function syncCartQuantity(productId, cantidad) {
    try {
        const res = await fetch('../api/carrito/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ producto_id: productId, cantidad })
        });
        const data = await res.json();
        if (!data.ok) console.warn('⚠️ No se pudo actualizar cantidad:', data.msg);
    } catch (err) {
        console.error('Error al sincronizar carrito:', err);
    }
}

/* =======================================================
   💳 Modal de pagos
   ======================================================= */
function openPaymentModal() {
    document.getElementById('paymentModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}

function selectPaymentMethod(method, event) {
    event.preventDefault();

    // Reset estilos
    document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.payment-form').forEach(form => form.classList.remove('active'));

    // Activar seleccionado
    const clicked = event.currentTarget;
    clicked.classList.add('active');

    const formMap = {
        'card': 'cardForm',
        'paypal': 'paypalForm',
        'transfer': 'transferForm',
        'cash': 'cashForm'
    };

    if (formMap[method]) {
        document.getElementById(formMap[method]).classList.add('active');
    }
}

/* =======================================================
   ✅ Confirmar pago
   ======================================================= */
function confirmPayment() {
    alert('✅ Pago procesado correctamente. ¡Gracias por tu compra!');
    closePaymentModal();

    // Reiniciar carrito visual (opcional)
    document.querySelectorAll('.product-item').forEach(p => p.remove());
    updateOrderSummary();

    // Podrías redirigir:
    // location.href = '../views/recibo.php';
}

/* =======================================================
   💸 Simular PayPal
   ======================================================= */
function redirectToPayPal() {
    const total = document.getElementById('total').textContent;
    alert('Redirigiendo a PayPal...\n\nTotal a pagar: ' + total);
    setTimeout(() => {
        alert('Pago completado exitosamente con PayPal ✓');
        closePaymentModal();
    }, 2000);
}

/* =======================================================
   🖱 Cerrar modal al hacer click fuera
   ======================================================= */
window.onclick = function(event) {
    const modal = document.getElementById('paymentModal');
    if (event.target === modal) closePaymentModal();
};

/* =======================================================
   📅 Fecha de entrega (5 días hábiles por defecto)
   ======================================================= */
document.addEventListener("DOMContentLoaded", () => {
    const deliveryDate = document.getElementById("deliveryDate");
    if (deliveryDate) {
        const today = new Date();
        today.setDate(today.getDate() + 5);
        deliveryDate.value = today.toISOString().split("T")[0];
    }

    updateOrderSummary();
});
