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
 * External services
 *
 * @package   local_cveteval
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cveteval;
defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_single_structure;
use external_value;


class external extends \external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_user_type_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'id of the user', null, NULL_NOT_ALLOWED)
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_single_structure
     */
    public static function get_user_type_returns() {
        return new external_single_structure(
            array(
                'type' => new external_value(PARAM_TEXT, 'the type of user'),
            )
        );
    }
    /**
     * Return the current role for the user
     */
    public static function get_user_type($userid) {
        $params = self::validate_parameters(self::get_user_type_parameters(), array('userid'=>$userid));

        $roleid =  \local_cveteval\local\role\entity::ROLE_STUDENT_ID;
        // Check that user exists first, if not it will be a student role.
        if ($user =\core_user::get_user($userid)) {
            $isappraiser = local\role\entity::record_exists_select(
                "userid = :userid AND type = :type", array('userid'=> $userid,
                    'type' => \local_cveteval\local\role\entity::ROLE_APPRAISER_ID));
            if ($isappraiser) {
                $roleid = \local_cveteval\local\role\entity::ROLE_APPRAISER_ID;
            }
        }

        return (object)['type' =>
            \local_cveteval\local\role\entity::ROLE_SHORTNAMES[
                $roleid
            ]
        ];
    }
}