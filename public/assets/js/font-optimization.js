/**
 * Optimizaciones para Carga de Fuentes
 * Mejora el rendimiento y manejo de errores de fuentes web
 */

(function() {
    'use strict';
    
    var FontOptimizer = {
        config: {
            timeout: 3000, // 3 segundos timeout para carga de fuentes
            fallbackFonts: {
                'Font Awesome 6 Free': 'Arial, sans-serif',
                'Font Awesome 6 Brands': 'Arial, sans-serif',
                'bootstrap-icons': 'Arial, sans-serif'
            },
            debug: true
        },
        
        log: function(message, type) {
            if (!this.config.debug) return;
            
            var prefix = '[FontOptimizer] ';
            switch(type) {
                case 'error':
                    console.error(prefix + message);
                    break;
                case 'warn':
                    console.warn(prefix + message);
                    break;
                default:
                    console.log(prefix + message);
            }
        },
        
        // Detectar y precargar fuentes críticas
        preloadCriticalFonts: function() {
            var criticalFonts = [
                '/assets/fonts/fontawesome6/6.5.2/webfonts/fa-solid-900.woff2',
                '/assets/fonts/fontawesome6/6.5.2/webfonts/fa-regular-400.woff2',
                '/assets/fonts/fontawesome6/6.5.2/webfonts/fa-brands-400.woff2',
                '/assets/fonts/bootstrapicons/1.11.3/fonts/bootstrap-icons.woff2'
            ];
            
            this.log('Precargando fuentes críticas...');
            
            criticalFonts.forEach(function(fontUrl) {
                var link = document.createElement('link');
                link.rel = 'preload';
                link.as = 'font';
                link.type = 'font/woff2';
                link.crossOrigin = 'anonymous';
                link.href = fontUrl;
                
                link.onload = function() {
                    FontOptimizer.log('Fuente precargada: ' + fontUrl);
                };
                
                link.onerror = function() {
                    FontOptimizer.log('Error precargando fuente: ' + fontUrl, 'warn');
                };
                
                document.head.appendChild(link);
            });
        },
        
        // Implementar font-display: swap via CSS
        implementFontDisplay: function() {
            var style = document.createElement('style');
            style.textContent = `
                /* Font Display Optimization */
                @font-face {
                    font-family: 'Font Awesome 6 Free';
                    font-display: swap;
                }
                
                @font-face {
                    font-family: 'Font Awesome 6 Brands';
                    font-display: swap;
                }
                
                @font-face {
                    font-family: 'bootstrap-icons';
                    font-display: swap;
                }
                
                /* Fallback para iconos */
                .fa, .fas, .far, .fab, .bi {
                    font-display: swap;
                }
            `;
            document.head.appendChild(style);
            this.log('Font-display: swap aplicado');
        },
        
        // Verificar carga de fuentes con timeout
        checkFontLoading: function() {
            var self = this;
            
            if (!document.fonts) {
                this.log('API de Fonts no disponible', 'warn');
                return;
            }
            
            var timeout = setTimeout(function() {
                self.log('Timeout de carga de fuentes alcanzado', 'warn');
                self.applyFallbackFonts();
            }, this.config.timeout);
            
            document.fonts.ready.then(function() {
                clearTimeout(timeout);
                self.log('Todas las fuentes cargadas correctamente');
                self.verifyFontLoading();
            }).catch(function(error) {
                clearTimeout(timeout);
                self.log('Error en carga de fuentes: ' + error.message, 'error');
                self.applyFallbackFonts();
            });
        },
        
        // Verificar si las fuentes se cargaron correctamente
        verifyFontLoading: function() {
            var self = this;
            
            Object.keys(this.config.fallbackFonts).forEach(function(fontFamily) {
                if (document.fonts.check('16px "' + fontFamily + '"')) {
                    self.log('Fuente verificada: ' + fontFamily);
                } else {
                    self.log('Fuente no disponible: ' + fontFamily, 'warn');
                }
            });
        },
        
        // Aplicar fuentes de respaldo en caso de error
        applyFallbackFonts: function() {
            var style = document.createElement('style');
            var css = '/* Fuentes de respaldo por error de carga */\n';
            
            for (var fontFamily in this.config.fallbackFonts) {
                css += `.fa, .fas, .far, .fab, .bi { font-family: ${this.config.fallbackFonts[fontFamily]} !important; }\n`;
            }
            
            style.textContent = css;
            document.head.appendChild(style);
            this.log('Fuentes de respaldo aplicadas');
        },
        
        // Monitorear errores de carga de CSS
        monitorCSSErrors: function() {
            var self = this;
            
            // Interceptar errores de elementos link
            var links = document.querySelectorAll('link[rel="stylesheet"]');
            links.forEach(function(link) {
                link.addEventListener('error', function() {
                    if (link.href.includes('fonts/') || link.href.includes('fontawesome') || link.href.includes('bootstrap-icons')) {
                        self.log('Error cargando CSS de fuentes: ' + link.href, 'error');
                        self.fixFontCSS(link);
                    }
                });
            });
        },
        
        // Intentar corregir CSS de fuentes con errores
        fixFontCSS: function(errorLink) {
            var originalHref = errorLink.href;
            
            // Intentar cambiar protocolo
            if (originalHref.startsWith('https://')) {
                var newHref = originalHref.replace('https://', 'http://');
                this.log('Intentando cargar con HTTP: ' + newHref);
                
                var newLink = document.createElement('link');
                newLink.rel = 'stylesheet';
                newLink.href = newHref;
                newLink.onload = function() {
                    FontOptimizer.log('CSS de fuentes corregido exitosamente');
                    errorLink.remove();
                };
                newLink.onerror = function() {
                    FontOptimizer.log('No se pudo corregir CSS de fuentes', 'error');
                };
                
                document.head.appendChild(newLink);
            }
        },
        
        // Optimizar iconos visibles
        optimizeVisibleIcons: function() {
            var icons = document.querySelectorAll('.fa, .fas, .far, .fab, .bi');
            var visibleIcons = Array.from(icons).filter(function(icon) {
                var rect = icon.getBoundingClientRect();
                return rect.top < window.innerHeight && rect.bottom > 0;
            });
            
            this.log('Iconos visibles encontrados: ' + visibleIcons.length);
            
            // Precargar fuentes solo para iconos visibles
            if (visibleIcons.length > 0) {
                this.preloadCriticalFonts();
            }
        },
        
        // Inicialización
        init: function() {
            this.log('Inicializando optimizador de fuentes...');
            
            // Aplicar optimizaciones inmediatamente
            this.implementFontDisplay();
            this.monitorCSSErrors();
            
            // Optimizaciones basadas en el estado del DOM
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    FontOptimizer.optimizeVisibleIcons();
                    FontOptimizer.checkFontLoading();
                });
            } else {
                this.optimizeVisibleIcons();
                this.checkFontLoading();
            }
            
            // Optimizaciones adicionales después de la carga completa
            window.addEventListener('load', function() {
                FontOptimizer.verifyFontLoading();
            });
            
            this.log('Optimizador de fuentes inicializado');
        }
    };
    
    // Auto-inicializar
    FontOptimizer.init();
    
    // Exponer globalmente para debugging
    window.FontOptimizer = FontOptimizer;
    
})();