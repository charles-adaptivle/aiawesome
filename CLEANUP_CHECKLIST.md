# AI Awesome Plugin - Cleanup Checklist
**Created:** 1 October 2025  
**Status:** In Progress  
**Estimated Time:** 4-6 hours total

---

## How to Use This Checklist

- [ ] = Not started
- [⏳] = In progress  
- [✅] = Complete
- [❌] = Skipped/Not needed
- [🔍] = Needs review/decision

---

## Phase 1: Critical Fixes (HIGH PRIORITY) 🔴

### 1.1 Create Database Upgrade Handler
**File:** `db/upgrade.php` (NEW)  
**Time:** 20 minutes  
**Priority:** CRITICAL

- [ ] Create `db/upgrade.php` file with proper structure
- [ ] Add migration check for version 2025092401
- [ ] Call `migration_helper::migrate_to_three_providers()`
- [ ] Test upgrade from old version (if possible)
- [ ] Update `version.php` to 2025100100

**Code Template:**
```php
<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_aiawesome_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025092401) {
        // Migrate from two-mode to three-provider architecture
        require_once(__DIR__ . '/../classes/migration_helper.php');
        \local_aiawesome\migration_helper::migrate_to_three_providers();
        upgrade_plugin_savepoint(true, 2025092401, 'local', 'aiawesome');
    }

    return true;
}
```

---

### 1.2 Fix Hardcoded CORS Headers
**File:** `stream.php`  
**Time:** 15 minutes  
**Priority:** CRITICAL

- [ ] Line 27: Replace hardcoded origin with dynamic detection
- [ ] Line 28: Keep headers configurable
- [ ] Line 44: Replace hardcoded origin
- [ ] Add CORS origin setting to `settings.php` (optional)
- [ ] Test with actual Moodle domain

**Current Code (Lines 26-28):**
```php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: https://ivan.dev.test'); // ❌ Hardcoded
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Moodle-Sesskey');
```

**Replace With:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . $CFG->wwwroot);
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Moodle-Sesskey');
```

**Also Fix Line 44:**
```php
// OLD:
header('Access-Control-Allow-Origin: https://ivan.dev.test');
// NEW:
header('Access-Control-Allow-Origin: ' . $CFG->wwwroot);
```

---

### 1.3 Resolve React App Status
**Files:** `amd/src/app.jsx`, `vite.config.js`  
**Time:** 30 minutes  
**Priority:** CRITICAL - Needs Decision

**Decision Required:** 🔍

Option A: **Use React App** (Complex, feature-rich)
- [ ] Investigate why `app.jsx` isn't building
- [ ] Check `vite.config.js` for missing entry point
- [ ] Update build config to include `app.jsx`
- [ ] Run `npm run build` and verify `amd/build/app.js` is created
- [ ] Update `boot.js` to load React app instead of simple_app
- [ ] Test full React functionality

Option B: **Use Simple App** (Current, working)
- [ ] Verify `simple_app.js` provides all needed functionality
- [ ] Delete `amd/src/app.jsx` (no longer needed)
- [ ] Update documentation to reflect vanilla JS approach
- [ ] Clean up any React references in documentation

**Recommendation:** Choose Option B (simple_app) unless React features are specifically needed

---

## Phase 2: Code Cleanup (MEDIUM PRIORITY) 🟡

### 2.1 Remove Test & Debug Files
**Time:** 10 minutes  
**Priority:** MEDIUM

- [ ] Delete `test.php` - General testing file (not needed)
- [ ] Delete `test_digitalocean.php` - Provider testing (covered by test_connection.php)
- [ ] Delete `debug.php` - Debug utility (not needed in production)
- [ ] Delete `bootstrap_probe.php` - Unclear purpose, likely obsolete
- [ ] Review `headers.php` - Has TODO comment (line 18), determine purpose
  - [ ] If needed: Fix TODO and keep
  - [ ] If not needed: Delete
- [ ] Keep `test_connection.php` ✅ (Used by settings page)

**Commands:**
```bash
cd /Volumes/Projects/docker-sites/ivan/moodle/local/aiawesome
rm test.php test_digitalocean.php debug.php bootstrap_probe.php
# Review headers.php before deleting
```

---

### 2.2 Clean Up Language Strings
**File:** `lang/en/local_aiawesome.php`  
**Time:** 20 minutes  
**Priority:** MEDIUM

**Remove these deprecated strings (old two-mode system):**

- [ ] Line 44: `$string['setting_auth_mode']` ❌
- [ ] Line 45: `$string['setting_auth_mode_desc']` ❌
- [ ] Line 46: `$string['auth_mode_oauth']` ❌
- [ ] Line 47: `$string['auth_mode_token']` ❌
- [ ] Line 49: `$string['setting_base_url']` ❌ (replaced by oauth_base_url)
- [ ] Line 50: `$string['setting_base_url_desc']` ❌
- [ ] Line 52: `$string['setting_app_id']` ❌ (replaced by oauth_app_id)
- [ ] Line 53: `$string['setting_app_id_desc']` ❌
- [ ] Line 55: `$string['setting_token_url']` ❌ (replaced by oauth_token_url)
- [ ] Line 56: `$string['setting_token_url_desc']` ❌
- [ ] Line 58: `$string['setting_client_id']` ❌ (replaced by oauth_client_id)
- [ ] Line 59: `$string['setting_client_id_desc']` ❌
- [ ] Line 61: `$string['setting_client_secret']` ❌ (replaced by oauth_client_secret)
- [ ] Line 62: `$string['setting_client_secret_desc']` ❌

**Keep these new strings (three-provider system):**
- ✅ `setting_oauth_base_url` (line 155)
- ✅ `setting_oauth_token_url` (line 158)
- ✅ `setting_oauth_client_id` (line 161)
- ✅ `setting_oauth_client_secret` (line 164)
- ✅ `setting_oauth_app_id` (line 167)

---

### 2.3 Update index.php (Health Check Page)
**File:** `index.php`  
**Time:** 25 minutes  
**Priority:** MEDIUM

**Update to use new provider system:**

- [ ] Line 54: Replace `base_url` with provider-specific detection
- [ ] Line 55: Replace `client_id` with provider-specific detection
- [ ] Line 56: Replace `client_secret` with provider-specific detection
- [ ] Line 57: Replace `token_url` with provider-specific detection
- [ ] Line 189: Update configuration check logic for three providers
- [ ] Add provider detection display (openai/custom_oauth/digitalocean)
- [ ] Show provider-specific configuration status
- [ ] Test page displays correctly

**Current Lines 54-57:**
```php
$base_url = get_config('local_aiawesome', 'base_url');
$client_id = get_config('local_aiawesome', 'client_id');
$client_secret = get_config('local_aiawesome', 'client_secret');
$token_url = get_config('local_aiawesome', 'token_url');
```

**Replace With:**
```php
$provider = get_config('local_aiawesome', 'ai_provider') ?: 'openai';

// Provider-specific configuration
switch ($provider) {
    case 'openai':
        $api_key = get_config('local_aiawesome', 'openai_api_key');
        $model = get_config('local_aiawesome', 'openai_model');
        break;
    case 'custom_oauth':
        $base_url = get_config('local_aiawesome', 'oauth_base_url');
        $client_id = get_config('local_aiawesome', 'oauth_client_id');
        $client_secret = get_config('local_aiawesome', 'oauth_client_secret');
        $token_url = get_config('local_aiawesome', 'oauth_token_url');
        break;
    case 'digitalocean':
        $endpoint = get_config('local_aiawesome', 'digitalocean_endpoint');
        $model = get_config('local_aiawesome', 'digitalocean_model');
        break;
}
```

---

### 2.4 Update diagnostics.php
**File:** `diagnostics.php`  
**Time:** 20 minutes  
**Priority:** MEDIUM

**Update to use new provider system:**

- [ ] Line 49: Replace `auth_mode` with `ai_provider` display
- [ ] Line 68: Replace `auth_mode === 'token'` check with `ai_provider === 'openai'`
- [ ] Line 77-79: Update to show provider-specific OAuth settings
- [ ] Add sections for each provider type
- [ ] Show only relevant config for active provider
- [ ] Test diagnostic output

**Current Line 49:**
```php
echo '<tr><td><strong>Authentication Mode</strong></td><td>' . (get_config('local_aiawesome', 'auth_mode') ?: 'oauth') . '</td></tr>';
```

**Replace With:**
```php
$provider = get_config('local_aiawesome', 'ai_provider') ?: 'openai';
echo '<tr><td><strong>AI Provider</strong></td><td>' . $provider . '</td></tr>';
```

**Current Line 68:**
```php
if (get_config('local_aiawesome', 'auth_mode') === 'token') {
```

**Replace With:**
```php
if ($provider === 'openai') {
```

---

## Phase 3: Testing & Verification (HIGH PRIORITY) 🔍

### 3.1 Test Three-Provider System
**Time:** 45 minutes  
**Priority:** HIGH

#### OpenAI Provider Testing
- [ ] Set `ai_provider` to `openai` in settings
- [ ] Configure OpenAI API key
- [ ] Set OpenAI model (e.g., gpt-4o-mini)
- [ ] Test connection via settings page
- [ ] Send test chat message
- [ ] Verify streaming response
- [ ] Check logs table for entry

#### Custom OAuth Provider Testing
- [ ] Set `ai_provider` to `custom_oauth` in settings
- [ ] Configure OAuth base URL, token URL, client ID/secret, app ID
- [ ] Test connection via settings page
- [ ] Verify token retrieval from OAuth endpoint
- [ ] Send test chat message
- [ ] Verify streaming response
- [ ] Check token caching in MUC

#### DigitalOcean Provider Testing
- [ ] Set `ai_provider` to `digitalocean` in settings
- [ ] Configure DigitalOcean endpoint
- [ ] Configure model name (e.g., llama3.1:8b)
- [ ] Add optional bearer token if needed
- [ ] Test connection via settings page
- [ ] Send test chat message
- [ ] Verify streaming response
- [ ] Test custom headers if configured

---

### 3.2 Test Migration Path
**Time:** 30 minutes  
**Priority:** HIGH

- [ ] Create backup of test database
- [ ] Set old configuration keys (simulate old installation):
  - [ ] `auth_mode` = 'oauth'
  - [ ] `client_id` = 'test-client-id'
  - [ ] `base_url` = 'https://test.example.com'
- [ ] Remove `ai_provider` setting (simulate pre-migration state)
- [ ] Trigger upgrade process
- [ ] Verify migration helper runs
- [ ] Check `ai_provider` is set correctly
- [ ] Verify OAuth settings migrated to `oauth_*` keys
- [ ] Check old settings cleaned up
- [ ] Test functionality with migrated settings

---

### 3.3 Frontend Testing
**Time:** 30 minutes  
**Priority:** HIGH

- [ ] Clear browser cache
- [ ] Clear Moodle caches (`Admin → Development → Purge caches`)
- [ ] Log in as different user roles:
  - [ ] Student
  - [ ] Teacher
  - [ ] Manager
- [ ] Verify chat icon appears in user menu
- [ ] Click icon to open chat drawer
- [ ] Test chat interface:
  - [ ] Send message
  - [ ] See streaming response
  - [ ] Stop button works
  - [ ] Close drawer
  - [ ] Reopen maintains state
- [ ] Test keyboard navigation:
  - [ ] Tab through interface
  - [ ] ESC closes drawer
  - [ ] Enter sends message
- [ ] Test on mobile/tablet responsive view

---

### 3.4 Security Testing
**Time:** 30 minutes  
**Priority:** HIGH

- [ ] Verify no secrets in browser network tab
- [ ] Check OAuth tokens never sent to client
- [ ] Test CSRF protection (invalid sesskey)
- [ ] Test capability checks:
  - [ ] User without `local/aiawesome:use` cannot access
  - [ ] User without `local/aiawesome:viewlogs` cannot see logs
- [ ] Verify context encryption/decryption works
- [ ] Check encrypted payload in network requests
- [ ] Test rate limiting (if configured)
- [ ] Verify session handling during long SSE

---

### 3.5 GDPR Privacy Testing
**Time:** 20 minutes  
**Priority:** MEDIUM

- [ ] Navigate to `Admin → Users → Privacy and policies → Data registry`
- [ ] Verify `local_aiawesome` appears in plugin list
- [ ] Test data export:
  - [ ] Request user data export
  - [ ] Verify AI chat logs included
  - [ ] Check content included/excluded based on settings
- [ ] Test data deletion:
  - [ ] Request user account deletion
  - [ ] Verify AI logs are deleted
  - [ ] Check database table for user records
- [ ] Verify privacy policy text is clear

---

## Phase 4: Documentation Updates (LOW PRIORITY) 📝

### 4.1 Update README.md
**File:** `README.md`  
**Time:** 20 minutes  
**Priority:** LOW

- [ ] Update configuration section for three providers
- [ ] Add OpenAI quick setup section (already exists, verify accuracy)
- [ ] Add Custom OAuth service setup section
- [ ] Add DigitalOcean setup section
- [ ] Update troubleshooting section
- [ ] Add migration notes for existing users
- [ ] Update file structure section
- [ ] Verify all code examples are current

---

### 4.2 Create Troubleshooting Guide
**File:** `TROUBLESHOOTING.md` (NEW)  
**Time:** 30 minutes  
**Priority:** LOW

- [ ] Create new troubleshooting guide
- [ ] Add common issues and solutions:
  - [ ] Chat icon not appearing
  - [ ] CORS errors
  - [ ] OAuth token failures
  - [ ] Streaming connection issues
  - [ ] Rate limiting errors
  - [ ] Provider-specific errors
- [ ] Add debugging steps
- [ ] Add log file locations
- [ ] Add configuration validation checklist
- [ ] Add network diagnostic steps

---

### 4.3 Update Version and Changelog
**File:** `version.php`  
**Time:** 5 minutes  
**Priority:** LOW

- [ ] Update version to `2025100100`
- [ ] Update release notes in comments
- [ ] Create `CHANGELOG.md` file with version history:
  - [ ] v1.1 (2025-10-01) - Three-provider architecture, cleanup
  - [ ] v1.0 (2025-09-24) - Initial release

---

## Phase 5: Optional Enhancements ⭐

### 5.1 Add Provider-Specific Health Checks
**File:** `classes/api_service.php`  
**Time:** 45 minutes  
**Priority:** OPTIONAL

- [ ] Create `validate_openai_config()` method
- [ ] Create `validate_custom_oauth_config()` method
- [ ] Create `validate_digitalocean_config()` method
- [ ] Add validation to settings page
- [ ] Show helpful error messages for incomplete config
- [ ] Add connection test per provider
- [ ] Display configuration hints

---

### 5.2 Add Admin Dashboard
**File:** `admin_dashboard.php` (NEW)  
**Time:** 2-3 hours  
**Priority:** OPTIONAL

- [ ] Create admin dashboard page
- [ ] Add usage statistics:
  - [ ] Total requests today/week/month
  - [ ] Requests by provider
  - [ ] Average response time
  - [ ] Error rate
  - [ ] Top users
  - [ ] Top courses
- [ ] Add charts (using Chart.js or similar)
- [ ] Add export functionality
- [ ] Add date range filters
- [ ] Add permission check (`local/aiawesome:viewlogs`)

---

### 5.3 Add Rate Limiting Configuration
**File:** `settings.php`  
**Time:** 30 minutes  
**Priority:** OPTIONAL

- [ ] Add per-user rate limit setting (already exists, verify working)
- [ ] Add global rate limit setting
- [ ] Add rate limit window setting (hourly/daily)
- [ ] Add rate limit bypass capability
- [ ] Test rate limiting enforcement
- [ ] Add rate limit exceeded error messages

---

### 5.4 Add Monitoring/Alerting
**File:** `classes/monitoring_service.php` (NEW)  
**Time:** 1-2 hours  
**Priority:** OPTIONAL

- [ ] Create monitoring service class
- [ ] Add error threshold alerts
- [ ] Add usage spike detection
- [ ] Add token expiry warnings
- [ ] Add email notifications for admins
- [ ] Add integration with Moodle's task system
- [ ] Create scheduled task for monitoring

---

## Phase 6: Final Checks ✅

### 6.1 Code Quality Review
**Time:** 30 minutes  
**Priority:** HIGH

- [ ] Run ESLint: `npm run lint`
- [ ] Fix any linting errors: `npm run lint:fix`
- [ ] Run Moodle code checker (if available)
- [ ] Check for PHP warnings/errors
- [ ] Verify all debugging statements removed
- [ ] Check file permissions are correct
- [ ] Verify no sensitive data in comments
- [ ] Remove any TODO comments or create issues

---

### 6.2 Build Verification
**Time:** 15 minutes  
**Priority:** HIGH

- [ ] Delete `node_modules` folder
- [ ] Delete `amd/build` folder
- [ ] Run `npm install`
- [ ] Run `npm run build`
- [ ] Verify all expected files in `amd/build/`:
  - [ ] boot.js
  - [ ] simple_app.js (or app.js if using React)
  - [ ] sse.js
- [ ] Check file sizes are reasonable (minified)
- [ ] Test built assets load in browser

---

### 6.3 Database Verification
**Time:** 15 minutes  
**Priority:** HIGH

- [ ] Check database schema is current
- [ ] Verify all indexes exist:
  - [ ] sessionid
  - [ ] createdat
  - [ ] status
  - [ ] userid_createdat
  - [ ] courseid_createdat
- [ ] Check foreign keys are correct
- [ ] Verify cache definitions in `db/caches.php`
- [ ] Check hooks registered in `db/hooks.php`

---

### 6.4 Permissions & Capabilities
**Time:** 10 minutes  
**Priority:** HIGH

- [ ] Verify capabilities defined in `db/access.php`:
  - [ ] local/aiawesome:view
  - [ ] local/aiawesome:use
  - [ ] local/aiawesome:viewlogs
- [ ] Check role archetypes are appropriate
- [ ] Test permission override at course level (if applicable)
- [ ] Verify capability checks in all endpoints

---

### 6.5 Production Deployment Prep
**Time:** 20 minutes  
**Priority:** HIGH

- [ ] Create deployment checklist document
- [ ] Document configuration requirements per provider
- [ ] Create backup procedure documentation
- [ ] Document rollback procedure
- [ ] Prepare release notes
- [ ] Tag version in git (if using version control)
- [ ] Create plugin package for distribution
- [ ] Test installation on clean Moodle instance

---

## Completion Summary

### Progress Tracking
```
Phase 1 (Critical):        [ ] 0/3  (0%)
Phase 2 (Cleanup):         [ ] 0/4  (0%)
Phase 3 (Testing):         [ ] 0/5  (0%)
Phase 4 (Documentation):   [ ] 0/3  (0%)
Phase 5 (Optional):        [ ] 0/4  (0%)
Phase 6 (Final Checks):    [ ] 0/5  (0%)

Total Required Tasks:      [ ] 0/20 (0%)
Total Optional Tasks:      [ ] 0/4  (0%)
Overall Completion:        [ ] 0/24 (0%)
```

### Time Tracking
- **Estimated Time:** 4-6 hours (required tasks only)
- **With Optional Tasks:** 8-12 hours
- **Start Time:** _____________
- **End Time:** _____________
- **Actual Time:** _____________

---

## Notes & Issues

**Issues Encountered:**
1. 
2. 
3. 

**Decisions Made:**
1. 
2. 
3. 

**Deferred Tasks:**
1. 
2. 
3. 

---

## Sign-Off

- [ ] All critical tasks completed
- [ ] All tests passing
- [ ] Documentation updated
- [ ] Ready for production deployment

**Completed By:** _____________  
**Date:** _____________  
**Reviewed By:** _____________  
**Date:** _____________

---

**Next Steps After Completion:**
1. Deploy to staging environment
2. Perform UAT (User Acceptance Testing)
3. Monitor for 48 hours
4. Deploy to production
5. Monitor and gather feedback
