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
// the owl library.  I am attempting to follow the pattern of
// the JSXGraph block.
//
// @copyright  2020 Neil Strickland
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.

require_once(__DIR__ . '/../block.interface.php');
require_once(__DIR__ . '/../block.factory.php');

require_once(__DIR__ . '/root.specialblock.php');
require_once(__DIR__ . '/stack_translate.specialblock.php');
require_once(__DIR__ . '/../../../../vle_specific.php');

require_once(__DIR__ . '/iframe.block.php');
stack_cas_castext2_iframe::register_counter('///OWL_COUNT///');

class stack_cas_castext2_owl extends stack_cas_castext2_block {

    private static $countowl = 1;

    public function compile($format, $options):  ? MP_Node {
        $r = new MP_List([new MP_String('iframe')]);

        $xpars = $this->params;
        $xpars['scrolling'] = false;
        $xpars['title'] = 'STACK OWL ///OWL_COUNT///';

        // We need to transfer the parameters forward.
        $s = json_encode($xpars);
        $s = stack_utils::php_string_to_maxima_string($s);
        $r->items[] = new MP_String($s);

        $r->items[] = new MP_List([
            new MP_String('script'),
            new MP_String(json_encode(['type' => 'text/javascript', 'src' => 'cors://owl.js'])),
        ]);

        foreach ($this->children as $item) {
            // Assume that all code inside is JavaScript and that we do not
            // want to do the markdown escaping or any other in it.
            $c = $item->compile(castext2_parser_utils::RAWFORMAT, $options);
            if ($c !== null) {
                $r->items[] = $c;
            }
        }

        return $r;
    }

    public function is_flat() : bool {
        return false;
    }

    public function postprocess(array $params, castext2_processor $processor, castext2_placeholder_holder $holder): string {
        global $PAGE;

        if (count($params) < 3) {
            // Nothing at all.
            return '';
        }

        $parameters = json_decode($params[1], true);
        $content    = '';
        for ($i = 2; $i < count($params); $i++) {
            if (is_array($params[$i])) {
                $content .= $processor->process($params[$i][0], $params[$i]);
            } else {
                $content .= $params[$i];
            }
        }

        $divid  = 'stack-owl-' . self::$countowl;
        $canvasid  = "stack-owl-canvas-" . self::$countowl;

        $width  = '500px';
        $height = '400px';
        $aspectratio = false;
        if (array_key_exists('width', $parameters)) {
            $width = $parameters['width'];
        }
        if (array_key_exists('height', $parameters)) {
            $height = $parameters['height'];
        }

        $style = "width:$width;height:$height;";

        if (array_key_exists('aspect-ratio', $parameters)) {
            $aspectratio = $parameters['aspect-ratio'];
            // Unset the undefined dimension, if both are defined then we have a problem.
            if (array_key_exists('height', $parameters)) {
                $style = "height:$height;aspect-ratio:$aspectratio;";
            } else if (array_key_exists('width', $parameters)) {
                $style = "width:$width;aspect-ratio:$aspectratio;";
            }
        }

        $code = $content;
        // Prefix the code with the id of the div.
        $code = "var divid = '$divid';\nvar canvasid = '$canvasid';\n$code";

        $code = '"use strict";try{if(document.getElementById("' . $divid .
            '")){' . $code . '}} '
            . 'catch(err) {console.log("STACK owl error in \"' . $divid
            . '\", (note a slight varying offset in the error position due to possible input references):");'
            . 'console.log(err);}';

        $attributes = ['class' => 'owlbox', 'style' => $style, 'id' => $divid];

        $PAGE->requires->js_amd_inline(
            'require(["core/yui",' .
            '"qtype_stack/owl","qtype_stack/vec",' .
            '"qtype_stack/babylonjs-gui","qtype_stack/babylonjs"],' .
            'function(Y,owl,vec,BABYLON_GUI,BABYLON) {' .
            'Y.use("mathjax",function(){' .
            $code .
            '});})');

        self::$countowl = self::$countowl + 1;

        return html_writer::tag('div', '', $attributes);
    }

    public function validate_extract_attributes(): array {
        return [];
    }

    public function validate(&$errors=[], $options=[]) : bool {
        return true;
    }
}
