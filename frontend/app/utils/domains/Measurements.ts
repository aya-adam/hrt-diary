import { gql, type TypedDocumentNode } from "@apollo/client";
import type { Moment } from "moment";
import moment from "moment";
import { assertDefined } from "../assertions";
import Decimal from "decimal.js";
import { LabResult, type LabResultDto } from "./LabResult";
import { Unit, type UnitDto } from "./Unit";
import { Hormone, type HormoneDto } from "./Hormone";
import { ReferenceRange, type ReferenceRangeDto } from "./ReferenceRange";

export type MeasurementDto = {
    id: string,
    labResult: LabResultDto,
    unit: UnitDto,
    hormone: HormoneDto,
    referenceRange: ReferenceRangeDto | null,
    value: string,
    createdAt: string,
    updatedAt: string,
};

type MeasurementItemIdentifierInput = {
    id: string,
};

type MeasurementCreateMutationInput = {
    labResult: string,
    unit?: string,
    hormone: string,
    referenceRange?: string,
    value: string,
};

type MeasurementUpdateMutationInput = {
    id: MeasurementItemIdentifierInput['id'],
    labResult?: string,
    unit?: string,
    hormone?: string,
    referenceRange?: string,
    value?: string,
};

type MeasurementCollectionQueryResponse = {
    measurements: MeasurementDto[],
};

type MeasurementItemQueryResponse = {
    measurement: MeasurementDto,
};

type MeasurementCreateMutationResponse = {
    createMeasurement: {
        measurement: MeasurementDto,
    }
};

type MeasurementUpdateMutationResponse = {
    updateMeasurement: {
        measurement: MeasurementDto,
    }
};

type MeasurementDeleteMutationResponse = {
    deleteMeasurement: {
        measurement: {
            id: MeasurementDto['id'],
        },
    }
};

export class Measurement {
    readonly id: string;
    readonly labResult: LabResult;
    readonly unit: Unit;
    readonly hormone: Hormone;
    readonly referenceRange?: ReferenceRange;
    readonly value: Decimal;
    readonly createdAt: Moment;
    readonly updatedAt: Moment;

    static readonly COLLECTION_QUERY: TypedDocumentNode<MeasurementCollectionQueryResponse> = gql`
        query Measurements {
            measurements {
                id
                labResult {
                    id
                    notes
                    publishedAt
                    drawnAt
                    createdAt
                    updatedAt
                }
                unit {
                    id
                    symbol
                    label
                }
                hormone {
                    id
                    name
                    commonName
                }
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
                value
                createdAt
                updatedAt
            }
        }
    `;

    static readonly ITEM_QUERY: TypedDocumentNode<MeasurementItemQueryResponse, MeasurementItemIdentifierInput> = gql`
        query Measurement($id: ID!) {
            measurement(id: $id) {
                id
                labResult {
                    id
                    notes
                    publishedAt
                    drawnAt
                    createdAt
                    updatedAt
                }
                unit {
                    id
                    symbol
                    label
                }
                hormone {
                    id
                    name
                    commonName
                }
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
                value
                createdAt
                updatedAt
            }
        }
    `;

    static readonly CREATE_MUTATION: TypedDocumentNode<MeasurementCreateMutationResponse, MeasurementCreateMutationInput> = gql`
        mutation CreateMeasurement($labResult: String!, $unit: String, $hormone: String!, $referenceRange: String, $value: String!) {
            createMeasurement(input: { labResult: $labResult, unit: $unit, hormone: $hormone, referenceRange: $referenceRange, value: $value }) {
                measurements {
                    id
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                    unit {
                        id
                        symbol
                        label
                    }
                    hormone {
                        id
                        name
                        commonName
                    }
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
                    value
                    createdAt
                    updatedAt
                }
            }
        }
    `;

    static readonly UPDATE_MUTATION: TypedDocumentNode<MeasurementUpdateMutationResponse, MeasurementUpdateMutationInput> = gql`
        mutation UpdateMeasurement($id: ID!, $labResult: String, $unit: String, $hormone: String, $referenceRange: String, $value: String) {
            updateMeasurement(input: { id: $id, labResult: $labResult, unit: $unit, hormone: $hormone, referenceRange: $referenceRange, value: $value }) {
                measurements {
                    id
                    labResult {
                        id
                        notes
                        publishedAt
                        drawnAt
                        createdAt
                        updatedAt
                    }
                    unit {
                        id
                        symbol
                        label
                    }
                    hormone {
                        id
                        name
                        commonName
                    }
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
                    value
                    createdAt
                    updatedAt
                }
            }
        }
    `;

    static readonly DELETE_MUTATION: TypedDocumentNode<MeasurementDeleteMutationResponse, MeasurementItemIdentifierInput> = gql`
        mutation DeleteMeasurement($id: ID!) {
            deleteMeasurement(input: { id: $id }) {
                measurement {
                    id
                }
            }
        }
    `;

    constructor(id: string, labResult: LabResultDto, unit: UnitDto, hormone: HormoneDto, referenceRange: ReferenceRangeDto | null, value: string, createdAt: string, updatedAt: string) {
        this.id = id;
        this.labResult = LabResult.fromDto(labResult);
        this.unit = Unit.fromDto(unit);
        this.hormone = Hormone.fromDto(hormone);
        this.referenceRange = referenceRange ? ReferenceRange.fromDto(referenceRange) : undefined;
        this.value = new Decimal(value);
        this.createdAt = moment(createdAt);
        this.updatedAt = moment(updatedAt);
    };

    static fromDto(dto: MeasurementDto | undefined): Measurement {
        assertDefined(dto, "MeasurementDto");

        return new Measurement(
            dto.id,
            dto.labResult,
            dto.unit,
            dto.hormone,
            dto.referenceRange,
            dto.value,
            dto.createdAt,
            dto.updatedAt,
        );
    }
}