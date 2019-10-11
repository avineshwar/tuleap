/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import ChildCard from "./ChildCard.vue";
import { Card, User } from "../../../../../type";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";

describe("ChildCard", () => {
    describe("Closed items", () => {
        it(`Given user does not want to see closed items
        When the card is closed
        Then it is not rendered`, () => {
            const wrapper = shallowMount(ChildCard, {
                propsData: {
                    card: {
                        id: 43,
                        assignees: [] as User[],
                        is_open: false
                    } as Card
                },
                mocks: {
                    $store: createStoreMock({
                        state: {
                            are_closed_items_displayed: false,
                            user: {
                                user_has_accessibility_mode: false
                            }
                        }
                    })
                }
            });

            expect(wrapper.isEmpty()).toBe(true);
        });

        it(`Given user wants to see closed items
        When the card is closed
        Then it is rendered`, () => {
            const wrapper = shallowMount(ChildCard, {
                propsData: {
                    card: {
                        id: 43,
                        assignees: [] as User[],
                        is_open: false
                    } as Card
                },
                mocks: {
                    $store: createStoreMock({
                        state: {
                            are_closed_items_displayed: true,
                            user: {
                                user_has_accessibility_mode: false
                            }
                        }
                    })
                }
            });

            expect(wrapper.isEmpty()).toBe(false);
        });
    });

    describe("Accessibility", () => {
        it(`Given user is not in accessibility mode
        Then the card is not displayed with accessibility patterns`, () => {
            const wrapper = shallowMount(ChildCard, {
                propsData: {
                    card: {
                        id: 43,
                        assignees: [] as User[],
                        is_open: true,
                        background_color: "fiesta-red"
                    } as Card
                },
                mocks: {
                    $store: createStoreMock({
                        state: {
                            are_closed_items_displayed: true,
                            user: {
                                user_has_accessibility_mode: false
                            }
                        }
                    })
                }
            });

            expect(wrapper.contains(".taskboard-card-accessibility")).toBe(false);
        });

        it(`Given user is in accessibility mode
        Then the card is displayed with accessibility patterns`, () => {
            const wrapper = shallowMount(ChildCard, {
                propsData: {
                    card: {
                        id: 43,
                        assignees: [] as User[],
                        is_open: true,
                        background_color: "fiesta-red"
                    } as Card
                },
                mocks: {
                    $store: createStoreMock({
                        state: {
                            are_closed_items_displayed: true,
                            user: {
                                user_has_accessibility_mode: true
                            }
                        }
                    })
                }
            });

            expect(wrapper.contains(".taskboard-card-accessibility")).toBe(true);
        });
    });
});
