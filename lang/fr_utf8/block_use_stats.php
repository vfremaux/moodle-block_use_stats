<?php
global $COURSE;

$string['blockname'] = 'Tracking activité';
$string['blocknameforstudents'] = 'Statistiques '.$COURSE->student;
$string['configcapturemodules'] = 'Modules pris en compte';
$string['configcapturemodules_desc'] = 'Liste des modules qui sont pris en compte dans l\'analyse de détail';
$string['configignoremodules'] = 'Modules ignorés';
$string['configignoremodules_desc'] = 'Liste des modules ignorés par le tracking';
$string['configfromwhen'] = 'Durée de compilation ';
$string['configfromwhen_desc'] = 'Valeur par défaut de la durée de compilation (en jours depuis aujourd\'hui) ';
$string['configlastpingcredit'] = 'Crédit temps dernière transaction';
$string['configlastpingcredit_desc'] = 'Lors du dernier contact d\'une session de travail, il  est impossible de déterminer quel temps effectif celui-ci a passé sur la dernière page. Ce paramètre permet d\'accorder un crédit temps systématique sur ce dernier enregistrement';
$string['configstudentscanuse'] = 'Les étudiants peuvent voir ce bloc (pour leur compte)';
$string['configstudentscanuseglobal'] = 'Autoriser les étudiants à voir les blocs de statistiques dans les espaces globaux (MyMoodle, hors cours)';
$string['configthreshold'] = 'Seuil de détection';
$string['configthreshold_desc'] = 'Au dela d\'une certaine pérode d\'inactivité (en minutes), le traceur doit considérer que la session de travail a été interrompue.';
$string['configlastcompiled'] = 'Date de dernière compilation';
$string['configlastcompiled_desc'] = 'Concerne les précompilations automatiques';
$string['credittime'] = 'Forfaitaire : '; //used in reports
$string['dimensionitem'] = 'Classes observables';
$string['errornorecords'] = 'Aucune donnée de tracking';
$string['eventscount'] = 'Nombre de hits';
$string['from'] = 'Depuis ';
$string['modulename'] = 'Tracking activité';
$string['noavailablelogs'] = 'Pas de logs disponibles pour cette évaluation';
$string['onthisMoodlefrom'] = ' sur ce Moodle depuis ';
$string['showdetails'] = 'Montrer les détails';
$string['timeelapsed'] = 'Temps passé';
$string['use_stats:seeowndetails'] = 'Peut voir son propre détail d\'usage';
$string['use_stats:seecoursedetails'] = 'Peut voir les détails de tous les utilisateurs de ses cours';
$string['use_stats:seegroupdetails'] = 'Peut voir les détails de tous les utilisateurs de ses groupes';
$string['use_stats:seesitedetails'] = 'Peut voir les détails de tous les utilisateurs';
$string['use_stats:view'] = 'Voir les statistiques';
$string['use_stats_rpc_service'] = 'Lecture distante des statistiques';
$string['use_stats_name'] = 'Acces distant aux statistiques d\'usage';
$string['use_stats_description'] = 'En publiant ce service, vous permettez au serveur distant de consulter les statistiques des utilisateurs locaux.<br/>En vous abonnant à ce service, vous autorisez le serveur local à consulter les satistiques d\'utilisateurs du serveur distant.<br/>';
$string['youspent'] = 'Vous avez déjà passé ';
$string['ignored'] = 'Module/Activité non pris en compte';

?>