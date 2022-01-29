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
 * Planning list for a given user
 *
 * @package   local_cveteval
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cveteval\local\assessment;
defined('MOODLE_INTERNAL') || die();

use context;
use core_table\local\filter\filter;
use local_cltools\local\crud\entity_table;
use local_cltools\local\filter\enhanced_filterset;
use local_cltools\local\filter\numeric_comparison_filter;
use local_cveteval\local\persistent\role\entity as role_entity;
use local_cveteval\roles;
use moodle_url;
use local_cveteval\local\persistent\situation\entity as situation_entity;

/**
 * Persistent list base class
 *
 * @package   local_cveteval
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class situations extends entity_table {

    protected static $persistentclass = situation_entity::class;

    public function __construct($uniqueid = null,
        $actionsdefs = null,
        $editable = false
    ) {
        global $PAGE;
        $this->fieldaliases = [
                'roletype' => 'role.type',
                'appraiserid' => 'role.userid'
        ];
        parent::__construct($uniqueid, $actionsdefs, $editable);
        $PAGE->requires->js_call_amd('local_cltools/tabulator-row-action-url', 'init', [
            $this->get_unique_id(),
            (new moodle_url('/local/cveteval/pages/assessment/mystudents.php'))->out(),
            (object) array('situationid' => 'id')
        ]);
    }

    protected function internal_get_sql_from($tablealias = 'e') {
        $from = situation_entity::get_historical_sql_query('entity');
        $rolesql = role_entity::get_historical_sql_query("role");
        return "$from  LEFT JOIN  $rolesql ON entity.id = role.clsituationid";
    }

    protected function col_description($row) {
        return $this->format_text($row->description, $row->descriptionformat);
    }

    /**
     * Get persistent columns definition
     *
     * @return array
     */
    protected function get_table_columns_definitions() {
        list($cols, $headers) = parent::get_table_columns_definitions();
        foreach ($cols as $index => $col) {
            if ($col === 'descriptionformat') {
                unset($headers[$index]);
                unset($cols[$index]);
                unset($this->fields[$col]);
            }
        }
        return [array_values($cols), array_values($headers)];
    }
    /**
     * Validate current user has access to the table instance
     *
     * Note: this can involve a more complicated check if needed and requires filters and all
     * setup to be done in order to make sure we validated against the right information
     * (such as for example a filter needs to be set in order not to return data a user should not see).
     *
     * @param context $context
     * @param bool $writeaccess
     * @throws \restricted_context_exception
     */
    public function validate_access(context $context, $writeaccess = false) {
        global $USER;
        if (!roles::can_assess($USER->id)) {
            throw new \restricted_context_exception();
        }
    }
}
