/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import { ListPickerItem } from "../type";

export function generateItemMapBasedOnSourceSelectOptions(
    source_select_box: HTMLSelectElement
): Map<string, ListPickerItem> {
    const map = new Map();
    const useless_options = [];
    let i = 0;
    for (const option of source_select_box.options) {
        if (option.value === "" || option.value === "?") {
            useless_options.push(option);
            continue;
        }

        let group_id = "";
        if (option.parentElement && option.parentElement.nodeName === "OPTGROUP") {
            const label = option.parentElement.getAttribute("label");

            if (label !== null) {
                group_id = label.replace(" ", "").toLowerCase();
            }
        }

        const id = `item-${i}`;
        const template =
            option.innerText !== "" && option.innerText !== undefined
                ? option.innerText
                : option.label;
        const is_disabled = Boolean(option.hasAttribute("disabled"));
        const item: ListPickerItem = {
            id,
            group_id,
            value: option.value,
            template,
            is_disabled,
            is_selected: false,
            target_option: option,
            element: getRenderedListItem(id, template, is_disabled),
        };
        map.set(id, item);
        option.setAttribute("data-item-id", id);
        i++;
    }

    useless_options.forEach((option) => source_select_box.removeChild(option));
    return map;
}

function getRenderedListItem(option_id: string, template: string, is_disabled: boolean): Element {
    const list_item = document.createElement("li");
    list_item.id = option_id;
    list_item.appendChild(document.createTextNode(template));
    list_item.setAttribute("role", "option");
    list_item.setAttribute("aria-selected", "false");

    if (is_disabled) {
        list_item.classList.add("list-picker-dropdown-option-value-disabled");
    } else {
        list_item.classList.add("list-picker-dropdown-option-value");
    }
    return list_item;
}
