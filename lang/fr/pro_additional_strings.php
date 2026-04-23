<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

$string['plugindist'] = 'Distribution du plugin';
$string['plugindist_desc'] = '
<p>Ce plugin est distribué dans la communauté Moodle pour l\'évaluation de ses fonctions centrales
correspondant à une utilisation courante du plugin. Une version "professionnelle" de ce plugin existe et est distribuée
sous certaines conditions, afin de soutenir l\'effort de développement, amélioration; documentation et suivi des versions.</p>
<p>Contactez un distributeur pour obtenir la version "Pro" et son support.</p>
<p><a href="http://www.activeprolearn.com/distributeurs">Distributeurs MyLF</a></p>';

// Caches.
$string['cachedef_pro'] = 'Stocke des données spécifiques de la zone "pro"';

require_once($CFG->dirroot.'/blocks/use_stats/lib.php'); // to get xx_supports_feature();
if ('pro' == block_use_stats_supports_feature()) {
    include($CFG->dirroot.'/blocks/use_stats/pro/lang/fr/pro.php');
}
