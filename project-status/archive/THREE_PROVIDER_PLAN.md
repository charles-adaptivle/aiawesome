# Three-Provider Architecture Implementation Plan

## Overview

Restructure AI Awesome to support three distinct AI provider types with clear separation of concerns and authentication methods.

## Current Problem

The existing implementation merged the original **OAuth-based custom endpoint** functionality into the OpenAI section, losing the clear separation between:

1. **OpenAI Direct API** (API key authentication)
2. **Custom OAuth Service** (third-party OAuth2 client-credentials flow)  
3. **DigitalOcean Custom Endpoint** (simple bearer token or no auth)

## Proposed Architecture

```
AI Awesome Settings
├── General Settings (enabled, default open, etc.)
├── AI Provider Selection (radio buttons)
│   ├── ○ OpenAI (Direct API)
│   ├── ○ Custom OAuth Service  
│   └── ○ DigitalOcean (Custom Endpoint)
└── Provider-Specific Configuration Sections
    ├── OpenAI Configuration
    │   ├── API Key
    │   ├── Organization ID
    │   ├── Project ID  
    │   └── Model Selection
    ├── Custom OAuth Service Configuration
    │   ├── Base URL
    │   ├── Token URL
    │   ├── Client ID
    │   ├── Client Secret
    │   └── App ID
    └── DigitalOcean Configuration
        ├── Endpoint URL
        ├── API Key (optional)
        ├── Model Name
        └── Custom Headers
```

## Implementation Plan

### Phase 1: Language Strings
- Add new language strings for Custom OAuth provider
- Update existing strings to clarify provider types
- Add descriptive help text for each provider option

### Phase 2: Settings Page Restructure
- Remove auth_mode from OpenAI section (now provider-specific)
- Create three distinct provider configuration sections
- Move existing OAuth settings to Custom OAuth section
- Ensure clean visual separation between providers

### Phase 3: API Service Enhancement
- Update constructor to use provider-only (remove auth_mode)
- Refactor get_api_endpoint() for three providers
- Refactor get_auth_header() for three providers
- Create provider-specific private methods
- Update prepare_chat_payload() for all providers

### Phase 4: Migration Logic
- Detect existing configurations and set appropriate provider
- Maintain backwards compatibility
- Handle edge cases where multiple configurations exist

### Phase 5: Connection Testing
- Update test_connection.php to handle all three providers
- Provider-specific test methods
- Clear error messages for each provider type

## Provider Specifications

### OpenAI Provider
- **Authentication**: Direct API key (Bearer token)
- **Endpoint**: Fixed `https://api.openai.com/v1/chat/completions`
- **Headers**: Optional Organization-ID, OpenAI-Project
- **Payload**: OpenAI standard format with model selection
- **Use Case**: Simple, direct OpenAI API access

### Custom OAuth Provider  
- **Authentication**: OAuth2 client-credentials flow → JWT Bearer token
- **Endpoint**: Configurable `{base_url}/chat/completions`
- **Headers**: Standard OAuth Bearer + custom app headers
- **Payload**: Custom format with app_id and context
- **Use Case**: Enterprise AI gateway, Azure OpenAI, custom AI platform

### DigitalOcean Provider
- **Authentication**: Optional Bearer token or none
- **Endpoint**: Fully configurable URL (auto-append `/v1/chat/completions` if needed)
- **Headers**: Custom headers from configuration
- **Payload**: OpenAI-compatible format with configurable model
- **Use Case**: Self-hosted models (Ollama, vLLM, TGI, etc.)

## Configuration Structure

### Current Settings (to be reorganized):
```
local_aiawesome/auth_mode = 'oauth'|'token'
local_aiawesome/base_url = 'https://...'
local_aiawesome/token_url = 'https://...'
local_aiawesome/client_id = '...'
local_aiawesome/client_secret = '...'
local_aiawesome/app_id = '...'
local_aiawesome/openai_api_key = '...'
local_aiawesome/openai_model = 'gpt-4o-mini'
local_aiawesome/digitalocean_endpoint = 'https://...'
local_aiawesome/digitalocean_api_key = '...'
local_aiawesome/digitalocean_model = 'llama3.1:8b'
```

### New Settings Structure:
```
local_aiawesome/ai_provider = 'openai'|'custom_oauth'|'digitalocean'

# OpenAI-specific
local_aiawesome/openai_api_key = '...'
local_aiawesome/openai_model = 'gpt-4o-mini'
local_aiawesome/openai_organization = '...'
local_aiawesome/openai_project = '...'

# Custom OAuth-specific  
local_aiawesome/oauth_base_url = '...'
local_aiawesome/oauth_token_url = '...'
local_aiawesome/oauth_client_id = '...'
local_aiawesome/oauth_client_secret = '...'
local_aiawesome/oauth_app_id = '...'

# DigitalOcean-specific
local_aiawesome/digitalocean_endpoint = '...'
local_aiawesome/digitalocean_api_key = '...'
local_aiawesome/digitalocean_model = '...'
local_aiawesome/digitalocean_headers = '...'
```

## Migration Strategy

### Auto-Detection Logic:
```php
function detect_current_provider() {
    if (!empty(get_config('local_aiawesome', 'digitalocean_endpoint'))) {
        return 'digitalocean';
    }
    
    $auth_mode = get_config('local_aiawesome', 'auth_mode');
    if ($auth_mode === 'token' && !empty(get_config('local_aiawesome', 'openai_api_key'))) {
        return 'openai';
    }
    
    if ($auth_mode === 'oauth' && !empty(get_config('local_aiawesome', 'base_url'))) {
        return 'custom_oauth';
    }
    
    return 'openai'; // Default
}
```

### Settings Migration:
```php
// Move existing OAuth settings to new prefixed names
base_url → oauth_base_url
token_url → oauth_token_url  
client_id → oauth_client_id
client_secret → oauth_client_secret
app_id → oauth_app_id
```

## Benefits

### ✅ Clear Separation
- Each provider has distinct configuration section
- No confusion between authentication methods
- Provider-specific help text and examples

### ✅ Backwards Compatibility  
- Existing configurations continue working
- Automatic migration of settings
- No breaking changes for current users

### ✅ Extensibility
- Easy to add new providers (Anthropic, Google, etc.)
- Provider-specific authentication patterns
- Custom features per provider

### ✅ User Experience
- Single provider selection at top
- Only relevant settings shown
- Clear understanding of each option

## Implementation Order

1. **Language Strings** - Foundation for UI
2. **Settings Page** - User-facing configuration  
3. **API Service** - Core functionality
4. **Migration Logic** - Backwards compatibility
5. **Connection Testing** - Validation and diagnostics
6. **Documentation** - User guidance

## Testing Strategy

### Manual Testing:
- Fresh install with each provider
- Migration from existing OAuth setup
- Migration from existing OpenAI setup
- Mixed configuration scenarios
- Connection testing for each provider

### Automated Testing:
- Unit tests for provider detection
- API service method testing
- Configuration migration testing
- Mock connection tests

## Success Criteria

- [ ] All three providers configurable independently
- [ ] Existing installations migrate seamlessly
- [ ] Clear provider selection in admin interface
- [ ] Connection testing works for all providers
- [ ] API service correctly routes to appropriate provider
- [ ] Documentation covers all provider types
- [ ] No breaking changes to existing functionality