/**
 * Sistema mejorado de gestión de alertas para Dolibarr
 * Este script mejora la visualización de las alertas y soluciona problemas con el contorno transparente
 */

document.addEventListener('DOMContentLoaded', function() {
    // Fix para contornos transparentes y manejo de alertas
    fixAlerts();
    
    // Observador de mutaciones para capturar alertas creadas dinámicamente
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                // Buscar nodos añadidos que sean alertas
                for (let i = 0; i < mutation.addedNodes.length; i++) {
                    const node = mutation.addedNodes[i];
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        if (node.classList && (
                            node.classList.contains('error') || 
                            node.classList.contains('warning') || 
                            node.classList.contains('info') || 
                            node.classList.contains('ok')
                        )) {
                            // Es una alerta, aplicar arreglos
                            fixAlertNode(node);
                        } else {
                            // Buscar alertas dentro del nodo añadido
                            const alerts = node.querySelectorAll('.error, .warning, .info, .ok');
                            if (alerts.length > 0) {
                                alerts.forEach(fixAlertNode);
                            }
                        }
                    }
                }
            }
        });
    });
    
    // Iniciar observación del documento
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

/**
 * Arregla todas las alertas existentes en la página
 */
function fixAlerts() {
    const alerts = document.querySelectorAll('.error, .warning, .info, .ok');
    alerts.forEach(fixAlertNode);
}

/**
 * Aplica arreglos a un nodo de alerta específico
 * @param {Element} alertNode - El nodo de alerta a arreglar
 */
function fixAlertNode(alertNode) {
    // Prevenir propagación de clics
    alertNode.addEventListener('click', function(e) {
        // Solo detener propagación si el clic fue directamente en la alerta (no en botones dentro de ella)
        if (e.target === alertNode) {
            e.stopPropagation();
        }
    });
    
    // Verificar si ya tiene un botón de cierre
    if (!alertNode.querySelector('.close')) {
        // Añadir botón de cierre si no existe
        const closeBtn = document.createElement('span');
        closeBtn.className = 'close';
        closeBtn.innerHTML = '×';
        closeBtn.title = 'Cerrar';
        closeBtn.style.cursor = 'pointer';
        
        // Manejar evento de cierre
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Animación de salida
            alertNode.style.animation = 'fadeOut 0.3s ease-out forwards';
            
            // Eliminar después de la animación
            setTimeout(function() {
                if (alertNode.parentNode) {
                    alertNode.parentNode.removeChild(alertNode);
                }
            }, 300);
        });
        
        // Añadir el botón al alerta
        alertNode.appendChild(closeBtn);
        
        // Asegurar que tiene posición relativa para el botón de cierre
        alertNode.style.position = 'relative';
    }
    
    // Eliminar cualquier elemento transparente padre que pueda estar causando problemas
    let parent = alertNode.parentNode;
    while (parent && parent !== document.body) {
        // Verificar si el padre tiene estilo que podría causar problemas
        const style = window.getComputedStyle(parent);
        if (style.position === 'absolute' && 
            (style.backgroundColor === 'transparent' || 
             style.backgroundColor === 'rgba(0, 0, 0, 0)')) {
            
            // Modificar el padre para que no interfiera
            parent.style.backgroundColor = 'transparent';
            parent.style.pointerEvents = 'none';
            parent.style.position = 'static';
        }
        parent = parent.parentNode;
    }
}

// Añadir keyframes para animación de salida
(function() {
    const style = document.createElement('style');
    style.type = 'text/css';
    style.innerHTML = `
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
            to {
                opacity: 0;
                transform: translate3d(0, -30px, 0);
            }
        }
    `;
    document.head.appendChild(style);
})(); 