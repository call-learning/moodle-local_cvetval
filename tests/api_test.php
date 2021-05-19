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
 * API tests
 *
 * @package     local_cveteval
 * @copyright   2020 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cltools;

use local_cveteval\local\external\appr_crit;
use local_cveteval\local\external\appraisal;
use local_cveteval\local\external\clsituation;
use local_cveteval\local\external\criterion;
use local_cveteval\local\external\cevalgrid;
use local_cveteval\local\external\evalplan;
use local_cveteval\local\external\group_assign;
use local_cveteval\local\external\role;
use local_cveteval\local\external\user_profile;
use local_cveteval\local\external\user_type;
use \local_cveteval\local\persistent\role\entity as role_entity;

/**
 * API tests
 *
 * @package     local_cltools
 * @copyright   2020 CALL Learning <contact@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cveteval_api_testcase extends \advanced_testcase {

    public function setUp() {
        global $CFG;
        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/local/cveteval/tests/helpers.php');
        $basepath = $CFG->dirroot . '/local/cveteval/tests/fixtures/';
        $data = [
            'users' => $CFG->dirroot . '/local/cveteval/tests/fixtures/ShortSample_Users.csv',
            'cveteval' => [
                'evaluation_grid' => "{$basepath}/Sample_Evalgrid.csv",
                'situation' => "{$basepath}/ShortSample_Situations.csv",
                'planning' => "{$basepath}/ShortSample_Planning.csv",
                'grouping' => "{$basepath}/ShortSample_Grouping.csv"
            ]
        ];
        import_sample_users($data['users']);
        import_sample_planning($data['cveteval'], true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
    }

    /**
     * Test if the User Type API is functional
     */
    public function test_get_user_type() {
        global $CFG;
        require_once($CFG->libdir . '/externallib.php');
        $this->assertEquals(
            (object)
            ['type' => role_entity::ROLE_SHORTNAMES[role_entity::ROLE_STUDENT_ID]],
            user_type::execute((\core_user::get_user_by_username('etu1'))->id));
        $this->assertEquals(
            (object)
            ['type' => role_entity::ROLE_SHORTNAMES[role_entity::ROLE_ASSESSOR_ID]],
            user_type::execute((\core_user::get_user_by_username('resp1'))->id));
        // Obs 1 to 5 are also assessors
        $this->assertEquals(
            (object)
            ['type' => role_entity::ROLE_SHORTNAMES[role_entity::ROLE_ASSESSOR_ID]],
            user_type::execute((\core_user::get_user_by_username('obs1'))->id));
        $this->assertEquals(
            (object)
            ['type' => role_entity::ROLE_SHORTNAMES[role_entity::ROLE_APPRAISER_ID]],
            user_type::execute((\core_user::get_user_by_username('obs6'))->id));
        // This user was both in groups and in roles, default to student.
        $this->assertEquals(
            (object)
            ['type' => role_entity::ROLE_SHORTNAMES[role_entity::ROLE_STUDENT_ID]],
            user_type::execute((\core_user::get_user_by_username('obs7'))->id));
    }

    /**
     * Test if the User Type API is functional
     */
    public function test_get_user_profile() {
        global $CFG;
        require_once($CFG->libdir . '/externallib.php');
        $userid = (\core_user::get_user_by_username('etu1'))->id;
        $this->assertEquals(
            [
                'userid' => $userid,
                'fullname' => 'Guest',
                'firstname' => '',
                'lastname' => '',
                'username' => 'anonymous',
                'userpictureurl' => 'https://www.example.com/moodle/theme/image.php/_s/boost/core/1/u/f1'],
            (array) user_profile::execute($userid));
    }

    /**
     * Test an API function
     */
    public function test_get_get_appraisal() {
        global $CFG;
        require_once($CFG->libdir . '/externallib.php');
        $user1 = \core_user::get_user_by_username('etu1');
        $user2 = \core_user::get_user_by_username('etu2');
        create_appraisal_for_students($user1->id, null, false);
        create_appraisal_for_students($user2->id, null, false);
        $appraisals = appraisal::get();
        $this->assertEmpty($appraisals);
        // Now, I am user 1, I should only get appraisal involving me (either as a student or appraiser)
        $this->setUser($user1);
        $appraisals = appraisal::get();
        $this->assertNotEmpty($appraisals);
        $this->assertCount(6, $appraisals); // 6 situations for this user in his planning.
        $user2 = \core_user::get_user_by_username('obs1'); // Now as obs1
        $this->setUser($user2);
        $appraisals = appraisal::get();
        $this->assertNotEmpty($appraisals);
        $this->assertCount(8, $appraisals); // 2 students appraisal and 4 observers
    }


    /**
     * Test an API function
     */
    public function test_get_get_appraisal_crit() {
        global $CFG;
        require_once($CFG->libdir . '/externallib.php');
        $user1 = \core_user::get_user_by_username('etu1');
        $user2 = \core_user::get_user_by_username('etu2');
        create_appraisal_for_students($user1->id, null, false);
        create_appraisal_for_students($user2->id, null, false);
        $appraisalscrit = appr_crit::get();
        $this->assertEmpty($appraisalscrit);
        // Now, I am user 1, I should only get appraisal involving me (either as a student or appraiser)
        $this->setUser($user1);
        $appraisalscrit = appr_crit::get();
        $this->assertNotEmpty($appraisalscrit);
        // 6 appraisals, 240 criteria
        $this->assertCount(6*40, $appraisalscrit); // 6 situations for this user in his planning.
        $user2 = \core_user::get_user_by_username('obs1'); // Now as obs1
        $this->setUser($user2);
        $appraisalscrit = appr_crit::get();
        $this->assertNotEmpty($appraisalscrit);
        $this->assertCount(8*40, $appraisalscrit); // 6 situations for this user in his planning.
    }

    /**
     * Test an API function
     */
    public function test_get_get_situation() {
        global $CFG;
        require_once($CFG->libdir . '/externallib.php');
        $situations = clsituation::get();
        $this->assertNotEmpty($situations);
        // We retrieve all situations here.
        $this->assertEquals(['TMG', 'TMI', 'TUS'],
            array_values(array_map(
                function($s) {
                    return $s->idnumber;
                }, $situations)));

    }

    /**
     * Test an API function
     */
    public function test_get_get_role() {
        global $CFG, $DB;
        require_once($CFG->libdir . '/externallib.php');
        $roles = role::get();
        $allusersmatch =
            $DB->get_records_menu('user', null, '', 'id,username');
        $allclsituation =
            $DB->get_records_menu('local_cveteval_clsituation', null, '', 'id,title');
        $this->assertNotEmpty($roles);
        // We retrieve all situations here.
        $this->assertEquals(
            [
                (object) array(
                    'userid' => 'obs1',
                    'clsituationid' => 'Consultations de médecine générale',
                    'type' => '1',
                ),
                (object) array(
                    'userid' => 'obs2',
                    'clsituationid' => 'Consultations de médecine générale',
                    'type' => '1',
                ),
                (object) array(
                    'userid' => 'resp1',
                    'clsituationid' => 'Consultations de médecine générale',
                    'type' => '2',
                ),
                (object) array(
                    'userid' => 'obs1',
                    'clsituationid' => 'Consultations de médecine générale',
                    'type' => '2',
                ),

                (object) array(
                    'userid' => 'obs2',
                    'clsituationid' => 'Consultations de médecine générale',
                    'type' => '2',
                ),

                (object) array(
                    'userid' => 'resp2',
                    'clsituationid' => 'Médecine interne',
                    'type' => '2',
                ),

                (object) array(
                    'userid' => 'obs3',
                    'clsituationid' => 'Médecine interne',
                    'type' => '2',
                ),

                (object) array(
                    'userid' => 'obs4',
                    'clsituationid' => 'Médecine interne',
                    'type' => '2',
                ),

                (object) array(
                    'userid' => 'obs1',
                    'clsituationid' => 'Urgences-Soins intensifs',
                    'type' => '1',
                ),

                (object) array(
                    'userid' => 'obs7',
                    'clsituationid' => 'Urgences-Soins intensifs',
                    'type' => '1',
                ),

                (object) array(
                    'userid' => 'obs6',
                    'clsituationid' => 'Urgences-Soins intensifs',
                    'type' => '1',
                ),

                (object) array(
                    'userid' => 'resp3',
                    'clsituationid' => 'Urgences-Soins intensifs',
                    'type' => '2',
                ),

                (object) array(
                    'userid' => 'obs5',
                    'clsituationid' => 'Urgences-Soins intensifs',
                    'type' => '2',
                ),
            ]
            ,
            array_values(array_map(function($r) use ($allusersmatch, $allclsituation) {
                return (object) [
                    'userid' => $allusersmatch[$r->userid],
                    'clsituationid' => $allclsituation[$r->clsituationid],
                    'type' => $r->type,
                ];
            }, $roles)));;

    }

    /**
     * Test an API function
     */
    public function test_get_get_criterion() {
        global $CFG;
        require_once($CFG->libdir . '/externallib.php');
        $criteria = criterion::get();
        $this->assertNotEmpty($criteria);
        // We retrieve all situations here.
        $this->assertEquals(
            ['Q001', 'Q002', 'Q003', 'Q004', 'Q005', 'Q006', 'Q007', 'Q008', 'Q009', 'Q010', 'Q011', 'Q012', 'Q013', 'Q014', 'Q015',
                'Q016', 'Q017', 'Q018', 'Q019', 'Q020', 'Q021', 'Q022', 'Q023', 'Q024', 'Q025', 'Q026', 'Q027', 'Q028', 'Q029',
                'Q030', 'Q031', 'Q032', 'Q033', 'Q034', 'Q035', 'Q036', 'Q037', 'Q038', 'Q039', 'Q040'],
            array_values(array_map(function($s) {
                return $s->idnumber;
            }, $criteria)));

    }

    /**
     * Test an API function
     */
    public function test_get_get_group_assign() {
        global $CFG, $DB;
        require_once($CFG->libdir . '/externallib.php');
        $groupassign = group_assign::get();
        $this->assertNotEmpty($groupassign);
        $this->assertCount(5, $groupassign);
        $allstudentsid =
            $DB->get_fieldset_select('user', 'id', $DB->sql_like('username', ':namelike'),
                array('namelike' => '%etu%'));
        $allstudentsid[] = (\core_user::get_user_by_username('obs7'))->id;
        // We retrieve all situations here.
        $this->assertEquals(
            $allstudentsid,
            array_values(array_map(function($s) {
                return $s->studentid;
            }, $groupassign)));

    }

    /**
     * Test an API function
     */
    public function test_get_get_evalgrid() {
        global $CFG, $DB;
        require_once($CFG->libdir . '/externallib.php');
        $evalgrid = cevalgrid::get();
        $this->assertNotEmpty($evalgrid);
        $this->assertCount(1, $evalgrid);
        // We retrieve all situations here.
        $this->assertEquals(
            ['GRID01'],
            array_values(array_map(function($e) {
                return $e->idnumber;
            }, $evalgrid)));

    }

    /**
     * Test an API function
     */
    public function test_get_get_evalplan_no_user() {
        global $CFG, $DB;
        require_once($CFG->libdir . '/externallib.php');
        // First, no user logged i
        $evalplan = evalplan::get();
        $this->assertEmpty($evalplan);
    }

    /**
     * Test an API function
     */
    public function test_get_get_evalplan_student() {
        global $CFG, $DB;
        require_once($CFG->libdir . '/externallib.php');
        // First, no user logged i
        $user1 = \core_user::get_user_by_username('etu1');
        // Now, I am user 1, I should only get evalplans involving me (either as a student or appraiser)
        $this->setUser($user1);
        $evalplan = evalplan::get();
        $this->assertNotEmpty($evalplan);
        $this->assertCount(6, $evalplan);
        $allgroupidmatch =
            $DB->get_records_menu('local_cveteval_group', null, '', 'id,name');
        $allclsituation =
            $DB->get_records_menu('local_cveteval_clsituation', null, '', 'id,title');
        // We retrieve all situations here.

        $this->assertEquals(
            array_values(array_filter($this->get_all_evalplans(), function($plan) {
                return $plan->groupid == 'Groupe A';
            })),
            array_values(array_map(function($s) use ($allgroupidmatch, $allclsituation) {
                return (object) [
                    'groupid' => $allgroupidmatch[$s->groupid],
                    'clsituationid' => $allclsituation[$s->clsituationid],
                    'starttime' => strftime('%d/%m/%Y', $s->starttime),
                    'endtime' => strftime('%d/%m/%Y', $s->endtime),
                ];
            }, $evalplan)));

    }

    /**
     * Test an API function
     */
    public function test_get_get_evalplan_observer() {
        global $CFG, $DB;
        require_once($CFG->libdir . '/externallib.php');
        // First, no user logged i
        $user1 = \core_user::get_user_by_username('obs1');
        // Now, I am user 1, I should only get evalplans involving me (either as a student or appraiser)
        $this->setUser($user1);
        $evalplan = evalplan::get();
        $this->assertNotEmpty($evalplan);
        $this->assertCount(4, $evalplan);
        $allgroupidmatch =
            $DB->get_records_menu('local_cveteval_group', null, '', 'id,name');
        $allclsituation =
            $DB->get_records_menu('local_cveteval_clsituation', null, '', 'id,title');
        // We retrieve all situations here.

        $this->assertEquals(
            array_values(array_filter($this->get_all_evalplans(), function($plan) {
                return $plan->clsituationid == 'Consultations de médecine générale';
            })),
            array_values(array_map(function($s) use ($allgroupidmatch, $allclsituation) {
                return (object) [
                    'groupid' => $allgroupidmatch[$s->groupid],
                    'clsituationid' => $allclsituation[$s->clsituationid],
                    'starttime' => strftime('%d/%m/%Y', $s->starttime),
                    'endtime' => strftime('%d/%m/%Y', $s->endtime),
                ];
            }, $evalplan)));

    }

    /**
     * All eval plans
     */
    protected function get_all_evalplans() {
        return [
            (object) [
                'groupid' => 'Groupe A',
                'clsituationid' => 'Consultations de médecine générale',
                'starttime' => '12/04/2021',
                'endtime' => '19/04/2021'
            ],
            (object) [
                'groupid' => 'Groupe B',
                'clsituationid' => 'Urgences-Soins intensifs',
                'starttime' => '12/04/2021',
                'endtime' => '19/04/2021'
            ],
            (object) [
                'groupid' => 'Groupe A',
                'clsituationid' => 'Médecine interne',
                'starttime' => '19/04/2021',
                'endtime' => '26/04/2021'
            ],
            (object) [
                'groupid' => 'Groupe B',
                'clsituationid' => 'Consultations de médecine générale',
                'starttime' => '19/04/2021',
                'endtime' => '26/04/2021',
            ],
            (object) [
                'groupid' => 'Groupe A',
                'clsituationid' => 'Urgences-Soins intensifs',
                'starttime' => '26/04/2021',
                'endtime' => '03/05/2021',
            ],
            (object) [
                'groupid' => 'Groupe B',
                'clsituationid' => 'Médecine interne',
                'starttime' => '26/04/2021',
                'endtime' => '03/05/202',
            ],
            (object) [
                'groupid' => 'Groupe A',
                'clsituationid' => 'Consultations de médecine générale',
                'starttime' => '03/05/2021',
                'endtime' => '10/05/2021',
            ],
            (object) [
                'groupid' => 'Groupe B',
                'clsituationid' => 'Urgences-Soins intensifs',
                'starttime' => '03/05/2021',
                'endtime' => '10/05/2021',
            ],
            (object) [
                'groupid' => 'Groupe A',
                'clsituationid' => 'Médecine interne',
                'starttime' => '10/05/2021',
                'endtime' => '17/05/2021',
            ],
            (object) [
                'groupid' => 'Groupe B',
                'clsituationid' => 'Consultations de médecine générale',
                'starttime' => '10/05/2021',
                'endtime' => '17/05/2021',
            ],
            (object) [
                'groupid' => 'Groupe A',
                'clsituationid' => 'Urgences-Soins intensifs',
                'starttime' => '17/05/2021',
                'endtime' => '24/05/2021',
            ],
            (object) [
                'groupid' => 'Groupe B',
                'clsituationid' => 'Médecine interne',
                'starttime' => '17/05/2021',
                'endtime' => '24/05/2021',
            ]
        ];
    }

}