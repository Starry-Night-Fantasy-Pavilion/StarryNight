/**
 * Default Theme JavaScript
 */

(function() {
    'use strict';

    // 主题初始化
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Default Theme Loaded');
        
        // 初始化导航菜单
        initNavigation();
        
        // 初始化动画效果
        initAnimations();
    });

    /**
     * 初始化导航菜单
     */
    function initNavigation() {
        const navMenu = document.querySelector('.nav-menu');
        if (!navMenu) return;

        // 高亮当前页面
        const currentPath = window.location.pathname;
        const links = navMenu.querySelectorAll('a');
        
        links.forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
        
        // 初始化移动端菜单
        initMobileMenu();
        
        // 初始化滚动效果
        initHeaderScroll();
    }
    
    /**
     * 初始化移动端菜单
     */
    function initMobileMenu() {
        const menuToggle = document.getElementById('mobile-menu-toggle');
        const headerNav = document.getElementById('header-nav');
        
        if (!menuToggle || !headerNav) return;
        
        menuToggle.addEventListener('click', function() {
            menuToggle.classList.toggle('active');
            headerNav.classList.toggle('active');
            document.body.style.overflow = headerNav.classList.contains('active') ? 'hidden' : '';
        });
        
        // 点击菜单项后关闭菜单
        const navLinks = headerNav.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                menuToggle.classList.remove('active');
                headerNav.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
        
        // 点击外部区域关闭菜单
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
    
    /**
     * 初始化头部滚动效果
     */
    function initHeaderScroll() {
        const header = document.getElementById('header');
        if (!header) return;
        
        let lastScroll = 0;
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            
            lastScroll = currentScroll;
        });
    }

    /**
     * 初始化动画效果
     */
    function initAnimations() {
        // 滚动动画
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);

        // 观察所有需要动画的元素
        const animatedElements = document.querySelectorAll('.feature, .hero');
        animatedElements.forEach(el => observer.observe(el));
    }

    /**
     * 主题工具函数
     */
    window.ThemeUtils = {
        /**
         * 平滑滚动到指定元素
         * @param {string} selector - 元素选择器
         */
        scrollTo: function(selector) {
            const element = document.querySelector(selector);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
            }
        },

        /**
         * 切换移动端菜单
         */
        toggleMobileMenu: function() {
            const navMenu = document.querySelector('.nav-menu');
            if (navMenu) {
                navMenu.classList.toggle('mobile-open');
            }
        },

        /**
         * 设置主题颜色
         * @param {string} color - 颜色值
         */
        setPrimaryColor: function(color) {
            document.documentElement.style.setProperty('--primary-color', color);
        }
    };
})();
