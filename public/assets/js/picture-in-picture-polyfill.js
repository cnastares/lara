/**
 * Picture-in-Picture API Polyfill
 * Fixes: ReferenceError: documentPictureInPicture is not defined
 */

(function() {
    'use strict';
    
    // Verificar si la API documentPictureInPicture no está disponible
    if (typeof window.documentPictureInPicture === 'undefined') {
        console.log('Picture-in-Picture API no está soportada, implementando polyfill...');
        
        // Crear un mock object con la estructura básica de la API
        window.documentPictureInPicture = {
            // Propiedades básicas
            window: null,
            
            // Método requestWindow (mock)
            requestWindow: function(options) {
                console.warn('documentPictureInPicture.requestWindow no está soportado en este navegador');
                return Promise.reject(new Error('Picture-in-Picture API no soportada'));
            },
            
            // Event listeners (mock)
            addEventListener: function(type, listener, options) {
                console.warn('documentPictureInPicture.addEventListener no está soportado en este navegador');
            },
            
            removeEventListener: function(type, listener, options) {
                console.warn('documentPictureInPicture.removeEventListener no está soportado en este navegador');
            }
        };
        
        // Agregar soporte para verificación de compatibilidad
        window.documentPictureInPicture.supported = false;
    } else {
        // Si está disponible, marcar como soportado
        window.documentPictureInPicture.supported = true;
        console.log('Picture-in-Picture API está disponible');
    }
    
    // Función helper para verificar compatibilidad
    window.isPictureInPictureSupported = function() {
        return 'documentPictureInPicture' in window && window.documentPictureInPicture.supported !== false;
    };
    
    // Wrapper seguro para usar la API
    window.safePictureInPicture = {
        isSupported: function() {
            return window.isPictureInPictureSupported();
        },
        
        requestWindow: function(options) {
            if (this.isSupported()) {
                return window.documentPictureInPicture.requestWindow(options);
            } else {
                console.warn('Intento de usar Picture-in-Picture en navegador no compatible');
                return Promise.reject(new Error('Picture-in-Picture no soportado'));
            }
        },
        
        addEventListener: function(type, listener, options) {
            if (this.isSupported()) {
                return window.documentPictureInPicture.addEventListener(type, listener, options);
            }
        },
        
        removeEventListener: function(type, listener, options) {
            if (this.isSupported()) {
                return window.documentPictureInPicture.removeEventListener(type, listener, options);
            }
        }
    };
    
})();

// Compatibilidad adicional para Video Picture-in-Picture (diferente API)
if (typeof HTMLVideoElement !== 'undefined' && !HTMLVideoElement.prototype.requestPictureInPicture) {
    HTMLVideoElement.prototype.requestPictureInPicture = function() {
        console.warn('Video Picture-in-Picture no está soportado en este navegador');
        return Promise.reject(new Error('Video Picture-in-Picture no soportado'));
    };
}

// Log de inicialización
console.log('Picture-in-Picture polyfill cargado correctamente');