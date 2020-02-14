<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\Service;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\ProjectRetriever;

final class EditControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var EditController */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProjectRetriever
     */
    private $project_retriever;

    protected function setUp(): void
    {
        $this->project_retriever = M::mock(ProjectRetriever::class);
        $this->controller        = new EditController(
            M::mock(ServiceUpdator::class),
            M::mock(ServicePOSTDataBuilder::class),
            M::mock(\ServiceManager::class),
            $this->project_retriever,
            M::mock(\CSRFSynchronizerToken::class)
        );
    }

    public function testNonProjectAdministratorCannotAccessThePage(): void
    {
        $project = M::mock(\Project::class)->shouldReceive('getID')
            ->andReturn('102')
            ->getMock();
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('102')
            ->once()
            ->andReturn($project);

        $request      = M::mock(\HTTPRequest::class);
        $current_user = M::mock(\PFUser::class);
        $current_user->shouldReceive('isAdmin')->andReturn(false);
        $request->shouldReceive('getCurrentUser')->andReturn($current_user);

        $this->expectException(ForbiddenException::class);
        $this->controller->process($request, M::mock(BaseLayout::class), ['id' => '102']);
    }
}