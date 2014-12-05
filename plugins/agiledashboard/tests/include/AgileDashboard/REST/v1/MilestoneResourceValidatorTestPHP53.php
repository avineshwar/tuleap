<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
namespace Tuleap\AgileDashboard\REST\v1;

use \TuleapTestCase;
use \Planning_Milestone;
use \PFUser;
use \AgileDashboard_Milestone_Backlog_BacklogStrategy;
use \AgileDashboard_BacklogItemPresenter;
use \AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection;

require_once dirname(__FILE__).'/../../../../bootstrap.php';

class MilestoneResourceValidatorTest extends TuleapTestCase {

    /** @var MilestoneResourceValidator */
    private $milestone_resource_validator;

    public function skip() {
        $this->skipIfNotPhp53();
    }

    public function setUp() {
        parent::setUp();

        $this->ids                            = array(102, 174);
        $parent_milestone                     = stub('Planning_Milestone')->getArtifact()->returns(anArtifact()->withId(101)->build());
        $this->milestone                      = stub('Planning_Milestone')->getParent()->returns($parent_milestone);
        $this->user                           = mock('PFUser');
        $strategy                             = mock('AgileDashboard_Milestone_Backlog_DescendantBacklogStrategy');
        $planning                             = stub('Planning')->getId()->returns(3);
        $this->artifact1                      = anArtifact()->withId(102)->withTrackerId(555)->build();
        $this->artifact2                      = anArtifact()->withId(174)->withTrackerId(666)->build();
        $this->unplanned_item                 = stub('AgileDashboard_BacklogItemPresenter')->id()->returns(102);
        $this->todo_item                      = stub('AgileDashboard_BacklogItemPresenter')->id()->returns(174);
        $this->unplanned_collection           = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $this->done_collection                = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $this->todo_collection                = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $this->planning_factory               = mock('PlanningFactory');
        $this->tracker_artifact_factory       = mock('Tracker_ArtifactFactory');
        $tracker_form_element_factory         = mock('Tracker_FormElementFactory');
        $backlog_strategy_factory       = mock('AgileDashboard_Milestone_Backlog_BacklogStrategyFactory');
        $milestone_factory                    = mock('Planning_MilestoneFactory');
        $backlog_row_collection_factory       = mock('AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory');

        stub($this->unplanned_item)->getArtifact()->returns($this->artifact1);
        stub($this->todo_item)->getArtifact()->returns($this->artifact2);
        stub($this->milestone)->getPlanning()->returns($planning);
        stub($backlog_strategy_factory)->getSelfBacklogStrategy($this->milestone)->returns($strategy);
        stub($backlog_row_collection_factory)->getDoneCollection()->returns($this->done_collection);
        stub($backlog_row_collection_factory)->getTodoCollection()->returns($this->todo_collection);
        stub($backlog_row_collection_factory)->getUnplannedOpenCollection()->returns($this->unplanned_collection);

        $this->milestone_resource_validator = new MilestoneResourceValidator(
            $this->planning_factory,
            $this->tracker_artifact_factory,
            $tracker_form_element_factory,
            $backlog_strategy_factory,
            $milestone_factory,
            $backlog_row_collection_factory
        );
    }

    public function itReturnsTrueIfEverythingIsOk() {
        $this->unplanned_collection->push($this->unplanned_item);
        $this->todo_collection->push($this->todo_item);

        stub($this->planning_factory)->getBacklogTrackersIds($this->milestone->getPlanning()->getId())->returns(array(555,666));
        stub($this->tracker_artifact_factory)->getArtifactById(102)->returns($this->artifact1);
        stub($this->tracker_artifact_factory)->getArtifactById(174)->returns($this->artifact2);

        $validation = $this->milestone_resource_validator->validateArtifactsFromBodyContent(
            $this->ids,
            $this->milestone,
            $this->user
        );

        $this->assertTrue($validation);
    }

    public function itThrowsAnExceptionIfArtifactIdIsPassedSeveralTime() {
        $this->expectException('Tuleap\AgileDashboard\REST\v1\IdsFromBodyAreNotUniqueException');

        $ids = array(102, 174, 102);

        $this->milestone_resource_validator->validateArtifactsFromBodyContent($ids, $this->milestone, $this->user);
    }

    public function itThrowsAnExceptionIfArtifactIdDoesNotExist() {
        $this->expectException('Tuleap\AgileDashboard\REST\v1\ArtifactDoesNotExistException');

        stub($this->planning_factory)->getBacklogTrackersIds($this->milestone->getPlanning()->getId())->returns(array(1,2,3));

        $this->milestone_resource_validator->validateArtifactsFromBodyContent($this->ids, $this->milestone, $this->user);
    }

    public function itThrowsAnExceptionIfArtifactIsNotInBacklogTracker() {
        $this->expectException('Tuleap\AgileDashboard\REST\v1\ArtifactIsNotInBacklogTrackerException');

        stub($this->planning_factory)->getBacklogTrackersIds($this->milestone->getPlanning()->getId())->returns(array(1,2,3));
        stub($this->tracker_artifact_factory)->getArtifactById(102)->returns($this->artifact1);
        stub($this->tracker_artifact_factory)->getArtifactById(174)->returns($this->artifact2);

        $this->milestone_resource_validator->validateArtifactsFromBodyContent($this->ids, $this->milestone, $this->user);
    }

    public function itThrowsAnExceptionIfArtifactIsClosedOrAlreadyPlannedInAnotherMilestone() {
        $this->expectException('Tuleap\AgileDashboard\REST\v1\ArtifactIsClosedOrAlreadyPlannedInAnotherMilestone');

        $this->unplanned_collection->push($this->unplanned_item);

        stub($this->planning_factory)->getBacklogTrackersIds($this->milestone->getPlanning()->getId())->returns(array(555,666));
        stub($this->tracker_artifact_factory)->getArtifactById(102)->returns($this->artifact1);
        stub($this->tracker_artifact_factory)->getArtifactById(174)->returns($this->artifact2);

        $this->milestone_resource_validator->validateArtifactsFromBodyContent($this->ids, $this->milestone, $this->user);
    }
}

class MilestoneResourceValidator_PatchAddRemoveTest extends TuleapTestCase {

    /** @var MilestoneResourceValidator */
    private $milestone_resource_validator;
    private $user;
    private $artifact;
    private $milestone;

    public function skip() {
        $this->skipIfNotPhp53();
    }

    public function setUp() {
        parent::setUp();

        $this->user                         = mock('PFUser');
        $this->artifact = aMockArtifact()->withLinkedArtifacts(
            array(
                anArtifact()->withId(112)->build(),
                anArtifact()->withId(113)->build(),
                anArtifact()->withId(114)->build(),
                anArtifact()->withId(115)->build(),
            )
        )->build();
        $this->milestone = aMilestone()->withArtifact($this->artifact)->build();
        $this->milestone_resource_validator = partial_mock('Tuleap\AgileDashboard\REST\v1\MilestoneResourceValidator', array('validateArtifactsFromBodyContent'));
    }

    public function itAllowsToRemoveFromContentWhenRemovedIdsArePartOfLinkedArtifacts() {
        $this->milestone_resource_validator->getValidatedArtifactsIdsToRemoveFromContent($this->user, $this->milestone, array(112, 113), null);
    }

    public function itForbidsToRemoveFromContentWhenRemovedIdsArePartOfLinkedArtifacts() {
        $this->expectException('Tuleap\AgileDashboard\REST\v1\ArtifactIsNotInMilestoneContentException');
        $this->milestone_resource_validator->getValidatedArtifactsIdsToRemoveFromContent($this->user, $this->milestone, array(566, 113), null);
    }

    public function itReturnsTheValidIds() {
        $this->assertEqual(
            $this->milestone_resource_validator->getValidatedArtifactsIdsToRemoveFromContent($this->user, $this->milestone, array(112, 113), null),
            array(114, 115)
        );
    }

    public function itAllowsToAddArtifactsThatAreValidForContent() {
        expect($this->milestone_resource_validator)->validateArtifactsFromBodyContent(array(210), $this->milestone, $this->user)->once();

        $this->milestone_resource_validator->getValidatedArtifactsIdsToRemoveFromContent($this->user, $this->milestone, null, array(210));
    }

    public function itDoesntAddWhenArrayIsEmpty() {
        expect($this->milestone_resource_validator)->validateArtifactsFromBodyContent()->never();

        $this->milestone_resource_validator->getValidatedArtifactsIdsToRemoveFromContent($this->user, $this->milestone, null, null);
    }

    public function itForbidsToAddArtifactsThatAreNotValidForContent() {
        expect($this->milestone_resource_validator)->validateArtifactsFromBodyContent(array(210), $this->milestone, $this->user)->once();
        stub($this->milestone_resource_validator)->validateArtifactsFromBodyContent()->throws(new \Exception());

        $this->expectException();

        $this->milestone_resource_validator->getValidatedArtifactsIdsToRemoveFromContent($this->user, $this->milestone, null, array(210));
    }

    public function itReturnsTheAddedIdsPlusTheExistingOne() {
        $this->assertEqual(
            $this->milestone_resource_validator->getValidatedArtifactsIdsToRemoveFromContent($this->user, $this->milestone, null, array(210)),
            array(112, 113, 114, 115, 210)
        );
    }

    public function itAllowsToAddAndRemoveInSameTime() {
        $this->assertEqual(
            $this->milestone_resource_validator->getValidatedArtifactsIdsToRemoveFromContent($this->user, $this->milestone, array(113, 115), array(210)),
            array(112, 114, 210)
        );
    }

    public function itSkipsWhenAnElementIsAddedAndRemovedAtSameTime() {
        $this->assertEqual(
            $this->milestone_resource_validator->getValidatedArtifactsIdsToRemoveFromContent($this->user, $this->milestone, array(114, 113), array(113, 210)),
            array(112, 113, 115, 210)
        );
    }

    public function itDoesntAddAnElementAlreadyInContent() {
        $this->assertEqual(
            $this->milestone_resource_validator->getValidatedArtifactsIdsToRemoveFromContent($this->user, $this->milestone, null, array(113)),
            array(112, 113, 114, 115)
        );
    }
}
