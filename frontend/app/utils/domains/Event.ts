import { gql, type TypedDocumentNode } from "@apollo/client";
import type { Moment } from "moment-timezone";
import moment from "moment-timezone";
import { assertDefined } from "../assertions";

export type EventDto = {
    id: string,
    name: string,
    date: string,
    repeatInterval: string,
    earlyNotification: string,
    critical: boolean,
    createdAt: string,
    updatedAt: string,
};

type EventItemIdentifierInput = {
    id: string,
};

type EventCreateMutationInput = {
    name: string,
    date: string,
    repeatInterval?: string,
    earlyNotification?: string,
    critical?: boolean,
};

type EventUpdateMutationInput = {
    id: EventItemIdentifierInput['id'],
    name?: string,
    date?: string,
    repeatInterval?: string,
    earlyNotification?: string,
    critical?: boolean,
};

type EventCollectionQueryResponse = {
    events: EventDto[],
};

type EventItemQueryResponse = {
    event: EventDto,
};

type EventCreateMutationResponse = {
    createEvent: {
        event: EventDto,
    }
};

type EventUpdateMutationResponse = {
    updateEvent: {
        event: EventDto,
    }
};

type EventDeleteMutationResponse = {
    deleteEvent: {
        event: {
            id: EventDto['id'],
        },
    }
};

const EVENT_IRI_PREFIX = "/events/";

export class Event {
    readonly id: string;
    readonly name: string;
    readonly date: Moment;
    readonly repeatInterval?: string;
    readonly earlyNotification?: string;
    readonly critical: boolean;
    readonly createdAt: Moment;
    readonly updatedAt: Moment;

    static readonly COLLECTION_QUERY: TypedDocumentNode<EventCollectionQueryResponse> = gql`
        query Events {
            events {
                id
                name
                date
                repeatInterval
                earlyNotification
                critical
                createdAt
                updatedAt
            }
        }
    `;

    static readonly ITEM_QUERY: TypedDocumentNode<EventItemQueryResponse, EventItemIdentifierInput> = gql`
        query Event($id: ID!) {
            event(id: $id) {
                id
                name
                date
                repeatInterval
                earlyNotification
                critical
                createdAt
                updatedAt
            }
        }
    `;

    static readonly CREATE_MUTATION: TypedDocumentNode<EventCreateMutationResponse, EventCreateMutationInput> = gql`
        mutation CreateEvent($name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
            createEvent(input: { name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                event {
                    id
                    name
                    date
                    repeatInterval
                    earlyNotification
                    critical
                    createdAt
                    updatedAt
                }
            }
        }
    `;

    static readonly UPDATE_MUTATION: TypedDocumentNode<EventUpdateMutationResponse, EventUpdateMutationInput> = gql`
        mutation UpdateEvent($id: ID!, $name: String!, $date: String!, $repeatInterval: String, $earlyNotification: String, $critical: Boolean) {
            updateEvent(input: { id: $id, name: $name, date: $date, repeatInterval: $repeatInterval, earlyNotification: $earlyNotification, critical: $critical }) {
                event {
                    id
                    name
                    date
                    repeatInterval
                    earlyNotification
                    critical
                    createdAt
                    updatedAt
                }
            }
        }
    `;

    static readonly DELETE_MUTATION: TypedDocumentNode<EventDeleteMutationResponse, EventItemIdentifierInput> = gql`
        mutation DeleteEvent($id: ID!) {
            deleteEvent(input: { id: $id }) {
                event {
                    id
                }
            }
        }
    `;

    constructor(id: string, name: string, date: string, repeatInterval: string, earlyNotification: string, critical: boolean, createdAt: string, updatedAt: string) {
        this.id = Event.toUuid(id);
        this.name = name;
        this.date = moment(date);
        this.repeatInterval = repeatInterval;
        this.earlyNotification = earlyNotification;
        this.critical = critical;
        this.createdAt = moment(createdAt);
        this.updatedAt = moment(updatedAt);
    };

    static fromDto(dto: EventDto | undefined): Event {
        assertDefined(dto, "EventDto");

        return new Event(
            dto.id,
            dto.name,
            dto.date,
            dto.repeatInterval,
            dto.earlyNotification,
            dto.critical,
            dto.createdAt,
            dto.updatedAt,
        );
    }

    static toUuid(id: string): string {
        return id.replace(EVENT_IRI_PREFIX, "");
    }
    
    static toIri(uuid: string): string {
        return uuid.startsWith(EVENT_IRI_PREFIX) ? uuid : `${EVENT_IRI_PREFIX}${uuid}`;
    }
}