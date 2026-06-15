/**
 * CEMABLN - JavaScript Global
 * Funciones de utilidad para interactividad y validaciones.
 */

// ── Confirmación de eliminación ────────────────────────────────────────
function confirmDelete(message) {
    return confirm(message || '¿Está seguro de que desea eliminar este registro?');
}

// ── Auto-hide flash messages ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('[role="alert"]');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 500);
        }, 5000);
    });
});

// ── Validación de formularios ──────────────────────────────────────────
function validateRequired(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    let valid = true;
    const inputs = form.querySelectorAll('[required]');
    
    inputs.forEach(function(input) {
        if (!input.value.trim()) {
            input.classList.add('border-red-500');
            valid = false;
        } else {
            input.classList.remove('border-red-500');
        }
    });
    
    if (!valid) {
        alert('Por favor, complete todos los campos obligatorios.');
    }
    return valid;
}

// ── Formato de números ─────────────────────────────────────────────────
function formatNumber(num) {
    return new Intl.NumberFormat('es-VE').format(num);
}

// ── Búsqueda en tabla en tiempo real ───────────────────────────────────
function tableSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    
    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(function(row) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}
