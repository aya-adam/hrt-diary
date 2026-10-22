import { gql, type TypedDocumentNode } from "@apollo/client";
import { assertDefined } from "../assertions";

export type UnitDto = {
    id: string,
    symbol: string,
    label: string,
};

type UnitItemIdentifierInput = {
    id: string,
};

type UnitCollectionQueryResponse = {
    units: UnitDto[],
};

type UnitItemQueryResponse = {
    unit: UnitDto,
};

const UNIT_IRI_PREFIX = "/units/";

export class Unit {
    readonly id: string;
    readonly symbol: string;
    readonly label: string;

    static readonly COLLECTION_QUERY: TypedDocumentNode<UnitCollectionQueryResponse> = gql`
        query Units {
            units {
                id
                symbol
                label
            }
        }
    `;

    static readonly ITEM_QUERY: TypedDocumentNode<UnitItemQueryResponse, UnitItemIdentifierInput> = gql`
        query Unit($id: ID!) {
            unit(id: $id) {
                id
                symbol
                label
            }
        }
    `;

    constructor(id: string, symbol: string, label: string) {
        this.id = Unit.toUuid(id);
        this.symbol = symbol;
        this.label = label;
    };

    static fromDto(dto: UnitDto | undefined): Unit {
        assertDefined(dto, "UnitDto");

        return new Unit(
            dto.id,
            dto.symbol,
            dto.label,
        );
    }

    static toUuid(id: string): string {
        return id.replace(UNIT_IRI_PREFIX, "");
    }
    
    static toIri(uuid: string): string {
        return uuid.startsWith(UNIT_IRI_PREFIX) ? uuid : `${UNIT_IRI_PREFIX}${uuid}`;
    }
}