/**
 * Inicializador de Polyfills
 * Este archivo debe ser cargado antes que cualquier otro script
 */

(function() {
    'use strict';
    
    // Auto-cargar el polyfill de Picture-in-Picture
    function loadPictureInPicturePolyfill() {
        if (typeof window.documentPictureInPicture === 'undefined') {
            console.log('Cargando polyfill de Picture-in-Picture...');
            
            var script = document.createElement('script');
            script.src = '/assets/js/picture-in-picture-polyfill.js';
            script.async = false; // Cargar de forma síncrona
            script.onload = function() {
                console.log('Polyfill de Picture-in-Picture cargado correctamente');
            };
            script.onerror = function() {
                console.warn('Error al cargar el polyfill de Picture-in-Picture');
            };
            
            // Insertar al inicio del head
            var firstScript = document.getElementsByTagName('script')[0];
            if (firstScript) {
                firstScript.parentNode.insertBefore(script, firstScript);
            } else {
                document.head.appendChild(script);
            }
        }
    }
    
    // Verificar y mostrar información de compatibilidad
    function checkBrowserCompatibility() {
        var features = {
            'Picture-in-Picture': 'documentPictureInPicture' in window,
            'Video PiP': 'pictureInPictureEnabled' in document,
            'Fetch API': 'fetch' in window,
            'LocalStorage': 'localStorage' in window,
            'SessionStorage': 'sessionStorage' in window
        };
        
        console.group('🔍 Verificación de Compatibilidad del Navegador');
        for (var feature in features) {
            var status = features[feature] ? '✅' : '❌';
            console.log(status + ' ' + feature + ': ' + (features[feature] ? 'Soportado' : 'No soportado'));
        }
        console.groupEnd();
    }
    
    // Función para manejar errores JavaScript globales
    function setupGlobalErrorHandling() {
        window.addEventListener('error', function(event) {
            // Capturar específicamente errores de documentPictureInPicture
            if (event.error && event.error.message && 
                event.error.message.includes('documentPictureInPicture')) {
                console.warn('Error de Picture-in-Picture capturado y manejado:', event.error.message);
                event.preventDefault(); // Prevenir que se muestre en consola
                return false;
            }
        });
        
        // Capturar errores no manejados de promesas
        window.addEventListener('unhandledrejection', function(event) {
            if (event.reason && event.reason.message && 
                event.reason.message.includes('Picture-in-Picture')) {
                console.warn('Error de Promise Picture-in-Picture capturado:', event.reason.message);
                event.preventDefault();
            }
        });
    }
    
    // Inicializar cuando el DOM esté listo
    function init() {
        checkBrowserCompatibility();
        loadPictureInPicturePolyfill();
        setupGlobalErrorHandling();
    }
    
    // Ejecutar inmediatamente si el DOM ya está listo, sino esperar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // También ejecutar en el evento load por si acaso
    window.addEventListener('load', function() {
        console.log('🚀 Polyfills inicializados correctamente en', window.location.href);
    });

})();