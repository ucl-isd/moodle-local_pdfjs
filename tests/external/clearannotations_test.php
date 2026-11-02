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

use advanced_testcase;
use context_module;
use core_external\external_api;
use local_pdfjs\local\lib;
use required_capability_exception;
use stored_file;

/**
 * @group local_pdfjs
 * @covers \local_pdfjs\external\clearannotations
 */
final class clearannotations_test extends advanced_testcase {
    private \stdClass $course;
    private context_module $context;
    private \stdClass $student;
    private \stdClass $teacher;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $this->course->id]);
        $this->context = context_module::instance($assign->cmid);
        $this->student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
    }

    public function test_clear_removes_markers_annotations(): void {
        $submissionfile = $this->create_submission_file('submission.pdf');
        $this->setUser($this->teacher);
        lib::register_file_for_annotating($submissionfile->get_id());
        $annotationfile = $this->save_annotations($submissionfile, 10);

        $result = $this->clear(10, $submissionfile->get_id());

        $this->assertTrue($result['success']);
        $this->assertFalse(get_file_storage()->get_file_by_id($annotationfile->get_id()));
    }

    public function test_clear_removes_file_owners_annotations(): void {
        $submissionfile = $this->create_submission_file('submission.pdf');
        $this->setUser($this->student);
        lib::register_file_for_annotating($submissionfile->get_id());
        $annotationfile = $this->save_annotations($submissionfile, 10);

        $result = $this->clear(10, $submissionfile->get_id());

        $this->assertTrue($result['success']);
        $this->assertFalse(get_file_storage()->get_file_by_id($annotationfile->get_id()));
    }

    public function test_clear_keeps_annotations_for_other_files_and_items(): void {
        $submissionfile = $this->create_submission_file('submission.pdf');
        $otherfile = $this->create_submission_file('other.pdf');
        $this->setUser($this->student);
        lib::register_file_for_annotating($submissionfile->get_id());
        $otherfileannotation = $this->save_annotations($otherfile, 10);
        $otheritemannotation = $this->save_annotations($submissionfile, 11);
        $this->save_annotations($submissionfile, 10);

        $this->clear(10, $submissionfile->get_id());

        $fs = get_file_storage();
        $this->assertNotFalse($fs->get_file_by_id($otherfileannotation->get_id()));
        $this->assertNotFalse($fs->get_file_by_id($otheritemannotation->get_id()));
        $this->assertCount(1, $this->fetch_annotation_files(10));
    }

    public function test_clear_missing_file(): void {
        $this->setUser($this->teacher);

        $result = $this->clear(10, 999999);

        $this->assertFalse($result['success']);
        $this->assertEquals('file not found', $result['errorcode']);
        $this->assertEquals(get_string('refreshpageforchanges', 'local_pdfjs'), $result['message']);
    }

    public function test_clear_requires_capability(): void {
        $submissionfile = $this->create_submission_file('submission.pdf');
        $this->setUser($this->teacher);
        $annotationfile = $this->save_annotations($submissionfile, 10);

        $this->setUser($this->getDataGenerator()->create_user());
        lib::register_file_for_annotating($submissionfile->get_id());

        try {
            $this->clear(10, $submissionfile->get_id());
            $this->fail('Expected a required_capability_exception');
        } catch (required_capability_exception $e) {
            $this->assertNotFalse(get_file_storage()->get_file_by_id($annotationfile->get_id()));
        }
    }

    public function test_clear_requires_file_registered_for_annotating(): void {
        $submissionfile = $this->create_submission_file('submission.pdf');
        $this->setUser($this->student);
        $annotationfile = $this->save_annotations($submissionfile, 10);

        try {
            $this->clear(10, $submissionfile->get_id());
            $this->fail('Expected an exception for a file not registered for annotating');
        } catch (\moodle_exception $e) {
            $this->assertNotFalse(get_file_storage()->get_file_by_id($annotationfile->get_id()));
        }
    }

    private function clear(int $pdfitemid, int $fileid): array {
        return external_api::clean_returnvalue(
            clearannotations::execute_returns(),
            clearannotations::execute($pdfitemid, $fileid)
        );
    }

    private function create_submission_file(string $filename): stored_file {
        return get_file_storage()->create_file_from_string([
            'contextid' => $this->context->id,
            'component' => 'assignsubmission_file',
            'filearea' => 'submission_files',
            'itemid' => 1,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => $this->student->id,
        ], 'original');
    }

    private function save_annotations(stored_file $file, int $pdfitemid): stored_file {
        $pathname = make_request_directory() . '/annotated.pdf';
        file_put_contents($pathname, 'annotated');

        return lib::save_annotations($file, $pdfitemid, $pathname, 9);
    }

    private function fetch_annotation_files(int $pdfitemid): array {
        return array_filter(
            lib::fetch_annotations($this->context, $pdfitemid),
            fn(stored_file $file) => !$file->is_directory()
        );
    }
}
