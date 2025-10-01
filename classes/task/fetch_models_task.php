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
 * Scheduled task to fetch and cache AI model lists.
 *
 * @package    local_aiawesome
 * @copyright  2025 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiawesome\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Scheduled task to fetch available models from AI providers.
 */
class fetch_models_task extends \core\task\scheduled_task {

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_fetch_models', 'local_aiawesome');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        mtrace('AI Awesome: Starting model fetch task...');
        
        $fetched = 0;
        
        // Fetch OpenAI models if API key is configured.
        $openai_key = get_config('local_aiawesome', 'openai_api_key');
        if (!empty($openai_key)) {
            try {
                $models = $this->fetch_openai_models($openai_key);
                if ($models) {
                    set_config('cached_openai_models', json_encode($models), 'local_aiawesome');
                    set_config('cached_openai_models_time', time(), 'local_aiawesome');
                    mtrace('  → Fetched ' . count($models) . ' OpenAI models');
                    $fetched++;
                }
            } catch (\Exception $e) {
                mtrace('  → OpenAI fetch failed: ' . $e->getMessage());
            }
        }
        
        // Fetch DigitalOcean models if endpoint is configured.
        $do_endpoint = get_config('local_aiawesome', 'digitalocean_endpoint');
        $do_apikey = get_config('local_aiawesome', 'digitalocean_api_key');
        if (!empty($do_endpoint) && !empty($do_apikey)) {
            try {
                $models = $this->fetch_digitalocean_models($do_endpoint, $do_apikey);
                if ($models) {
                    set_config('cached_digitalocean_models', json_encode($models), 'local_aiawesome');
                    set_config('cached_digitalocean_models_time', time(), 'local_aiawesome');
                    mtrace('  → Fetched ' . count($models) . ' DigitalOcean models');
                    $fetched++;
                }
            } catch (\Exception $e) {
                mtrace('  → DigitalOcean fetch failed: ' . $e->getMessage());
            }
        }
        
        if ($fetched === 0) {
            mtrace('  → No providers configured, skipping model fetch');
        }
        
        mtrace('AI Awesome: Model fetch task completed');
    }

    /**
     * Fetch models from OpenAI API.
     *
     * @param string $api_key OpenAI API key
     * @return array Array of model objects
     * @throws \Exception If API call fails
     */
    private function fetch_openai_models(string $api_key): array {
        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $api_key);
        $curl->setHeader('Content-Type: application/json');
        
        $response = $curl->get('https://api.openai.com/v1/models');
        $httpcode = $curl->info['http_code'];
        
        if ($httpcode !== 200) {
            throw new \Exception('OpenAI API error (HTTP ' . $httpcode . ')');
        }
        
        $data = json_decode($response, true);
        if (!$data || !isset($data['data'])) {
            throw new \Exception('Invalid response from OpenAI API');
        }
        
        // Filter for chat models.
        $chat_models = array_filter($data['data'], function($model) {
            $id = $model['id'];
            return (strpos($id, 'gpt-') === 0 || 
                    strpos($id, 'o1-') === 0 ||
                    strpos($id, 'chatgpt-') === 0) &&
                   !strpos($id, 'instruct');
        });
        
        // Extract model IDs and sort.
        $models = array_map(function($model) {
            return [
                'id' => $model['id'],
                'name' => $model['id'],
                'created' => $model['created'] ?? null,
            ];
        }, $chat_models);
        
        usort($models, function($a, $b) {
            return strcmp($b['id'], $a['id']);
        });
        
        return $models;
    }

    /**
     * Fetch models from DigitalOcean API.
     *
     * @param string $endpoint DigitalOcean endpoint URL
     * @param string $api_key DigitalOcean API key
     * @return array Array of model objects
     */
    private function fetch_digitalocean_models(string $endpoint, string $api_key): array {
        $models_url = rtrim($endpoint, '/') . '/models';
        
        $curl = new \curl();
        $curl->setHeader('Content-Type: application/json');
        $curl->setHeader('Authorization: Bearer ' . $api_key);
        
        $response = $curl->get($models_url);
        $httpcode = $curl->info['http_code'];
        
        if ($httpcode !== 200) {
            // Return default models if endpoint doesn't support /models.
            return $this->get_default_digitalocean_models();
        }
        
        $data = json_decode($response, true);
        if (!$data || !isset($data['data'])) {
            return $this->get_default_digitalocean_models();
        }
        
        return array_map(function($model) {
            return [
                'id' => $model['id'],
                'name' => $model['id'],
                'created' => $model['created'] ?? null,
            ];
        }, $data['data']);
    }

    /**
     * Get default DigitalOcean models.
     *
     * @return array Default model list
     */
    private function get_default_digitalocean_models(): array {
        return [
            ['id' => 'openai-gpt-oss-120b', 'name' => 'OpenAI GPT OSS 120B'],
            ['id' => 'openai-gpt-oss-20b', 'name' => 'OpenAI GPT OSS 20B'],
            ['id' => 'llama3.3-70b-instruct', 'name' => 'Llama 3.3 70B Instruct'],
            ['id' => 'deepseek-r1-distill-llama-70b', 'name' => 'DeepSeek R1 Distill Llama 70B'],
            ['id' => 'llama3-8b-instruct', 'name' => 'Llama 3 8B Instruct'],
            ['id' => 'alibaba-qwen3-32b', 'name' => 'Alibaba Qwen3 32B'],
            ['id' => 'mistral-nemo-instruct-2407', 'name' => 'Mistral Nemo Instruct 2407'],
        ];
    }
}
