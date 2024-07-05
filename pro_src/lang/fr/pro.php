<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,00
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

$string['activationoption'] = 'Option d\'activation';
$string['emulatecommunity'] = '<a name="getsupportlicense"></a>Emuler la version communautaire';
$string['emulatecommunity_desc'] = 'Bascule le code sur la version communautaire. Le résultat est plus compatible avec d\'autres installations, 
mais certaines fonctionnalités avancées ne seront plus disponibles.';
$string['getlicensekey'] = 'Obtenir une clef de license support';
$string['getlicensekey_desc'] = '<a name="getsupportlicense"></a>Dans certains cas, les intégrateurs (ou administrateurs) peuvent obtenir directement une clef de license support auprès
d\'un fournisseur pour activer les parties "pro" du plugin.
<br><a href="{$a}">Enregistrer le plugin</a>';
$string['licensestatus'] = 'Etat de license pro';
$string['licensekey'] = 'Clef de license pro';
$string['licensekey_desc'] = 'Entrez ici la clef de produit que vous avez reçu de votre distributeur.';
$string['licenseprovider'] = 'Fournisseur version Pro';
$string['licenseprovider_desc'] = 'Entrez la clef de votre distributeur.';
$string['provider'] = 'Fournisseur de support';
$string['partnerkey'] = 'Clef distributeur partenaire';
$string['specificprosettings'] = 'Réglages spécifiques version "pro"';
$string['errorjson'] = 'Erreur : La réponse JSON est vide ou n\'est pas interprétable.';
$string['errorresponse'] = 'Erreur : La réponse est valide mais en erreur : {$a}';
$string['errornokeygenerated'] = 'Erreur : La clef n\'est pas générée ou n\'est pas conforme.';
$string['errornooptions'] = 'Erreur : Aucune option d\'activation trouvée.';
$string['erroremptydistributorkey'] = 'Clef du distributeur non fournie';
$string['errornodistributorkey'] = 'Clef distributeru non fournie';
$string['erroremptyprovider'] = 'Fournisseur non spécifié';
$string['options'] = 'Options d\'activation';
$string['start'] = 'Identification du distributeur';
$string['continue'] = 'Continuer';
$string['activate'] = 'Activer';
$string['chooseoption'] = 'Choisir une option d\'activation...';
$string['noproaccess'] = 'Ceci est une partie "pro" limitée du plugin qui n\'est pas activée.';

$string['provider_help'] = 'Code du fournisseur du support. Ce code identifie le prestataire fournissant le support de niveau 3 et la garantie de continuité du plugin.';
$string['partnerkey_help'] = 'La clef partenaire a été fournie à l\'acteur désigné pour installer le plugin.';

$string['emulatecommunity_desc'] = 'Si elle est activée, cette option force le composant à fonctionner en
version communautaire. Le fonctionnement sera plus compatible avec d\'autres installations, mais certaines
fonctionnalités ne seront plus disponibles.';

$string['activationoption_help'] = 'Ce plugin peut avoir plusieurs options d\'activation dans le catalogue du fournisseur. Choissisez celle qui contient le mieux à votre situation.';
