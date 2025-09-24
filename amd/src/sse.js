/**
 * Server-Sent Events client for AI Awesome plugin.
 *
 * @module     local_aiawesome/sse
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * SSE client class with retry logic and error handling.
 */
export class SSEClient extends EventTarget {
    constructor(url, options = {}) {
        super();
        this.url = url;
        this.options = {
            maxRetries: 2,
            retryDelay: 1000,
            timeout: 120000,
            ...options
        };
        
        this.eventSource = null;
        this.abortController = null;
        this.retryCount = 0;
        this.isConnected = false;
        this.lastEventId = null;
    }

    /**
     * Connect to the SSE endpoint.
     * 
     * @param {Object} data - Data to send in POST request
     * @returns {Promise} Connection promise
     */
    async connect(data = {}) {
        this.abortController = new AbortController();
        
        try {
            // For Moodle, we need to POST first to authenticate and get the stream.
            // The stream endpoint will handle SSE after authentication.
            const response = await fetch(this.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                },
                body: JSON.stringify({
                    ...data,
                    sesskey: M.cfg.sesskey, // Moodle CSRF protection
                }),
                signal: this.abortController.signal,
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            // Check if response is actually SSE
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('text/event-stream')) {
                const errorText = await response.text();
                throw new Error(`Expected SSE response, got: ${contentType}. Response: ${errorText}`);
            }

            // Process the streaming response
            await this.processStream(response);
            
        } catch (error) {
            if (error.name === 'AbortError') {
                this.emit('close', { reason: 'aborted' });
                return;
            }

            this.emit('error', { error, retry: this.shouldRetry() });

            if (this.shouldRetry()) {
                await this.scheduleRetry(data);
            }
        }
    }

    /**
     * Process the streaming response.
     * 
     * @param {Response} response - Fetch response object
     */
    async processStream(response) {
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        this.isConnected = true;
        this.emit('open', { timestamp: Date.now() });

        try {
            while (true) {
                const { value, done } = await reader.read();
                
                if (done) {
                    break;
                }

                if (this.abortController.signal.aborted) {
                    break;
                }

                buffer += decoder.decode(value, { stream: true });
                
                // Process complete lines
                while (buffer.includes('\n')) {
                    const lineEnd = buffer.indexOf('\n');
                    const line = buffer.slice(0, lineEnd);
                    buffer = buffer.slice(lineEnd + 1);
                    
                    this.processLine(line.trim());
                }
            }
        } finally {
            reader.releaseLock();
            this.isConnected = false;
        }

        this.emit('close', { reason: 'completed' });
    }

    /**
     * Process a single SSE line.
     * 
     * @param {string} line - SSE line to process
     */
    processLine(line) {
        if (line === '') {
            return; // Empty line, ignore
        }

        if (line.startsWith('data: ')) {
            const data = line.slice(6);
            try {
                const parsedData = JSON.parse(data);
                this.emit('message', { data: parsedData, timestamp: Date.now() });
            } catch (error) {
                // Non-JSON data, emit as text
                this.emit('message', { data, timestamp: Date.now() });
            }
        } else if (line.startsWith('event: ')) {
            const eventType = line.slice(7);
            this.currentEventType = eventType;
        } else if (line.startsWith('id: ')) {
            this.lastEventId = line.slice(4);
        } else if (line.startsWith('retry: ')) {
            const retryTime = parseInt(line.slice(7), 10);
            if (!isNaN(retryTime)) {
                this.options.retryDelay = retryTime;
            }
        }
    }

    /**
     * Disconnect from the stream.
     */
    disconnect() {
        if (this.abortController) {
            this.abortController.abort();
        }
        
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        
        this.isConnected = false;
        this.retryCount = 0;
    }

    /**
     * Check if we should retry the connection.
     * 
     * @returns {boolean} Whether to retry
     */
    shouldRetry() {
        return this.retryCount < this.options.maxRetries;
    }

    /**
     * Schedule a retry attempt.
     * 
     * @param {Object} data - Original connection data
     */
    async scheduleRetry(data) {
        this.retryCount++;
        
        const delay = this.options.retryDelay * Math.pow(2, this.retryCount - 1); // Exponential backoff
        
        this.emit('retrying', { 
            attempt: this.retryCount, 
            maxRetries: this.options.maxRetries,
            delay 
        });

        await new Promise(resolve => setTimeout(resolve, delay));
        
        if (!this.abortController.signal.aborted) {
            await this.connect(data);
        }
    }

    /**
     * Emit a custom event.
     * 
     * @param {string} type - Event type
     * @param {Object} detail - Event detail
     */
    emit(type, detail = {}) {
        this.dispatchEvent(new CustomEvent(type, { detail }));
    }

    /**
     * Check if currently connected.
     * 
     * @returns {boolean} Connection status
     */
    get connected() {
        return this.isConnected;
    }

    /**
     * Get connection statistics.
     * 
     * @returns {Object} Connection stats
     */
    get stats() {
        return {
            connected: this.isConnected,
            retryCount: this.retryCount,
            maxRetries: this.options.maxRetries,
            lastEventId: this.lastEventId,
        };
    }
}

/**
 * Create an SSE client instance.
 * 
 * @param {string} url - SSE endpoint URL
 * @param {Object} options - Client options
 * @returns {SSEClient} SSE client instance
 */
export function createSSEClient(url, options = {}) {
    return new SSEClient(url, options);
}

/**
 * Utility function to handle common SSE patterns for AI streaming.
 * 
 * @param {string} url - SSE endpoint URL
 * @param {Object} data - Data to send
 * @param {Object} callbacks - Event callbacks
 * @returns {SSEClient} SSE client instance
 */
export function streamAIResponse(url, data, callbacks = {}) {
    const client = createSSEClient(url, {
        maxRetries: 2,
        retryDelay: 1000,
        timeout: 120000,
    });

    // Set up event listeners
    client.addEventListener('open', (event) => {
        if (callbacks.onOpen) {
            callbacks.onOpen(event.detail);
        }
    });

    client.addEventListener('message', (event) => {
        const { data: messageData } = event.detail;
        
        if (callbacks.onMessage) {
            callbacks.onMessage(messageData);
        }
        
        // Handle specific AI event types
        if (messageData && typeof messageData === 'object') {
            if (messageData.text && callbacks.onToken) {
                callbacks.onToken(messageData.text, messageData);
            }
            
            if (messageData.references && callbacks.onReferences) {
                callbacks.onReferences(messageData.references);
            }
            
            if (messageData.code && callbacks.onError) {
                callbacks.onError(messageData);
            }
        }
    });

    client.addEventListener('error', (event) => {
        const { error, retry } = event.detail;
        
        if (callbacks.onError) {
            callbacks.onError({ 
                code: 'NETWORK_ERROR', 
                message: error.message, 
                canRetry: retry 
            });
        }
    });

    client.addEventListener('retrying', (event) => {
        if (callbacks.onRetry) {
            callbacks.onRetry(event.detail);
        }
    });

    client.addEventListener('close', (event) => {
        if (callbacks.onClose) {
            callbacks.onClose(event.detail);
        }
    });

    // Start the connection
    client.connect(data);

    return client;
}