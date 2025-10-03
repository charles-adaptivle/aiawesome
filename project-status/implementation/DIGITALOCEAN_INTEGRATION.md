# Adding DigitalOcean AI Platform Support to AI Awesome

This document describes how to add DigitalOcean support to the AI Awesome Moodle plugin, allowing users to connect to their own AI models hosted on DigitalOcean GPU Droplets.

## Overview

DigitalOcean's Gradient AI platform provides GPU-powered infrastructure for running AI models. Unlike OpenAI's hosted API, DigitalOcean allows users to deploy their own models (like Llama, DeepSeek, Mistral, etc.) on dedicated GPU instances and access them via custom endpoints.

## Integration Approach

### 1. Provider Type: Custom Endpoint
DigitalOcean integration will work by allowing users to:
- Deploy models on DigitalOcean GPU Droplets using Ollama, vLLM, or similar
- Configure their custom endpoint URL in AI Awesome
- Use their own models with full control and privacy

### 2. Supported Model Deployment Methods

#### Option A: Ollama (Easiest)
- Deploy Ollama on a GPU Droplet
- Models: Llama, Mistral, DeepSeek, CodeLlama, etc.
- API format: OpenAI-compatible endpoints

#### Option B: vLLM (Performance-focused)
- High-performance inference server
- OpenAI-compatible API
- Better for production workloads

#### Option C: Text Generation Inference (Hugging Face)
- Supports most open-source models
- Production-ready inference server

## Implementation Plan

### Phase 1: Settings Enhancement

#### 1.1 Add DigitalOcean Provider Option
```php
// In settings.php - Add new provider option
$providers = [
    'openai' => get_string('provider_openai', 'local_aiawesome'),
    'digitalocean' => get_string('provider_digitalocean', 'local_aiawesome'),
];
```

#### 1.2 DigitalOcean-specific Settings
- **Endpoint URL**: Custom endpoint (e.g., `https://your-droplet.example.com`)
- **API Key**: Optional authentication token
- **Model Name**: User-specified model identifier
- **Custom Headers**: Additional headers if needed

#### 1.3 Settings Validation
- Test connection to custom endpoint
- Validate model availability
- Check API format compatibility

### Phase 2: API Service Enhancement

#### 2.1 Update api_service.php
```php
class api_service {
    private function get_digitalocean_config() {
        return [
            'endpoint' => get_config('local_aiawesome', 'digitalocean_endpoint'),
            'api_key' => get_config('local_aiawesome', 'digitalocean_api_key'),
            'model' => get_config('local_aiawesome', 'digitalocean_model'),
        ];
    }
    
    private function prepare_digitalocean_payload($query, $context) {
        // Format request for OpenAI-compatible endpoints
        return [
            'model' => $this->get_digitalocean_config()['model'],
            'messages' => $this->build_messages($query, $context),
            'stream' => true,
            'max_tokens' => 1000,
        ];
    }
}
```

#### 2.2 Authentication Handling
- Support for API keys
- Bearer token authentication
- Custom headers for specialized setups

### Phase 3: Frontend Integration

#### 3.1 Model Selection UI
- Dropdown for deployed models
- Auto-discovery of available models (if supported)
- Custom model name input

#### 3.2 Provider-specific Settings
- Connection status indicator
- Model performance metrics
- Cost tracking (instance hours)

### Phase 4: Documentation & Deployment Guides

## Detailed Implementation

### 1. Settings Schema Updates

#### Database Schema (db/install.xml)
```xml
<!-- Add DigitalOcean-specific settings -->
<FIELD NAME="digitalocean_endpoint" TYPE="char" LENGTH="255" NOTNULL="false"/>
<FIELD NAME="digitalocean_api_key" TYPE="char" LENGTH="255" NOTNULL="false"/>
<FIELD NAME="digitalocean_model" TYPE="char" LENGTH="100" NOTNULL="false"/>
<FIELD NAME="digitalocean_headers" TYPE="text" NOTNULL="false"/>
```

#### Settings Form Enhancement
```php
// Provider selection
$settings->add(new admin_setting_configselect(
    'local_aiawesome/ai_provider',
    get_string('ai_provider', 'local_aiawesome'),
    get_string('ai_provider_desc', 'local_aiawesome'),
    'openai',
    [
        'openai' => get_string('provider_openai', 'local_aiawesome'),
        'digitalocean' => get_string('provider_digitalocean', 'local_aiawesome'),
    ]
));

// DigitalOcean-specific settings (conditional display)
$settings->add(new admin_setting_configtext(
    'local_aiawesome/digitalocean_endpoint',
    get_string('digitalocean_endpoint', 'local_aiawesome'),
    get_string('digitalocean_endpoint_desc', 'local_aiawesome'),
    '',
    PARAM_URL
));

$settings->add(new admin_setting_configpasswordunmask(
    'local_aiawesome/digitalocean_api_key',
    get_string('digitalocean_api_key', 'local_aiawesome'),
    get_string('digitalocean_api_key_desc', 'local_aiawesome'),
    ''
));

$settings->add(new admin_setting_configtext(
    'local_aiawesome/digitalocean_model',
    get_string('digitalocean_model', 'local_aiawesome'),
    get_string('digitalocean_model_desc', 'local_aiawesome'),
    'llama3.1:8b'
));
```

### 2. API Service Enhancement

#### Enhanced api_service.php
```php
public function get_api_endpoint(): ?string {
    $provider = get_config('local_aiawesome', 'ai_provider');
    
    switch ($provider) {
        case 'openai':
            return $this->get_openai_endpoint();
        case 'digitalocean':
            return $this->get_digitalocean_endpoint();
        default:
            return null;
    }
}

private function get_digitalocean_endpoint(): ?string {
    $endpoint = get_config('local_aiawesome', 'digitalocean_endpoint');
    if (empty($endpoint)) {
        return null;
    }
    
    // Ensure proper endpoint format
    $endpoint = rtrim($endpoint, '/');
    if (strpos($endpoint, '/v1/chat/completions') === false) {
        $endpoint .= '/v1/chat/completions';
    }
    
    return $endpoint;
}

public function prepare_chat_payload(string $query, array $context): array {
    $provider = get_config('local_aiawesome', 'ai_provider');
    
    switch ($provider) {
        case 'openai':
            return $this->prepare_openai_payload($query, $context);
        case 'digitalocean':
            return $this->prepare_digitalocean_payload($query, $context);
        default:
            throw new \Exception('Unknown AI provider: ' . $provider);
    }
}

private function prepare_digitalocean_payload(string $query, array $context): array {
    $config = $this->get_digitalocean_config();
    
    return [
        'model' => $config['model'] ?? 'llama3.1:8b',
        'messages' => $this->build_messages($query, $context),
        'stream' => true,
        'max_tokens' => (int) get_config('local_aiawesome', 'max_tokens') ?: 1000,
        'temperature' => (float) get_config('local_aiawesome', 'temperature') ?: 0.7,
    ];
}
```

### 3. Connection Testing

#### Enhanced test_connection.php
```php
class digitalocean_connection_test {
    public static function test_connection(): array {
        $config = self::get_digitalocean_config();
        
        if (empty($config['endpoint'])) {
            return [
                'success' => false,
                'message' => 'DigitalOcean endpoint not configured',
            ];
        }
        
        // Test basic connectivity
        $connectivity_test = self::test_endpoint_connectivity($config['endpoint']);
        if (!$connectivity_test['success']) {
            return $connectivity_test;
        }
        
        // Test model availability
        $model_test = self::test_model_availability($config);
        if (!$model_test['success']) {
            return $model_test;
        }
        
        // Test chat completion
        return self::test_chat_completion($config);
    }
    
    private static function test_endpoint_connectivity(string $endpoint): array {
        // Remove /v1/chat/completions for health check
        $base_url = preg_replace('#/v1/chat/completions$#', '', $endpoint);
        $health_url = $base_url . '/health'; // Common for Ollama/vLLM
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $health_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($http_code === 200) {
            return [
                'success' => true,
                'message' => 'DigitalOcean endpoint is reachable',
            ];
        }
        
        return [
            'success' => false,
            'message' => "Cannot reach DigitalOcean endpoint (HTTP $http_code)",
        ];
    }
}
```

### 4. Language Strings

#### lang/en/local_aiawesome.php additions
```php
// DigitalOcean provider strings
$string['provider_digitalocean'] = 'DigitalOcean (Custom Endpoint)';
$string['digitalocean_endpoint'] = 'DigitalOcean Endpoint URL';
$string['digitalocean_endpoint_desc'] = 'Full URL to your DigitalOcean-hosted model endpoint (e.g., https://your-droplet.example.com:8000/v1/chat/completions)';
$string['digitalocean_api_key'] = 'API Key (Optional)';
$string['digitalocean_api_key_desc'] = 'API key for authentication if your endpoint requires it';
$string['digitalocean_model'] = 'Model Name';
$string['digitalocean_model_desc'] = 'Name of the model deployed on your DigitalOcean instance (e.g., llama3.1:8b, deepseek-r1:7b)';
$string['digitalocean_test_success'] = 'DigitalOcean connection test successful! Model: {$a->model}, Response time: {$a->response_time}ms';
$string['digitalocean_test_failed'] = 'DigitalOcean connection test failed: {$a}';
```

## Deployment Guides for Users

### Guide 1: Deploying Ollama on DigitalOcean

#### Step 1: Create GPU Droplet
1. Go to DigitalOcean control panel
2. Create new Droplet
3. Choose GPU-enabled instance (e.g., GPU-H100x1-80GB)
4. Select AI/ML ready OS image
5. Configure networking and SSH keys

#### Step 2: Install Ollama
```bash
# SSH into your droplet
ssh root@your-droplet-ip

# Install Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Start Ollama service
systemctl start ollama
systemctl enable ollama

# Configure Ollama to accept external connections
export OLLAMA_HOST=0.0.0.0:11434
echo 'OLLAMA_HOST=0.0.0.0:11434' >> /etc/environment

# Restart Ollama
systemctl restart ollama
```

#### Step 3: Deploy Model
```bash
# Pull and run a model (this may take several minutes)
ollama pull llama3.1:8b
ollama pull deepseek-r1:7b
ollama pull codellama:7b

# Verify model is running
curl http://localhost:11434/v1/models
```

#### Step 4: Configure Security
```bash
# Set up nginx reverse proxy with SSL
apt update && apt install nginx certbot python3-certbot-nginx

# Configure nginx
cat > /etc/nginx/sites-available/ollama << 'EOF'
server {
    listen 80;
    server_name your-domain.com;
    
    location / {
        proxy_pass http://127.0.0.1:11434;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
EOF

# Enable site and get SSL
ln -s /etc/nginx/sites-available/ollama /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
certbot --nginx -d your-domain.com
```

### Guide 2: Configuring AI Awesome

#### Step 1: Plugin Settings
1. Go to Site Administration > Plugins > Local plugins > AI Awesome
2. Set "AI Provider" to "DigitalOcean (Custom Endpoint)"
3. Enter your endpoint: `https://your-domain.com/v1/chat/completions`
4. Set model name: `llama3.1:8b` (or your deployed model)
5. Leave API key blank if no authentication required

#### Step 2: Test Connection
1. Click "Test DigitalOcean Connection"
2. Verify successful connection and model response
3. Save settings

## Benefits of DigitalOcean Integration

### For Users
- **Full Control**: Own your models and data
- **Privacy**: No data sent to third-party APIs
- **Cost Predictability**: Pay for compute time, not per token
- **Model Choice**: Deploy any open-source model
- **Performance**: Dedicated GPU resources

### For Institutions
- **Compliance**: Keep sensitive data in-house
- **Customization**: Fine-tune models for specific use cases
- **Scalability**: Scale infrastructure as needed
- **Integration**: Connect to existing infrastructure

## Estimated Costs

### DigitalOcean GPU Droplet Pricing (as of 2025)
- **Single GPU (H100)**: ~$2.50/hour
- **Multi-GPU setups**: Scale accordingly
- **Storage**: Additional cost for model storage

### Cost Comparison
- **OpenAI GPT-4**: ~$0.03/1K tokens ($30-60/million tokens)
- **DigitalOcean**: Fixed hourly rate regardless of usage
- **Break-even**: ~1-2 million tokens per day

## Security Considerations

### Network Security
- Use HTTPS/TLS for all communications
- Implement proper firewall rules
- Consider VPN access for sensitive deployments

### Authentication
- API key authentication for endpoint access
- Moodle session validation (already implemented)
- Rate limiting at both Moodle and endpoint levels

### Data Privacy
- All data stays within your infrastructure
- No third-party API calls for chat content
- Full audit trail of AI interactions

## Future Enhancements

### Multi-Model Support
- Deploy multiple models on same instance
- Dynamic model selection based on query type
- Load balancing across multiple models

### Advanced Features
- Model performance monitoring
- Usage analytics dashboard
- Automatic model updates
- Fine-tuning integration

### Integration Options
- Docker deployment guides
- Kubernetes orchestration
- Auto-scaling configurations
- Model marketplace integration

## Conclusion

Adding DigitalOcean support to AI Awesome provides users with a flexible, private, and cost-effective alternative to hosted AI services. This implementation maintains the same user experience while giving institutions full control over their AI infrastructure.

The phased approach ensures compatibility with existing functionality while adding powerful new capabilities for users who want to host their own models.