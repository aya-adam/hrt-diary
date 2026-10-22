import { gql, type TypedDocumentNode } from "@apollo/client";
import { assertDefined } from "../assertions";

export type HormoneDto = {
    id: string,
    name: string,
    commonName: string | null,
};

type HormoneItemIdentifierInput = {
    id: string,
};

type HormoneItemQueryResponse = {
    hormone: HormoneDto,
};

type HormoneCollectionQueryResponse = {
    hormones: HormoneDto[],
};

const HORMONE_IRI_PREFIX = "/hormones/";

export class Hormone {
    readonly id: string;
    readonly name: string;
    readonly commonName?: string;

    static readonly COLLECTION_QUERY: TypedDocumentNode<HormoneCollectionQueryResponse> = gql`
        query Hormones {
            hormones {
                id
                name
                commonName
            }
        }
    `;

    static readonly ITEM_QUERY: TypedDocumentNode<HormoneItemQueryResponse, HormoneItemIdentifierInput> = gql`
        query Hormone($id: ID!) {
            hormone(id: $id) {
                id
                name
                commonName
            }
        }
    `;

    constructor(id: string, name: string, commonName: string | null | undefined) {
        this.id = Hormone.toUuid(id);
        this.name = name;
        this.commonName = commonName ?? undefined;
    };

    static fromDto(dto: HormoneDto | undefined): Hormone {
        assertDefined(dto, "HormoneDto");

        return new Hormone(
            dto.id,
            dto.name,
            dto.commonName,
        );
    }

    static toUuid(id: string): string {
        return id.replace(HORMONE_IRI_PREFIX, "");
    }
    
    static toIri(uuid: string): string {
        return uuid.startsWith(HORMONE_IRI_PREFIX) ? uuid : `${HORMONE_IRI_PREFIX}${uuid}`;
    }
}