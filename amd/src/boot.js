/**
 * Boot module for AI Awesome plugin.
 * 
 * This module handles the initial setup and integration with Moodle's UI.
 * It injects the chat toggle button and manages the drawer lifecycle.
 *
 * @module     local_aiawesome/boot
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

// Cache for preloaded app module and mounted state.
let preloadedApp = null;
let appMountedOnce = false;

/**
 * Initialize the AI Awesome chat feature.
 * 
 * This function is called by Moodle when the page loads.
 * It sets up the UI integration and event handlers.
 */
export const init = () => {
    // Check if user has permission and feature is enabled
    if (!M.cfg.developerdebug && !document.body.hasAttribute('data-aiawesome-enabled')) {
        return; // Feature disabled or no permission
    }

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupUI);
    } else {
        setupUI();
    }
    
    // Preload the app module in the background for faster drawer opening
    preloadAppModule();
};

/**
 * Preload the app module in the background to reduce drawer open time.
 */
function preloadAppModule() {
    // Use setTimeout to defer loading until after page is interactive
    setTimeout(() => {
        require(['local_aiawesome/simple_app'], (app) => {
            preloadedApp = app;
        }, (error) => {
            // Silent fail - will load normally when drawer opens
        });
    }, 1000); // Wait 1 second after page load to avoid blocking page rendering
}

/**
 * Set up the user interface elements.
 */
async function setupUI() {
    try {
        // Inject the toggle button
        await injectToggleButton();
        
        // Create the drawer container
        createDrawerContainer();
        
        // Set up global event handlers
        setupGlobalHandlers();
        
    } catch (error) {
        console.error('AI Awesome: Failed to initialize UI', error);
    }
}

/**
 * Inject the AI chat toggle button into the user menu.
 * 
 * This function attempts to find the user menu and add our toggle button.
 * It's designed to be theme-agnostic and resilient to DOM changes.
 */
async function injectToggleButton() {
    // Get the localized title string
    const title = await getString('chat_toggle_title', 'local_aiawesome');
    
    // Try multiple selectors to find the user menu
    const selectors = [
        '.usermenu .dropdown-menu',           // Boost theme
        '#user-menu-dropdown',                // Classic theme
        '[data-region="user-menu"] .dropdown-menu', // Modern themes
        '.navbar-nav .dropdown-menu:has([data-title="usermenu"])', // Generic
    ];
    
    let userMenu = null;
    for (const selector of selectors) {
        userMenu = document.querySelector(selector);
        if (userMenu) break;
    }
    
    if (!userMenu) {
        console.warn('AI Awesome: Could not find user menu to inject toggle button');
        // Fallback: inject into a fixed position
        injectFloatingToggle(title);
        return;
    }
    
    // Create the toggle button
    const toggleButton = document.createElement('a');
    toggleButton.className = 'dropdown-item';
    toggleButton.href = '#';
    toggleButton.setAttribute('data-aiawesome-toggle', 'true');
    toggleButton.setAttribute('aria-label', title);
    toggleButton.setAttribute('role', 'button');
    toggleButton.innerHTML = `
        <i class="icon fa fa-comments-o fa-fw" aria-hidden="true"></i>
        <span class="menu-action-text">${title}</span>
    `;
    
    // Add click handler
    toggleButton.addEventListener('click', handleToggleClick);
    
    // Insert the button safely
    try {
        const divider = userMenu.querySelector('.dropdown-divider');
        if (divider && divider.parentNode === userMenu) {
            // Only use insertBefore if divider is a direct child
            userMenu.insertBefore(toggleButton, divider);
        } else {
            // Fallback to appendChild
            userMenu.appendChild(toggleButton);
        }
    } catch (insertError) {
        // Fallback to appendChild if insertBefore fails
        userMenu.appendChild(toggleButton);
    }
}

/**
 * Create a floating toggle button as fallback.
 * 
 * @param {string} title - Button title
 */
function injectFloatingToggle(title) {
    const toggleButton = document.createElement('button');
    toggleButton.className = 'aiawesome-floating-toggle';
    toggleButton.setAttribute('data-aiawesome-toggle', 'true');
    toggleButton.setAttribute('aria-label', title);
    toggleButton.setAttribute('title', title);
    toggleButton.innerHTML = '<i class="fa fa-comments-o" aria-hidden="true"></i>';
    
    // Style the floating button
    Object.assign(toggleButton.style, {
        position: 'fixed',
        top: '80px',
        right: '20px',
        zIndex: '1050',
        width: '50px',
        height: '50px',
        borderRadius: '50%',
        backgroundColor: '#007bff',
        color: 'white',
        border: 'none',
        boxShadow: '0 2px 10px rgba(0,0,0,0.2)',
        cursor: 'pointer',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontSize: '18px',
        transition: 'all 0.3s ease',
    });
    
    // Add hover effects
    toggleButton.addEventListener('mouseenter', () => {
        toggleButton.style.backgroundColor = '#0056b3';
        toggleButton.style.transform = 'scale(1.1)';
    });
    
    toggleButton.addEventListener('mouseleave', () => {
        toggleButton.style.backgroundColor = '#007bff';
        toggleButton.style.transform = 'scale(1)';
    });
    
    toggleButton.addEventListener('click', handleToggleClick);
    
    document.body.appendChild(toggleButton);
}

/**
 * Create the drawer container element.
 */
function createDrawerContainer() {
    // Check if drawer already exists
    if (document.getElementById('aiawesome-drawer')) {
        return;
    }
    
    const drawer = document.createElement('aside');
    drawer.id = 'aiawesome-drawer';
    drawer.className = 'aiawesome-drawer';
    drawer.setAttribute('role', 'complementary');
    drawer.setAttribute('aria-label', 'AI Chat');
    drawer.setAttribute('aria-hidden', 'true');
    drawer.style.display = 'none';
    
    // Add basic styles
    Object.assign(drawer.style, {
        position: 'fixed',
        top: '0',
        right: '0',
        width: '400px',
        height: '100vh',
        backgroundColor: 'white',
        boxShadow: '-2px 0 10px rgba(0,0,0,0.1)',
        zIndex: '1040',
        transform: 'translateX(100%)',
        transition: 'transform 0.3s ease-in-out',
        display: 'flex',
        flexDirection: 'column',
    });
    
    // Add loading skeleton HTML
    drawer.innerHTML = `
        <div class="aiawesome-drawer-loading">
            <div class="aiawesome-loading-header">
                <div class="aiawesome-loading-header-title"></div>
                <div class="aiawesome-loading-header-close"></div>
            </div>
            <div class="aiawesome-loading-content">
                <div class="aiawesome-loading-spinner">
                    <div class="aiawesome-loading-icon"></div>
                    <div class="aiawesome-loading-text">
                        Loading AI Chat<span class="aiawesome-loading-dots-text">...</span>
                    </div>
                </div>
            </div>
            <div class="aiawesome-loading-input">
                <div class="aiawesome-loading-input-box"></div>
            </div>
        </div>
    `;
    
    // Add to body
    document.body.appendChild(drawer);
}

/**
 * Handle toggle button click.
 * 
 * @param {Event} event - Click event
 */
async function handleToggleClick(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const drawer = document.getElementById('aiawesome-drawer');
    if (!drawer) return;
    
    const isOpen = drawer.classList.contains('open');
    
    if (isOpen) {
        closeDrawer();
    } else {
        await openDrawer();
    }
    
    // Close dropdown menu if open
    const dropdown = event.target.closest('.dropdown');
    if (dropdown) {
        const menu = dropdown.querySelector('.dropdown-menu');
        if (menu && menu.classList.contains('show')) {
            menu.classList.remove('show');
        }
    }
}

/**
 * Open the AI chat drawer.
 */
async function openDrawer() {
    const drawer = document.getElementById('aiawesome-drawer');
    if (!drawer) return;
    
    try {
        // Show drawer
        drawer.style.display = 'flex';
        drawer.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        drawer.style.transform = 'translateX(0)';
        
        // Load and mount the React app
        await loadAndMountApp();
        
        // Focus management
        trapFocus(drawer);
        
        // Update toggle button state
        updateToggleState(true);
        
    } catch (error) {
        console.error('AI Awesome: Failed to open drawer', error);
        closeDrawer();
    }
}

/**
 * Close the AI chat drawer.
 */
function closeDrawer() {
    const drawer = document.getElementById('aiawesome-drawer');
    if (!drawer) {
        return;
    }
    
    drawer.classList.remove('open');
    drawer.setAttribute('aria-hidden', 'true');
    drawer.style.transform = 'translateX(100%)';
    
    // Hide after transition
    setTimeout(() => {
        if (!drawer.classList.contains('open')) {
            drawer.style.display = 'none';
            // Keep the mounted app in DOM - don't destroy it
            // This makes subsequent opens instant
        }
    }, 300);
    
    // Update toggle button state
    updateToggleState(false);
    
    // Return focus to toggle button
    const toggle = document.querySelector('[data-aiawesome-toggle]');
    if (toggle) {
        toggle.focus();
    }
}

/**
 * Load and mount the React application.
 */
async function loadAndMountApp() {
    const drawer = document.getElementById('aiawesome-drawer');
    if (!drawer) {
        return;
    }
    
    // Check if app is already mounted - just return if so
    if (appMountedOnce && drawer.hasAttribute('data-app-mounted')) {
        return;
    }
    
    try {
        let app = preloadedApp;
        
        // If module was preloaded, use it immediately (fast path)
        if (app && app.mount) {
            await app.mount(drawer);
            drawer.setAttribute('data-app-mounted', 'true');
            appMountedOnce = true;
            return;
        }
        
        // Otherwise, load it now (slower path)
        await new Promise((resolve, reject) => {
            require(['local_aiawesome/simple_app'], (loadedApp) => {
                if (loadedApp && loadedApp.mount) {
                    preloadedApp = loadedApp; // Cache for next time
                    loadedApp.mount(drawer).then(() => {
                        drawer.setAttribute('data-app-mounted', 'true');
                        appMountedOnce = true;
                        resolve();
                    }).catch(reject);
                } else {
                    reject(new Error('Simple app module does not have mount function'));
                }
            }, (error) => {
                reject(error);
            });
        });
        
    } catch (error) {
        // Fallback: create a simple chat interface
        createSimpleChatInterface(drawer);
        drawer.setAttribute('data-app-mounted', 'true');
        appMountedOnce = true;
    }
}

/**
 * Create a simple chat interface as fallback.
 *
 * @param {Element} drawer - Drawer element
 */
function createSimpleChatInterface(drawer) {
    drawer.innerHTML = `
        <div class="aiawesome-drawer-loading">
            <div class="aiawesome-loading-header">
                <div style="color: white; font-weight: 600;">AI Chat Assistant</div>
                <button onclick="this.closest('.aiawesome-drawer-loading').parentElement.style.display='none'" 
                        style="background: rgba(255,255,255,0.3); color: white; border: none; 
                               padding: 0.5rem; border-radius: 50%; cursor: pointer; width: 32px; height: 32px;">
                    ×
                </button>
            </div>
            <div class="aiawesome-loading-content">
                <div style="text-align: center; max-width: 300px; margin: 0 auto;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
                    <h3 style="color: #333; margin-bottom: 15px;">Module Loading Error</h3>
                    <div style="color: #666; font-size: 14px; line-height: 1.5;">
                        <p style="margin-bottom: 10px;">The AI chat interface failed to load.</p>
                        <p style="margin-bottom: 15px; font-size: 12px; color: #999;">
                            Check the browser console for technical details or try refreshing the page.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Set up global event handlers.
 */
function setupGlobalHandlers() {
    // Handle escape key to close drawer
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const drawer = document.getElementById('aiawesome-drawer');
            if (drawer && drawer.classList.contains('open')) {
                closeDrawer();
            }
        }
    });
    
    // Handle clicks outside drawer
    document.addEventListener('click', (event) => {
        const drawer = document.getElementById('aiawesome-drawer');
        if (!drawer || !drawer.classList.contains('open')) return;
        
        if (!drawer.contains(event.target) && 
            !event.target.hasAttribute('data-aiawesome-toggle') &&
            !event.target.closest('[data-aiawesome-toggle]')) {
            closeDrawer();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', () => {
        const drawer = document.getElementById('aiawesome-drawer');
        if (!drawer) return;
        
        // Adjust drawer width on small screens
        if (window.innerWidth < 768) {
            drawer.style.width = '100vw';
        } else {
            drawer.style.width = '400px';
        }
    });
}

/**
 * Trap focus within the drawer for accessibility.
 * 
 * @param {Element} drawer - Drawer element
 */
function trapFocus(drawer) {
    const focusableElements = drawer.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    if (focusableElements.length === 0) return;
    
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    
    // Focus first element
    setTimeout(() => firstElement.focus(), 100);
    
    // Tab trap
    drawer.addEventListener('keydown', (event) => {
        if (event.key !== 'Tab') return;
        
        if (event.shiftKey) {
            // Shift + Tab
            if (document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            }
        } else {
            // Tab
            if (document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        }
    });
}

/**
 * Update toggle button state.
 * 
 * @param {boolean} isOpen - Whether drawer is open
 */
function updateToggleState(isOpen) {
    const toggle = document.querySelector('[data-aiawesome-toggle]');
    if (!toggle) return;
    
    toggle.setAttribute('aria-expanded', isOpen.toString());
    
    if (isOpen) {
        toggle.classList.add('active');
    } else {
        toggle.classList.remove('active');
    }
}

/**
 * Get current course ID if available.
 * 
 * @returns {number|null} Course ID
 */
export function getCurrentCourseId() {
    // Try to get course ID from various sources
    if (M.cfg.courseId && M.cfg.courseId !== 1) {
        return M.cfg.courseId;
    }
    
    // Try from URL
    const urlMatch = window.location.pathname.match(/\/course\/view\.php.*[?&]id=(\d+)/);
    if (urlMatch) {
        return parseInt(urlMatch[1], 10);
    }
    
    // Try from body data attribute
    const courseId = document.body.getAttribute('data-course-id');
    if (courseId && courseId !== '1') {
        return parseInt(courseId, 10);
    }
    
    return null;
}

/**
 * Generate a session ID for tracking related requests.
 * 
 * @returns {string} Session ID
 */
export function generateSessionId() {
    return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}