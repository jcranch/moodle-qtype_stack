<?php
// This file is part of Stack - http://stack.maths.ed.ac.uk/
//
// Stack is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stack is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stack.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

// Input that allows the student to build a section of text by
// selecting phrases in order from a given pool.
//
// @copyright  2020 University of Sheffield.
// @author     Neil Strickland.
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.

class stack_builder_input extends stack_input {

    /*
     * phrases will be a list of lists of two strings.
     * The first string is a phrase that the student can use
     * in their answer, and the second string is a key 
     * used to identify the phrase.  The internal Maxima
     * representation of the student's answer will be a list
     * of these keys.  The keys should consist of characters
     * that do not need to be escaped.
     */
    protected $phrases          = array();
    protected $phrases_by_key   = array();
    protected $phrases_by_index = array();
    protected $correct_phrases  = array();
    protected $correct_keys     = array();
    protected $correct_indices  = array();
    protected $correct_text     = array();

    public $teacheranswerraw = '';
    public $teacheranswervalue = '';
    public $teacheranswerdisplay = '';
 
    /* As with multiple response questions etc, the teacher 
     * answer field is not really the teacher answer but 
     * instead is a complete specification of the question.
     * It should consist of a list of triples
     * [key,text,pos] where phrase and key are strings
     * and pos is a natural number.  The phrases are 
     * given in the order in which they should be shown to
     * the student.  The teacher's answer consists of the 
     * phrases where pos > 0, in the order specified by pos.
     */
    public function adapt_to_model_answer($teacheranswer) {
        $values = json_decode($teacheranswer);
        if (empty($values)) {
            $this->errors[] = stack_string('builder_badanswer', $teacheranswer);
            $this->teacheranswerraw = '[ERR]';
            $this->teacheranswervalue = '[ERR]';
            $this->teacheranswerdisplay = '<code>'.'[ERR]'.'</code>';
            $this->phrases = array();
            return false;
        }

        $this->phrases          = array();
        $this->phrases_by_key   = array();
        $this->phrases_by_index = array();
        $this->correct_phrases  = array();
        $this->correct_keys     = array();
        $this->correct_indices  = array();
        $this->correct_text     = array();

        $index = 1;

        foreach ($values as $value) {
            if (is_array($value) && count($value) == 3 &&
                is_string($value[0]) && is_string($value[1]) && is_int($value[2])) {
                $x = array();
                $x['key']   = $value[0];
                $x['text']  = $value[1];
                $x['pos']   = $value[2];
                $x['index'] = $index++;
                if (! preg_match('/^[-A-Za-z0-9 %^*()_+={}:;]*$/',$x['key'])) {
                    $this->errors[] = stack_string('builder_badkey');
                }
                if (array_key_exists($x['key'],$this->phrases_by_key)) {
                    $this->errors[] = stack_string('builder_duplicates');
                }
                $this->phrases[] = $x;
                $this->phrases_by_key[$x['key']] = $x;
                $this->phrases_by_index[$x['index']] = $x;
                
                if ($x['pos'] > 0) {
                    $this->correct_phrases[] = $x;
                }
            } else {
                $this->errors[] = stack_string('builder_badanswer', $teacheranswer);
            }
        }

        usort($this->correct_phrases,
              function($a, $b) { return $a['pos'] <=> $b['pos']; });

        $this->correct_keys =
          array_map(function($x) { return($x['key']); },$this->correct_phrases);
        
        $this->correct_indices =
          array_map(function($x) { return($x['index']); },$this->correct_phrases);
        
        $this->correct_text =
          array_map(function($x) { return($x['text']); },$this->correct_phrases);
        
        $this->teacheranswerraw = json_encode($this->correct_indices);
        $this->teacheranswervalue   = json_encode($this->correct_keys);
        $this->teacheranswerdisplay = implode(' ',$this->correct_text);
    }

    protected function validate_contents($contents, $basesecurity, $localoptions) {
        $valid = true;
        $errors = $this->errors;

        $n = count($this->phrases);
        $used = array();
        
        foreach ($contents as $i) {
            if (is_int($i) && 1 <= $i && $i <= $n) {
                if (array_key_exists($i,$used)) {
                    $valid = false;
                    $errors[] = stack_string('builder_gotrepeatedvalue');
                } else {
                    $used[$i] = true;
                }
            } else {
                $valid = false;
                $errors[] = stack_string('builder_gotunrecognisedvalue');
            }
        }

        $value = $valid ? $this->contents_to_maxima($contents) : '';
        list ($secrules, $filterstoapply) = $this->validate_contents_filters($basesecurity);
        $answer = stack_ast_container::make_from_student_source($value, '',
                                                                $secrules, $filterstoapply);
        $answer->get_valid();
        $notes = array();
        $caslines = array();
        
        return array($valid, $errors, $notes, $answer, $caslines);
    }

    public function validate_student_response($response,
                                              $options,
                                              $teacheranswer,
                                              $basesecurity,
                                              $ajaxinput=false,
                                              $castextprocessor = null,
                                              $questionvariables = null,
                                              $lang=null) {
        if ($ajaxinput) {
            $response = $this->ajax_to_response_array($response);
        }

        $status = self::SCORE;
        $errors = array();
        $raw_name = $this->name . '_raw';
        
        if (! array_key_exists($raw_name,$response)) {
            return new stack_input_state(self::BLANK, array(), '', '', '', '', '');
        }

        $response0 = $response[$raw_name];

        if (! is_string($response0)) {
            $status = self::INVALID;
            $errors[] = stack_string('builder_badresponse');
            $response0 = "[]";
        }

        $response0 = trim($response0);

        if ($response0 == '') {
            return new stack_input_state(self::BLANK, array(), '', '', '', '', '');
        }
        
        $indices = json_decode($response0);

        if (! is_array($indices)) {
            $status = self::INVALID;
            $errors[] = stack_string('builder_badresponse');
            $indices = [];
        }

        if (! $indices) {
            return new stack_input_state(self::BLANK, array(), '', '', '', '', '');
        }

        $used_indices = array();
        $contents = array();
        $text = array();
        $keys = array();
        
        foreach ($indices as $i) {
            if (array_key_exists($i,$this->phrases_by_index)) {
                if (array_key_exists($i,$used_indices)) {
                    $status = self::INVALID;
                    $errors[] = stack_string('builder_gotrepeatedvalue');
                } else {
                    $p = $this->phrases_by_index[$i];
                    $used_indices[$i] = true;
                    $contents[] = $i;
                    $keys[] = $p['key'];
                    $text[]  = $p['text'];
                }
            } else {
                $status = self::INVALID;
                $errors[] = stack_string('builder_badresponse');
            }
        }

        $errors = implode(' ',$errors);
        
        return new stack_input_state($status,
                                     $contents,
                                     json_encode($keys),
                                     implode(' ',$text),
                                     $errors,
                                     '',
                                     '',
                                     false);
    }

    /**
     * Transforms the contents array into a maxima list.
     *
     * @param array $contents
     * @return string
     */
    public function contents_to_maxima($contents) {
        $keys = array();
        foreach ($contents as $i) {
            $keys[] = $this->phrases_by_index[$i]['key'];
        }
        return json_encode($keys);
    }

    public function render(stack_input_state $state, $fieldname, $readonly, $tavalue) {
        global $PAGE;

        if (! $readonly) {
            $PAGE->requires->js_amd_inline('require(["qtype_stack/builder"],' .
             'function(builder) { ' .
             'console.log(builder); ' .
             'builder.init("' . $fieldname . '"); })');
        }
        
        if ($this->errors) {
            return $this->render_error($this->errors);
        }

        $phrase_html_by_index = array();
                            
        foreach($this->phrases_by_index as $i => $x) {
            $id = $fieldname . '_' . $i;
            $phrase_html_by_index[$i] = 
              html_writer::tag('li',$x['text'],array('class' => "builder_phrase",'id' => $id));
        }
        
        $used = array();
        $unused = array();
        
        $is_used = array();

        foreach($state->contents as $i) {
            $is_used[$i] = true;
            $used[] = $phrase_html_by_index[$i];
        }

        $contents_string = '[' . implode(',',$state->contents) . ']';
        
        foreach($this->phrases as $x) {
            $i = $x['index'];
            if (! array_key_exists($i,$is_used)) {
                $unused[] = $phrase_html_by_index[$i];
            }
        }

        $used_ol   = html_writer::tag('ol',implode(PHP_EOL,$used),
                      array('class' => 'builder_used', 'id' => $fieldname . '_used_ol'));

        $unused_ol = html_writer::tag('ol',implode(PHP_EOL,$unused),
                      array('class' => 'builder_unused','id' => $fieldname . '_unused_ol'));

        $used_div   = html_writer::div($used_ol,'builder_used',
                                     array('id' => $fieldname . '_used_div'));

        $unused_div = html_writer::div($unused_ol,'builder_unused',
                                     array('id' => $fieldname . '_unused_div'));
        
        $hidden = html_writer::tag(
          'input','',
          array('id' => $fieldname . '_raw',
                'name' => $fieldname . '_raw',
                'type' => 'hidden',
                'value' => $contents_string)
        );
        
        $full_div = html_writer::div(
            html_writer::div(stack_string('builder_instructions')) .
            '<br/>' . PHP_EOL . 
            $used_div .
            '<br/>' . PHP_EOL .
            $unused_div . PHP_EOL .
            $hidden . PHP_EOL
        );

        return $full_div;
    }

    /**
     * Get the input variable that this input expects to process.
     * All the variable names should start with $this->name.
     * @return array string input name => PARAM_... type constant.
     */
    public function get_expected_data() {
        $expected = array();
        $expected[$this->name . '_raw'] = PARAM_RAW;
        return $expected;
    }

    public function add_to_moodleform_testinput(MoodleQuickForm $mform) {
        $mform->addElement('text', $this->name, $this->name);
        $mform->setDefault($this->name, '');
        $mform->setType($this->name, PARAM_RAW);
    }

    public static function get_parameters_defaults() {

        return array(
            'mustVerify'     => false,
            'showValidation' => 0,
            'options'        => '',
        );
    }

    /**
     * This is used by the question to get the teacher's correct response.
     * The builder type needs to intercept this to filter the correct answers.
     * @param unknown_type $in
     */
    public function get_correct_response($in) {
        $this->adapt_to_model_answer($in);
        return array($this->name . '_raw' => $this->teacheranswerraw);
    }

    public function get_teacher_answer_display($value,$display) {
        return stack_string('teacheranswershow_builder',
                            array('display' => $this->teacheranswerdisplay));
    }
 
    /**
     * Transforms a Maxima expression into an array of raw inputs which are part of a response.
     * Most inputs are very simple, but textarea and matrix need more here.
     * @param array|string $in
     * @return array
     */
    public function maxima_to_response_array($in) {
        if ('' === $in || '[]' === $in) {
            return array();
        }

        $response = array();
        $response[$this->name] = $in;
        return $response;
    }

    protected function ajax_to_response_array($in) {
        if (((string) $in) === '') {
            return array();
        }

        $response = array();
        $response[$this->name] = $in;
        return $response;
    }

    /**
     * Converts the input passed in via many input elements into an array.
     *
     * @param string $in
     * @return string
     * @access public
     */
    public function response_to_contents($response) {
        $keys = json_decode($response[$this->name]);
        $contents = array();
        foreach ($keys as $k) {
            $contents[] = $this->phrases_by_key[$k]['index'];
        }
        return $contents;
    }

    /**
     * Decide if the contents of this attempt is blank.
     *
     * @param array $contents a non-empty array of the student's input as a split array of raw strings.
     * @return string any error messages describing validation failures. An empty
     *      string if the input is valid - at least according to this test.
     */
    protected function is_blank_response($contents) {
        return (! $contents);
    }
}
