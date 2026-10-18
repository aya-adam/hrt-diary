import { gql, type TypedDocumentNode } from "@apollo/client";
import type { Moment } from "moment";
import moment from "moment";
import { assertDefined } from "../assertions";

export type AdministrationCycleDto = {
    id: string,
    name: string,
    startingDate: string,
    locations: string[],
    startingIndex: number,
    createdAt: string,
    updatedAt: string,
};

type AdministrationCycleItemIdentifierInput = {
    id: string,
};

type AdministrationCycleCreateMutationInput = {
    name: string,
    startingDate: string,
    locations: string[],
    startingIndex: number,
};

type AdministrationCycleUpdateMutationInput = {
    id: AdministrationCycleItemIdentifierInput['id'],
    name?: string,
    startingDate?: string,
    locations?: string[],
    startingIndex?: number,
};

type AdministrationCycleCollectionQueryResponse = {
    administrationCycles: AdministrationCycleDto[],
};

type AdministrationCycleItemQueryResponse = {
    administrationCycle: AdministrationCycleDto,
};

type AdministrationCycleCreateMutationResponse = {
    createAdministrationCycle: {
        administrationCycle: AdministrationCycleDto,
    }
};

type AdministrationCycleUpdateMutationResponse = {
    updateAdministrationCycle: {
        administrationCycle: AdministrationCycleDto,
    }
};

type AdministrationCycleDeleteMutationResponse = {
    deleteAdministrationCycle: {
        administrationCycle: {
            id: AdministrationCycleDto['id'],
        },
    }
};

export class AdministrationCycle {
    readonly id: string;
    readonly name: string;
    readonly startingDate: Moment;
    readonly locations: string[];
    readonly startingIndex: number;
    readonly createdAt: Moment;
    readonly updatedAt: Moment;

    static readonly COLLECTION_QUERY: TypedDocumentNode<AdministrationCycleCollectionQueryResponse> = gql`
        query AdministrationCycles {
            administrationCycles {
                id
                name
                startingDate
                locations
                startingIndex
                createdAt
                updatedAt
            }
        }
    `;

    static readonly ITEM_QUERY: TypedDocumentNode<AdministrationCycleItemQueryResponse, AdministrationCycleItemIdentifierInput> = gql`
    query AdministrationCycle($id: ID!) {
        administrationCycle(id: $id) {
            id
            name
            startingDate
            locations
            startingIndex
            createdAt
            updatedAt
        }
    }
    `;

    static readonly CREATE_MUTATION: TypedDocumentNode<AdministrationCycleCreateMutationResponse, AdministrationCycleCreateMutationInput> = gql`
        mutation CreateAdministrationCycle($name: String!, $startingDate: String!, $locations: Iterable!, $startingIndex: Int!) {
            createAdministrationCycle(input: { name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                administrationCycle {
                    id
                    name
                    startingDate
                    locations
                    startingIndex
                    createdAt
                    updatedAt
                }
            }
        }
    `;

    static readonly UPDATE_MUTATION: TypedDocumentNode<AdministrationCycleUpdateMutationResponse, AdministrationCycleUpdateMutationInput> = gql`
        mutation UpdateAdministrationCycle($id: ID!, $name: String, $startingDate: String, $locations: Iterable, $startingIndex: Int) {
            updateAdministrationCycle(input: { id: $id, name: $name, startingDate: $startingDate, locations: $locations, startingIndex: $startingIndex }) {
                administrationCycle {
                    id
                    name
                    startingDate
                    locations
                    startingIndex
                    createdAt
                    updatedAt
                }
            }
        }
    `;

    static readonly DELETE_MUTATION: TypedDocumentNode<AdministrationCycleDeleteMutationResponse, AdministrationCycleItemIdentifierInput> = gql`
        mutation DeleteAdministrationCycle($id: ID!) {
            deleteAdministrationCycle(input: { id: $id }) {
                administrationCycle {
                    id
                }
            }
        }
    `;

    constructor(id: string, name: string, startingDate: string, locations: string[], startingIndex: number, createdAt: string, updatedAt: string) {
        this.id = id;
        this.name = name;
        this.startingDate = moment(startingDate);
        this.locations = locations;
        this.startingIndex = startingIndex;
        this.createdAt = moment(createdAt);
        this.updatedAt = moment(updatedAt);
    };

    static fromDto(dto: AdministrationCycleDto | undefined): AdministrationCycle {
        assertDefined(dto, "AdministrationCycleDto");

        return new AdministrationCycle(
            dto.id,
            dto.name,
            dto.startingDate,
            dto.locations,
            dto.startingIndex,
            dto.createdAt,
            dto.updatedAt,
        );
    }
}