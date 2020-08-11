/*
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

import { buildGeneralSection } from "./general-information-builder";
import { ReportCell, TextCell } from "./report-cells";
import { buildRequirementsSection } from "./requirements-builder";
import { BacklogItem, Campaign } from "../../type";
import { buildTestResultsSection } from "./test-results-builder";
import { getPlannedTestCasesAssociatedWithCampaignAndTestExec } from "./get-planned-test-cases";
import { buildJustificationsSection } from "./justifications-builder";

export interface ReportSection {
    readonly title?: TextCell;
    readonly headers?: ReadonlyArray<TextCell>;
    readonly rows: ReadonlyArray<ReadonlyArray<ReportCell>>;
}

export interface ExportReport {
    readonly sections: ReadonlyArray<ReportSection>;
}

export function createExportReport(
    gettext_provider: VueGettextProvider,
    project_name: string,
    milestone_title: string,
    user_display_name: string,
    current_date: Date,
    backlog_items: ReadonlyArray<BacklogItem>,
    campaigns: ReadonlyArray<Campaign>
): ExportReport {
    const planned_test_cases = getPlannedTestCasesAssociatedWithCampaignAndTestExec(
        gettext_provider,
        backlog_items,
        campaigns
    );

    return {
        sections: [
            buildGeneralSection(
                gettext_provider,
                project_name,
                milestone_title,
                user_display_name,
                current_date
            ),
            buildRequirementsSection(gettext_provider, backlog_items),
            buildTestResultsSection(gettext_provider, planned_test_cases),
            buildJustificationsSection(gettext_provider, planned_test_cases),
        ],
    };
}
