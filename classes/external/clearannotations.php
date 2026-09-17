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
namespace local_pdfjs\external;

use coding_exception;
use core\context;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_pdfjs\local\lib;

class clearannotations extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pdfitemid' => new external_value(PARAM_INT, 'The pdf item ID', VALUE_REQUIRED),
            'fileid' => new external_value(PARAM_INT, 'The annotated file ID', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $pdfitemid
     * @param int $fileid
     * @return array
     */
    public static function execute(int $pdfitemid, int $fileid): array {
        global $USER;
        self::validate_parameters(
            self::execute_parameters(),
            [
                'pdfitemid' => $pdfitemid,
                'fileid' => $fileid,
            ]
        );

        $file = get_file_storage()->get_file_by_id($fileid);

        if (!$file) {
            return [
                'success' => false,
                'errorcode' => 'file not found',
                'message' => get_string('refreshpageforchanges', 'local_pdfjs'),
            ];
        }

        require_capability('local/pdfjs:annotatepdf', context::instance_by_id($file->get_contextid()));
        lib::file_registered_for_annotating($file->get_source());
        if (
            $file->get_userid() != $USER->id
            || $file->get_itemid() != $pdfitemid
            || $file->get_filearea() != 'pdfannotations'
        ) {
            return [
                'success' => false,
                'errorcode' => 'invalid file',
                'message' => get_string('refreshpageforchanges', 'local_pdfjs'),
            ];
        }

        if (!$file->delete()) {
            return [
                'success' => false,
                'errorcode' => 'unable to delete file',
                'message' => get_string('refreshpageforchanges', 'local_pdfjs'),
            ];
        }
        return [
            'success' => true,
            'errorcode' => null,
            'message' => '',
        ];
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether successful'),
            'errorcode' => new external_value(PARAM_RAW, 'The error code if applicable'),
            'message' => new external_value(PARAM_RAW, 'The message to show user'),
        ]);
    }
}
