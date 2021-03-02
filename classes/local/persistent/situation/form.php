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
 * Clinical situation entry form
 *
 * @package   local_cveteval
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cveteval\local\persistent\situation;
defined('MOODLE_INTERNAL') || die();
use local_cltools\local\crud\form\entity_form;

/**
 * Clinical situation entry form
 *
 * @package   local_cveteval
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form extends entity_form {
    /**
     * Form property in order to display the right widget for the form.
     *
     * @return array|array[]
     * @throws \coding_exception
     */
    protected static function define_properties() {
        return array(
            'title' => (object) [
                'type' => 'text'
            ],
            'description' => [
                'type' => 'editor'
            ],
            'idnumber' => [
                'type' => 'text'
            ],
            'expectedevalsnb' => array(
                'type' => 'int',
            ),
            'evalgridid' => [
                'type' => 'entity_selector',
                'selector_info' => (object) [
                    'entity_type' => '\\local_cveteval\\local\\persistent\\evaluation_grid\\entity',
                    'display_field' => 'name'
                ]
            ]
        );
    }

    /** @var string The fully qualified classname. */
    protected static $persistentclass = '\\local_cveteval\\local\\persistent\\situation\\entity';
}