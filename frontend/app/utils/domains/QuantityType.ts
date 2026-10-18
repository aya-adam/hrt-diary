import { gql, type TypedDocumentNode } from "@apollo/client";
import { assertDefined } from "../assertions";

export type QuantityTypeDto = {
    id: string,
    label: string,
    slug: string,
};

type QuantityTypeItemIdentifierInput = {
    id: string,
};

type QuantityTypeCollectionQueryResponse = {
    quantityTypes: QuantityTypeDto[],
};

type QuantityTypeItemQueryResponse = {
    quantityType: QuantityTypeDto,
};

export class QuantityType {
    readonly id: string;
    readonly label: string;
    readonly slug: string;

    static readonly COLLECTION_QUERY: TypedDocumentNode<QuantityTypeCollectionQueryResponse> = gql`
        query QuantityTypes {
            quantityTypes {
                id
                label
                slug
            }
        }
    `;

    static readonly ITEM_QUERY: TypedDocumentNode<QuantityTypeItemQueryResponse, QuantityTypeItemIdentifierInput> = gql`
        query QuantityType($id: ID!) {
            quantityType(id: $id) {
                id
                label
                slug
            }
        }
    `;

    constructor(id: string, label: string, slug: string) {
        this.id = id;
        this.label = label;
        this.slug = slug;
    };

    static fromDto(dto: QuantityTypeDto | undefined): QuantityType {
        assertDefined(dto, "QuantityTypeDto");

        return new QuantityType(
            dto.id,
            dto.label,
            dto.slug,
        );
    }
}