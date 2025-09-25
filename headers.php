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
 * TODO describe file headers
 *
 * @package    local_aiawesome
 * @copyright  2025 2024 Charles Horton <charles@adaptivle.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


//require(__DIR__ . "/../../config.php");
// WITHOUT requiring config.php for now to avoid Moodle intercept
header("Content-Type: text/plain");
foreach ([
  "HTTP_HOST","HTTPS","SERVER_PORT","REQUEST_SCHEME",
  "HTTP_X_FORWARDED_HOST","HTTP_X_FORWARDED_PROTO",
  "HTTP_X_FORWARDED_SSL","HTTP_FRONT_END_HTTPS",
  "HTTP_X_PROBE","HTTP_X_FORWARDED_FOR"
] as $k) {
    echo $k . "=" . ($_SERVER[$k] ?? "—") . "\n";
}