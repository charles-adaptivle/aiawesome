<?php
/**
 * CLI script to manually fetch and cache AI models.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Require the task class.
require_once(__DIR__ . '/../classes/task/fetch_models_task.php');

mtrace('');
mtrace('===========================================');
mtrace('AI Awesome: Manual Model Fetch');
mtrace('===========================================');
mtrace('');

// Create and execute the task.
$task = new \local_aiawesome\task\fetch_models_task();
$task->execute();

mtrace('');
mtrace('===========================================');
mtrace('Model fetch completed!');
mtrace('===========================================');
mtrace('');

// Show what was cached.
$openai_cache = get_config('local_aiawesome', 'cached_openai_models');
$do_cache = get_config('local_aiawesome', 'cached_digitalocean_models');

if ($openai_cache) {
    $models = json_decode($openai_cache, true);
    mtrace('OpenAI Models Cached: ' . count($models));
    mtrace('  Sample models: ' . implode(', ', array_slice(array_column($models, 'id'), 0, 5)));
}

if ($do_cache) {
    $models = json_decode($do_cache, true);
    mtrace('DigitalOcean Models Cached: ' . count($models));
    mtrace('  Sample models: ' . implode(', ', array_slice(array_column($models, 'id'), 0, 3)));
}

mtrace('');
mtrace('You can now visit the settings page to see the dropdown menus populated with models.');
mtrace('');
