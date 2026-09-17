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
 * @package    local_pdfjs
 * @copyright  2026 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Andrew Hancox <andrewdchancox@googlemail.com>
 */

use local_pdfjs\local\lib;

/**
 * Serves files for pluginfile.php
 * @param $course
 * @param $cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @return bool
 */
function local_pdfjs_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload): bool {
    global $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_capability('local/pdfjs:viewannotations', $context);

    require_once($CFG->dirroot . '/lib/filelib.php');

    if ($filearea !== 'pdfannotations') {
        return false;
    }

    $itemid = (int)array_shift($args);
    $relativepath = implode('/', $args);

    $file = get_file_storage()->get_file($context->id, 'local_pdfjs', $filearea, $itemid, "/", $relativepath);
    if (!$file || $file->is_directory()) {
        return false;
    }

    if (
        !lib::file_registered_for_annotating($file->get_source())
        &&
        !lib::file_registered_for_viewing($file->get_id())
    ) {
        throw new Exception('file not registered for viewing');
    }

    send_stored_file($file, 0);
    return true;
}
