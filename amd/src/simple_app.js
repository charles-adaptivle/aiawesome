/**
 * AI Awesome Vanilla JS Application
 *
 * @module     local_aiawesome/simple_app
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import { getString } from 'core/str';
import { getCurrentCourseId, generateSessionId } from './boot';

/**
 * Mount the simple chat interface to the drawer.
 *
 * @param {Element} container - Container element
 * @returns {Promise<void>}
 */
export async function mount(container) {
    if (!container) {
        throw new Error('Container element is required');
    }

    // Get localized strings
    const strings = await getLocalizedStrings();
    
    // Create the chat interface
    createChatInterface(container, strings);
    
    // Set up event handlers
    setupEventHandlers(container, strings);
}

/**
 * Get localized strings.
 *
 * @returns {Promise<Object>} Localized strings
 */
async function getLocalizedStrings() {
    const stringKeys = [
        'chat_placeholder',
        'chat_send',
        'chat_stop',
        'chat_clear',
        'chat_close',
        'error_network',
        'error_server'
    ];

    const strings = {};
    for (const key of stringKeys) {
        try {
            strings[key] = await getString(key, 'local_aiawesome');
        } catch (error) {
            // Fallback to English
            strings[key] = getEnglishFallback(key);
        }
    }
    
    return strings;
}

/**
 * Get English fallback for string keys.
 *
 * @param {string} key - String key
 * @returns {string} Fallback string
 */
function getEnglishFallback(key) {
    const fallbacks = {
        'chat_placeholder': 'Ask me anything about this course...',
        'chat_send': 'Send',
        'chat_stop': 'Stop',
        'chat_clear': 'Clear chat',
        'chat_close': 'Close',
        'error_network': 'Network error. Please check your connection.',
        'error_server': 'Server error. Please try again later.'
    };
    
    return fallbacks[key] || key;
}

/**
 * Create the chat interface HTML.
 *
 * @param {Element} container - Container element
 * @param {Object} strings - Localized strings
 */
function createChatInterface(container, strings) {
    container.innerHTML = `
        <div class="aiawesome-app">
            <!-- Header -->
            <div class="aiawesome-header">
                <h3 class="aiawesome-title">AI Chat Assistant</h3>
                <button class="aiawesome-close-btn" data-action="close" 
                        aria-label="${strings.chat_close}" title="${strings.chat_close}">
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
            
            <!-- Messages Container -->
            <div class="aiawesome-messages" id="aiawesome-messages">
                <div class="aiawesome-welcome">
                    <div class="aiawesome-message aiawesome-message-assistant">
                        <div class="aiawesome-message-content">
                            <p>👋 Hello! I'm your AI assistant. I can help you with questions about this course and your learning.</p>
                            <p>What would you like to know?</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Input Area -->
            <div class="aiawesome-input-area">
                <form class="aiawesome-input-form" id="aiawesome-input-form">
                    <div class="aiawesome-input-container">
                        <textarea class="aiawesome-input" 
                                id="aiawesome-input" 
                                placeholder="${strings.chat_placeholder}"
                                rows="1"
                                maxlength="2000"></textarea>
                        <button type="submit" 
                                class="aiawesome-send-btn" 
                                id="aiawesome-send-btn"
                                aria-label="${strings.chat_send}">
                            <i class="fa fa-paper-plane" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="aiawesome-actions">
                        <button type="button" 
                                class="aiawesome-action-btn aiawesome-stop-btn" 
                                id="aiawesome-stop-btn" 
                                style="display: none;"
                                aria-label="${strings.chat_stop}">
                            <i class="fa fa-stop" aria-hidden="true"></i>
                            ${strings.chat_stop}
                        </button>
                        <button type="button" 
                                class="aiawesome-action-btn aiawesome-clear-btn" 
                                id="aiawesome-clear-btn"
                                aria-label="${strings.chat_clear}">
                            <i class="fa fa-trash" aria-hidden="true"></i>
                            ${strings.chat_clear}
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Status -->
            <div class="aiawesome-status" id="aiawesome-status" style="display: none;"></div>
        </div>
    `;
}

/**
 * Set up event handlers for the chat interface.
 *
 * @param {Element} container - Container element
 * @param {Object} strings - Localized strings
 */
function setupEventHandlers(container, strings) {
    const form = container.querySelector('#aiawesome-input-form');
    const input = container.querySelector('#aiawesome-input');
    const sendBtn = container.querySelector('#aiawesome-send-btn');
    const stopBtn = container.querySelector('#aiawesome-stop-btn');
    const clearBtn = container.querySelector('#aiawesome-clear-btn');
    const closeBtn = container.querySelector('[data-action="close"]');
    const messagesContainer = container.querySelector('#aiawesome-messages');
    const statusDiv = container.querySelector('#aiawesome-status');
    
    let isStreaming = false;
    let currentAbortController = null;
    let sessionId = generateSessionId();

    // Auto-resize textarea
    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Handle form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = input.value.trim();
        if (!message || isStreaming) return;
        
        // Add user message to chat
        addMessage('user', message);
        
        // Clear input and reset height
        input.value = '';
        input.style.height = 'auto';
        
        // Start streaming
        await startStreaming(message);
    });

    // Handle stop button
    stopBtn.addEventListener('click', function() {
        if (currentAbortController) {
            currentAbortController.abort();
        }
        stopStreaming();
    });

    // Handle clear button
    clearBtn.addEventListener('click', function() {
        if (confirm('Clear all messages?')) {
            clearMessages();
        }
    });

    // Handle close button
    closeBtn.addEventListener('click', function() {
        // Close the drawer
        const drawer = container.closest('#aiawesome-drawer');
        if (drawer) {
            drawer.classList.remove('open');
            drawer.setAttribute('aria-hidden', 'true');
            drawer.style.transform = 'translateX(100%)';
            setTimeout(() => {
                drawer.style.display = 'none';
            }, 300);
        }
    });

    /**
     * Add a message to the chat.
     *
     * @param {string} role - Message role (user, assistant, system)
     * @param {string} content - Message content
     * @returns {Element} Message element
     */
    function addMessage(role, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `aiawesome-message aiawesome-message-${role}`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'aiawesome-message-content';
        contentDiv.textContent = content;
        
        messageDiv.appendChild(contentDiv);
        messagesContainer.appendChild(messageDiv);
        
        // Scroll to bottom
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        return messageDiv;
    }

    /**
     * Start streaming response from AI.
     *
     * @param {string} message - User message
     */
    async function startStreaming(message) {
        isStreaming = true;
        sendBtn.style.display = 'none';
        stopBtn.style.display = 'inline-flex';
        
        // Add empty assistant message for streaming
        const assistantMessage = addMessage('assistant', '');
        const contentDiv = assistantMessage.querySelector('.aiawesome-message-content');
        
        // Add thinking indicator
        contentDiv.innerHTML = '<span class="aiawesome-thinking">💭 Thinking...</span>';
        
        try {
            currentAbortController = new AbortController();
            
            const courseId = getCurrentCourseId();
            const requestData = {
                query: message,
                session: sessionId,
                courseid: courseId,
                sesskey: M.cfg.sesskey
            };

            const response = await fetch(M.cfg.wwwroot + '/local/aiawesome/stream.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(requestData),
                credentials: 'include',
                signal: currentAbortController.signal
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let content = '';

            // Clear thinking indicator
            contentDiv.innerHTML = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop();

                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        try {
                            const data = JSON.parse(line.substring(6));
                            // Handle OpenAI streaming format
                            if (data.choices && data.choices[0] && data.choices[0].delta && data.choices[0].delta.content) {
                                content += data.choices[0].delta.content;
                                contentDiv.textContent = content;
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            }
                        } catch (e) {
                            // Ignore parse errors
                        }
                    }
                }
            }

            // If no content was received, show error
            if (!content) {
                contentDiv.textContent = 'Sorry, I encountered an error processing your request.';
                contentDiv.className += ' aiawesome-error';
            }

        } catch (error) {
            if (error.name === 'AbortError') {
                contentDiv.textContent = 'Response cancelled.';
            } else {
                contentDiv.textContent = 'Sorry, I encountered an error: ' + error.message;
                contentDiv.className += ' aiawesome-error';
            }
        } finally {
            stopStreaming();
        }
    }

    /**
     * Stop streaming and reset UI.
     */
    function stopStreaming() {
        isStreaming = false;
        sendBtn.style.display = 'inline-flex';
        stopBtn.style.display = 'none';
        currentAbortController = null;
    }

    /**
     * Clear all messages.
     */
    function clearMessages() {
        messagesContainer.innerHTML = `
            <div class="aiawesome-welcome">
                <div class="aiawesome-message aiawesome-message-assistant">
                    <div class="aiawesome-message-content">
                        <p>👋 Hello! I'm your AI assistant. I can help you with questions about this course and your learning.</p>
                        <p>What would you like to know?</p>
                    </div>
                </div>
            </div>
        `;
        sessionId = generateSessionId();
    }
}