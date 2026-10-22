import { gql, type TypedDocumentNode } from "@apollo/client";
import type { Moment } from "moment";
import moment from "moment";
import { assertDefined } from "../assertions";

export type LabResultDto = {
    id: string,
    notes: string | null,
    drawnAt: string,
    publishedAt: string | null,
    createdAt: string,
    updatedAt: string,
};

type LabResultItemIdentifierInput = {
    id: string,
};

type LabResultCreateMutationInput = {
    notes: string,
    published?: boolean,
    drawnAt: string,
};

type LabResultUpdateMutationInput = {
    id: LabResultItemIdentifierInput['id'],
    notes?: string,
    published?: boolean,
    drawnAt?: string,
};

type LabResultCollectionQueryResponse = {
    labResults: LabResultDto[],
};

type LabResultItemQueryResponse = {
    labResult: LabResultDto,
};

type LabResultCreateMutationResponse = {
    createLabResult: {
        labResult: LabResultDto,
    }
};

type LabResultUpdateMutationResponse = {
    updateLabResult: {
        labResult: LabResultDto,
    }
};

type LabResultDeleteMutationResponse = {
    deleteLabResult: {
        labResult: {
            id: LabResultDto['id'],
        },
    }
};

const LAB_RESULT_IRI_PREFIX = "/lab_results/";

export class LabResult {
    readonly id: string;
    readonly notes?: string;
    readonly publishedAt?: Moment;
    readonly drawnAt: Moment;
    readonly createdAt: Moment;
    readonly updatedAt: Moment;

    static readonly COLLECTION_QUERY: TypedDocumentNode<LabResultCollectionQueryResponse> = gql`
        query LabResults {
            labResults {
                id
                notes
                publishedAt
                drawnAt
                createdAt
                updatedAt
            }
        }
    `;

    static readonly ITEM_QUERY: TypedDocumentNode<LabResultItemQueryResponse, LabResultItemIdentifierInput> = gql`
        query LabResult($id: ID!) {
            labResult(id: $id) {
                id
                notes
                publishedAt
                drawnAt
                createdAt
                updatedAt
            }
        }
    `;

    static readonly CREATE_MUTATION: TypedDocumentNode<LabResultCreateMutationResponse, LabResultCreateMutationInput> = gql`
        mutation CreateLabResult($notes: String!, $published: Boolean, $drawnAt: String!) {
            createLabResult(input: { notes: $notes, published: $published, drawnAt: $drawnAt }) {
                labResult {
                    id
                    notes
                    publishedAt
                    drawnAt
                    createdAt
                    updatedAt
                }
            }
        }
    `;

    static readonly UPDATE_MUTATION: TypedDocumentNode<LabResultUpdateMutationResponse, LabResultUpdateMutationInput> = gql`
        mutation UpdateLabResult($id: ID!, $notes: String, $published: Boolean, $drawnAt: String) {
            updateLabResult(input: { id: $id, notes: $notes, published: $published, drawnAt: $drawnAt }) {
                labResult {
                    id
                    notes
                    publishedAt
                    drawnAt
                    createdAt
                    updatedAt
                }
            }
        }
    `;

    static readonly DELETE_MUTATION: TypedDocumentNode<LabResultDeleteMutationResponse, LabResultItemIdentifierInput> = gql`
        mutation DeleteLabResult($id: ID!) {
            deleteLabResult(input: { id: $id }) {
                labResult {
                    id
                }
            }
        }
    `;

    constructor(id: string, notes: string | null | undefined, publishedAt: string | null | undefined, drawnAt: string, createdAt: string, updatedAt: string) {
        this.id = LabResult.toUuid(id);
        this.notes = notes ?? undefined;
        this.publishedAt = publishedAt ? moment(publishedAt) : undefined;
        this.drawnAt = moment(drawnAt);
        this.createdAt = moment(createdAt);
        this.updatedAt = moment(updatedAt);
    };

    static fromDto(dto: LabResultDto | undefined): LabResult {
        assertDefined(dto, "LabResultDto");

        return new LabResult(
            dto.id,
            dto.notes,
            dto.publishedAt,
            dto.drawnAt,
            dto.createdAt,
            dto.updatedAt,
        );
    }

    static toUuid(id: string): string {
        return id.replace(LAB_RESULT_IRI_PREFIX, "");
    }
    
    static toIri(uuid: string): string {
        return uuid.startsWith(LAB_RESULT_IRI_PREFIX) ? uuid : `${LAB_RESULT_IRI_PREFIX}${uuid}`;
    }
}