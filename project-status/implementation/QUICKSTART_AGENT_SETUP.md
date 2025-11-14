# Quick Start: DigitalOcean Agent Platform Setup

## Prerequisites

✅ AI Awesome plugin installed (v1.2+)  
✅ DigitalOcean Agent Platform account  
✅ Agent created and API key generated

## 5-Minute Setup

### Step 1: Get Your Agent Credentials

From DigitalOcean Agent Platform dashboard:

1. Copy your **Agent Endpoint URL**
   - Format: `https://xxxxxxxxxxxxx.agents.do-ai.run`
   - Example: `https://cmdti4aw5tkwfys5iwr2id6r.agents.do-ai.run`

2. Copy your **API Key**
   - Format: Long alphanumeric string
   - Example: `UHlL4eyq3fvf7-PaW8HyG73jA8eDVunt`

3. Note your **Model Name** (optional, for reference)
   - Example: `OpenAI GPT-oss-120b`

### Step 2: Configure in Moodle

Navigate to: **Site Administration → Plugins → Local plugins → AI Awesome**

1. **AI Provider** dropdown: Select **"DigitalOcean Agent Platform"**

2. Scroll to **DigitalOcean Agent Platform Configuration** section

3. Fill in the three fields:
   - **Agent Endpoint URL:** Paste your endpoint URL
   - **Agent API Key:** Paste your API key  
   - **Agent Model:** Enter model name (optional, informational only)

### Step 3: Test Connection

1. Scroll down to **Testing & Diagnostics** section
2. Click the **"Test API Connection"** button
3. Wait for response (2-5 seconds)
4. Verify you see: ✅ **"Success: API connection successful (Mode: digitalocean_agent)"**

### Step 4: Save and Enable

1. Scroll to top and verify **"Enable AI Awesome"** is checked
2. Click **"Save changes"** button
3. Done! 🎉

## Verification Checklist

After setup, verify:

- [ ] Connection test shows success
- [ ] No error messages in test result
- [ ] Mode shows "digitalocean_agent" (not "openai" or "digitalocean")
- [ ] Chat icon appears in user menu (for users with permission)
- [ ] Test query in chat returns streaming response

## Testing the Chat Interface

### As Admin (or permitted user):

1. Look for chat icon in top-right user menu
2. Click to open chat drawer
3. Type: **"What is Moodle?"**
4. Press Enter or click Send
5. Watch response stream in real-time

**Expected behavior:**
- Response begins within 2-3 seconds
- Text appears word-by-word (streaming)
- Complete response about Moodle's purpose and features
- No error messages

## Troubleshooting Quick Fixes

### ❌ "HTTP 405: Method Not Allowed"

**Problem:** You included `/api/v1/chat/completions` in endpoint URL

**Fix:** Use base URL only
- ❌ Wrong: `https://xxx.agents.do-ai.run/api/v1/chat/completions`
- ✅ Right: `https://xxx.agents.do-ai.run`

The plugin adds the path automatically.

---

### ❌ "HTTP 404: Not Found"

**Problem:** Incorrect endpoint URL or typo

**Fix:** Double-check your agent endpoint in DigitalOcean dashboard

---

### ❌ "Authentication failed" or "HTTP 401"

**Problem:** Invalid or expired API key

**Fix:** 
1. Go to DigitalOcean Agent Platform dashboard
2. Regenerate API key if needed
3. Update in Moodle settings
4. Test connection again

---

### ❌ "CONFIG_ERROR: API service not configured"

**Problem:** AI Provider not set to "DigitalOcean Agent Platform"

**Fix:** Check **AI Provider** dropdown is set correctly

---

### ❌ Chat icon doesn't appear

**Problem:** Plugin disabled or user lacks permission

**Fix:**
1. Verify **"Enable AI Awesome"** is checked
2. Check user has `local/aiawesome:view` capability
3. Clear caches: **Site Administration → Development → Purge caches**

## Command-Line Setup (Alternative)

For server administrators, use the CLI script:

```bash
# Access your Moodle container/server
cd /var/www/html

# Run the configuration script
php configure_agent.php
```

The script will:
- Set AI provider to digitalocean_agent
- Configure endpoint: `https://cmdti4aw5tkwfys5iwr2id6r.agents.do-ai.run`
- Set API key: `UHlL4eyq3fvf7-PaW8HyG73jA8eDVunt`
- Enable the plugin

## Provider Comparison

| Feature | OpenAI | DigitalOcean Direct | **DigitalOcean Agent** |
|---------|--------|-------------------|---------------------|
| Setup Complexity | Low | Medium | **Low** |
| Model Selection | Manual | Manual | **Pre-configured** |
| RAG/Knowledge Base | ❌ | ❌ | **✅** |
| Function Calling | ❌ | ❌ | **✅** |
| Custom Guardrails | ❌ | ❌ | **✅** |
| Cost | Pay per token | Infrastructure | **Per agent** |
| Best For | Quick start | Full control | **Production** |

## Next Steps

After successful setup:

1. **Set Permissions**
   - Go to: **Site Administration → Users → Permissions → Define roles**
   - Grant `local/aiawesome:view` and `local/aiawesome:use` to desired roles

2. **Configure Guardrails**
   - Set rate limits (requests per hour)
   - Adjust temperature (0.0-1.0)
   - Set max tokens per response

3. **Monitor Usage**
   - Enable logging in settings
   - Review diagnostics page periodically
   - Check for errors or rate limit issues

4. **Train Users**
   - Demonstrate chat interface
   - Share example queries
   - Explain AI limitations

## Support

- **Documentation:** See `DIGITALOCEAN_AGENT_PLATFORM.md`
- **Issues:** Check Moodle error logs
- **Community:** Moodle forums
- **Vendor:** DigitalOcean support for agent-specific issues

---

**Last Updated:** 7 November 2025  
**Plugin Version:** 1.2+  
**Tested On:** Moodle 4.5.6+
