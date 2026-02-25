/**
 * Theme JavaScript - 柔和自然风格
 * 支持深色/浅色/自动主题切换
 */

(function() {
    'use strict';

    const THEME_KEY = 'theme-preference';
    const THEMES = {
        LIGHT: 'light',
        DARK: 'dark',
        AUTO: 'auto'
    };

    let currentTheme = THEMES.AUTO;

    document.addEventListener('DOMContentLoaded', function() {
        initTheme();
        initNavigation();
        initAnimations();
        initScrollEffects();
    });

    function initTheme() {
        const savedTheme = localStorage.getItem(THEME_KEY) || THEMES.AUTO;
        setTheme(savedTheme);

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', function(e) {
            if (currentTheme === THEMES.AUTO) {
                applySystemTheme(e.matches);
            }
        });

        window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', function(e) {
            if (currentTheme === THEMES.AUTO) {
                applySystemTheme(!e.matches);
            }
        });
    }

    function setTheme(theme) {
        currentTheme = theme;
        localStorage.setItem(THEME_KEY, theme);

        if (theme === THEMES.AUTO) {
            document.documentElement.removeAttribute('data-theme');
            applySystemTheme(window.matchMedia('(prefers-color-scheme: dark)').matches);
        } else {
            document.documentElement.setAttribute('data-theme', theme);
        }

        updateThemeToggleUI(theme);
    }

    function applySystemTheme(isDark) {
        if (currentTheme !== THEMES.AUTO) return;
        
        if (isDark) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    }

    function updateThemeToggleUI(theme) {
        const toggles = document.querySelectorAll('[data-theme-toggle]');
        toggles.forEach(toggle => {
            toggle.setAttribute('data-current-theme', theme);
            const icon = toggle.querySelector('.theme-toggle-icon');
            if (icon) {
                icon.className = 'theme-toggle-icon';
                if (theme === THEMES.DARK) {
                    icon.classList.add('icon-moon');
                } else if (theme === THEMES.LIGHT) {
                    icon.classList.add('icon-sun');
                } else {
                    icon.classList.add('icon-auto');
                }
            }
        });
    }

    window.toggleTheme = function() {
        const themes = [THEMES.AUTO, THEMES.LIGHT, THEMES.DARK];
        const currentIndex = themes.indexOf(currentTheme);
        const nextIndex = (currentIndex + 1) % themes.length;
        setTheme(themes[nextIndex]);
    };

    window.setTheme = setTheme;

    window.getTheme = function() {
        return currentTheme;
    };

    function initNavigation() {
        const navMenu = document.querySelector('.nav-menu');
        if (!navMenu) return;

        const currentPath = window.location.pathname;
        const links = navMenu.querySelectorAll('a');
        
        links.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPath || (href !== '/' && currentPath.startsWith(href))) {
                link.classList.add('active');
            }
        });
        
        initMobileMenu();
    }
    
    function initMobileMenu() {
        const menuToggle = document.getElementById('mobileMenuToggle') || 
                          document.getElementById('mobile-menu-toggle');
        const headerNav = document.getElementById('headerNav') || 
                         document.getElementById('header-nav');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (menuToggle && headerNav) {
            menuToggle.addEventListener('click', function() {
                menuToggle.classList.toggle('active');
                headerNav.classList.toggle('active');
                document.body.style.overflow = headerNav.classList.contains('active') ? 'hidden' : '';
            });

            const navLinks = headerNav.querySelectorAll('a');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    menuToggle.classList.remove('active');
                    headerNav.classList.remove('active');
                    document.body.style.overflow = '';
                });
            });

            document.addEventListener('click', function(e) {
                if (headerNav.classList.contains('active') && 
                    !headerNav.contains(e.target) && 
                    !menuToggle.contains(e.target)) {
                    menuToggle.classList.remove('active');
                    headerNav.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }

        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-open');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('show');
                }
                document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                if (sidebar) sidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('show');
                document.body.style.overflow = '';
                if (menuToggle) menuToggle.classList.remove('active');
            });
        }
    }

    function initScrollEffects() {
        const header = document.getElementById('mainHeader') || 
                      document.getElementById('header') ||
                      document.querySelector('.header');
        
        if (header) {
            let ticking = false;
            window.addEventListener('scroll', function() {
                if (!ticking) {
                    window.requestAnimationFrame(function() {
                        const currentScroll = window.pageYOffset;
                        
                        if (currentScroll > 50) {
                            header.classList.add('scrolled');
                        } else {
                            header.classList.remove('scrolled');
                        }
                        
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        }
    }

    function initAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const animatedElements = document.querySelectorAll(
            '.feature, .hero, .card, .stat-card, .feature-card, [data-animate]'
        );
        animatedElements.forEach(function(el) {
            el.classList.add('animate-ready');
            observer.observe(el);
        });

        addAnimationStyles();
    }

    function addAnimationStyles() {
        if (document.getElementById('theme-animation-styles')) return;

        const style = document.createElement('style');
        style.id = 'theme-animation-styles';
        style.textContent = `
            .animate-ready {
                opacity: 0;
                transform: translateY(20px);
                transition: opacity 0.6s ease, transform 0.6s ease;
            }
            .animate-in {
                opacity: 1;
                transform: translateY(0);
            }
            @media (prefers-reduced-motion: reduce) {
                .animate-ready {
                    opacity: 1;
                    transform: none;
                    transition: none;
                }
            }
        `;
        document.head.appendChild(style);
    }

    window.ThemeUtils = {
        scrollTo: function(selector, offset) {
            offset = offset || 0;
            const element = document.querySelector(selector);
            if (element) {
                const top = element.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({
                    top: top,
                    behavior: 'smooth'
                });
            }
        },

        toggleMobileMenu: function() {
            const menuToggle = document.getElementById('mobileMenuToggle');
            if (menuToggle) {
                menuToggle.click();
            }
        },

        setPrimaryColor: function(color) {
            document.documentElement.style.setProperty('--primary-500', color);
        },

        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                return navigator.clipboard.writeText(text);
            }
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            return Promise.resolve();
        },

        debounce: function(func, wait) {
            let timeout;
            return function executedFunction() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const context = this;
                const args = arguments;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() {
                        inThrottle = false;
                    }, limit);
                }
            };
        },

        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            }
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },

        formatDate: function(date, format) {
            format = format || 'Y-m-d';
            const d = new Date(date);
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            
            return format
                .replace('Y', year)
                .replace('m', month)
                .replace('d', day)
                .replace('H', hours)
                .replace('i', minutes);
        }
    };

})();
