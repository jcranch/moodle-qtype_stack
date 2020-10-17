<?php
// This file is part of Stack - https://stack.maths.ed.ac.uk
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

// This block is intended to allow embedding of 3D diagrams using
// the BABYLON library.  I am attempting to follow the pattern of
// the JSXGraph block.
//
// @copyright  2020 Neil Strickland
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.

require_once("block.interface.php");
require_once(__DIR__ . '/../../../../../../lib/pagelib.php');

class stack_cas_castext_babylon extends stack_cas_castext_block {

    private static $countbabylon = 1;

    public function extract_attributes($tobeevaluatedcassession, $conditionstack = null) {
        // There are currently no CAS evaluated attributes.
        // Only reasonable such would be dynamic size parameters.
    }

    public function content_evaluation_context($conditionstack = array()) {
        // Nothing changes, we want the contents to be evaluated as they are.
        return $conditionstack;
    }

    public function process_content($evaluatedcassession, $conditionstack = null) {
        // There is nothing to do before the full CASText has been evaluated.
        return false;
    }

    public function clear() {
        global $PAGE, $CFG;
        // Now is the time to replace the block with the div and the code.
        $code = "";
        $iter = $this->get_node()->firstchild;
        while ($iter !== null) {
            $code .= $iter->to_string();
            $iter = $iter->nextsibling;
        }

        $divid  = "stack-babylon-" . self::$countbabylon;
        $canvasid  = "stack-babylon-canvas-" . self::$countbabylon;

        // Prefix the code with the id of the div.
        $code = "var divid = '$divid';\nvar canvasid = '$canvasid';\n$code";

        // We restrict the actions of the block code a bit by stopping it from
        // rewriting some things in the surrounding scopes.
        // Also catch errors inside the code and try to provide console logging
        // of them for the author.
        // We could calculate the actual offset but I'll leave that for
        // someone else. 1+2*n probably, or we could just write all the preamble
        // on the same line and make the offset always be the same?
        $code = '"use strict";try{if(document.getElementById("' . $divid . '")){' . $code . '}} '
            . 'catch(err) {console.log("STACK Babylon error in \"' . $divid
            . '\":");'
            . 'console.log(err);}';

        $width  = $this->get_node()->get_parameter('width', '500px');
        $height = $this->get_node()->get_parameter('height', '400px');

        $style  = "width:$width;height:$height;";

        // Empty tags seem to be an issue.
        $div = html_writer::div(
         html_writer::tag('canvas','',array('class' => 'babyloncanvas',
                                            'id' => $canvasid,
                                            'style' => $style)),
         'babylonbox',
         array('id' => $divid)
        );
        
        $this->get_node()->convert_to_text($div);

        $PAGE->requires->js_amd_inline(
         'require(["core/yui","qtype_stack/babylonjs-gui","qtype_stack/babylonjs"],' .
         'function(Y,BABYLON_GUI,BABYLON) {' .
         'Y.use("mathjax",function(){' .
         $code .
         '});})');

        // Up the graph number to generate unique names.
        self::$countbabylon = self::$countbabylon + 1;
    }

    public function validate_extract_attributes() {
        // There are currently no CAS evaluated attributes.
        return array();
    }

    public function validate(&$errors=array()) {
        // Basically, check that the dimensions have units we know.
        // Also that the references make sense.
        $valid      = true;
        $width      = $this->get_node()->get_parameter('width', '500px');
        $height     = $this->get_node()->get_parameter('height', '400px');

        // NOTE! List ordered by length. For the trimming logic.
        $validunits = array("vmin", "vmax", "rem", "em", "ex", "px", "cm", "mm",
                            "in", "pt", "pc", "ch", "vh", "vw", "%");

        $widthend   = false;
        $heightend  = false;
        $widthtrim  = $width;
        $heighttrim = $height;

        foreach ($validunits as $suffix) {
            if (!$widthend && strlen($width) > strlen($suffix) &&
                    substr($width, -strlen($suffix)) === $suffix) {
                $widthend = true;
                $widthtrim = substr($width, 0, -strlen($suffix));
            }
            if (!$heightend && strlen($height) > strlen($suffix) &&
                    substr($height, -strlen($suffix)) === $suffix) {
                $heightend = true;
                $heighttrim = substr($height, 0, -strlen($suffix));
            }
            if ($widthend && $heightend) {
                break;
            }
        }

        if (!$widthend) {
            $valid = false;
            $errors[] = stack_string('stackBlock_babylon_width');
        }
        if (!$heightend) {
            $valid = false;
            $errors[] = stack_string('stackBlock_babylon_height');
        }
        if (!preg_match('/^[0-9]*[\.]?[0-9]+$/', $widthtrim)) {
            $valid = false;
            $errors[] = stack_string('stackBlock_babylon_width_num');
        }
        if (!preg_match('/^[0-9]*[\.]?[0-9]+$/', $heighttrim)) {
            $valid = false;
            $errors[] = stack_string('stackBlock_babylon_height_num');
        }

        // Finally check parent for other issues, should be none.
        if ($valid) {
            $valid = parent::validate($errors);
        }

        return $valid;
    }
}
