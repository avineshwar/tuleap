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

import { get } from "tlp";
import { limitConcurrencyPool } from "./concurrency-limit-pool";

const MAX_CHUNK_SIZE_ARTIFACTS = 100;
const MAX_CONCURRENT_REQUESTS_WHEN_RETRIEVING_ARTIFACT_CHUNKS = 5;

export interface Artifact {
    id: number;
    values_by_field: {
        [field_name: string]:
            | { type: never }
            | {
                  type: "text";
                  format: "text" | "html";
                  value: string;
              };
    };
}

interface ArtifactsCollection {
    collection: Artifact[];
}

export async function retrieveArtifacts(
    artifact_ids: ReadonlyArray<number>
): Promise<Map<number, Artifact>> {
    const artifacts: Map<number, Artifact> = new Map();
    const responses = await limitConcurrencyPool(
        MAX_CONCURRENT_REQUESTS_WHEN_RETRIEVING_ARTIFACT_CHUNKS,
        getArtifactIDsChunks(artifact_ids),
        getArtifactIDsChunkResponse
    );

    for (const response of responses) {
        const artifacts_collection: ArtifactsCollection = await response.json();

        for (const artifact of artifacts_collection.collection) {
            artifacts.set(artifact.id, artifact);
        }
    }

    return artifacts;
}

function getArtifactIDsChunks(
    artifact_ids: ReadonlyArray<number>
): ReadonlyArray<ReadonlyArray<number>> {
    const artifact_ids_chunks: ReadonlyArray<number>[] = [];

    let index = 0;
    while (index < artifact_ids.length) {
        artifact_ids_chunks.push(artifact_ids.slice(index, index + MAX_CHUNK_SIZE_ARTIFACTS));
        index += MAX_CHUNK_SIZE_ARTIFACTS;
    }

    return artifact_ids_chunks;
}

function getArtifactIDsChunkResponse(artifact_ids_chunk: ReadonlyArray<number>): Promise<Response> {
    return get(
        `/api/v1/artifacts?query=${encodeURIComponent(JSON.stringify({ id: artifact_ids_chunk }))}`
    );
}
