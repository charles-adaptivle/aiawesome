# AI Awesome - React Slide-Out AI Chat for Moodle - DEV EDITION!!!

A Moodle 4.5+ local plugin that adds a secure, React-powered AI chat interface accessible from any page. Features server-sent event streaming, OAuth2 authentication, encrypted context, and comprehensive privacy controls.

## Features

- 🚀 **React-powered UI**: Modern chat interface with streaming responses
- 🔐 **Secure**: OAuth2 client-credentials flow with server-side secrets
- 🔒 **Privacy**: Encrypted context, GDPR-compliant, minimal logging
- 📱 **Responsive**: Theme-agnostic design that works on all devices
- ⚡ **Performance**: Lazy-loaded components, efficient streaming
- ♿ **Accessible**: WCAG 2.1 AA compliant with keyboard navigation
- 🎨 **Theme-friendly**: Integrates seamlessly with Boost-family themes

## Requirements

- **Moodle**: 4.5+ (PHP 8.2/8.3)
- **Node.js**: 18+ (for building frontend assets)
- **npm**: 9+ (package management)
- **PHP Extensions**: OpenSSL, cURL, JSON
- **AI Service**: Compatible OAuth2 endpoint with SSE streaming

## Quick Installation

1. **Install Plugin**
   ```bash
   # Navigate to your Moodle local plugins directory
   cd /path/to/moodle/local/
   
   # Clone or extract the plugin
   git clone <repository-url> aiawesome
   # OR extract: unzip aiawesome.zip
   ```

2. **Install Dependencies & Build**
   ```bash
   cd aiawesome
   npm install
   npm run build
   ```

3. **Complete Moodle Installation**
   - Visit `Site Administration → Notifications`
   - Click "Upgrade Moodle database now"
   - Follow the installation prompts

4. **Configure Settings**
   - Go to `Site Administration → Plugins → Local plugins → AI Awesome`
   - Configure your AI service endpoints and OAuth credentials
   - Enable the feature and set appropriate guardrails

5. **Set Permissions**
   - Go to `Site Administration → Users → Permissions → Define roles`
   - Assign `local/aiawesome:view` and `local/aiawesome:use` capabilities
   - Default: All authenticated users have access

## Configuration

### Authentication Modes

The plugin supports two authentication modes:

#### 1. OAuth2 Mode (Custom AI Services)
For custom AI services that use OAuth2 client credentials flow:
- **Authentication Mode**: OAuth2 (Custom Service)
- **AI Service Base URL**: Your AI service endpoint
- **Application ID**: Your app identifier
- **OAuth2 Token URL**: Token endpoint for authentication
- **OAuth2 Client ID**: Your client identifier
- **OAuth2 Client Secret**: Your client secret

#### 2. Token Mode (OpenAI API)
For direct OpenAI API access:
- **Authentication Mode**: API Token (OpenAI)
- **OpenAI API Key**: Get yours at https://platform.openai.com/api-keys
- **OpenAI API Base URL**: Default `https://api.openai.com/v1` (or custom proxy)
- **OpenAI Model**: Model to use (e.g., `gpt-4o-mini`, `gpt-4o`, `gpt-3.5-turbo`)
- **OpenAI Organization ID**: Optional organization ID
- **OpenAI Project ID**: Optional project ID

### Quick Setup for OpenAI Testing

1. **Get an OpenAI API Key**:
   - Visit https://platform.openai.com/api-keys
   - Create a new secret key
   - Copy the key (starts with `sk-`)

2. **Configure the Plugin**:
   - Go to Site Administration → Plugins → Local plugins → AI Awesome
   - Set **Authentication Mode** to "API Token (OpenAI)"
   - Paste your API key in **OpenAI API Key**
   - Set **OpenAI Model** to `gpt-4o-mini` (cost-effective) or `gpt-4o` (more capable)
   - Leave other OpenAI settings as default unless you need specific organization/project settings

3. **Test the Connection**:
   - Scroll down to the "Testing & Diagnostics" section
   - Click **Test API Connection** button
   - You should see a green success message if everything is configured correctly

4. **Enable the Plugin**:
   - Ensure **Enable AI Awesome** is checked
   - Save changes

### Configuration

### AI Service Settings

Navigate to `Site Administration → Plugins → Local plugins → AI Awesome`:

| Setting | Description | Example |
|---------|-------------|---------|
| **Enable AI Awesome** | Master feature toggle | ✓ Enabled |
| **AI Service Base URL** | Your AI service endpoint | `https://api.example.com` |
| **Application ID** | Your app identifier | `moodle-chat-prod` |
| **OAuth2 Token URL** | Authentication endpoint | `https://auth.example.com/oauth/token` |
| **OAuth2 Client ID** | Your client identifier | `your-client-id` |
| **OAuth2 Client Secret** | Your client secret (secure) | `your-client-secret` |
| **Maximum Tokens** | Response length limit | `2000` |
| **Temperature** | Response creativity (0.0-1.0) | `0.7` |
| **Rate Limit** | Requests per user/hour | `100` |

### Security Configuration

The plugin implements multiple security layers:

- **OAuth2 Client Credentials**: Server-side authentication only
- **Context Encryption**: AES-256-GCM with HKDF-derived keys
- **Rate Limiting**: Per-user request throttling
- **Capability Checks**: Moodle permission system integration
- **CSRF Protection**: Automatic sesskey validation

### Privacy & Logging

| Setting | Description | Recommendation |
|---------|-------------|----------------|
| **Enable Logging** | Track usage statistics | ✓ Enable for monitoring |
| **Log Content** | Include conversation content | ❌ Disable for privacy |

**Note**: When content logging is disabled, only metadata (timestamps, byte counts, status) is stored.

## Development Setup

### Prerequisites

```bash
# Check Node.js version (requires 18+)
node --version

# Check npm version (requires 9+)
npm --version
```

### Build Process

```bash
# Install dependencies
npm install

# Development build (with watching)
npm run dev

# Production build
npm run build

# Lint code
npm run lint

# Fix linting issues
npm run lint:fix
```

### File Structure

```
local/aiawesome/
├── version.php                 # Plugin metadata
├── settings.php               # Admin configuration
├── stream.php                 # SSE proxy endpoint
├── package.json              # Node.js dependencies
├── vite.config.js            # Build configuration
├── styles.css                # Plugin styles
│
├── db/
│   ├── access.php            # Capability definitions
│   ├── install.xml           # Database schema
│   └── caches.php            # MUC cache definitions
│
├── classes/
│   ├── oauth_service.php     # OAuth2 client
│   ├── crypto_utils.php      # Encryption utilities
│   ├── logging_service.php   # Usage tracking
│   └── privacy/
│       └── provider.php      # GDPR compliance
│
├── lang/en/
│   └── local_aiawesome.php   # Language strings
│
└── amd/
    ├── src/                  # Source files
    │   ├── boot.js          # UI integration
    │   ├── app.jsx          # React application
    │   └── sse.js           # Streaming client
    └── build/               # Built assets (generated)
        ├── boot.min.js
        ├── app.min.js
        └── sse.min.js
```

## Usage

### For Users

1. **Access**: Look for the chat icon in your user menu (top-right)
2. **Chat**: Click the icon to open the AI chat drawer
3. **Ask**: Type your question and press Enter or click Send
4. **Stream**: Watch responses stream in real-time
5. **Stop**: Click the stop button to halt generation if needed
6. **Context**: The AI knows about your current course and enrollment

### For Administrators

1. **Monitor Usage**: Check logs in the database (`local_aiawesome_logs`)
2. **Manage Permissions**: Control access via role capabilities
3. **Review Settings**: Adjust rate limits and guardrails as needed
4. **Privacy Compliance**: Export/delete user data via standard Moodle tools

## API Integration

### Expected AI Service API

Your AI service should support:

```http
POST /v3/ai/chat/stream/completion
Authorization: Bearer <oauth-token>
Content-Type: application/json

{
  "appId": "your-app-id",
  "query": "User question",
  "chat_session": "session-uuid",
  "metadata": {
    "context": "encrypted-context-data"
  },
  "max_tokens": 2000,
  "temperature": 0.7
}
```

**Response**: Server-Sent Events stream:
```
data: {"text": "Response chunk..."}

data: {"text": " continues here"}

event: final_response
data: {"status": "completed", "references": [...]}
```

### Context Data Structure

The encrypted context contains:
```json
{
  "userId": 123,
  "courseId": 456,
  "courseName": "Course Title",
  "userInfo": {
    "fullname": "User Name",
    "username": "username"
  },
  "enrolledCourseIds": [456, 789]
}
```

## Troubleshooting

### Common Issues

**Chat icon not appearing**
- Check user has `local/aiawesome:view` capability
- Verify plugin is enabled in settings
- Clear caches: `Site Administration → Development → Purge caches`

**Authentication errors**
- Verify OAuth2 credentials in settings
- Check AI service is accessible from Moodle server
- Review PHP error logs for cURL issues

**Streaming not working**
- Test SSE endpoint: `/local/aiawesome/stream.php`
- Check server supports long-running connections
- Verify firewall/proxy doesn't block EventSource

**Build errors**
- Ensure Node.js 18+ is installed
- Delete `node_modules` and run `npm install`
- Check for permission issues in `amd/build/` directory

### Debug Mode

Enable debugging for detailed logs:
```php
// In config.php
$CFG->debug = (E_ALL | E_STRICT);
$CFG->debugdisplay = 1;
```

### Health Check

Test the plugin health:
```bash
# Check if OAuth service is working
curl -X POST https://your-moodle/local/aiawesome/stream.php \
  -H "Content-Type: application/json" \
  -d '{"query":"test","session":"test","sesskey":"....."}'
```

## Security Considerations

### Server Configuration

- **TLS**: Ensure HTTPS is enabled for all endpoints
- **Headers**: Configure appropriate CSP headers
- **Timeouts**: Set reasonable limits for SSE connections
- **Rate Limiting**: Use proxy-level rate limiting if needed

### Data Protection

- **Encryption**: All context data is encrypted before transmission
- **Secrets**: Client credentials never reach the browser
- **Logging**: Content logging is opt-in and privacy-controlled
- **Retention**: Configure log retention policies

## Performance Optimization

### Caching

The plugin uses Moodle's MUC for:
- OAuth token caching (1 hour TTL)
- Configuration caching (request-scoped)
- Rate limiting counters (1 hour TTL)

### Frontend Optimization

- Lazy loading: React app loads only when drawer opens
- Code splitting: Separate bundles for boot, app, and SSE
- Minimization: Production builds are minified and optimized

## Additional Documentation

For detailed technical documentation, implementation guides, and project history, see the **[project-status/](project-status/)** folder:

- **[Features Documentation](project-status/features/)** - Detailed guides for implemented features (bouncing dots, loading skeleton, visual enhancements)
- **[Implementation Guides](project-status/implementation/)** - Technical setup guides (DigitalOcean integration, model caching, project specification)
- **[Project Archive](project-status/archive/)** - Historical documentation (phase completions, implementation plans, project status snapshots)

Start with the **[Project Documentation Index](project-status/README.md)** for a complete overview.

## Contributing

### Code Standards

- **PHP**: Follow Moodle coding guidelines
- **JavaScript**: ESLint configuration enforces standards
- **React**: Functional components with hooks
- **CSS**: BEM methodology with theme compatibility

### Development Workflow

1. Fork the repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Make changes and test thoroughly
4. Run linting: `npm run lint`
5. Build assets: `npm run build`
6. Commit changes: `git commit -m 'Add amazing feature'`
7. Push to branch: `git push origin feature/amazing-feature`
8. Open pull request

## License

This project is licensed under the GNU GPL v3 or later - see the [COPYING.txt](COPYING.txt) file for details.

## Support

- **Documentation**: https://docs.example.com/ai-awesome
- **Issues**: https://github.com/example/ai-awesome/issues
- **Community**: https://moodle.org/plugins/view/local_aiawesome

## Changelog

### v1.0.0 (2025-09-24)
- Initial release
- React-based chat interface
- Server-sent event streaming
- OAuth2 authentication
- Context encryption
- GDPR compliance
- Accessibility features
- Theme-agnostic design