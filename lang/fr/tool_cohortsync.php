<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'tool_cohortsync', language 'fr'.
 *
 * @package    tool_cohortsync
 * @copyright  2016 Universite de Montreal
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['cohortscreated'] = '"{$a}" cohorte(s) ont été crée';
$string['cohortsyncheader'] = 'Paramètrage de la synchronisation des cohortes';
$string['createcohort'] = 'Créer la cohorte si elle n\'existe pas';
$string['createcohortdesc'] = 'Quand ce paramètre est à "oui", on crée la cohorte si elle n\'existe pas.';
$string['csvdelimiter'] = 'Délimiteur CSV';
$string['encoding'] = 'Encodage';
$string['errordelimiterfile'] = 'Seuls ces délimiteurs sont autorisés: comma, semicolon, colon, tab';
$string['errorreadingfile'] = 'Le fichier CSV "{$a}" n\'est pas lisible ou n\'existe pas';
$string['errorreadingdefaultfile'] = 'Le fichier CSV par défaut "{$a}" n\'est pas lisible ou n\'existe pas';
$string['erroruseridentifier'] = 'Seuls ces identifiants utilisateur sont autorisés: user_id, username, user_idnumber';
$string['filepathsource'] = 'Le chemin vers le fichier source';
$string['formatcsv'] = 'Format du fichier CSV';
$string['formatcsvdesc'] = 'Les formats suivants sont autorisés<br>
        <pre>name,idnumber,description,descriptionformat,contextid,visible,username</pre><br>
        Le "username" est utilisé comme identifiant de l\'utilisateur on peut aussi utiliser "user_id" ou  "user_idnumber"<br>
        Si "visible" est non renseigné, il sera visible par défaut: les valeurs acceptées sont: "no" ou 0 pour non visible et "yes" ou 1 pour visible<br>
        "descriptionformat" est le type de format choisit pour la description:<br>
            0 = Moodle auto-format<br>
            1 = HTML format<br>
            2 = Plain text format<br>
            4 = Markdown format<br><br>
        Si le "contextid" est non trouvé le context système sera utilisé<br><br>
        Tous les champs sauf ceux de l\'utilisateur (user_id, user_idnumber, username), sont associés à la cohorte<br><br>
        <pre>name,idnumber,description,descriptionformat,category_name,visible,username</pre><br>
        On peut utiliser le "category_name" pour récuperer le context, si la categorie est non trouvé le contexte système sera utilisé<br>
        On peut aussi remplacer le "category_name" par "category_path", "category_id" ou "category_idnumber"';
$string['idnumbercolumnmissing'] = 'La colonne "idnumber" est manquant';
$string['pluginname'] = 'Synchronisation des cohortes';
$string['notfounduser'] = 'L\'utilisateur "{$a}" non trouvé dans la base de donnée';
$string['useradded'] = '"{$a->count}" utilisateur(s) ont été ajouté à la cohorte "{$a->name}"';
$string['useridentifier'] = 'Identifiant de l\'utilisateur';
$string['useridentifierdesc'] = 'Ce paramètre est utilisé pour identifier l\'utilisateur qui va être associé à la cohorte.';