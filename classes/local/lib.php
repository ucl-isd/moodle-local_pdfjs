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

namespace local_pdfjs\local;

use core\context;
use Exception;

class lib {
    public static function register_file_for_annotating(int $fileid): void {
        global $SESSION;

        if (!isset($SESSION)) {
            throw new Exception('session not set');
        }

        if (!isset($SESSION->local_pdfjs_annotatingfiles)) {
            $SESSION->local_pdfjs_annotatingfiles = [];
        }

        if (!in_array($fileid, $SESSION->local_pdfjs_annotatingfiles)) {
            $SESSION->local_pdfjs_annotatingfiles[] = $fileid;
        }
    }

    public static function require_file_registered_for_annotating(int $fileid): void {
        global $SESSION;

        if (!in_array($fileid, $SESSION->local_pdfjs_annotatingfiles)) {
            throw new Exception('file not registered for annotating');
        }
    }

    public static function remove_annotations(context $context, int $pdfitemid): void {
        $files = get_file_storage()->get_area_files(
            $context->id,
            'local_pdfjs',
            'pdfannotations',
            $pdfitemid
        );

        foreach ($files as $file) {
            $file->delete();
        }
    }

    // Where annotations were given a provisional itemid based on their original source file
    // reallocate them to the new item id.
    // Useful when the annotations may have been saved before the object they are directly associated with.
    public static function reallocate_annotations(context $context, int $sourceitemid, int $targetitemid, int $userid): void {
        $fs = get_file_storage();

        $files = $fs->get_area_files(
            $context->id,
            'local_pdfjs',
            'pdfannotations',
            $sourceitemid
        );

        foreach ($files as $filetomove) {
            $sourcefile = $fs->get_file_by_id($filetomove->get_source());

            if (
                !$sourcefile
                ||
                (int)$sourcefile->get_itemid() !== $sourceitemid
                ||
                (int)$sourcefile->get_userid() !== $userid
            ) {
                continue;
            }

            $fs->create_file_from_storedfile(['itemid' => $targetitemid], $filetomove);
            $filetomove->delete();
        }
    }
}
