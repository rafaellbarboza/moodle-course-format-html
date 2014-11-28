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
 * Grid Format - A topics based format that uses a grid of user selectable images to popup a light box of the section.
 *
 * @package    course/format
 * @subpackage grid
 * @copyright  &copy; 2012 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @author     Based on code originally written by Paul Krix and Julian Ridden.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* Imports */
require_once('../../../config.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/course/format/html/editimage_form.php');
require_once($CFG->dirroot . '/course/format/html/lib.php');

global $DB;

/* Page parameters */
$contextid = required_param('contextid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);
$id = optional_param('id', null, PARAM_INT);

/* No idea, copied this from an example. Sets form data options but I don't know what they all do exactly */
$formdata = new stdClass();
$formdata->userid = required_param('userid', PARAM_INT);
$formdata->offset = optional_param('offset', null, PARAM_INT);
$formdata->forcerefresh = optional_param('forcerefresh', null, PARAM_INT);
$formdata->mode = optional_param('mode', null, PARAM_ALPHA);

$url = new moodle_url('/course/format/html/editimage.php', array(
    'contextid' => $contextid,
    'id' => $id,
    'offset' => $formdata->offset,
    'forcerefresh' => $formdata->forcerefresh,
    'userid' => $formdata->userid,
    'mode' => $formdata->mode));

/* Not exactly sure what this stuff does, but it seems fairly straightforward */
list($context, $course, $cm) = get_context_info_array($contextid);

require_login($course, true, $cm);
if (isguestuser()) {
    die();
}

$PAGE->set_url($url);
$PAGE->set_context($context);

/* Functional part. Create the form and display it, handle results, etc */
$options = array(
    'subdirs' => 0,
    'maxfiles' => 1,
    'accepted_types' => array('gif', 'jpe', 'jpeg', 'jpg', 'png'),
    'return_types' => FILE_INTERNAL);

$mform = new grid_image_form(null, array(
    'contextid' => $contextid,
    'userid' => $formdata->userid,
    'sectionid' => $sectionid,
    'options' => $options));

if ($mform->is_cancelled()) {
    // Someone has hit the 'cancel' button.
    redirect(new moodle_url($CFG->wwwroot . '/course/view.php?id=' . $course->id));
} else if ($formdata = $mform->get_data()) { // Form has been submitted.
    //Save Custom data - Rafael Milani Barbosa
    $headerCustom = $formdata->header_title;
    $labelCustom = $formdata->label_topic_description['text'];

    //get record
    $tcustom = $DB->count_records('course_grid_topic_desc', array('sectionid' => $formdata->sectionid, 'courseid' => $course->id));

    if($tcustom > 0) {
        $rcustom = $DB->get_record('course_grid_topic_desc', array('sectionid' => $formdata->sectionid, 'courseid' => $course->id));
        $recordupdt = new stdClass();
        $recordupdt->id = $rcustom->id;
        $recordupdt->courseid = $rcustom->courseid;    
        $recordupdt->sectionid = $rcustom->sectionid;    
        $recordupdt->cmid = $rcustom->cmid;    
        $recordupdt->header_title = $headerCustom;  
        $recordupdt->label_topic_description = $labelCustom; 
        $DB->update_record('course_grid_topic_desc', $recordupdt);
    }
    else {
        $record = new stdClass();
        $record->courseid         = $course->id;
        $record->sectionid = $formdata->sectionid;
        $record->cmid = 0;
        $record->header_title = $headerCustom;
        $record->label_topic_description = $labelCustom;
        $lastinsertid = $DB->insert_record('course_grid_topic_desc', $record);
    }
    if ($formdata->deleteimage == 1) {
        // Delete the old images....
        $courseformat = course_get_format($course);
        $courseformat->delete_image($sectionid, $context->id);
    } else if ($newfilename = $mform->get_new_filename('imagefile')) {
        $fs = get_file_storage();

        // We have a new file so can delete the old....
        $courseformat = course_get_format($course);
        $sectionimage = $courseformat->get_image($course->id, $sectionid);
        if (isset($sectionimage->image)) {
            if ($file = $fs->get_file($context->id, 'course', 'section', $sectionid, '/', $sectionimage->image)) {
                $file->delete();
            }
        }

        // Resize the new image and save it...
        $storedfilerecord = $courseformat->create_original_image_record($contextid, $sectionid, $newfilename);
        $tempfile = $mform->save_stored_file(
                'imagefile',
                $storedfilerecord['contextid'],
                $storedfilerecord['component'],
                $storedfilerecord['filearea'],
                $storedfilerecord['itemid'],
                $storedfilerecord['filepath'],
                'temp.' . $storedfilerecord['filename'],
                true);

        $courseformat->create_section_image($tempfile, $storedfilerecord, $sectionimage);
    }
    redirect($CFG->wwwroot . "/course/view.php?id=" . $course->id);
}

/* Draw the form */
echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
