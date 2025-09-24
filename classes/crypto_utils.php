<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Cryptographic utilities for AI Awesome plugin.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiawesome;

defined('MOODLE_INTERNAL') || die();

/**
 * Cryptographic utilities class for secure context encryption.
 */
class crypto_utils {

    /** @var string Algorithm for encryption */
    private const ALGORITHM = 'aes-256-gcm';

    /** @var string HKDF hash algorithm */
    private const HKDF_HASH = 'sha256';

    /** @var int Key length for AES-256 */
    private const KEY_LENGTH = 32;

    /** @var int IV length for AES-GCM */
    private const IV_LENGTH = 12;

    /** @var int Tag length for AES-GCM */
    private const TAG_LENGTH = 16;

    /**
     * Get the plugin-specific salt for key derivation.
     *
     * @return string Plugin salt
     */
    private static function get_plugin_salt(): string {
        global $CFG;
        
        // Create a deterministic salt based on site-specific data.
        $components = [
            $CFG->wwwroot,
            get_config('local_aiawesome', 'client_id') ?: 'default',
            'aiawesome_v1',
        ];
        
        return hash('sha256', implode('|', $components));
    }

    /**
     * Derive an encryption key using HKDF-SHA256.
     *
     * @param string $material Input key material (e.g., JWT sub claim)
     * @param string $info Optional context info
     * @return string Derived key
     */
    public static function derive_key(string $material, string $info = 'aiawesome-context'): string {
        $salt = self::get_plugin_salt();
        
        // Use HKDF for key derivation.
        return hash_hkdf(self::HKDF_HASH, $material, self::KEY_LENGTH, $info, $salt);
    }

    /**
     * Encrypt data using AES-256-GCM.
     *
     * @param string $plaintext Data to encrypt
     * @param string $key Encryption key (32 bytes)
     * @param string $additionaldata Optional additional authenticated data
     * @return string|false Base64-encoded encrypted data with IV and tag, or false on failure
     */
    public static function encrypt(string $plaintext, string $key, string $additionaldata = ''): string|false {
        if (strlen($key) !== self::KEY_LENGTH) {
            debugging('AI Awesome: Invalid key length for encryption', DEBUG_DEVELOPER);
            return false;
        }

        // Generate random IV.
        $iv = random_bytes(self::IV_LENGTH);
        
        // Encrypt the data.
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $additionaldata
        );

        if ($ciphertext === false) {
            debugging('AI Awesome: Encryption failed', DEBUG_DEVELOPER);
            return false;
        }

        // Combine IV + ciphertext + tag and encode.
        $encrypted = $iv . $ciphertext . $tag;
        return base64_encode($encrypted);
    }

    /**
     * Decrypt data using AES-256-GCM.
     *
     * @param string $encrypted Base64-encoded encrypted data
     * @param string $key Decryption key (32 bytes)
     * @param string $additionaldata Optional additional authenticated data
     * @return string|false Decrypted plaintext or false on failure
     */
    public static function decrypt(string $encrypted, string $key, string $additionaldata = ''): string|false {
        if (strlen($key) !== self::KEY_LENGTH) {
            debugging('AI Awesome: Invalid key length for decryption', DEBUG_DEVELOPER);
            return false;
        }

        $data = base64_decode($encrypted);
        if ($data === false || strlen($data) < (self::IV_LENGTH + self::TAG_LENGTH)) {
            debugging('AI Awesome: Invalid encrypted data format', DEBUG_DEVELOPER);
            return false;
        }

        // Extract IV, ciphertext, and tag.
        $iv = substr($data, 0, self::IV_LENGTH);
        $tag = substr($data, -self::TAG_LENGTH);
        $ciphertext = substr($data, self::IV_LENGTH, -self::TAG_LENGTH);

        // Decrypt the data.
        $plaintext = openssl_decrypt(
            $ciphertext,
            self::ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $additionaldata
        );

        if ($plaintext === false) {
            debugging('AI Awesome: Decryption failed - data may be corrupted or key invalid', DEBUG_DEVELOPER);
            return false;
        }

        return $plaintext;
    }

    /**
     * Create encrypted context for AI service.
     *
     * @param array $context Context data to encrypt
     * @param string $keymaterial Key material (e.g., JWT sub claim)
     * @return string|false Encrypted context or false on failure
     */
    public static function encrypt_context(array $context, string $keymaterial): string|false {
        $key = self::derive_key($keymaterial);
        $json = json_encode($context, JSON_UNESCAPED_UNICODE);
        
        if ($json === false) {
            debugging('AI Awesome: Failed to encode context as JSON', DEBUG_DEVELOPER);
            return false;
        }

        return self::encrypt($json, $key, 'context');
    }

    /**
     * Decrypt context from AI service response.
     *
     * @param string $encrypted Encrypted context
     * @param string $keymaterial Key material (e.g., JWT sub claim)
     * @return array|false Decrypted context array or false on failure
     */
    public static function decrypt_context(string $encrypted, string $keymaterial): array|false {
        $key = self::derive_key($keymaterial);
        $json = self::decrypt($encrypted, $key, 'context');
        
        if ($json === false) {
            return false;
        }

        $context = json_decode($json, true);
        if ($context === null) {
            debugging('AI Awesome: Failed to decode decrypted context JSON', DEBUG_DEVELOPER);
            return false;
        }

        return $context;
    }

    /**
     * Generate a secure random session ID.
     *
     * @return string Random session ID
     */
    public static function generate_session_id(): string {
        return bin2hex(random_bytes(16)); // 32 char hex string.
    }

    /**
     * Check if the crypto system is properly configured.
     *
     * @return bool True if crypto functions are available
     */
    public static function is_crypto_available(): bool {
        if (!function_exists('openssl_encrypt') || !function_exists('hash_hkdf')) {
            return false;
        }

        if (!in_array(self::ALGORITHM, openssl_get_cipher_methods(true))) {
            return false;
        }

        return true;
    }

    /**
     * Get system crypto information for debugging.
     *
     * @return array System crypto information
     */
    public static function get_crypto_info(): array {
        return [
            'openssl_available' => function_exists('openssl_encrypt'),
            'hkdf_available' => function_exists('hash_hkdf'),
            'algorithm_supported' => in_array(self::ALGORITHM, openssl_get_cipher_methods(true)),
            'algorithm' => self::ALGORITHM,
            'key_length' => self::KEY_LENGTH,
            'iv_length' => self::IV_LENGTH,
            'tag_length' => self::TAG_LENGTH,
        ];
    }
}