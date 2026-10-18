import { gql, type TypedDocumentNode } from "@apollo/client";
import type { Moment } from "moment";
import moment from "moment";
import { assertDefined } from "../assertions";
import { Hormone, type HormoneDto } from "./Hormone";
import { Unit, type UnitDto } from "./Unit";
import Decimal from "decimal.js";

export type ReferenceRangeDto = {
    id: string,
    hormone: HormoneDto,
    unit: UnitDto,
    name: string,
    lowerLimit: string,
    upperLimit: string,
    notes: string | null,
    createdAt: string,
    updatedAt: string,
};

type ReferenceRangeItemIdentifierInput = {
    id: string,
};

type ReferenceRangeCreateMutationInput = {
    hormone: string,
    unit: string,
    name: string,
    lowerLimit: string,
    upperLimit: string,
    notes?: string,
};

type ReferenceRangeUpdateMutationInput = {
    id: ReferenceRangeItemIdentifierInput['id'],
    hormone?: string,
    unit?: string,
    name?: string,
    lowerLimit?: string,
    upperLimit?: string,
    notes?: string,
};

type ReferenceRangeCollectionQueryResponse = {
    referenceRanges: ReferenceRangeDto[],
};

type ReferenceRangeItemQueryResponse = {
    referenceRange: ReferenceRangeDto,
};

type ReferenceRangeCreateMutationResponse = {
    createReferenceRange: {
        referenceRange: ReferenceRangeDto,
    }
};

type ReferenceRangeUpdateMutationResponse = {
    updateReferenceRange: {
        referenceRange: ReferenceRangeDto,
    }
};

type ReferenceRangeDeleteMutationResponse = {
    deleteReferenceRange: {
        referenceRange: {
            id: ReferenceRangeDto['id'],
        },
    }
};

export class ReferenceRange {
    id: string;
    hormone?: Hormone;
    unit: Unit;
    name: string;
    lowerLimit: Decimal;
    upperLimit: Decimal;
    notes?: string;
    createdAt: Moment;
    updatedAt: Moment;

    static readonly COLLECTION_QUERY: TypedDocumentNode<ReferenceRangeCollectionQueryResponse> = gql`
        query ReferenceRanges {
            referenceRanges {
                id
                hormone {
                    id
                    name
                    commonName
                }
                unit {
                    id
                    symbol
                    label
                }
                name
                lowerLimit
                upperLimit
                notes
                createdAt
                updatedAt
            }
        }
    `;

    static readonly ITEM_QUERY: TypedDocumentNode<ReferenceRangeItemQueryResponse, ReferenceRangeItemIdentifierInput> = gql`
        query ReferenceRange($id: ID!) {
            referenceRange(id: $id) {
                id
                hormone {
                    id
                    name
                    commonName
                }
                unit {
                    id
                    symbol
                    label
                }
                name
                lowerLimit
                upperLimit
                notes
                createdAt
                updatedAt
            }
        }
    `;

    static readonly CREATE_MUTATION: TypedDocumentNode<ReferenceRangeCreateMutationResponse, ReferenceRangeCreateMutationInput> = gql`
        mutation CreateReferenceRange($hormone: String!, $unit: String!, $name: String!, $lowerLimit: String!, $upperLimit: String!, $notes: String) {
            createReferenceRange(input: { hormone: $hormone, unit: $unit, name: $name, lowerLimit: $lowerLimit, upperLimit: $upperLimit, notes: $notes }) {
                referenceRange {
                    id
                    hormone {
                        id
                        name
                        commonName
                    }
                    unit {
                        id
                        symbol
                        label
                    }
                    name
                    lowerLimit
                    upperLimit
                    notes
                    createdAt
                    updatedAt
                }
            }
        }
    `;

    static readonly UPDATE_MUTATION: TypedDocumentNode<ReferenceRangeUpdateMutationResponse, ReferenceRangeUpdateMutationInput> = gql`
        mutation UpdateReferenceRange($id: ID!, $hormone: String, $unit: String, $name: String, $lowerLimit: String, $upperLimit: String, $notes: String) {
            updateReferenceRange(input: { id: $id, hormone: $hormone, unit: $unit, name: $name, lowerLimit: $lowerLimit, upperLimit: $upperLimit, notes: $notes }) {
                referenceRange {
                    id
                    hormone {
                        id
                        name
                        commonName
                    }
                    unit {
                        id
                        symbol
                        label
                    }
                    name
                    lowerLimit
                    upperLimit
                    notes
                    createdAt
                    updatedAt
                }
            }
        }
    `;

    static readonly DELETE_MUTATION: TypedDocumentNode<ReferenceRangeDeleteMutationResponse, ReferenceRangeItemIdentifierInput> = gql`
        mutation DeleteReferenceRange($id: ID!) {
            deleteReferenceRange(input: { id: $id }) {
                referenceRange {
                    id
                }
            }
        }
    `;

    constructor(id: string, hormone: HormoneDto, unit: UnitDto, name: string, lowerLimit: string, upperLimit: string, notes: string | null, createdAt: string, updatedAt: string) {
        this.id = id;
        this.hormone = hormone ? Hormone.fromDto(hormone) : undefined;
        this.unit = Unit.fromDto(unit);
        this.name = name;
        this.lowerLimit = new Decimal(lowerLimit);
        this.upperLimit = new Decimal(upperLimit);
        this.notes = notes ?? undefined;
        this.createdAt = moment(createdAt);
        this.updatedAt = moment(updatedAt);
    };

    static fromDto(dto: ReferenceRangeDto | undefined): ReferenceRange {
        assertDefined(dto, "ReferenceRangeDto");

        return new ReferenceRange(
            dto.id,
            dto.hormone,
            dto.unit,
            dto.name,
            dto.lowerLimit,
            dto.upperLimit,
            dto.notes,
            dto.createdAt,
            dto.updatedAt,
        );
    }
}