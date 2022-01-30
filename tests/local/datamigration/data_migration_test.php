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
 * Data migration test
 *
 * @package   local_cveteval
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cveteval\local\assessment;

use core_table\local\filter\string_filter;
use local_cveteval\local\datamigration\data_migration_controller;
use local_cveteval\local\datamigration\data_model_matcher;
use local_cveteval\local\datamigration\helpers\user_data_migration_helper;
use local_cveteval\local\datamigration\matchers\criterion as criterion_matcher;
use local_cveteval\local\datamigration\matchers\evaluation_grid as evaluation_grid_matcher;
use local_cveteval\local\datamigration\matchers\group as group_matcher;
use local_cveteval\local\datamigration\matchers\group_assignment as group_assignment_matcher;
use local_cveteval\local\datamigration\matchers\situation as situation_matcher;
use local_cveteval\local\datamigration\matchers\planning as planning_matcher;
use local_cveteval\local\datamigration\matchers\role as role_matcher;
use local_cveteval\local\persistent\criterion\entity as criterion_entity;
use local_cveteval\local\persistent\evaluation_grid\entity as evaluation_grid_entity;
use local_cveteval\local\persistent\group\entity as group_entity;
use local_cveteval\local\persistent\group_assignment\entity as group_assignment_entity;
use local_cveteval\local\persistent\history\entity;
use local_cveteval\local\persistent\history\entity as history_entity;
use local_cveteval\local\persistent\situation\entity as situation_entity;
use local_cveteval\local\persistent\planning\entity as planning_entity;
use local_cveteval\local\persistent\role\entity as role_entity;
use local_cveteval\output\dmc_entity_renderer_base;
use local_cveteval\test\assessment_test_trait;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class data_migration_matching_test extends \advanced_testcase {

    use assessment_test_trait;

    protected $dm;

    protected $oldentities, $newentities;

    /**
     * History 1
     *
     * @return \stdClass
     */
    protected function get_sample_origin1($planstart, $planend) {
        $sample = new \stdClass();

        $sample->criteria = [
            [
                'evalgrididnumber' => 'evalgrid',
                'idnumber' => 'criterion1',
                'parentid' => 0,
                'sort' => 1
            ],
            [
                'evalgrididnumber' => 'evalgrid',
                'idnumber' => 'criterion1bis',
                'parentidnumber' => 'criterion1',
                'sort' => 1
            ],
            [
                'evalgrididnumber' => 'evalgrid',
                'idnumber' => 'criterion2',
                'parentidnumber' => 'criterion1',
                'sort' => 1
            ]
        ];
        $sample->situations = [
            [
                'evalgrididnumber' => 'evalgrid',
                'title' => 'Situation 1',
                'description' => 'Situation desc',
                'descriptionformat' => FORMAT_PLAIN,
                'idnumber' => 'SIT1',
                'expectedevalsnb' => 2
            ],
            [
                'evalgrididnumber' => 'evalgrid',
                'title' => 'Situation 2',
                'description' => 'Situation desc',
                'descriptionformat' => FORMAT_PLAIN,
                'idnumber' => 'SIT2',
                'expectedevalsnb' => 1
            ],
        ];
        $sample->evalplans = [
            [
                'groupname' => 'Group 1',
                'clsituationidnumber' => 'SIT1',
                'starttime' => $planstart,
                'endtime' => $planend
            ],
            [
                'groupname' => 'Group 2',
                'clsituationidnumber' => 'SIT2',
                'starttime' => $planstart,
                'endtime' => $planend
            ],
            [
                'groupname' => 'Group 2bis',
                'clsituationidnumber' => 'SIT2',
                'starttime' => $planstart,
                'endtime' => $planend
            ],
        ];
        $sample->criteriaeval = [
            [
                'criterionidnumber' => 'criterion1',
                'grade' => 1,
                'comment' => 'Context crit1',
                'commentformat' => FORMAT_PLAIN,
            ],
            [
                'criterionidnumber' => 'criterion1bis',
                'grade' => 2,
                'comment' => 'Context crit1bis',
                'commentformat' => FORMAT_PLAIN,
            ],
            [
                'criterionidnumber' => 'criterion2',
                'grade' => 3,
                'comment' => 'Context crit2',
                'commentformat' => FORMAT_PLAIN,
            ]
        ];
        $sample->appraisals = [
            [
                'studentname' => 'student1',
                'appraisername' => 'assessor1',
                'evalplandatestart' => $planstart,
                'evalplansituation' => 'SIT1',
                'context' => 'Context',
                'contextformat' => FORMAT_PLAIN,
                'comment' => 'Context',
                'commentformat' => FORMAT_PLAIN,
                'criteria' => $sample->criteriaeval
            ],
            [
                'studentname' => 'student1',
                'appraisername' => 'assessor2',
                'evalplandatestart' => $planstart,
                'evalplansituation' => 'SIT2',
                'context' => 'Context',
                'contextformat' => FORMAT_PLAIN,
                'comment' => 'Context',
                'commentformat' => FORMAT_PLAIN,
                'criteria' => $sample->criteriaeval
            ],
        ];
        $sample->assessors = ['assessor1' => 'SIT1', 'assessor2' => 'SIT2'];
        $sample->students = ['student1' => ['Group 1'], 'student2' => ['Group 1', 'Group 2']];
        return $sample;
    }

    /**
     * History 2
     *
     * @return \stdClass
     */
    protected function get_sample_dest2($planstart, $planend) {
        $sample = new \stdClass();

        $sample->criteria = [
            [
                'evalgrididnumber' => 'evalgrid',
                'idnumber' => 'criterion1',
                'parentid' => 0,
                'sort' => 1
            ],
            [
                'evalgrididnumber' => 'evalgrid',
                'idnumber' => 'criterion2',
                'parentidnumber' => 'criterion1',
                'sort' => 1
            ],
            [
                'evalgrididnumber' => 'evalgrid',
                'idnumber' => 'criterion1bis',
                'parentidnumber' => 'criterion2',
                'sort' => 1
            ]
        ];
        $sample->situations = [
            [
                'evalgrididnumber' => 'evalgrid',
                'title' => 'Situation 1',
                'description' => 'Situation desc',
                'descriptionformat' => FORMAT_PLAIN,
                'idnumber' => 'SIT1',
                'expectedevalsnb' => 2
            ],
            [
                'evalgrididnumber' => 'evalgrid',
                'title' => 'Situation 2',
                'description' => 'Situation desc',
                'descriptionformat' => FORMAT_PLAIN,
                'idnumber' => 'SIT2',
                'expectedevalsnb' => 2
            ],
            [
                'evalgrididnumber' => 'evalgrid',
                'title' => 'Situation 3',
                'description' => 'Situation desc',
                'descriptionformat' => FORMAT_PLAIN,
                'idnumber' => 'SIT3',
                'expectedevalsnb' => 1
            ],
        ];
        $sample->evalplans = [
            [
                'groupname' => 'Group 1',
                'clsituationidnumber' => 'SIT1',
                'starttime' => $planstart,
                'endtime' => $planend
            ],
            [
                'groupname' => 'Group 2',
                'clsituationidnumber' => 'SIT2',
                'starttime' => $planstart,
                'endtime' => $planend
            ],
            [
                'groupname' => 'Group 3',
                'clsituationidnumber' => 'SIT2',
                'starttime' => $planstart,
                'endtime' => $planend
            ],
        ];
        $sample->criteriaeval = [
            [
                'criterionidnumber' => 'criterion1',
                'grade' => 1,
                'comment' => 'Context crit1',
                'commentformat' => FORMAT_PLAIN,
            ],
            [
                'criterionidnumber' => 'criterion1bis',
                'grade' => 2,
                'comment' => 'Context crit1bis',
                'commentformat' => FORMAT_PLAIN,
            ],
            [
                'criterionidnumber' => 'criterion2',
                'grade' => 3,
                'comment' => 'Context crit2',
                'commentformat' => FORMAT_PLAIN,
            ]
        ];
        $sample->appraisals = [
            [
                'studentname' => 'student1',
                'appraisername' => 'assessor1',
                'evalplandatestart' => $planstart,
                'evalplansituation' => 'SIT1',
                'context' => 'Context',
                'contextformat' => FORMAT_PLAIN,
                'comment' => 'Context',
                'commentformat' => FORMAT_PLAIN,
                'criteria' => $sample->criteriaeval
            ],
            [
                'studentname' => 'student1',
                'appraisername' => 'assessor2',
                'evalplandatestart' => $planstart,
                'evalplansituation' => 'SIT2',
                'context' => 'Context',
                'contextformat' => FORMAT_PLAIN,
                'comment' => 'Context',
                'commentformat' => FORMAT_PLAIN,
                'criteria' => $sample->criteriaeval
            ],
        ];
        $sample->assessors = ['assessor1' => 'SIT1', 'assessor2' => 'SIT2'];
        $sample->students = ['student1' => ['Group 1'], 'student2' => ['Group 1', 'Group 2'], 'student3' => ['Group 3']];
        return $sample;
    }

    public function setUp() {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->resetAfterTest();
        $planstart = time();
        $duration = 3600 * 24;
        $planend = time() + $duration;

        $historyold = new history_entity(0, (object) ['idnumber' => 'history1', 'comments' => '', 'isactive'=>true]);
        $historyold->create();
        history_entity::set_current_id($historyold->get('id'));
        $this->oldentities = (object) [
            'criteria' => [],
            'situations' => [],
            'evalplans' => [],
            'students' => [],
            'assessors' => [],
            'appraisals' => [],
        ];
        [$this->oldentities->criteria, $this->oldentities->situations, $this->oldentities->evalplans,
            $this->oldentities->students, $this->oldentities->assessors, $this->oldentities->appraisals] =
            $this->set_up($this->get_sample_origin1($planstart, $planend));

        $historynew = new history_entity(0, (object) ['idnumber' => 'history2', 'comments' => '', 'isactive'=>true]);
        $historynew->create();
        history_entity::set_current_id($historynew->get('id'));
        $this->newentities = (object) [
            'criteria' => [],
            'situations' => [],
            'evalplans' => [],
            'students' => [],
            'assessors' => [],
            'appraisals' => [],
        ];
        [$this->newentities->criteria, $this->newentities->situations, $this->newentities->evalplans,
            $this->newentities->students, $this->newentities->assessors, $this->newentities->appraisals] =
            $this->set_up($this->get_sample_dest2($planstart, $planend));

        history_entity::reset_current_id();
        $this->dm = new data_model_matcher($historyold->get('id'), $historynew->get('id'));

    }

    /**
    * Test general migration of data
     */
    public function test_migration() {
        $data = new stdClass();
        $data->matchedentities = $this->dm->get_matched_entities_list();
        $data->unmatchedentities = $this->dm->get_unmatched_entities_list();
        $data->orphanedentities = $this->dm->get_orphaned_entities_list();
        entity::disable_history();
        $convertedappraisalsinfo =
                user_data_migration_helper::convert_origin_appraisals(dmc_entity_renderer_base::ALL_CONTEXTS, $data);
        $convertedfinalevalsinfo =
                user_data_migration_helper::convert_origin_finaleval(dmc_entity_renderer_base::ALL_CONTEXTS, $data);
        $this->assertNotEmpty($convertedappraisalsinfo);
    }


}
