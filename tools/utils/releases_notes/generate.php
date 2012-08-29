#!/usr/bin/php
<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
$separator = "\n";
$today = date('l, F jS Y');
$tuleap_version = trim(file_get_contents('VERSION'));

$changelog         = file('ChangeLog', FILE_IGNORE_NEW_LINES);
$release_notes     = array();
$in_plugins        = false;
$in_section        = false;
$sub_release_notes = array();
foreach ($changelog as $line) {
    if (strpos($line, 'Version ') === 0) {
        if ($release_notes) {
            if ($sub_release_notes) {
                $release_notes[] = convert_to_ul_li($sub_release_notes);
            }
            break;
        }
        preg_match('/\((.*)\)/', $line, $matches);
        $release_notes[] = '<h1>Tuleap '. $tuleap_version .' - Release Notes</h1>';
        $release_notes[] = '<em>'. $matches[1] .'</em>';
    } elseif (preg_match('/^\s*== /', $line)) {
        if ($sub_release_notes) {
            $release_notes[] = convert_to_ul_li($sub_release_notes);
        }
        $section           = trim(str_replace('==', '', $line));
        $release_notes[]   = '  <h2>'. $section .'</h2>';
        $in_plugins        = (strtolower($section) == 'plugins');
        $in_section        = true;
        $sub_release_notes = array();
    } else {
        if ($in_plugins && $line) {
            preg_match('/^\s*\* (.*):\s*(.*)/', $line, $matches);
            $plugin_name = $matches[1];
            $release_notes[] = '    <h3>'. ucfirst($plugin_name) .'</h3>';
            $release_notes[] = '    <em>'. $matches[2] .'</em>';
            $release_notes[] = extract_changelog_of_plugin($plugin_name);
            $release_notes[] = '';
        } else {
            if ($in_section && $line) {
                $sub_release_notes[] = $line;
            } else {
                $release_notes[] = $line;
            }
        }
    }
}
echo implode($separator, $release_notes);
echo $separator;


function extract_changelog_of_plugin($plugin_name) {
    global $tuleap_version, $separator;
    $release_notes = array();
    $changelog = file('plugins/'.$plugin_name.'/ChangeLog', FILE_IGNORE_NEW_LINES);
    foreach ($changelog as $line) {
        if (strpos($line, 'Version ') === 0) {
            if (!preg_match('/Tuleap '. $tuleap_version .'\s*$/i', $line)) {
                break;
            }
        } elseif ($line) {
            $release_notes[] = $line;
        }
    }
    return convert_to_ul_li($release_notes);
    return implode($separator, $release_notes);
}

function convert_to_ul_li(array $release_notes) {
    global $separator;
    $html  = '    <ul>'. $separator;
    $in_li = false;
    foreach ($release_notes as $line) {
        if (preg_match('/^\s*\*/', $line)) {
            if ($in_li) {
                $html .= '</li>'. $separator;
            }
            $html .= '      <li>';
            $in_li = true;
        } else {
            $html .= '<br />';
        }
        $html .= preg_replace('/^\s*\*\s*/', '', $line);
    }
    if ($in_li) {
        $html .= '</li>'. $separator;
    }
    $html .= '    </ul>';
    return $html;
}
?>
