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
 * @subpackage html
 * @copyright  &copy; 2012 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @author     Based on code originally written by Paul Krix and Julian Ridden.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/renderer.php');
require_once($CFG->dirroot . '/course/format/html/lib.php');

class format_html_renderer extends format_section_renderer_base {

    private $topic0_at_top; // Boolean to state if section zero is at the top (true) or in the grid (false).
    private $courseformat; // Our course format object as defined in lib.php.
    private $settings; // Settings array.
    private $shadeboxshownarray = array(); // Value of 1 = not shown, value of 2 = shown - to reduce ambiguity in JS.

    /**
     * Constructor method, calls the parent constructor - MDL-21097
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $this->courseformat = course_get_format($page->course);
        $this->settings = $this->courseformat->get_settings();

        /* Since format_html_renderer::section_edit_controls() only displays the 'Set current section' control when editing
           mode is on we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any
           other managing capability. */
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', array('class' => 'gtopics', 'id' => 'gtopics'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('sectionname', 'format_html');
    }

     /**
     * Generate the html for the 'Jump to' menu on a single section page.
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param $displaysection the current displayed section number.
     * @author Rafael Milani Barbosa [rafael.barbosa@across.com.br]
     * @return string HTML to output.
     */
    protected function section_nav_selection($course, $sections, $displaysection) {
        global $CFG;
        return false;
    }

    /**
     * Output the html for a single section page .
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     * @param int $displaysection The section number in the course which is being displayed
     * @author Rafael Milani Barbosa [rafael.barbosa@across.com.br]
     */
    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
        global $PAGE, $DB, $CFG;

        echo 'SILGLE SECTION';
        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();
        $context = context_course::instance($course->id);
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param array $mods
     * @param array $modnames
     * @param array $modnamesused
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE, $CFG, $DB;

        echo 'MULTIPLE SECTIONS';
        $summarystatus = $this->courseformat->get_summary_visibility($course->id);
        $context = context_course::instance($course->id);
        $editing = $PAGE->user_is_editing();
        $hascapvishidsect = has_capability('moodle/course:viewhiddensections', $context);
    }

    /**
     * Generate the edit controls of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of links with edit controls
     */
    protected function section_edit_controls($course, $section, $onsectionpage = false) {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $controls = array();
        if (has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $strmarkedthissection = get_string('markedthissection', 'format_html');
                $controls[] = html_writer::link($url,
                                    html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marked'),
                                        'class' => 'icon ', 'alt' => $strmarkedthissection)),
                                    array('title' => $strmarkedthissection, 'class' => 'editing_highlight'));
            } else {
                $strmarkthissection = get_string('markthissection', 'format_html');
                $url->param('marker', $section->section);
                $controls[] = html_writer::link($url,
                                html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marker'),
                                    'class' => 'icon', 'alt' => $strmarkthissection)),
                                array('title' => $strmarkthissection, 'class' => 'editing_highlight'));
            }
        }

        return array_merge($controls, parent::section_edit_controls($course, $section, $onsectionpage));
    }

    // Grid format specific code.
    /**
     * Makes section zero.
     */
    private function make_block_topic0($course, $sections, $modinfo, $editing, $urlpicedit, $streditsummary,
            $onsectionpage) {
        $section = 0;
        if (!array_key_exists($section, $sections)) {
            return false;
        }

        $thissection = $modinfo->get_section_info($section);
        if (!is_object($thissection)) {
            return false;
        }

        if ($this->topic0_at_top) {
            echo html_writer::start_tag('ul', array('class' => 'gtopics-0'));
        }

        $sectionname = get_section_name($course, $thissection);
        echo html_writer::start_tag('li', array(
            'id' => 'section-0',
            'class' => 'section main' . ($this->topic0_at_top ? '' : ' grid_section hide_section'),
            'role' => 'region',
            'aria-label' => $sectionname)
        );

        echo html_writer::tag('div', '&nbsp;', array('class' => 'right side'));

        echo html_writer::start_tag('div', array('class' => 'content'));

        if (!$onsectionpage) {
            echo $this->output->heading($sectionname, 3, 'sectionname');
        }

        echo html_writer::start_tag('div', array('class' => 'summary'));

        echo $this->format_summary_text($thissection);

        if ($editing) {
            $link = html_writer::link(
                            new moodle_url('editsection.php', array('id' => $thissection->id)),
                                html_writer::empty_tag('img', array('src' => $urlpicedit,
                                                                     'alt' => $streditsummary,
                                                                     'class' => 'iconsmall edit')),
                                                        array('title' => $streditsummary));
            echo $this->topic0_at_top ? html_writer::tag('p', $link) : $link;
        }
        echo html_writer::end_tag('div');

        echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);

        if ($editing) {
            echo $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0, 0);

            if ($this->topic0_at_top) {
                $strhidesummary = get_string('hide_summary', 'format_html');
                $strhidesummaryalt = get_string('hide_summary_alt', 'format_html');

                echo html_writer::link(
                        $this->courseformat->grid_moodle_url('mod_summary.php', array(
                            'sesskey' => sesskey(),
                            'course' => $course->id,
                            'showsummary' => 0)), html_writer::empty_tag('img', array(
                            'src' => $this->output->pix_url('into_grid', 'format_html'),
                            'alt' => $strhidesummaryalt)) . '&nbsp;' . $strhidesummary, array('title' => $strhidesummaryalt));
            }
        }
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('li');

        if ($this->topic0_at_top) {
            echo html_writer::end_tag('ul');
        }
        return true;
    }

    /**
     * Makes the grid image containers.
     */
    private function make_block_icon_topics($contextid, $modinfo, $course, $editing, $hascapvishidsect,
            $urlpicedit) {
        global $USER, $CFG;

        if ($this->settings['newactivity'] == 2) {
            $currentlanguage = current_language();
            if (!file_exists("$CFG->dirroot/course/format/grid/pix/new_activity_" . $currentlanguage . ".png")) {
                $currentlanguage = 'en';
            }
            $url_pic_new_activity = $this->output->pix_url('new_activity_' . $currentlanguage, 'format_html');

            // Get all the section information about which items should be marked with the NEW picture.
            $sectionupdated = $this->new_activity($course);
        }

        if ($editing) {
            $streditimage = '';
            $streditimagealt = get_string('editimage_alt', 'format_html');
        }
        
        //get active module into min and max dates
        $gTime = time(); 
        $sactv = '';
        for ($section = $this->topic0_at_top ? 1 : 0; $section <= $course->numsections; $section++) {           
            //get this section into loop
            $thissection = $modinfo->get_section_info($section);
            //get availability
            $ts = $thissection->availability;
            //convert string to json object in php
            $array_ts = json_decode($ts); 
            //get min date
            $minDateWeek = $array_ts->c[0]->t;
            //get max date
            $maxDateWeek = $array_ts->c[1]->t;
            //set active module infos if timestamp between periods of date
            if($minDateWeek && $maxDateWeek) {
                //check timestamp between dates
                if($gTime >= $minDateWeek && $gTime <= $maxDateWeek) {
                    $sactv = $section;
                }
            } 
        }
        
        // Get the section images for the course.
        $sectionimages = $this->courseformat->get_images($course->id);

        // CONTRIB-4099:...
        $gridimagepath = $this->courseformat->get_image_path();

        // Start at 1 to skip the summary block or include the summary block if it's in the grid display.
        for ($section = $this->topic0_at_top ? 1 : 0; $section <= $course->numsections; $section++) {
            $gShow = 0;
            $thissection = $modinfo->get_section_info($section);
            //get availability
            $ts = $thissection->availability;
            //convert string to json object in php
            $array_ts = json_decode($ts); 
            //get min date
            $minDateWeek = $array_ts->c[0]->t;
            //check visibility module between date (NOW()) - Rafael Milani Barbosa
            if($minDateWeek < time()) {
                $gShow = 0;
            }
            else {
                $gShow = 1;
            }
            // Check if section is visible to user.
            $showsection = $hascapvishidsect || ($thissection->visible && ($thissection->available ||
                    $thissection->showavailability || !$course->hiddensections));

            if ($showsection) {
                // We now know the value for the grid shade box shown array.
                $this->shadeboxshownarray[$section] = 2;

                $sectionname = $this->courseformat->get_section_name($thissection);

                /* Roles info on based on: http://www.w3.org/TR/wai-aria/roles.
                   Looked into the 'grid' role but that requires 'row' before 'gridcell' and there are none as the grid
                   is responsive, so as the container is a 'navigation' then need to look into converting the containing
                   'div' to a 'nav' tag (www.w3.org/TR/2010/WD-html5-20100624/sections.html#the-nav-element) when I'm
                   that all browsers support it against the browser requirements of Moodle. */
                $liattributes = array(
                    'role' => 'region',
                    'class' => 'col-lg-3',
                    'aria-label' => $sectionname
                );
                if ($this->courseformat->is_section_current($section)) {
                    $liattributes['class'] = 'currenticon';
                }
           
                if($section != $sactv) {    
                    if($gShow == 1) {
                        echo html_writer::start_tag('li', $liattributes);

                        // Ensure the record exists.
                        if  (($sectionimages === false) || (!array_key_exists($thissection->id, $sectionimages))) {
                            // get_image has 'repair' functionality for when there are issues with the data.
                            $sectionimage = $this->courseformat->get_image($course->id, $thissection->id);
                        } else {
                            $sectionimage = $sectionimages[$thissection->id];
                        }

                        // If the image is set then check that displayedimageindex is greater than 0 otherwise create the displayed image.
                        // This is a catch-all for existing courses.
                        if (isset($sectionimage->image) && ($sectionimage->displayedimageindex < 1)) {
                            // Set up the displayed image:...
                            $sectionimage->newimage = $sectionimage->image;
                            $sectionimage = $this->courseformat->setup_displayed_image($sectionimage, $contextid,
                                $this->settings);
                            if (format_html::is_developer_debug()) {
                                error_log('make_block_icon_topics: Updated displayed image for section ' . $thissection->id . ' to ' .
                                        $sectionimage->newimage . ' and index ' . $sectionimage->displayedimageindex);
                            }
                        }

                        if ($course->coursedisplay != COURSE_DISPLAY_MULTIPAGE) {
                            echo html_writer::start_tag('a', array(
                                'href' => '#',
                                'id' => 'gridsection-' . $thissection->section,
                                'class' => 'gridicon_link rlink',
                                'role' => 'link',
                                'msg_alert' => get_string('rlink_close', 'format_html'),
                                'aria-label' => $sectionname));

                            echo html_writer::start_tag('div', array('class' => 'col-lg-1'));
                            echo html_writer::tag('h1', $section);
                            echo html_writer::end_tag('div');
                            echo html_writer::start_tag('div', array('class' => 'col-lg-9'));
                            echo html_writer::tag('h4', $sectionname, array('class' => 'icon_content'));
                            //add count number of videos and activities
                            echo html_writer::start_tag('p');
                            echo html_writer::tag('span', '', array('class' => 'glyphicon glyphicon-facetime-video nactvs1'));
                            echo html_writer::tag('span', '', array('class' => 'glyphicon glyphicon-pencil nactvs2'));
            
                            echo html_writer::end_tag('p');
                            echo html_writer::end_tag('div');
                            echo html_writer::start_tag('div', array('class' => 'col-lg-1'));
                            echo html_writer::tag('span', '', array('class' => 'glyphicon glyphicon-lock', 'id' => 'locked'));
                            echo html_writer::end_tag('div');
                            
                            echo html_writer::end_tag('a');

                            if ($editing) {
                                echo html_writer::link(
                                        $this->courseformat->grid_moodle_url('editimage.php', array(
                                            'sectionid' => $thissection->id,
                                            'class' => 'change_image_cfg',
                                            'contextid' => $contextid,
                                            'userid' => $USER->id,
                                            'role' => 'link',
                                            'aria-label' => $streditimagealt)), html_writer::empty_tag('img', array(
                                            'src' => $urlpicedit,
                                            'alt' => $streditimagealt,
                                            'role' => 'img',
                                            'aria-label' => $streditimagealt)),
                                        array('title' => $streditimagealt));

                                if ($section == 0) {
                                    $strdisplaysummary = get_string('display_summary', 'format_html');
                                    $strdisplaysummaryalt = get_string('display_summary_alt', 'format_html');

                                    echo html_writer::empty_tag('br') . html_writer::link(
                                            $this->courseformat->grid_moodle_url('mod_summary.php', array(
                                                'sesskey' => sesskey(),
                                                'course' => $course->id,
                                                'showsummary' => 1,
                                                'role' => 'link',
                                                'aria-label' => $strdisplaysummaryalt)), html_writer::empty_tag('img', array(
                                                'src' => $this->output->pix_url('out_of_grid', 'format_html'),
                                                'alt' => $strdisplaysummaryalt,
                                                'role' => 'img',
                                                'aria-label' => $strdisplaysummaryalt)) . '&nbsp;' . $strdisplaysummary, array('title' => $strdisplaysummaryalt));
                                }
                            }
                            echo html_writer::end_tag('li');
                        } else {
                            $title = html_writer::start_tag('div', array('class' => 'col-lg-1 topic-column1'));
                            $title .= html_writer::tag('h1', $section);
                            $title .= html_writer::end_tag('div');
                            $title .= html_writer::start_tag('div', array('class' => 'col-lg-9 topic-column2'));
                            $title .= html_writer::tag('h4', $sectionname, array('class' => 'icon_content'));
                            
                            //add count number of videos and activities
                            $title .= html_writer::start_tag('p');
                            
                            //number of activities Rafael milani barbosa
                            // Generate array with count of activities in this section:
                            global $DB;
                            $qt = $DB->count_records('course_modules', array('course' => $course->id, 'module' => '15', 'section' => $thissection->id));
                            $qt2 = $DB->get_record_sql('SELECT count(id) AS nt FROM mdl_course_modules WHERE course = ? AND module <> ? AND section = ?', array($course->id, '15', $thissection->id));
       
                            $title .= html_writer::tag('span', $qt, array('class' => 'glyphicon glyphicon-facetime-video nactvs1'));
                            $title .= html_writer::tag('span', $qt2->nt, array('class' => 'glyphicon glyphicon-pencil nactvs2'));
            
                            $title .= html_writer::end_tag('p');
                            $title .= html_writer::end_tag('div');
                            $title .= html_writer::start_tag('div', array('class' => 'col-lg-1 topi'));
                            $title .= html_writer::tag('span', '', array('class' => 'glyphicon glyphicon-lock', 'id' => 'locked'));
                            $title .= html_writer::end_tag('div');

                         

                            $url = course_get_url($course, $thissection->section);
                            if ($url) {
                                $title = html_writer::link('#', $title, array(
                                    'id' => 'gridsection-' . $thissection->section,
                                    'role' => 'link',
                                    'class' => 'rlink',
                                    'msg_alert' => get_string('rlink_close', 'format_html'),
                                    'aria-label' => $sectionname));
                            }
                            echo $title;

                            if ($editing) {
                                echo html_writer::link(
                                        $this->courseformat->grid_moodle_url('editimage.php', array(
                                            'sectionid' => $thissection->id,
                                            'contextid' => $contextid,
                                            'class' => 'change_image_cfg',
                                            'userid' => $USER->id,
                                            'role' => 'link',
                                            'aria-label' => $streditimagealt)), html_writer::empty_tag('img', array(
                                            'src' => $urlpicedit,
                                            'alt' => $streditimagealt,
                                            'role' => 'img',
                                            'aria-label' => $streditimagealt)),
                                        array('title' => $streditimagealt));

                                if ($section == 0) {
                                    $strdisplaysummary = get_string('display_summary', 'format_html');
                                    $strdisplaysummaryalt = get_string('display_summary_alt', 'format_html');

                                    echo html_writer::empty_tag('br') . html_writer::link(
                                            $this->courseformat->grid_moodle_url('mod_summary.php', array(
                                                'sesskey' => sesskey(),
                                                'course' => $course->id,
                                                'showsummary' => 1,
                                                'role' => 'link',
                                                'aria-label' => $strdisplaysummaryalt)), html_writer::empty_tag('img', array(
                                                'src' => $this->output->pix_url('out_of_grid', 'format_html'),
                                                'alt' => $strdisplaysummaryalt,
                                                'role' => 'img',
                                                'aria-label' => $strdisplaysummaryalt)) . '&nbsp;' . $strdisplaysummary,
                                            array('title' => $strdisplaysummaryalt));
                                }
                            }
                            echo html_writer::end_tag('li');
                        }
                    }
                } else {
                    // We now know the value for the grid shade box shown array.
                    $this->shadeboxshownarray[$section] = 1;
                }
            }
        }
    }

    /**
     * If currently moving a file then show the current clipboard.
     */
    private function make_block_show_clipboard_if_file_moving($course) {
        global $USER;

        if (is_object($course) && ismoving($course->id)) {
            $strcancel = get_string('cancel');

            $stractivityclipboard = clean_param(format_string(
                            get_string('activityclipboard', '', $USER->activitycopyname)), PARAM_NOTAGS);
            $stractivityclipboard .= '&nbsp;&nbsp;('
                    . html_writer::link(new moodle_url('/mod.php', array(
                        'cancelcopy' => 'true',
                        'sesskey' => sesskey())), $strcancel);

            echo html_writer::tag('li', $stractivityclipboard, array('class' => 'clipboard'));
        }
    }

    /**
     * Makes the list of sections to show.
     */
    private function make_block_topics($course, $sections, $modinfo, $editing, $hascapvishidsect, $streditsummary,
            $urlpicedit, $onsectionpage) {
        $context = context_course::instance($course->id);
        unset($sections[0]);
        for ($section = 1; $section <= $course->numsections; $section++) {
            $thissection = $modinfo->get_section_info($section);

            if (!$hascapvishidsect && !$thissection->visible && $course->hiddensections) {
                unset($sections[$section]);
                continue;
            }

            $sectionstyle = 'section main';
            if (!$thissection->visible) {
                $sectionstyle .= ' hidden';
            }
            if ($this->courseformat->is_section_current($section)) {
                $sectionstyle .= ' current';
            }
            $sectionstyle .= ' grid_section hide_section';

            $sectionname = get_section_name($course, $thissection);
            echo html_writer::start_tag('li', array(
                'id' => 'section-' . $section,
                'class' => $sectionstyle,
                'role' => 'region',
                'aria-label' => $sectionname)
            );

            if ($editing) {
                // Note, 'left side' is BEFORE content.
                $leftcontent = $this->section_left_content($thissection, $course, $onsectionpage);
                echo html_writer::tag('div', $leftcontent, array('class' => 'left side'));
                // Note, 'right side' is BEFORE content.
                $rightcontent = $this->section_right_content($thissection, $course, $onsectionpage);
                echo html_writer::tag('div', $rightcontent, array('class' => 'right side'));
            }

            echo html_writer::start_tag('div', array('class' => 'content'));
            if ($hascapvishidsect || ($thissection->visible && $thissection->available)) {
                // If visible.
                echo $this->output->heading($sectionname, 3, 'sectionname');

                echo html_writer::start_tag('div', array('class' => 'summary'));

                echo $this->format_summary_text($thissection);

                if ($editing) {
                    echo html_writer::link(
                            new moodle_url('editsection.php', array('id' => $thissection->id)),
                            html_writer::empty_tag('img', array('src' => $urlpicedit, 'alt' => $streditsummary,
                                'class' => 'iconsmall edit')), array('title' => $streditsummary));
                }
                echo html_writer::end_tag('div');

                echo $this->section_availability_message($thissection,has_capability('moodle/course:viewhiddensections',
                        $context));

                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
            } else {
                echo html_writer::tag('h2', $this->get_title($thissection));
                echo html_writer::tag('p', get_string('hidden_topic', 'format_html'));

                echo $this->section_availability_message($thissection, has_capability('moodle/course:viewhiddensections',
                        $context));
            }

            echo html_writer::end_tag('div');
            echo html_writer::end_tag('li');

            unset($sections[$section]);
        }

        if ($editing) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $course->numsections or empty($modinfo->sections[$section])) {
                    // This is not stealth section or it is empty.
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            echo html_writer::start_tag('div', array('id' => 'changenumsections', 'class' => 'mdl-right'));

            // Increase number of sections.
            $straddsection = get_string('increasesections', 'moodle');
            $url = new moodle_url('/course/changenumsections.php', array('courseid' => $course->id,
                'increase' => true,
                'sesskey' => sesskey()));
            $icon = $this->output->pix_icon('t/switch_plus', $straddsection);
            echo html_writer::link($url, $icon . get_accesshide($straddsection), array('class' => 'increase-sections'));

            if ($course->numsections > 0) {
                // Reduce number of sections sections.
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php', array('courseid' => $course->id,
                    'increase' => false,
                    'sesskey' => sesskey()));
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                echo html_writer::link($url, $icon . get_accesshide($strremovesection), array('class' => 'reduce-sections'));
            }

            echo html_writer::end_tag('div');
        } else {
            echo $this->end_section_list();
        }
    }

    /**
     * Attempts to return a 40 character title for the section image container.
     * If section names are set, they are used. Otherwise it scans 
     * the summary for what looks like the first line.
     */
    private function get_title($section) {
        $title = is_object($section) && isset($section->name) &&
                is_string($section->name) ? trim($section->name) : '';

        if (!empty($title)) {
            // Apply filters and clean tags.
            $title = trim(format_string($section->name, true));
        }

        if (empty($title)) {
            $title = trim(format_text($section->summary));

            // Finds first header content. If it is not found, then try to find the first paragraph.
            foreach (array('h[1-6]', 'p') as $tag) {
                if (preg_match('#<(' . $tag . ')\b[^>]*>(?P<text>.*?)</\1>#si', $title, $m)) {
                    if (!$this->is_empty_text($m['text'])) {
                        $title = $m['text'];
                        break;
                    }
                }
            }
            $title = trim(clean_param($title, PARAM_NOTAGS));
        }

        if (strlen($title) > 40) {
            $title = $this->text_limit($title, 40);
        }

        return $title;
    }

    /**
     * States if the text is empty.
     * @param type $text The text to test.
     * @return boolean Yes(true) or No(false).
     */
    public function is_empty_text($text) {
        return empty($text) ||
                preg_match('/^(?:\s|&nbsp;)*$/si', htmlentities($text, 0 /* ENT_HTML401 */, 'UTF-8', true));
    }

    /**
     * Cuts long texts up to certain length without breaking words.
     */
    private function text_limit($text, $length, $replacer = '...') {
        if (strlen($text) > $length) {
            $text = wordwrap($text, $length, "\n", true);
            $pos = strpos($text, "\n");
            if ($pos === false) {
                $pos = $length;
            }
            $text = trim(substr($text, 0, $pos)) . $replacer;
        }
        return $text;
    }

    /**
     * Checks whether there has been new activity.
     */
    private function new_activity($course) {
        global $CFG, $USER, $DB;

        $sectionsedited = array();
        if (isset($USER->lastcourseaccess[$course->id])) {
            $course->lastaccess = $USER->lastcourseaccess[$course->id];
        } else {
            $course->lastaccess = 0;
        }

        $sql = "SELECT id, section FROM {$CFG->prefix}course_modules " .
                "WHERE course = :courseid AND added > :lastaccess";

        $params = array(
            'courseid' => $course->id,
            'lastaccess' => $course->lastaccess);

        $activity = $DB->get_records_sql($sql, $params);
        foreach ($activity as $record) {
            $sectionsedited[$record->section] = true;
        }

        return $sectionsedited;
    }
}
