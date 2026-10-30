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

use advanced_testcase;
use context_module;
use stored_file;

/**
 * @group local_pdfjs
 * @covers \local_pdfjs\local\lib::save_annotations
 */
final class lib_test extends advanced_testcase {
    private context_module $context;
    private \stdClass $student;
    private \stdClass $teacher;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $this->context = context_module::instance($assign->cmid);
        $this->student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
    }

    public function test_save_annotations_creates_annotation_file(): void {
        $submissionfile = $this->create_submission_file('submission.pdf');
        $this->setUser($this->teacher);

        $annotationfile = lib::save_annotations($submissionfile, 10, $this->create_upload('annotated'), 9);

        $this->assertEquals($this->context->id, $annotationfile->get_contextid());
        $this->assertEquals('local_pdfjs', $annotationfile->get_component());
        $this->assertEquals('pdfannotations', $annotationfile->get_filearea());
        $this->assertEquals(10, $annotationfile->get_itemid());
        $this->assertEquals('/', $annotationfile->get_filepath());
        $this->assertEquals('submission.pdf', $annotationfile->get_filename());
        $this->assertEquals($this->teacher->id, $annotationfile->get_userid());
        $this->assertEquals(fullname($this->teacher), $annotationfile->get_author());
        $this->assertEquals($submissionfile->get_id(), $annotationfile->get_source());
        $this->assertEquals('annotated', $annotationfile->get_content());
    }

    public function test_save_annotations_replaces_existing_annotations(): void {
        $submissionfile = $this->create_submission_file('submission.pdf');
        $this->setUser($this->teacher);

        lib::save_annotations($submissionfile, 10, $this->create_upload('first'), 5);
        $annotationfile = lib::save_annotations($submissionfile, 10, $this->create_upload('second'), 6);

        $annotations = $this->fetch_annotation_files(10);
        $this->assertCount(1, $annotations);
        $this->assertEquals($annotationfile->get_id(), reset($annotations)->get_id());
        $this->assertEquals('second', $annotationfile->get_content());
    }

    public function test_save_annotations_keeps_annotations_for_other_files_and_items(): void {
        $submissionfile = $this->create_submission_file('submission.pdf');
        $otherfile = $this->create_submission_file('other.pdf');
        $this->setUser($this->teacher);

        $otherfileannotation = lib::save_annotations($otherfile, 10, $this->create_upload('other file'), 10);
        $otheritemannotation = lib::save_annotations($submissionfile, 11, $this->create_upload('other item'), 10);

        lib::save_annotations($submissionfile, 10, $this->create_upload('annotated'), 9);
        lib::save_annotations($submissionfile, 10, $this->create_upload('annotated again'), 15);

        $fs = get_file_storage();
        $this->assertNotFalse($fs->get_file_by_id($otherfileannotation->get_id()));
        $this->assertNotFalse($fs->get_file_by_id($otheritemannotation->get_id()));
        $this->assertCount(2, $this->fetch_annotation_files(10));
        $this->assertCount(1, $this->fetch_annotation_files(11));
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

    private function create_upload(string $content): string {
        $pathname = make_request_directory() . '/annotated.pdf';
        file_put_contents($pathname, $content);

        return $pathname;
    }

    private function fetch_annotation_files(int $pdfitemid): array {
        return array_filter(
            lib::fetch_annotations($this->context, $pdfitemid),
            fn(stored_file $file) => !$file->is_directory()
        );
    }
}
