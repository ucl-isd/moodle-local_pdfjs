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

use core\antivirus\manager;
use local_pdfjs\local\lib;

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

// Authenticate the user.
$pdfitemid = required_param('pdfitemid', PARAM_INT);
$annotatedfileid = required_param('annotatedfileid', PARAM_INT);

lib::file_registered_for_annotating($annotatedfileid);
require_sesskey();

$fs = get_file_storage();
$annotatedfile = $fs->get_file_by_id($annotatedfileid);
$context = context::instance_by_id($annotatedfile->get_contextid());

require_login($context->get_course_context()->instanceid);
require_capability('local/pdfjs:annotatepdf', $context);

if (!is_array($_FILES) || count($_FILES) < 1) {
    throw new moodle_exception('nofile');
} else if (count($_FILES) > 1) {
    throw new Exception('too many files');
}

$uploadedfile = reset($_FILES);

// Check upload errors.
if (!empty($uploadedfile['error'])) {
    throw new moodle_exception('badfile: ' . $uploadedfile['error']);
}
if ($uploadedfile['type'] !== 'application/pdf') {
    throw new moodle_exception('badfile: ' . $uploadedfile['type']);
}
if (($uploadedfile['size'] > get_max_upload_file_size($CFG->maxbytes))) {
    throw new moodle_exception('maxbytes', 'error');
}

manager::scan_file($uploadedfile['tmp_name'], $uploadedfile['name'], true);

$fs = get_file_storage();
// Check if the user already has annotations stored against this file.
$existingfiles = $fs->get_area_files(
    $context->id,
    'local_pdfjs',
    'pdfannotations',
    $pdfitemid
);

foreach ($existingfiles as $existingfile) {
    if ($existingfile->get_source() == $annotatedfileid) {
        $existingfile->delete();
    }
}

$filerecord = new stdClass();
$filerecord->component = 'local_pdfjs';
$filerecord->contextid = $context->id;
$filerecord->userid = $USER->id;
$filerecord->filearea = 'pdfannotations';
$filerecord->filename = $annotatedfile->get_filename();
$filerecord->filepath = '/';
$filerecord->itemid = $pdfitemid;
$filerecord->license = $CFG->sitedefaultlicense;
$filerecord->author = fullname($USER);
$filerecord->source = $annotatedfile->get_id();
$filerecord->filesize = $uploadedfile['size'];

$annotationfile = $fs->create_file_from_pathname($filerecord, $uploadedfile['tmp_name']);

echo json_encode((object)[
    'fileid' => $annotationfile->get_id(),
]);
