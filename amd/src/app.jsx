/**
 * AI Awesome React Application
 *
 * @module     local_aiawesome/app
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import React, { useState, useEffect, useRef, useCallback } from 'react';
import { createRoot } from 'react-dom/client';
import { streamAIResponse } from './sse';
import { getCurrentCourseId, generateSessionId } from './boot';
import { getString } from 'core/str';

// Global strings cache
let strings = {};

/**
 * Main AI Chat Application Component
 */
function AIAwesomeApp() {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [isStreaming, setIsStreaming] = useState(false);
    const [error, setError] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [sessionId] = useState(() => generateSessionId());
    
    const messagesEndRef = useRef(null);
    const inputRef = useRef(null);
    const currentStreamRef = useRef(null);
    const abortControllerRef = useRef(null);

    // Auto-scroll to bottom when new messages arrive
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    // Focus input when app mounts
    useEffect(() => {
        setTimeout(() => inputRef.current?.focus(), 100);
    }, []);

    // Handle input submission
    const handleSubmit = useCallback(async (e) => {
        e.preventDefault();
        
        const query = input.trim();
        if (!query || isStreaming) return;
        
        setInput('');
        setError(null);
        setIsLoading(true);
        
        // Add user message
        const userMessage = {
            id: Date.now(),
            type: 'user',
            content: query,
            timestamp: new Date(),
        };
        
        setMessages(prev => [...prev, userMessage]);
        
        // Prepare assistant message
        const assistantMessage = {
            id: Date.now() + 1,
            type: 'assistant',
            content: '',
            timestamp: new Date(),
            streaming: true,
        };
        
        setMessages(prev => [...prev, assistantMessage]);
        
        try {
            const courseId = getCurrentCourseId();
            
            // Create abort controller for this request
            abortControllerRef.current = new AbortController();
            
            // Start streaming
            const client = streamAIResponse(
                M.cfg.wwwroot + '/local/aiawesome/stream.php',
                {
                    query,
                    session: sessionId,
                    courseid: courseId,
                },
                {
                    onOpen: () => {
                        setIsStreaming(true);
                        setIsLoading(false);
                    },
                    
                    onToken: (token) => {
                        setMessages(prev => prev.map(msg => 
                            msg.id === assistantMessage.id 
                                ? { ...msg, content: msg.content + token }
                                : msg
                        ));
                    },
                    
                    onReferences: (references) => {
                        setMessages(prev => prev.map(msg => 
                            msg.id === assistantMessage.id 
                                ? { ...msg, references }
                                : msg
                        ));
                    },
                    
                    onError: (errorData) => {
                        console.error('AI Awesome: Stream error', errorData);
                        setError({
                            code: errorData.code,
                            message: errorData.message || strings.error_network || 'An error occurred',
                            canRetry: errorData.canRetry,
                        });
                        setIsStreaming(false);
                        setIsLoading(false);
                        
                        // Mark message as error
                        setMessages(prev => prev.map(msg => 
                            msg.id === assistantMessage.id 
                                ? { ...msg, streaming: false, error: true }
                                : msg
                        ));
                    },
                    
                    onRetry: (retryInfo) => {
                        console.log('AI Awesome: Retrying connection', retryInfo);
                        // Could show a retry indicator here
                    },
                    
                    onClose: (closeInfo) => {
                        setIsStreaming(false);
                        setIsLoading(false);
                        
                        // Mark message as completed
                        setMessages(prev => prev.map(msg => 
                            msg.id === assistantMessage.id 
                                ? { ...msg, streaming: false }
                                : msg
                        ));
                        
                        if (closeInfo.reason === 'completed') {
                            // Success - focus back to input
                            setTimeout(() => inputRef.current?.focus(), 100);
                        }
                    }
                }
            );
            
            currentStreamRef.current = client;
            
        } catch (err) {
            console.error('AI Awesome: Request failed', err);
            setError({
                code: 'REQUEST_FAILED',
                message: err.message || strings.error_network || 'Request failed',
                canRetry: true,
            });
            setIsStreaming(false);
            setIsLoading(false);
        }
    }, [input, isStreaming, sessionId]);

    // Handle stop generation
    const handleStop = useCallback(() => {
        if (currentStreamRef.current) {
            currentStreamRef.current.disconnect();
            currentStreamRef.current = null;
        }
        
        if (abortControllerRef.current) {
            abortControllerRef.current.abort();
            abortControllerRef.current = null;
        }
        
        setIsStreaming(false);
        setIsLoading(false);
        
        // Mark last message as stopped
        setMessages(prev => prev.map((msg, index) => 
            index === prev.length - 1 && msg.streaming
                ? { ...msg, streaming: false, stopped: true }
                : msg
        ));
    }, []);

    // Handle clear chat
    const handleClear = useCallback(() => {
        setMessages([]);
        setError(null);
        inputRef.current?.focus();
    }, []);

    // Handle close drawer
    const handleClose = useCallback(() => {
        // Call the close function from the parent boot module
        const drawer = document.getElementById('aiawesome-drawer');
        if (drawer && drawer.classList.contains('open')) {
            // Trigger close event
            const event = new CustomEvent('aiawesome:close');
            drawer.dispatchEvent(event);
        }
    }, []);

    return (
        <div className="aiawesome-app">
            {/* Header */}
            <div className="aiawesome-header">
                <h3>{strings.chat_toggle_title || 'AI Chat Assistant'}</h3>
                <div className="aiawesome-header-actions">
                    {messages.length > 0 && (
                        <button 
                            type="button" 
                            className="btn btn-sm btn-outline-secondary"
                            onClick={handleClear}
                            title={strings.chat_clear || 'Clear chat'}
                        >
                            <i className="fa fa-trash" aria-hidden="true"></i>
                        </button>
                    )}
                    <button 
                        type="button" 
                        className="btn btn-sm btn-outline-secondary"
                        onClick={handleClose}
                        title={strings.chat_close || 'Close'}
                    >
                        <i className="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            
            {/* Messages */}
            <div className="aiawesome-messages">
                {messages.length === 0 && (
                    <div className="aiawesome-welcome">
                        <div className="aiawesome-welcome-icon">
                            <i className="fa fa-comments-o fa-3x" aria-hidden="true"></i>
                        </div>
                        <h4>Welcome to AI Chat</h4>
                        <p>Ask me anything about this course or topic. I'm here to help!</p>
                    </div>
                )}
                
                {messages.map((message) => (
                    <div key={message.id} className={`aiawesome-message aiawesome-message--${message.type}`}>
                        <div className="aiawesome-message-content">
                            {message.content}
                            {message.streaming && (
                                <span className="aiawesome-cursor" aria-hidden="true">▊</span>
                            )}
                            {message.error && (
                                <div className="aiawesome-message-error">
                                    <i className="fa fa-exclamation-triangle" aria-hidden="true"></i>
                                    Failed to get response
                                </div>
                            )}
                            {message.stopped && (
                                <div className="aiawesome-message-stopped">
                                    <i className="fa fa-stop-circle" aria-hidden="true"></i>
                                    Response stopped
                                </div>
                            )}
                        </div>
                        
                        {message.references && message.references.length > 0 && (
                            <div className="aiawesome-references">
                                <h6>References:</h6>
                                <ul>
                                    {message.references.map((ref, index) => (
                                        <li key={index}>
                                            <a href={ref.url} target="_blank" rel="noopener noreferrer">
                                                {ref.title || ref.type}
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                        
                        <div className="aiawesome-message-time">
                            {message.timestamp.toLocaleTimeString()}
                        </div>
                    </div>
                ))}
                
                <div ref={messagesEndRef} />
            </div>
            
            {/* Error Display */}
            {error && (
                <div className="aiawesome-error">
                    <div className="alert alert-danger" role="alert">
                        <i className="fa fa-exclamation-triangle" aria-hidden="true"></i>
                        <strong>{error.code}:</strong> {error.message}
                        {error.canRetry && (
                            <button 
                                type="button"
                                className="btn btn-sm btn-outline-danger ml-2"
                                onClick={() => setError(null)}
                            >
                                Dismiss
                            </button>
                        )}
                    </div>
                </div>
            )}
            
            {/* Input Form */}
            <form onSubmit={handleSubmit} className="aiawesome-input-form">
                <div className="aiawesome-input-container">
                    <textarea
                        ref={inputRef}
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        placeholder={strings.chat_placeholder || 'Ask me anything about this course...'}
                        rows={1}
                        disabled={isStreaming}
                        className="form-control aiawesome-input"
                        onKeyDown={(e) => {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                handleSubmit(e);
                            }
                        }}
                    />
                    <div className="aiawesome-input-actions">
                        {isStreaming ? (
                            <button
                                type="button"
                                onClick={handleStop}
                                className="btn btn-outline-danger btn-sm"
                                title={strings.chat_stop || 'Stop'}
                            >
                                <i className="fa fa-stop" aria-hidden="true"></i>
                            </button>
                        ) : (
                            <button
                                type="submit"
                                disabled={!input.trim() || isLoading}
                                className="btn btn-primary btn-sm"
                                title={strings.chat_send || 'Send'}
                            >
                                {isLoading ? (
                                    <i className="fa fa-spinner fa-spin" aria-hidden="true"></i>
                                ) : (
                                    <i className="fa fa-paper-plane" aria-hidden="true"></i>
                                )}
                            </button>
                        )}
                    </div>
                </div>
            </form>
        </div>
    );
}

/**
 * Mount the React application to a DOM element.
 * 
 * @param {Element} container - Container element
 * @returns {Promise} Mount promise
 */
export async function mount(container) {
    try {
        // Load required strings
        strings = await loadStrings();
        
        // Create root and render
        const root = createRoot(container);
        root.render(<AIAwesomeApp />);
        
        // Handle close events
        container.addEventListener('aiawesome:close', () => {
            // Trigger the actual drawer close
            const closeButton = document.querySelector('[data-aiawesome-toggle]');
            if (closeButton) {
                closeButton.click();
            }
        });
        
        return root;
        
    } catch (error) {
        console.error('AI Awesome: Failed to mount React app', error);
        throw error;
    }
}

/**
 * Load required language strings.
 * 
 * @returns {Promise<Object>} Loaded strings
 */
async function loadStrings() {
    const stringKeys = [
        'chat_toggle_title',
        'chat_placeholder',
        'chat_send',
        'chat_stop',
        'chat_clear',
        'chat_close',
        'error_network',
        'error_server',
        'error_rate_limit',
    ];
    
    const loadedStrings = {};
    
    try {
        // Load all strings in parallel
        const promises = stringKeys.map(key => 
            getString(key, 'local_aiawesome').catch(error => {
                console.warn(`Failed to load string: ${key}`, error);
                return key; // Fallback to key name
            })
        );
        
        const results = await Promise.all(promises);
        
        stringKeys.forEach((key, index) => {
            loadedStrings[key] = results[index];
        });
        
    } catch (error) {
        console.warn('AI Awesome: Failed to load some strings', error);
    }
    
    return loadedStrings;
}